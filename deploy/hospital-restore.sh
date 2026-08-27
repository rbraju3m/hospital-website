#!/usr/bin/env bash
#
# RBR Hospital — restore.
#
# Puts back what deploy/hospital-backup.sh took: the database, the patient
# documents on the private disk, and the uploads on the public one.
#
# Usage:
#   deploy/hospital-restore.sh [options] <backup-directory>
#
#   --database NAME   load into NAME instead of DB_DATABASE from .env
#   --db-only         leave the files alone
#   --files-only      leave the database alone
#   --no-snapshot     skip the safety dump taken before overwriting
#   --app-dir DIR     the checkout to restore into (default: this script's ../)
#   --yes             do not ask (for scripts; think twice about cron)
#
# Rehearse it, which is the point of having it:
#   deploy/hospital-restore.sh --db-only --database hospital_restore_check \
#       /var/backups/hospital/hospital-20260827-023000
#   # then: mysql hospital_restore_check -e 'SELECT COUNT(*) FROM appointments'
#   #       mysql -e 'DROP DATABASE hospital_restore_check'
#
# For real, after something has gone wrong:
#   sudo systemctl stop hospital-queue         # or it works on half-restored data
#   deploy/hospital-restore.sh /var/backups/hospital/hospital-20260827-023000
#   sudo systemctl start hospital-queue
#
# Three things this script does that are not obvious, and are the reason it is
# a script rather than a paragraph in a README:
#
#   It dumps the current database before overwriting it. A restore is itself a
#   destructive act, usually performed at speed by somebody having a bad day,
#   and "we restored last night's backup over today's bookings" needs a way
#   back. --no-snapshot turns that off; there is rarely a good reason.
#
#   It never deletes the files it replaces. The old private and public disks
#   are moved aside to <dir>.replaced-<stamp>, not removed. An orphaned file on
#   the private disk is a medical record, and deleting one to tidy up after a
#   restore is not a mistake that can be walked back.
#
#   It counts the rows afterwards and compares them with the manifest. A dump
#   that loads without erroring is not the same as a database that came back.
#
# It does not touch .env. The backup carries a copy at <backup>/env; putting it
# back is a decision about APP_KEY, the database password and the SMS gateway
# key, and it is made by a person looking at both files.

set -euo pipefail
export TZ="${TZ:-Asia/Dhaka}"

SCRIPT_DIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
APP_DIR="${HOSPITAL_APP_DIR:-$(cd -- "$SCRIPT_DIR/.." && pwd)}"
STAMP=$(date +%Y%m%d-%H%M%S)

BACKUP_DIR=
TARGET_DB=
DO_DB=1
DO_FILES=1
SNAPSHOT=1
ASSUME_YES=0
DEFAULTS_FILE=
PROBLEMS=0

log()  { printf '%s  %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"; }
warn() { log "WARNING: $*" >&2; PROBLEMS=$((PROBLEMS + 1)); }
die()  { log "ERROR: $*" >&2; exit 1; }

# Set while a disk is mid-swap: the old one has been moved aside and the new
# one is not in place yet. That window is a few milliseconds wide, and this
# script is run by people in a hurry who press Ctrl-C — so it is worth closing.
# Leaving no private disk at all is a worse state than the one we started in.
SWAP_TARGET=
SWAP_ASIDE=
SWAP_STAGE=

cleanup() {
    local status=$?
    if [ "$status" -ne 0 ] && [ -n "$SWAP_TARGET" ] && [ ! -e "$SWAP_TARGET" ] && [ -e "$SWAP_ASIDE" ]; then
        log "interrupted mid-swap — putting $SWAP_TARGET back" >&2
        mv -- "$SWAP_ASIDE" "$SWAP_TARGET" || true
    fi
    [ -n "$SWAP_STAGE" ] && [ -d "$SWAP_STAGE" ] && rm -rf -- "$SWAP_STAGE"
    [ -n "$DEFAULTS_FILE" ] && rm -f "$DEFAULTS_FILE"
    return $status
}
trap cleanup EXIT

# Die through the EXIT trap rather than under the signal, or the rollback above
# never runs. PIPE is in here because `... | head` is how somebody reads this.
trap 'exit 130' INT
trap 'exit 143' TERM
trap 'exit 141' PIPE

while [ $# -gt 0 ]; do
    case "$1" in
        --database)   TARGET_DB=${2:-}; shift 2 ;;
        --app-dir)    APP_DIR=${2:-};   shift 2 ;;
        --db-only)    DO_FILES=0;       shift ;;
        --files-only) DO_DB=0;          shift ;;
        --no-snapshot) SNAPSHOT=0;      shift ;;
        --yes|-y)     ASSUME_YES=1;     shift ;;
        -h|--help)    sed -n '2,40p' "${BASH_SOURCE[0]}"; exit 0 ;;
        -*)           die "unknown option: $1" ;;
        *)            BACKUP_DIR=$1;    shift ;;
    esac
