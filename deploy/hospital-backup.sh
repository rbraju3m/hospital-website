#!/usr/bin/env bash
#
# RBR Hospital — backup.
#
# Takes the two things this application cannot be rebuilt from: the MySQL
# database (appointments, patients, staff accounts, the notification log) and
# storage/app/private, which is uploaded reports, prescriptions and bills. The
# public disk goes too — doctor photographs and the gallery are not in git
# either, and nothing in the panel can tell you a file has gone missing.
#
# Everything else — code, seeded content, the stand-in photography — is in the
# repository and comes back with a checkout.
#
# Install:
#   sudo install -m 750 deploy/hospital-backup.sh /usr/local/sbin/hospital-backup
#   sudo install -d -m 700 /var/backups/hospital
#   sudo cp deploy/hospital-backup.cron /etc/cron.d/hospital-backup
#   sudo chmod 644 /etc/cron.d/hospital-backup
#   sudo systemctl restart cron
#
# Run it by hand:
#   sudo /usr/local/sbin/hospital-backup
#   HOSPITAL_BACKUP_DIR=/tmp/try deploy/hospital-backup.sh   # somewhere harmless
#
# Settings, all optional, all from the environment — set them in the cron file
# rather than editing this script:
#   HOSPITAL_BACKUP_DIR        where backups are written    (/var/backups/hospital)
#   HOSPITAL_BACKUP_KEEP_DAYS  how long to keep them        (14; 0 keeps them for ever)
#   HOSPITAL_BACKUP_KEEP_MIN   never fall below this many   (3)
#   HOSPITAL_BACKUP_REMOTE     rsync destination, off-site  (unset)
#   HOSPITAL_BACKUP_SKIP_ENV   1 to leave .env out          (unset)
#   HOSPITAL_APP_DIR           the checkout                 (this script's ../)
#
# Two things worth reading before trusting this:
#
# A copy on the same disk as the database is not a backup — it is a second file
# on the disk that is going to fail. Set HOSPITAL_BACKUP_REMOTE to somewhere
# that is not this machine, or copy the directory off by hand. Without that,
# this protects against exactly one accident: somebody running DELETE.
#
# The archive contains .env — the database password and the SMS gateway key —
# and the dump is medical records. Wherever it lands has to be as private as
# this machine: 0700, and not a bucket somebody made public for convenience.
# HOSPITAL_BACKUP_SKIP_ENV=1 leaves the credentials out, at the price of
# writing .env again by hand after a restore, APP_KEY included — and a new
# APP_KEY invalidates every signed confirmation link and every live session.
#
# Restore with deploy/hospital-restore.sh. A backup nobody has restored is a
# hope, not a backup; restore one into a scratch database twice a year.

set -euo pipefail

# The database stores Dhaka local time rather than UTC, and the reminder runs
# on Dhaka hours. One clock for the whole box means a backup's name says the
# same thing as the timestamps inside it.
export TZ="${TZ:-Asia/Dhaka}"

SCRIPT_DIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
APP_DIR="${HOSPITAL_APP_DIR:-$(cd -- "$SCRIPT_DIR/.." && pwd)}"
BACKUP_ROOT="${HOSPITAL_BACKUP_DIR:-/var/backups/hospital}"
KEEP_DAYS="${HOSPITAL_BACKUP_KEEP_DAYS:-14}"
KEEP_MIN="${HOSPITAL_BACKUP_KEEP_MIN:-3}"
REMOTE="${HOSPITAL_BACKUP_REMOTE:-}"
SKIP_ENV="${HOSPITAL_BACKUP_SKIP_ENV:-}"

ENV_FILE="$APP_DIR/.env"
STAMP=$(date +%Y%m%d-%H%M%S)
RUN_DIR="$BACKUP_ROOT/hospital-$STAMP"
DEFAULTS_FILE=
DEGRADED=0

