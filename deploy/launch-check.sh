#!/usr/bin/env bash
#
# RBR Hospital — what is actually installed on this machine.
#
# The application is complete and the deployment is not, and three of the gaps
# fail silently: a booking succeeds, nothing errors, and the SMS is never sent.
# The repository cannot tell you about any of that. deploy/ holding a correct
# queue unit says nothing about whether the unit is running, and this is not a
# hypothetical — the vhost enabled on this box was an earlier copy of the one
# in deploy/, serving plain http, while the repository's own documentation
# described the TLS it was not doing.
#
# So this reports on THE BOX, never on the repository. Run it, fix what it
# calls OPEN, run it again.
#
#   deploy/launch-check.sh
#   deploy/launch-check.sh --quiet     # only what is not done
#
# It changes nothing, ever: no writes, no restarts, no migrations. It is safe
# to run on a live server in the middle of the afternoon, which is the only
# way a check like this actually gets run.
#
# Exit status is 0 when nothing is OPEN and 1 otherwise, so it can gate a
# deploy the way deploy/csp-walk.js does. WARN never fails the run: it is for
# things that are done but worth a second look, and a check that cries wolf is
# one people learn to skip.
#
# It reads .env directly rather than going through artisan, for the same reason
# the backup scripts do: the moment you most want to know what is configured is
# often the moment the application will not boot.

set -euo pipefail

SCRIPT_DIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
APP_DIR="${HOSPITAL_APP_DIR:-$(cd -- "$SCRIPT_DIR/.." && pwd)}"
ENV_FILE="$APP_DIR/.env"
BACKUP_ROOT="${HOSPITAL_BACKUP_DIR:-/var/backups/hospital}"

QUIET=0
[ "${1:-}" = "--quiet" ] && QUIET=1

OPEN=0
DEFAULTS_FILE=

cleanup() { [ -n "$DEFAULTS_FILE" ] && rm -f "$DEFAULTS_FILE"; return 0; }
trap cleanup EXIT

# Colour only when a human is watching. Piped into a log, escape codes are noise.
if [ -t 1 ]; then
    C_OK=$'\033[32m'; C_OPEN=$'\033[31m'; C_WARN=$'\033[33m'; C_OFF=$'\033[0m'
else
    C_OK=; C_OPEN=; C_WARN=; C_OFF=
fi

ok()   { [ "$QUIET" -eq 1 ] || printf '  %sok  %s%-34s %s\n' "$C_OK" "$C_OFF" "$1" "${2:-}"; }
warn() { printf '  %sWARN%s %-34s %s\n' "$C_WARN" "$C_OFF" "$1" "${2:-}"; }
open() { printf '  %sOPEN%s %-34s %s\n' "$C_OPEN" "$C_OFF" "$1" "${2:-}"; OPEN=$((OPEN + 1)); }
# Not named `head`: this script pipes into head(1), and a function of that
# name shadows it — which silently emptied the vhost lookup below.
section() { [ "$QUIET" -eq 1 ] || printf '\n%s\n' "$1"; }

