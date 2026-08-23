# RBR Hospital

A modern hospital website built with **Laravel 13**, **Blade**, **Tailwind CSS 4** and **Alpine.js**, backed by MySQL 8.

## Features

- **Find a Doctor** — filterable consultant directory (search, department, gender, sort) with individual profiles showing qualifications, expertise, chamber schedule and fees
- **Online appointments** — department → consultant → live open slots → patient details → reference number, with triple-layered double-booking prevention
- **Departments** — 16 clinical departments, 8 flagged as centres of excellence, each with treatments, highlights and its consultant list
- **Health packages** — 9 preventive screening packages with itemised test lists and pricing
- **Services & facilities** — grouped clinical, diagnostic, support and patient services
- **Health Hub** — consultant-authored health articles
- **Emergency page** — symptom guidance, ambulance information and direct-dial numbers
- **International patients** — visa letters, transfers, accommodation and interpreter support
- **Fully bilingual (English + বাংলা)** — interface *and* database content in both locales: departments, consultant profiles, services, packages, testimonials and articles. Locale switcher, `Accept-Language` detection, session persistence, locale-aware dates, per-key fallback, and consultant search that works in either script
- **Booking emails** — the patient gets a confirmation the moment they book and again when the desk confirms or cancels, in the language they booked in; the appointment desk is alerted to every website booking. Queued, so a slow mail server never holds up the booking page
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
php8.3 artisan queue:work            # in a second terminal — sends the booking emails
```

Set `MAIL_*` in `.env` before emails leave the building; the default `MAIL_MAILER=log` writes them to `storage/logs/laravel.log`, which is handy for checking the wording.

Create the first staff account for `/admin`:

```bash
php8.3 artisan admin:create
```

There is no public sign-up — accounts exist only because someone with shell access made one.

For Apache, install `deploy/hospital.local.conf` — see the comments at the top of that file. DocumentRoot must point at `public/`. In production the queue worker needs to run as a service too: `deploy/hospital-queue.service` is ready to install.

Without a worker running, bookings still succeed and nothing errors — the emails simply queue up in the `jobs` table and never send. Worth knowing before debugging "confirmations stopped arriving".

## Tests

```bash
vendor/bin/phpunit
```

137 feature tests covering page rendering, doctor search, the contact form, the appointment booking flow (including double-booking, out-of-schedule and out-of-window rejection), UI localisation (persistence, fallback, allow-list enforcement, date localisation, exact key + placeholder parity), content localisation (full Bangla coverage on every record, per-locale setting cache, and both-script search), the staff panel (every route guarded, login throttling, translation writes and clears, slug generation, image upload/replace/remove, chamber-hour overlap rejection, front-desk booking, and the delete guards that protect existing records), and the notification emails (who gets one and who does not, queued rather than sent inline, the patient's language surviving a staff member working in the other one, and a dead mail server not breaking a booking).

## Project notes

See `CLAUDE.md` for architecture, the appointment slot engine, localisation and design-system conventions.

## Adding a language

Add the locale to `available_locales` in `config/app.php`, create `lang/<code>/` with a file per domain, and translate keys as you go — untranslated keys fall back to English automatically. For database content, add a seeder under `database/seeders/Translations/` writing to the `translations` JSON column. `vendor/bin/phpunit --filter LocalisationTest` and `--filter ContentTranslationTest` will tell you what is still missing.
