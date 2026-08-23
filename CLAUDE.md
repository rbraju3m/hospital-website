# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**RBR Hospital** — a public-facing hospital website built on **Laravel 13** with **Blade + Tailwind CSS 4 + Alpine.js**, backed by **MySQL 8**. Phase 1 (complete) is the public site, a working online appointment engine, and a full i18n layer. There is **no admin panel and no patient portal yet** — those are Phase 2, and the patient-portal service page describes functionality that does not exist behind it.

Not to be confused with `/var/www/html/c-hospital-website`, a separate older Laravel 11 hospital site using the ftco Bootstrap theme. This project shares nothing with it.

## Runtime versions — this matters

| Context | PHP | Why |
|---|---|---|
| CLI, Composer, artisan, tests | **8.3** (`php8.3`) | `vendor/` was installed under 8.3 |
| Apache (mod_php) | 8.4 | system-wide `php8.4.load`, not per-vhost |

Both satisfy Laravel 13's `^8.3`. Always invoke CLI work as `php8.3` so Composer post-scripts run on the same runtime that built `vendor/`.

**`pdo_sqlite` is not installed.** Laravel's default `:memory:` test database will not work — `phpunit.xml` is pointed at a dedicated MySQL schema (`hospital_site_test`) instead. Don't "fix" it back to SQLite.

## Common commands

```bash
# Serve (dev)
php8.3 artisan serve --host=127.0.0.1 --port=8321
npm run dev                       # Vite HMR alongside the above

# Assets — required after any CSS/JS/Blade class change if not running `npm run dev`
npm run build

# Database
php8.3 artisan migrate
php8.3 artisan migrate:fresh --seed        # rebuild + reseed (seeders are idempotent)
php8.3 artisan db:seed --class=DoctorSeeder

# Tests (37 feature tests)
vendor/bin/phpunit
vendor/bin/phpunit --filter test_the_same_slot_cannot_be_booked_twice
vendor/bin/phpunit tests/Feature/AppointmentBookingTest.php
vendor/bin/phpunit --filter LocalisationTest

# Composer — invoke through php8.3 so post-autoload scripts use the right runtime
php8.3 /usr/bin/composer require some/package
php8.3 /usr/bin/composer dump-autoload

php8.3 artisan route:list --except-vendor
php8.3 artisan view:clear && php8.3 artisan config:clear
```

### Serving over Apache

There is **no vhost installed yet**. `deploy/hospital.local.conf` is ready to install — see the header comment in that file for the three commands. DocumentRoot must be `public/`; pointing Apache at the project root produces a directory listing that exposes `.env`.

## Architecture

### Request flow

Controllers live under `app/Http/Controllers/Web/` (thin — query, pass to view). Views are organised as `resources/views/pages/<area>/<action>.blade.php`, all extending `layouts/site.blade.php`.

`AppServiceProvider` registers a **view composer** that binds `$navDepartments` to `partials.header` and `partials.footer` only. Controllers must not pass the department list for navigation — it is already there.

### The appointment engine — the part with real logic

Everything non-trivial lives in `app/Services/AppointmentSlotService.php`. Read it before touching booking.

Slots are **derived, never stored**. `doctor_schedules` holds a weekly recurring pattern (`day_of_week` 0=Sunday…6=Saturday, matching `Carbon::dayOfWeek`); the service expands that pattern into concrete times for a date and subtracts rows already in `appointments`. There is no slots table to keep in sync.

Double-booking is guarded at **three** layers, and all three are load-bearing:

1. `slotsFor()` excludes times already at `capacity_per_slot`.
2. `AppointmentController@store` re-checks `isSlotAvailable()` at submit time — closes the window between page load and submit.
3. A unique index `appt_doctor_slot_unique` on `(doctor_id, appointment_date, appointment_time)`. The controller catches MySQL error 1062 on that index name and converts it to a friendly validation error rather than a 500.

Booking constraints: `BOOKING_WINDOW_DAYS = 30` ahead, `MIN_LEAD_MINUTES = 60` for same-day slots. These constants are referenced from `StoreAppointmentRequest`, so changing them changes validation too.

The booking page drives itself through two JSON endpoints (`appointment.doctors`, `appointment.slots`) consumed by an Alpine component defined inline in `pages/appointment/create.blade.php`.

### Data model

`Department` 1—n `Doctor` 1—n `DoctorSchedule`; `Appointment` belongs to both `Doctor` and `Department` (denormalised at write time from the doctor). `Service`, `HealthPackage`, `Testimonial`, `Post`, `ContactMessage` are standalone.

Models use `$guarded = []` with a `casts()` method. Slug is the route key on every public-facing model. Scopes are consistent: `active()`, `ordered()`, plus `published()`/`latestFirst()` on `Post` and `search()` on `Doctor`.

`Setting` is a key/value store cached forever under `settings.all`, busted on save/delete. **Read it via the global `setting('key')` helper** (`app/Support/helpers.php`, registered in composer `autoload.files`). Its accessors are named `cachedMap()`/`config()` — deliberately *not* `all()`/`get()`, which `Model::__callStatic` forwards to the query builder.

