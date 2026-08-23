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
npm run build

php8.3 artisan serve --host=127.0.0.1 --port=8321
```

For Apache, install `deploy/hospital.local.conf` — see the comments at the top of that file. DocumentRoot must point at `public/`.

## Tests

```bash
vendor/bin/phpunit
```

48 feature tests covering page rendering, doctor search, the contact form, the appointment booking flow (including double-booking, out-of-schedule and out-of-window rejection), UI localisation (persistence, fallback, allow-list enforcement, date localisation, exact key + placeholder parity), and content localisation (full Bangla coverage on every record, per-locale setting cache, and both-script search).

## Project notes

See `CLAUDE.md` for architecture, the appointment slot engine, localisation and design-system conventions.

## Adding a language

Add the locale to `available_locales` in `config/app.php`, create `lang/<code>/` with a file per domain, and translate keys as you go — untranslated keys fall back to English automatically. For database content, add a seeder under `database/seeders/Translations/` writing to the `translations` JSON column. `vendor/bin/phpunit --filter LocalisationTest` and `--filter ContentTranslationTest` will tell you what is still missing.
