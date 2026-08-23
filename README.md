# RBR Hospital

A modern hospital website built with **Laravel 13**, **Blade**, **Tailwind CSS 4** and **Alpine.js**, backed by MySQL 8.

Public site, online appointments, booking notifications by SMS and email, a diagnostics price list, a bilingual staff panel and a patient portal — all of it in English and বাংলা. 244 feature tests.

**Not yet deployed.** The Apache vhost, queue worker and scheduler cron are written under `deploy/` but not installed, and mail and SMS are pointed at the log rather than at real providers. See *Running it for real* below.

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

A word on SMS cost: operators bill per segment, and a segment is 160 Latin characters but only **70 Bangla** ones, because one Bangla character switches the whole message to UCS-2. Today every English template fits one segment and every Bangla one takes two. `lang/*/sms.php` is where to shorten them, and a test fails if one grows past two — which is what every template costs today.

Create the first staff account for `/admin`:

```bash
php8.3 artisan admin:create
```

There is no public sign-up for **staff** accounts: they exist only because somebody with shell access made one. Patients register themselves at `/portal`.

## Running it for real

Five things stand between a working checkout and a live site. Each deploy file carries its install commands in a header comment.

| What | Where | If it is missing |
|---|---|---|
| Apache vhost | `deploy/hospital.local.conf` | DocumentRoot **must** be `public/` — pointing Apache at the project root exposes `.env` as a directory listing |
| Queue worker | `deploy/hospital-queue.service` | **Silent.** Mail and SMS queue up in `jobs` and never send |
| Scheduler cron | `deploy/hospital-scheduler.cron` | **Silent.** The day-before reminder never runs |
| SMTP | `.env` `MAIL_*` | Mail is written to the log instead of sent |
| SMS gateway | `.env` `SMS_*` | Text messages are written to the log instead of sent |

Everything below the vhost fails quietly: nothing errors, nothing is logged as a failure, the messages simply never go anywhere. Check those four first if somebody reports that notifications stopped arriving.

`php8.3 artisan appointments:remind --dry-run` shows who would be reminded tonight without sending anything.

## Tests

```bash
vendor/bin/phpunit
```

244 feature tests covering page rendering, doctor search, the contact form, the appointment booking flow (including double-booking, out-of-schedule and out-of-window rejection), UI localisation (persistence, fallback, allow-list enforcement, date localisation, exact key + placeholder parity), content localisation (full Bangla coverage on every record, per-locale setting cache, and both-script search), the staff panel (every route guarded, login throttling, translation writes and clears, slug generation, image upload/replace/remove, chamber-hour overlap rejection, front-desk booking, and the delete guards that protect existing records), and the notifications (who gets an email or an SMS and who does not, queued rather than sent inline, the patient's language surviving a staff member working in the other one, number normalisation, gateway responses that say 200 OK while failing, SMS template segment budgets, and neither a dead mail server nor a dead gateway breaking a booking), the day-before reminder (confirmed bookings only, never twice, dry runs, and the idempotence that lets a failed run be repeated by hand), the diagnostics catalogue (search by name and code, category filtering, hidden tests staying unreachable, and test requests reaching the inbox with the price attached), and the patient portal (guards that keep patients out of the panel, appointments matched across every spelling of a mobile number, single-use expiring reset codes, and documents that one patient can never read from another's account).

## Before launch

**All the Bangla needs a native-speaker review.** It was written without one and covers every locale file — the interface, seeded content, the staff panel, emails, SMS templates, 23 clinical test descriptions and the patient portal. The tests prove that both locales define the same keys and keep the same placeholders; they cannot tell whether a sentence is right.

Start with the two where a mistranslation costs more than embarrassment: the **diagnostics preparation instructions** (`database/seeders/Translations/DiagnosticTestTranslationSeeder.php`), where a wrong fasting window sends somebody home unfasted, and the **emergency symptom list** (`lang/bn/pages.php`).

## Project notes

See `CLAUDE.md` for architecture, the appointment slot engine, the notification and portal design, localisation and design-system conventions.

## Adding a language

Add the locale to `available_locales` in `config/app.php`, create `lang/<code>/` with a file per domain, and translate keys as you go — untranslated keys fall back to English automatically. For database content, add a seeder under `database/seeders/Translations/` writing to the `translations` JSON column. `vendor/bin/phpunit --filter LocalisationTest` and `--filter ContentTranslationTest` will tell you what is still missing.