done

[ -n "$BACKUP_DIR" ] || die "which backup? usage: $0 [options] <backup-directory>"
[ -d "$BACKUP_DIR" ] || die "no such directory: $BACKUP_DIR"
BACKUP_DIR=$(cd -- "$BACKUP_DIR" && pwd)
[ -f "$BACKUP_DIR/manifest.txt" ] || die "$BACKUP_DIR has no manifest.txt — is it a backup taken by hospital-backup.sh?"
[ "$DO_DB" -eq 1 ] || [ "$DO_FILES" -eq 1 ] || die "--db-only and --files-only together leave nothing to do"

ENV_FILE="$APP_DIR/.env"
[ -f "$ENV_FILE" ] || die "no .env at $ENV_FILE — restore the checkout and its .env first"

env_get() {
    local key=$1 line value
    line=$(grep -E "^[[:space:]]*${key}=" "$ENV_FILE" | tail -n 1 || true)
    [ -n "$line" ] || return 0
    value=${line#*=}
    value=${value%$'\r'}
    if [[ $value == \"*\" && ${#value} -ge 2 ]]; then
        value=${value:1:${#value}-2}
    elif [[ $value == \'*\' && ${#value} -ge 2 ]]; then
        value=${value:1:${#value}-2}
    fi
    printf '%s' "$value"
}

manifest_get() { awk -v k="$1" '$1 == k { print $2 }' "$BACKUP_DIR/manifest.txt"; }

write_defaults_file() {
    local escaped
    DEFAULTS_FILE=$(mktemp "${TMPDIR:-/tmp}/hospital-restore.XXXXXX")
    {
        printf '[client]\n'
        printf 'host=%s\n' "$DB_HOST"
        printf 'port=%s\n' "$DB_PORT"
        printf 'user=%s\n' "$DB_USER"
        if [ -n "$DB_PASS" ]; then
            escaped=${DB_PASS//\\/\\\\}
            escaped=${escaped//\"/\\\"}
            printf 'password="%s"\n' "$escaped"
        fi
        printf 'default-character-set=utf8mb4\n'
    } > "$DEFAULTS_FILE"
}

sql() { mysql --defaults-extra-file="$DEFAULTS_FILE" --batch --skip-column-names -e "$1"; }

umask 077
for tool in mysql mysqldump gzip tar sha256sum; do
    command -v "$tool" >/dev/null || die "$tool is not installed"
done

DB_HOST=$(env_get DB_HOST); DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=$(env_get DB_PORT); DB_PORT=${DB_PORT:-3306}
DB_USER=$(env_get DB_USERNAME); DB_USER=${DB_USER:-root}
DB_PASS=$(env_get DB_PASSWORD)
TARGET_DB=${TARGET_DB:-$(env_get DB_DATABASE)}
[ -n "$TARGET_DB" ] || die "no target database: DB_DATABASE is unset and --database was not given"

DB_CHARSET=$(manifest_get charset);     DB_CHARSET=${DB_CHARSET:-utf8mb4}
DB_COLLATION=$(manifest_get collation); DB_COLLATION=${DB_COLLATION:-utf8mb4_unicode_ci}
SOURCE_DB=$(manifest_get database)

# ------------------------------------------------------------ verify first ---
# Before anything is overwritten, prove the backup is whole. Finding out that
# the archive is truncated *after* dropping the live tables is the worst
# possible order to learn it in.
log "checking the backup"
( cd "$BACKUP_DIR" && sha256sum -c --quiet SHA256SUMS ) || die "checksums do not match — this backup is damaged, use another"

if [ "$DO_DB" -eq 1 ]; then
    [ -f "$BACKUP_DIR/database.sql.gz" ] || die "no database.sql.gz in $BACKUP_DIR"
    gzip -t "$BACKUP_DIR/database.sql.gz" || die "database.sql.gz is not readable gzip"
    gzip -dc "$BACKUP_DIR/database.sql.gz" | tail -n 3 | grep -q "Dump completed" \
        || die "database.sql.gz is truncated — no completion marker"
fi

write_defaults_file
sql "SELECT 1" >/dev/null 2>&1 || die "cannot connect to MySQL at $DB_HOST:$DB_PORT as $DB_USER"

# ------------------------------------------------------------------- plan ----
TARGET_EXISTS=0
sql "USE \`$TARGET_DB\`" >/dev/null 2>&1 && TARGET_EXISTS=1

echo
echo "  from        $BACKUP_DIR"
echo "  taken       $(awk '/^taken/ { $1=""; print substr($0,2) }' "$BACKUP_DIR/manifest.txt")"
echo "  dumped from $SOURCE_DB"
echo
if [ "$DO_DB" -eq 1 ]; then
    if [ "$TARGET_EXISTS" -eq 1 ]; then
        echo "  database    $TARGET_DB  — EXISTS, and every table in the dump will be dropped and rewritten"
    else
        echo "  database    $TARGET_DB  — will be created ($DB_CHARSET / $DB_COLLATION)"
    fi
else
    echo "  database    untouched (--files-only)"
fi
if [ "$DO_FILES" -eq 1 ]; then
    echo "  private     $APP_DIR/storage/app/private  — replaced, the current one moved aside"
    echo "  public      $APP_DIR/storage/app/public   — replaced, the current one moved aside"
else
    echo "  files       untouched (--db-only)"
fi
echo "  .env        untouched, always"
echo

if [ "$ASSUME_YES" -ne 1 ]; then
    [ -t 0 ] || [ -r /dev/tty ] || die "not a terminal and --yes was not given; refusing to guess"
    printf 'Type the target database name to go ahead: '
    if [ -r /dev/tty ]; then read -r answer < /dev/tty; else read -r answer; fi
    [ "$answer" = "$TARGET_DB" ] || die "that is not '$TARGET_DB' — nothing was changed"
fi

# --------------------------------------------------------------- database ----
if [ "$DO_DB" -eq 1 ]; then
    if [ "$TARGET_EXISTS" -eq 1 ] && [ "$SNAPSHOT" -eq 1 ]; then
        SNAP_DIR="$(dirname -- "$BACKUP_DIR")/pre-restore-$STAMP"
        mkdir -p "$SNAP_DIR"; chmod 700 "$SNAP_DIR"
        log "dumping '$TARGET_DB' as it is now, into $SNAP_DIR (the way back)"
        mysqldump --defaults-extra-file="$DEFAULTS_FILE" \
            --single-transaction --quick --no-tablespaces --routines --triggers \
            --default-character-set=utf8mb4 --set-gtid-purged=OFF \
            "$TARGET_DB" | gzip -9 > "$SNAP_DIR/database.sql.gz" \
            || die "could not snapshot the current database — stopping before anything is overwritten"
        chmod 600 "$SNAP_DIR/database.sql.gz"
    elif [ "$TARGET_EXISTS" -eq 1 ]; then
        log "skipping the safety snapshot (--no-snapshot)"
    fi

    log "creating '$TARGET_DB' if it is not there"
    sql "CREATE DATABASE IF NOT EXISTS \`$TARGET_DB\` CHARACTER SET $DB_CHARSET COLLATE $DB_COLLATION"

    # The dump drops and recreates each table it holds. A table that exists in
    # the target but not in the dump is left alone — that is a schema newer
    # than the backup, and this script is not the place to decide about it.
    log "loading the dump into '$TARGET_DB'"
    gzip -dc "$BACKUP_DIR/database.sql.gz" \
        | mysql --defaults-extra-file="$DEFAULTS_FILE" --default-character-set=utf8mb4 "$TARGET_DB" \
        || die "the dump failed to load — the database is now half-written; the snapshot above is the way back"

    log "comparing the row counts with the manifest"
    if [ -f "$BACKUP_DIR/counts.txt" ]; then
        while IFS=$'\t' read -r table expected; do
            [ -n "$table" ] || continue
            actual=$(sql "SELECT COUNT(*) FROM \`$TARGET_DB\`.\`$table\`" 2>/dev/null || echo missing)
            if [ "$actual" = "missing" ]; then
                warn "table '$table' is not in the restored database at all"
            elif [ "$actual" != "$expected" ]; then
                warn "table '$table' holds $actual rows, the backup recorded $expected"
            fi
        done < "$BACKUP_DIR/counts.txt"
        [ "$PROBLEMS" -eq 0 ] && log "every table matches the counts recorded when the backup was taken"
    else
        warn "this backup has no counts.txt, so there is nothing to check the restore against"
    fi
fi

# ------------------------------------------------------------------ files ----
restore_tree() {
    local label=$1 archive=$2 dir=$3
    local parent="$APP_DIR/storage/app"
    local target="$parent/$dir"
    local stage="$parent/.restoring-$dir-$STAMP"

    if [ ! -f "$archive" ]; then
        warn "no archive for $label in this backup — $target left exactly as it is"
        return 0
    fi

    log "restoring $label"
    rm -rf -- "$stage"; mkdir -p "$stage"; SWAP_STAGE="$stage"
    tar -xzf "$archive" -C "$stage" || { rm -rf -- "$stage"; die "could not unpack $label"; }
    [ -d "$stage/$dir" ] || { rm -rf -- "$stage"; die "$archive does not contain a '$dir' directory"; }

    if [ -e "$target" ]; then
        SWAP_TARGET="$target"; SWAP_ASIDE="$target.replaced-$STAMP"
        mv -- "$target" "$SWAP_ASIDE"
        mv -- "$stage/$dir" "$target"
        SWAP_TARGET=; SWAP_ASIDE=
        log "  the previous $dir disk is at $target.replaced-$STAMP — delete it once you are satisfied, not before"
    else
        mv -- "$stage/$dir" "$target"
    fi
    rm -rf -- "$stage"; SWAP_STAGE=

    # Apache runs as www-data and Laravel writes here; the same setgid dance as
    # the vhost's install notes, or the next upload fails on a restored disk.
    if [ "$(id -u)" -eq 0 ]; then
        chgrp -R www-data "$target" && chmod -R g+rwX "$target"
        find "$target" -type d -exec chmod g+s {} \;
    else
        log "  not running as root, so ownership is left as it is — check it can still be written to"
    fi
}

if [ "$DO_FILES" -eq 1 ]; then
    restore_tree "patient documents (private disk)" "$BACKUP_DIR/private.tar.gz" private
    restore_tree "uploads (public disk)"            "$BACKUP_DIR/public.tar.gz"  public
fi

# ------------------------------------------------------------------- after ---
echo
log "restored. Still to do, in this order:"
echo "    php8.3 artisan config:clear && php8.3 artisan view:clear"
echo "    php8.3 artisan storage:link          # the symlink is not in the backup"
echo "    php8.3 artisan migrate               # if the code is newer than the dump"
echo "    php8.3 artisan queue:restart         # the worker is holding the old classes"
echo
echo "  Then check the site: a doctor's photograph loads, a patient document downloads,"
echo "  and /admin/notifications is not filling up with rows stuck at 'queued'."
if [ -f "$BACKUP_DIR/env" ]; then
    echo
    echo "  This backup also holds a copy of .env at $BACKUP_DIR/env."
    echo "  It was NOT applied. Compare it with $ENV_FILE by hand — APP_KEY especially:"
    echo "  a different key invalidates every signed link and every session."
fi

[ "$PROBLEMS" -eq 0 ] || die "$PROBLEMS thing(s) above did not look right — read them before calling this done"