Content lives in seeders, not migrations, and every seeder uses `updateOrCreate` keyed on slug, so re-running is safe and non-destructive.

## Localisation

**No user-facing string belongs in a template.** Everything renders through `__()` / `trans_choice()` against `lang/<locale>/<domain>.php`. Domains: `common`, `nav`, `home`, `departments`, `doctors`, `services`, `packages`, `posts`, `appointment`, `pages` (about/emergency/international/contact), `forms` (validation messages and attribute names, referenced from `app/Http/Requests/`).

Rule of thumb for where a key goes: used on more than one page → `common`; used on one page → that page's file.

`config('app.available_locales')` is the single source of truth for which locales exist — it drives the switcher, the `hreflang` tags, the route guard and the tests. Adding a locale means adding a key there and a directory under `lang/`.

`SetLocale` middleware (appended to the `web` group, so it runs after the session starts) resolves the locale as: session choice → `Accept-Language` → `config('app.locale')`. **Anything outside `available_locales` is discarded** at every step, so a tampered session value or header cannot point the translator at an arbitrary path — there is a test for this.

`lang/en` and `lang/bn` are both complete — 506 keys across 11 domains, full parity. Laravel still falls back per key, so a future English-only key renders English rather than a raw key rather than breaking the page.

**All Bangla needs native-speaker review before launch.** It was written without one.

Three tests keep the locales honest and will fail the moment a key is added to one locale only:

- every locale has a file per domain
- the locales define **exactly** the same key set, in both directions
- **every `:placeholder` survives translation** — a dropped `:count` or `:name` leaves a literal gap mid-sentence that no page-level test would catch

When adding a UI string, add it to *both* locales in the same change.

Things that are correctly *not* translated: phone/email format examples in placeholders (`01712345678`), and all seeded **database** content — department and service names, doctor names and qualifications, package test lists, testimonials, article bodies, and the `settings` values (address, tagline, accreditation). On a Bangla page every piece of chrome is Bangla and everything still in English comes from the database. Localising that needs translated columns, which is Phase 2.

**Dates need two things set, not one.** Carbon keeps its own locale independently of the app locale, so `SetLocale` sets `Carbon::setLocale()` and `CarbonImmutable::setLocale()` as well — without that, month and weekday names stay English on an otherwise Bangla page. Always use `translatedFormat()` (never `format()`) where a month or weekday **name** is rendered; `format()` stays correct for machine formats like `H:i` and `Y-m-d`. Weekday labels in the chamber schedule come from `DoctorSchedule::dayLabel()`, not the `DAYS` constant — that constant is English-only and exists for seeding and internal reference. Dates in the Alpine booking component format via `document.documentElement.lang`.

## Design system

The look is the product. Deep navy (`navy-*`) + teal accent (`teal-*`) on near-white surfaces, generous whitespace, `rounded-[1.25rem]` cards, soft shadows.

**`urgent-*` (red) is reserved exclusively for emergency and ambulance affordances.** Do not use it for generic errors elsewhere in the UI or the emergency signal loses its meaning.

Tokens and component classes are defined in `resources/css/app.css`. Component classes (`btn`, `btn-primary`, `card`, `input`, `shell`, `section`, `eyebrow`, …) are declared with Tailwind 4's **`@utility`, not `@layer components`** — this is required, because `@layer components` classes cannot be `@apply`-ed by other classes in v4 and the build fails with "Cannot apply unknown utility class". Follow that pattern when adding new component classes, and define a class before anything that applies it.

Reusable Blade components are in `resources/views/components/`: `icon` (inline Lucide paths keyed by name — add new icons to the `$paths` array), `doctor-card`, `department-card`, `package-card`, `post-card`, `section-heading`, `page-hero`, `doctor-avatar`, `rating`, `article-body`.

Article bodies use a markdown-lite convention (`## heading`, `- bullet`, `**bold**`) rendered by `x-article-body`, which escapes first and re-introduces only bold via the `inline_markup()` helper. Do not render post bodies with raw `{!! !!}`.

Scroll reveals: add `class="reveal"` and an IntersectionObserver in `resources/js/app.js` adds `reveal-in`. Motion is disabled under `prefers-reduced-motion`.

## Conventions worth keeping

- Bangladeshi mobile validation is a shared regex in both form requests: `/^(?:\+?88)?01[3-9]\d{8}$/`.
- Money is stored as integer BDT (no minor units) and rendered `৳{{ number_format(...) }}`.
- Public POST routes are rate-limited (`throttle:10,1`).
- Copy is written plainly and avoids marketing superlatives — claims in seeded content are specific and checkable (response times, staffing ratios) by design. Keep that voice.
- `.idea/` is untracked and stays that way.

## Not built yet (Phase 2)

Admin CRUD, patient portal, diagnostics test catalogue with pricing.

On localisation specifically: the UI is fully translated in both locales, but **database content is not**. Outstanding are translated columns on the content models (departments, doctors, services, packages, posts, testimonials) plus the `settings` table, and native-speaker review of the existing Bangla.
