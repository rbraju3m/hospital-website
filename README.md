# RBR Hospital

A modern hospital website built with **Laravel 13**, **Blade**, **Tailwind CSS 4** and **Alpine.js**, backed by MySQL 8.

## Features

- **Find a Doctor** — filterable consultant directory (search, department, gender, sort) with individual profiles showing qualifications, expertise, chamber schedule and fees
- **Online appointments** — department → consultant → live open slots → patient details → reference number, with triple-layered double-booking prevention
- **Departments** — 16 clinical departments, 8 flagged as centres of excellence, each with treatments, highlights and its consultant list
- **Health packages** — 9 preventive screening packages with itemised test lists and pricing
- **Diagnostics catalogue** — 23 laboratory, imaging, cardiac and endoscopy tests with prices, preparation instructions, sample type and report turnaround; searchable by name or by the code on a prescription, with a call-back request that lands in the staff inbox
- **Services & facilities** — grouped clinical, diagnostic, support and patient services
- **Health Hub** — consultant-authored health articles
- **Emergency page** — symptom guidance, ambulance information and direct-dial numbers
- **International patients** — visa letters, transfers, accommodation and interpreter support
- **Fully bilingual (English + বাংলা)** — interface *and* database content in both locales: departments, consultant profiles, services, packages, testimonials and articles. Locale switcher, `Accept-Language` detection, session persistence, locale-aware dates, per-key fallback, and consultant search that works in either script
- **Booking notifications, by SMS and email** — the patient is texted the moment they book and again when the desk confirms or cancels, in the language they booked in; the desk is alerted to every website booking. SMS is the channel that matters: email is optional on the booking form, phone is not. Both are queued, so a slow gateway never holds up the booking page
- **Day-before reminder** — confirmed appointments get an SMS and an email at 6pm the evening before, once and only once. Unconfirmed bookings are reported to the desk instead of being reminded
- **Pluggable SMS gateway** — `log` and `discard` drivers plus a generic HTTP driver that adapts to most Bangladeshi providers (Alpha SMS, BulkSMSBD, MIMSMS, Elitbuzz, Reve) through `.env` alone
- **Patient portal** (`/portal`) — patients register with the mobile number they book with and find their appointments already there, alongside reports, prescriptions and bills published by staff. Password sign-in with recovery by SMS, because email is optional everywhere else on this site too
- **Staff panel** (`/admin`) — the appointment book (filter, search, front-desk booking, status, CSV export), the contact inbox, and bilingual CRUD over departments, consultants and their chamber hours, services, health packages, articles, testimonials, site settings and staff accounts. Every content form edits both languages side by side and flags what is still untranslated; image uploads are handled here too
- Responsive, accessible (skip link, focus rings, `prefers-reduced-motion`), SEO meta and self-hosted fonts

## Requirements

- PHP 8.3+ (CLI work uses `php8.3`; `pdo_sqlite` is not required, MySQL is)
- Composer 2, Node 20+, MySQL 8

## Setup

```bash
php8.3 /usr/bin/composer install
npm install

cp .env.example .env
php8.3 artisan key:generate
# set DB_DATABASE / DB_USERNAME / DB_PASSWORD in .env

php8.3 artisan migrate --seed
php8.3 artisan storage:link          # uploaded images 404 without this
npm run build

php8.3 artisan serve --host=127.0.0.1 --port=8321
php8.3 artisan queue:work            # in a second terminal — sends the notifications
```

The app runs on **Asia/Dhaka**, not UTC: chamber hours, the booking window and the reminder are all local to the hospital's wall clock. Timestamps are stored in that zone.

Set `MAIL_*` and `SMS_*` in `.env` before anything leaves the building. Both default to `log`, writing to `storage/logs/laravel.log` — handy for checking wording and, for SMS, the segment count you will be billed for.

A word on SMS cost: operators bill per segment, and a segment is 160 Latin characters but only **70 Bangla** ones, because one Bangla character switches the whole message to UCS-2. Today every English template fits one segment and every Bangla one takes two. `lang/*/sms.php` is where to shorten them, and a test fails if one grows past three.

Create the first staff account for `/admin`:

```bash
php8.3 artisan admin:create
```

There is no public sign-up — accounts exist only because someone with shell access made one.

For Apache, install `deploy/hospital.local.conf` — see the comments at the top of that file. DocumentRoot must point at `public/`. Production also needs two background pieces, both ready to install:

- `deploy/hospital-queue.service` — the queue worker, which delivers every email and SMS
- `deploy/hospital-scheduler.cron` — the single cron entry that runs the day-before reminder

Both fail silently when missing. Without the worker, bookings still succeed and nothing errors — the messages simply queue up in the `jobs` table. Without the cron entry, the reminder never runs at all. Worth knowing before debugging "notifications stopped arriving".

`php8.3 artisan appointments:remind --dry-run` shows who would be reminded tonight without sending anything.

## Tests

```bash
vendor/bin/phpunit
```

244 feature tests covering page rendering, doctor search, the contact form, the appointment booking flow (including double-booking, out-of-schedule and out-of-window rejection), UI localisation (persistence, fallback, allow-list enforcement, date localisation, exact key + placeholder parity), content localisation (full Bangla coverage on every record, per-locale setting cache, and both-script search), the staff panel (every route guarded, login throttling, translation writes and clears, slug generation, image upload/replace/remove, chamber-hour overlap rejection, front-desk booking, and the delete guards that protect existing records), and the notifications (who gets an email or an SMS and who does not, queued rather than sent inline, the patient's language surviving a staff member working in the other one, number normalisation, gateway responses that say 200 OK while failing, SMS template segment budgets, and neither a dead mail server nor a dead gateway breaking a booking), the day-before reminder (confirmed bookings only, never twice, dry runs, and the idempotence that lets a failed run be repeated by hand), the diagnostics catalogue (search by name and code, category filtering, hidden tests staying unreachable, and test requests reaching the inbox with the price attached), and the patient portal (guards that keep patients out of the panel, appointments matched across every spelling of a mobile number, single-use expiring reset codes, and documents that one patient can never read from another's account).

## Project notes

See `CLAUDE.md` for architecture, the appointment slot engine, localisation and design-system conventions.

## Adding a language

Add the locale to `available_locales` in `config/app.php`, create `lang/<code>/` with a file per domain, and translate keys as you go — untranslated keys fall back to English automatically. For database content, add a seeder under `database/seeders/Translations/` writing to the `translations` JSON column. `vendor/bin/phpunit --filter LocalisationTest` and `--filter ContentTranslationTest` will tell you what is still missing.