# Read one key out of .env. Deliberately not `source`: .env is not shell, and
# sourcing it would execute whatever a stray backtick in a password did.
env_get() {
    local key=$1 line value
    [ -f "$ENV_FILE" ] || return 0
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

[ -f "$ENV_FILE" ] || { echo "no .env at $ENV_FILE — wrong directory?" >&2; exit 1; }

APP_URL=$(env_get APP_URL)
APP_ENV=$(env_get APP_ENV)
APP_DEBUG=$(env_get APP_DEBUG)
DB_DATABASE=$(env_get DB_DATABASE)
DB_USERNAME=$(env_get DB_USERNAME)
DB_PASSWORD=$(env_get DB_PASSWORD)
DB_HOST=$(env_get DB_HOST)
MAIL_MAILER=$(env_get MAIL_MAILER)
MAIL_FROM=$(env_get MAIL_FROM_ADDRESS)
SMS_DRIVER=$(env_get SMS_DRIVER)
CSP_ENFORCE=$(env_get CSP_ENFORCE)
REMOTE=$(env_get HOSPITAL_BACKUP_REMOTE)
[ -n "$REMOTE" ] || REMOTE="${HOSPITAL_BACKUP_REMOTE:-}"

printf 'RBR Hospital — launch check\n  %s\n  %s\n' "$APP_DIR" "$(date '+%Y-%m-%d %H:%M:%S %z')"

# ---------------------------------------------------------------- web server

section 'Web server'

# -R, not -r: everything in sites-enabled is a symlink into sites-available,
# and -r does not follow them — so -r finds no vhost on a correctly
# configured server and reports the one gap that is not there.
VHOST=$(grep -RlE "DocumentRoot[[:space:]]+$APP_DIR/public" /etc/apache2/sites-enabled/ 2>/dev/null | head -n 1 || true)

if [ -z "$VHOST" ]; then
    open 'apache vhost' "nothing in sites-enabled serves $APP_DIR/public"
else
    ok 'apache vhost' "$(basename "$VHOST")"

    # The failure this exists for: the enabled file is an older copy of the one
    # in deploy/, so every TLS decision written down in the repository is
    # simply not in force, and nothing about the running site looks wrong.
    MATCHED=
    for candidate in "$SCRIPT_DIR"/*.conf; do
        [ -f "$candidate" ] || continue
        if diff -q "$VHOST" "$candidate" >/dev/null 2>&1; then MATCHED=$candidate; break; fi
    done
    if [ -n "$MATCHED" ]; then
        ok 'vhost matches deploy/' "$(basename "$MATCHED")"
    else
        warn 'vhost differs from deploy/' "diff $VHOST $SCRIPT_DIR/*.conf"
    fi

    if grep -qE '^[[:space:]]*<VirtualHost[^>]*:443' "$VHOST"; then
        ok 'vhost terminates TLS' 'has a :443 block'
    else
        open 'vhost terminates TLS' 'no :443 block — this vhost is http only'
    fi
fi

if [ -e /etc/apache2/mods-enabled/ssl.load ]; then
    ok 'mod_ssl' 'enabled'
else
    open 'mod_ssl' 'sudo a2enmod ssl'
fi

for m in rewrite headers; do
    if [ -e "/etc/apache2/mods-enabled/$m.load" ]; then
        ok "mod_$m" 'enabled'
    else
        open "mod_$m" "sudo a2enmod $m"
    fi
done

# --------------------------------------------------------------------- HTTPS

section 'HTTPS'

CERT=
[ -n "$VHOST" ] && CERT=$(grep -E '^[[:space:]]*SSLCertificateFile' "$VHOST" 2>/dev/null | awk '{print $2}' | head -n 1 || true)

case "$APP_URL" in
    https://*)
        ok 'APP_URL scheme' "$APP_URL"
        # A signed link is signed over its scheme. https here with no working
        # certificate is not a cosmetic mismatch: it is a 403 on the
        # confirmation link in every appointment email, for bookings that are
        # perfectly fine, with nothing in the log mentioning the scheme.
        if [ -n "$CERT" ] && [ -f "$CERT" ]; then
            ok 'certificate' "$CERT"
            if command -v openssl >/dev/null 2>&1; then
                if openssl x509 -checkend 604800 -noout -in "$CERT" >/dev/null 2>&1; then
                    ok 'certificate validity' "more than 7 days left"
                else
                    open 'certificate validity' "expires within 7 days — $CERT"
                fi
            fi
        else
            open 'certificate' "APP_URL is https and there is no certificate: every signed confirmation link 403s"
        fi
        ;;
    http://*)
        if [ -n "$CERT" ] && [ -f "$CERT" ]; then
            open 'APP_URL scheme' "a certificate is installed but APP_URL is still $APP_URL"
        else
            open 'APP_URL scheme' "$APP_URL — set https only after the certificate is in place"
        fi
        ;;
    *)
        open 'APP_URL scheme' "unreadable: '$APP_URL'"
        ;;
esac

# ---------------------------------------------------------- worker and cron

section 'Background work'

# A worker has to be THIS application's. There are thirty-odd vhosts on this
# machine and other Laravel projects run their own queues, so matching
# `artisan queue:work` by name alone reports somebody else's worker as ours —
# the one answer this script must never give, because it is the gap that is
# already invisible. Match the command line or the process's own directory.
ours_queue_work() {
    local pid cmd cwd
    for pid in $(pgrep -f 'artisan queue:work' 2>/dev/null || true); do
        cmd=$(tr '\0' ' ' < "/proc/$pid/cmdline" 2>/dev/null || true)
        case "$cmd" in *"$APP_DIR"*) return 0 ;; esac
        cwd=$(readlink -f "/proc/$pid/cwd" 2>/dev/null || true)
        [ "$cwd" = "$APP_DIR" ] && return 0
    done
    return 1
}

if systemctl is-active --quiet hospital-queue 2>/dev/null; then
    ok 'queue worker' 'hospital-queue is active'
elif ours_queue_work; then
    warn 'queue worker' 'a queue:work is running for this app, but not under systemd — it will not survive a reboot'
else
    open 'queue worker' 'nothing is draining the queue: every email and SMS will sit in jobs for ever'
fi

cron_has() { { crontab -l 2>/dev/null; cat /etc/cron.d/* 2>/dev/null; } | grep -qs -- "$1"; }

if cron_has 'schedule:run'; then
    ok 'scheduler cron' 'schedule:run is scheduled'
else
    open 'scheduler cron' 'the day-before reminder never runs, and nothing is pruned'
fi

if cron_has 'hospital-backup'; then
    ok 'backup cron' 'scheduled'
else
    open 'backup cron' 'nothing is backed up on a schedule'
fi

# ------------------------------------------------------------------- backups

section 'Backups'

if [ -d "$BACKUP_ROOT" ]; then
    NEWEST=$(find "$BACKUP_ROOT" -maxdepth 1 -type d -name 'hospital-*' -printf '%T@ %p\n' 2>/dev/null | sort -rn | head -n 1 | cut -d' ' -f2- || true)
    if [ -n "$NEWEST" ]; then
        AGE_H=$(( ( $(date +%s) - $(stat -c %Y "$NEWEST") ) / 3600 ))
        # A dump that stopped halfway can still exit 0, so the marker is what
        # says the whole of it arrived. The restore checks this too.
        if [ -f "$NEWEST/database.sql.gz" ] && zcat "$NEWEST/database.sql.gz" 2>/dev/null | tail -c 400 | grep -q 'Dump completed'; then
            if [ "$AGE_H" -le 24 ]; then
                ok 'most recent backup' "${AGE_H}h old, complete — $(basename "$NEWEST")"
            else
                warn 'most recent backup' "${AGE_H}h old — $(basename "$NEWEST")"
            fi
        else
            open 'most recent backup' "no completion marker in $(basename "$NEWEST") — that backup is not one"
        fi
    else
        open 'most recent backup' "$BACKUP_ROOT exists but holds no backup"
    fi
else
    open 'backup directory' "$BACKUP_ROOT does not exist"
fi

if [ -n "$REMOTE" ]; then
    ok 'off-site copy' "$REMOTE"
else
    open 'off-site copy' 'HOSPITAL_BACKUP_REMOTE is unset: every copy is on the disk it protects'
fi

# ------------------------------------------------------------- notifications

section 'Notifications'

case "$MAIL_MAILER" in
    log|'') open 'mail transport' "MAIL_MAILER=${MAIL_MAILER:-unset} — mail is written to the log, not sent" ;;
    *)      ok   'mail transport' "$MAIL_MAILER" ;;
esac

case "$MAIL_FROM" in
    *example.com|*example.test|'') open 'mail from address' "${MAIL_FROM:-unset} — a real address, or it is spam-filed" ;;
    *)                             ok   'mail from address' "$MAIL_FROM" ;;
esac

case "$SMS_DRIVER" in
    # `discard` is deliberate and silent; `log` is the default and usually
    # means nobody has been given the gateway credentials yet. SMS is the only
    # channel that reaches every patient, because email is optional on the form.
    log|'')  open 'sms gateway' "SMS_DRIVER=${SMS_DRIVER:-unset} — texts are written to the log, not sent" ;;
    discard) warn 'sms gateway' 'discard — texts are deliberately thrown away' ;;
    *)       ok   'sms gateway' "$SMS_DRIVER" ;;
esac

# ---------------------------------------------------------------- application

section 'Application'

if [ -L "$APP_DIR/public/storage" ]; then
    ok 'storage symlink' 'present'
else
    open 'storage symlink' 'php8.3 artisan storage:link — every upload 404s without it'
fi

case "$CSP_ENFORCE" in
    true|1) ok 'CSP' 'enforced' ;;
    *)      warn 'CSP' "CSP_ENFORCE=${CSP_ENFORCE:-unset} — report-only. Walk the site (deploy/csp-walk.js), then enforce" ;;
esac

if [ "$APP_ENV" = "production" ]; then
    case "$APP_DEBUG" in
        false|0) ok 'APP_DEBUG' 'off' ;;
        *)       open 'APP_DEBUG' "APP_DEBUG=$APP_DEBUG in production — stack traces, .env values and queries to any visitor who triggers an error" ;;
    esac
else
    warn 'APP_ENV' "$APP_ENV — not production"
fi

# ---------------------------------------------------------------------- data

section 'Data'

if command -v mysql >/dev/null 2>&1 && [ -n "$DB_DATABASE" ]; then
    # The password goes in a defaults file, never in argv: a command line is
    # visible in `ps` to every user on the box for as long as it runs.
    DEFAULTS_FILE=$(mktemp)
    chmod 600 "$DEFAULTS_FILE"
    {
        printf '[client]\n'
        printf 'user=%s\n' "${DB_USERNAME:-root}"
        printf 'password=%s\n' "$DB_PASSWORD"
        [ -n "$DB_HOST" ] && printf 'host=%s\n' "$DB_HOST"
    } > "$DEFAULTS_FILE"

    q() { mysql --defaults-extra-file="$DEFAULTS_FILE" --batch --skip-column-names \
              --default-character-set=utf8mb4 "$DB_DATABASE" -e "$1" 2>/dev/null || true; }

    if [ -n "$(q 'SELECT 1')" ]; then
        STUCK=$(q "SELECT COUNT(*) FROM notification_logs WHERE status = 'queued' AND created_at < NOW() - INTERVAL 30 MINUTE")
        if [ "${STUCK:-0}" -gt 0 ]; then
            open 'messages stuck queued' "$STUCK waiting more than 30 minutes — the worker is not running"
        else
            ok 'messages stuck queued' 'none'
        fi

        JOBS=$(q 'SELECT COUNT(*) FROM jobs')
        [ "${JOBS:-0}" -gt 0 ] && warn 'queue backlog' "$JOBS job(s) waiting" || ok 'queue backlog' 'empty'

        FAILED=$(q 'SELECT COUNT(*) FROM failed_jobs')
        [ "${FAILED:-0}" -gt 0 ] && warn 'failed jobs' "$FAILED — php8.3 artisan queue:failed" || ok 'failed jobs' 'none'

        STAFF=$(q 'SELECT COUNT(*) FROM users')
        ROLES=$(q "SELECT COUNT(DISTINCT role) FROM users")
        if [ "${STAFF:-0}" -le 1 ]; then
            warn 'staff accounts' "$STAFF — the seeded administrator is still the only one, so the roles are untested"
        elif [ "${ROLES:-0}" -le 1 ]; then
            warn 'staff accounts' "$STAFF accounts, all one role"
        else
            ok 'staff accounts' "$STAFF across $ROLES roles"
        fi
    else
        warn 'database' "cannot read $DB_DATABASE with the .env credentials"
    fi
else
    warn 'database' 'mysql client not found — skipped the data checks'
fi

# -------------------------------------------------------------------- verdict

printf '\n'
if [ "$OPEN" -eq 0 ]; then
    printf '%sNothing open.%s Anything above marked WARN is done but worth a look.\n' "$C_OK" "$C_OFF"
    exit 0
fi

printf '%s%d open.%s Each one is a thing this machine is not doing that the site assumes it is.\n' \
    "$C_OPEN" "$OPEN" "$C_OFF"
printf 'Install commands are in the header of each file in %s.\n' "$SCRIPT_DIR"
exit 1