log()  { printf '%s  %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"; }
warn() { log "WARNING: $*" >&2; DEGRADED=1; }
die()  { log "ERROR: $*" >&2; exit 1; }

cleanup() {
    local status=$?
    [ -n "$DEFAULTS_FILE" ] && rm -f "$DEFAULTS_FILE"
    # A half-written backup is worse than none: it looks like a backup in the
    # listing, and is discovered to be one file short on the day it is needed.
    if [ "$status" -ne 0 ] && [ -d "$RUN_DIR" ]; then
        log "run failed — removing the incomplete $RUN_DIR"
        rm -rf "$RUN_DIR"
    fi
    return $status
}
trap cleanup EXIT

# Read one key out of .env. Deliberately not `source`: .env is not shell, and
# sourcing it would execute whatever a stray backtick in a password did.
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

# mysqldump reads the password from a file rather than the command line: an
# argument is visible in `ps` to every user on the box for as long as the dump
# runs, which on a busy night is long enough.
write_defaults_file() {
    local escaped
    DEFAULTS_FILE=$(mktemp "${TMPDIR:-/tmp}/hospital-backup.XXXXXX")
    {
        printf '[client]\n'
        printf 'host=%s\n' "$DB_HOST"
        printf 'port=%s\n' "$DB_PORT"
        printf 'user=%s\n' "$DB_USER"
        if [ -n "$DB_PASS" ]; then
            # Option files take backslash escapes inside a quoted value.
            escaped=${DB_PASS//\\/\\\\}
            escaped=${escaped//\"/\\\"}
            printf 'password="%s"\n' "$escaped"
        fi
        printf 'default-character-set=utf8mb4\n'
    } > "$DEFAULTS_FILE"
}

sql() { mysql --defaults-extra-file="$DEFAULTS_FILE" --batch --skip-column-names -e "$1"; }

umask 077

[ -f "$ENV_FILE" ] || die "no .env at $ENV_FILE — is HOSPITAL_APP_DIR right?"
for tool in mysql mysqldump gzip tar sha256sum; do
    command -v "$tool" >/dev/null || die "$tool is not installed"
done

DB_CONNECTION=$(env_get DB_CONNECTION); DB_CONNECTION=${DB_CONNECTION:-mysql}
[ "$DB_CONNECTION" = "mysql" ] || die "DB_CONNECTION is '$DB_CONNECTION'; this script only speaks MySQL"

DB_NAME=$(env_get DB_DATABASE)
DB_HOST=$(env_get DB_HOST); DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=$(env_get DB_PORT); DB_PORT=${DB_PORT:-3306}
DB_USER=$(env_get DB_USERNAME); DB_USER=${DB_USER:-root}
DB_PASS=$(env_get DB_PASSWORD)
[ -n "$DB_NAME" ] || die "DB_DATABASE is not set in $ENV_FILE"

write_defaults_file
sql "SELECT 1" >/dev/null 2>&1 || die "cannot connect to MySQL at $DB_HOST:$DB_PORT as $DB_USER"
sql "USE \`$DB_NAME\`" >/dev/null 2>&1 || die "database '$DB_NAME' does not exist"

mkdir -p "$BACKUP_ROOT"
chmod 700 "$BACKUP_ROOT" 2>/dev/null || true

# Two runs at once — cron firing while somebody takes one by hand — would
# interleave into one directory and produce a dump that matches no moment.
LOCK_FILE="$BACKUP_ROOT/.lock"
exec 9>"$LOCK_FILE"
flock -n 9 || die "another backup is already running (lock: $LOCK_FILE)"

log "backing up '$DB_NAME' and the uploads into $RUN_DIR"
mkdir -p "$RUN_DIR"
chmod 700 "$RUN_DIR"

# ---------------------------------------------------------------- database ---
# --single-transaction takes the dump from one consistent snapshot without
# locking the tables, so the site keeps answering while it runs.
# --no-tablespaces so the dump does not need the PROCESS privilege, which a
# dedicated backup user should not be granted just to read its own rows.
# --default-character-set=utf8mb4 is not optional here: the content is half
# Bangla, and a latin1 dump silently mangles all of it.
log "dumping the database"
if ! mysqldump --defaults-extra-file="$DEFAULTS_FILE" \
        --single-transaction --quick --no-tablespaces \
        --routines --triggers \
        --default-character-set=utf8mb4 \
        --set-gtid-purged=OFF \
        "$DB_NAME" | gzip -9 > "$RUN_DIR/database.sql.gz"; then
    die "mysqldump failed"
fi

# mysqldump can exit 0 on a dump that stops halfway when the connection drops.
# The completion marker is the only thing that says the whole thing arrived.
gzip -t "$RUN_DIR/database.sql.gz" || die "the dump is not readable gzip"
if ! gzip -dc "$RUN_DIR/database.sql.gz" | tail -n 3 | grep -q "Dump completed"; then
    die "the dump is truncated — no completion marker at the end"
fi

# The dump carries no database name (no --databases), so a restore can load it
# into a scratch schema to be checked without going anywhere near the live one.
# The name, charset and collation are written down here instead.
read -r DB_CHARSET DB_COLLATION <<< "$(sql "SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME
    FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '$DB_NAME'")"

# Exact row counts, not information_schema.TABLE_ROWS — that figure is an
# estimate on InnoDB, and an estimate cannot tell a good restore from a bad one.
log "counting rows"
: > "$RUN_DIR/counts.txt"
while IFS= read -r table; do
    [ -n "$table" ] || continue
    printf '%s\t%s\n' "$table" "$(sql "SELECT COUNT(*) FROM \`$DB_NAME\`.\`$table\`")" >> "$RUN_DIR/counts.txt"
done < <(sql "SELECT TABLE_NAME FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = '$DB_NAME' AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME")

# ------------------------------------------------------------------- files ---
archive_tree() {
    local label=$1 parent=$2 dir=$3 out=$4
    if [ ! -d "$parent/$dir" ]; then
        log "no $label at $parent/$dir — skipping"
        return 0
    fi
    log "archiving $label"
    tar -czf "$out" -C "$parent" "$dir" || die "could not archive $label"
    gzip -t "$out" || die "the $label archive is not readable gzip"
}

archive_tree "patient documents (private disk)" "$APP_DIR/storage/app" private "$RUN_DIR/private.tar.gz"
archive_tree "uploads (public disk)"            "$APP_DIR/storage/app" public  "$RUN_DIR/public.tar.gz"

if [ "$SKIP_ENV" = "1" ]; then
    log "leaving .env out (HOSPITAL_BACKUP_SKIP_ENV=1) — APP_KEY will have to be written again by hand"
else
    # Stored as `env` rather than `.env`: hidden here, it is missed by an
    # ordinary `cp backup/* .` and by every listing somebody eyeballs.
    cp "$ENV_FILE" "$RUN_DIR/env"
    chmod 600 "$RUN_DIR/env"
fi

# ---------------------------------------------------------------- manifest ---
{
    printf 'taken            %s\n' "$(date '+%Y-%m-%d %H:%M:%S %Z')"
    printf 'host             %s\n' "$(hostname)"
    printf 'app_dir          %s\n' "$APP_DIR"
    printf 'database         %s\n' "$DB_NAME"
    printf 'charset          %s\n' "$DB_CHARSET"
    printf 'collation        %s\n' "$DB_COLLATION"
    printf 'tables           %s\n' "$(wc -l < "$RUN_DIR/counts.txt")"
    printf 'mysql_server     %s\n' "$(sql 'SELECT VERSION()')"
    printf 'mysqldump        %s\n' "$(mysqldump --version | head -1)"
    printf 'env_included     %s\n' "$([ -f "$RUN_DIR/env" ] && echo yes || echo no)"
    if git -C "$APP_DIR" rev-parse --short HEAD >/dev/null 2>&1; then
        printf 'git_commit       %s\n' "$(git -C "$APP_DIR" rev-parse --short HEAD)"
        printf 'git_dirty        %s\n' "$([ -n "$(git -C "$APP_DIR" status --porcelain)" ] && echo yes || echo no)"
    fi
} > "$RUN_DIR/manifest.txt"

( cd "$RUN_DIR" && sha256sum -- * > SHA256SUMS.tmp && mv SHA256SUMS.tmp SHA256SUMS )
chmod 600 "$RUN_DIR"/*

log "done — $(du -sh "$RUN_DIR" | cut -f1) in $RUN_DIR"

# ------------------------------------------------------------------ remote ---
if [ -n "$REMOTE" ]; then
    log "copying to $REMOTE"
    # No --delete: if this box ever loses its backup directory, a mirror would
    # dutifully erase the off-site copies too. Retention off-site is a decision
    # somebody makes there, deliberately.
    if rsync -a --partial "$RUN_DIR" "$REMOTE"; then
        log "off-site copy done"
    else
        warn "the off-site copy failed — the local backup is fine, this machine is now the only copy of it"
    fi
else
    log "HOSPITAL_BACKUP_REMOTE is not set — this backup lives on the same machine as the data it protects"
fi

# --------------------------------------------------------------- retention ---
# Age alone is not enough. If backups have been failing for a fortnight, an
# age-only sweep deletes the last good one on the morning it is needed, so the
# newest KEEP_MIN are held whatever their date.
if [ -n "$KEEP_DAYS" ] && [ "$KEEP_DAYS" -gt 0 ] 2>/dev/null; then
    mapfile -t existing < <(find "$BACKUP_ROOT" -maxdepth 1 -mindepth 1 -type d -name 'hospital-*' -printf '%f\n' | sort -r)
    if [ "${#existing[@]}" -gt "$KEEP_MIN" ]; then
        for name in "${existing[@]:$KEEP_MIN}"; do
            dir="$BACKUP_ROOT/$name"
            if [ -n "$(find "$dir" -maxdepth 0 -mtime "+$KEEP_DAYS")" ]; then
                log "removing $name (older than $KEEP_DAYS days)"
                rm -rf -- "$dir"
            fi
        done
    fi
fi

[ "$DEGRADED" -eq 0 ] || die "the backup was taken, but something above needs looking at"
log "backup complete"
