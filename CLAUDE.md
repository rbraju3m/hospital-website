# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**RBR Hospital** — a public-facing hospital website built on **Laravel 13** with **Blade + Tailwind CSS 4 + Alpine.js**, backed by **MySQL 8**. Phase 1 (complete) is the public site, a working online appointment engine, and a full i18n layer. Phase 2 so far is the **staff panel at `/admin`** — bilingual CRUD over every content type, the appointment book, the contact inbox, site settings and staff accounts. There is still **no patient portal**, and the patient-portal service page describes functionality that does not exist behind it.

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

# Staff panel
php8.3 artisan admin:create                        # prompts for name / email / password
php8.3 artisan admin:create --name=… --email=… --password=…
php8.3 artisan storage:link                        # required once, or uploads 404

# Queue — appointment emails are queued; without a worker they never send
php8.3 artisan queue:work                          # dev; see deploy/hospital-queue.service for prod
php8.3 artisan queue:work --stop-when-empty        # drain and exit
php8.3 artisan queue:failed                        # anything that gave up after 3 tries
php8.3 artisan queue:restart                       # after deploying code

# Tests (137 feature tests)
vendor/bin/phpunit
vendor/bin/phpunit --filter test_the_same_slot_cannot_be_booked_twice
vendor/bin/phpunit tests/Feature/AppointmentBookingTest.php
vendor/bin/phpunit tests/Feature/Admin           # the staff panel
vendor/bin/phpunit --filter AppointmentNotificationTest # the emails
vendor/bin/phpunit --filter LocalisationTest      # UI strings
vendor/bin/phpunit --filter ContentTranslationTest # database content

# Composer — invoke through php8.3 so post-autoload scripts use the right runtime
php8.3 /usr/bin/composer require some/package
php8.3 /usr/bin/composer dump-autoload

php8.3 artisan route:list --except-vendor
php8.3 artisan view:clear && php8.3 artisan config:clear
```

### Serving over Apache

There is **no vhost installed yet**. `deploy/hospital.local.conf` is ready to install — see the header comment in that file for the three commands. DocumentRoot must be `public/`; pointing Apache at the project root produces a directory listing that exposes `.env`.

`deploy/hospital-queue.service` is the matching systemd unit for the queue worker, and is **not installed either**. Its absence is silent: bookings still succeed and nothing errors, the emails just sit in the `jobs` table forever. That is the failure mode to check first if someone says confirmations stopped arriving.

## Architecture

### Request flow

Public controllers live under `app/Http/Controllers/Web/` (thin — query, pass to view). Views are organised as `resources/views/pages/<area>/<action>.blade.php`, all extending `layouts/site.blade.php`.

The staff panel mirrors that: `app/Http/Controllers/Admin/`, views under `resources/views/admin/<area>/`, extending `admin/layouts/app.blade.php`. Its routes live in **`routes/admin.php`**, loaded by the `then:` callback in `bootstrap/app.php` inside the `web` group — so session, CSRF and `SetLocale` all apply and the panel is bilingual for the same reasons the site is.

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

### The staff panel

Auth is hand-rolled and minimal: login, logout, no registration and no password-reset mail. Accounts come from `php8.3 artisan admin:create`. `bootstrap/app.php` points `redirectGuestsTo()` at `admin.login` — there is no public account area, so `auth` only ever guards `/admin`. Login is rate-limited per email+IP by `LoginRequest`.

**Every content form writes two locales at once.** The fallback locale posts in ordinary fields; every other locale posts under `translations[<locale>][<column>]`, which is exactly the shape `HasTranslations` reads back. `HandlesTranslatableContent::fillTranslatable()` splits the payload and is the only thing besides the seeders that writes the `translations` column. Three details in there are load-bearing:

- A translated field **submitted empty is removed**, not stored as `''`. Both fall back at read time, but only removal leaves `missingTranslations()` telling the truth about what still needs translating.
- **List columns are derived from the model's casts**, not declared per controller — anything cast to `array` is edited as a one-item-per-line textarea and split on save. Adding a JSON column to a model therefore cannot leave a form silently storing a string.
- The **`?untranslated=<locale>` filter runs in PHP**, not SQL. `missingTranslations()` skips fields that are blank in the source too, and a `JSON_EXTRACT` query cannot tell those apart from a real gap. `paginateContent()` keeps ordinary listings paginating in the database.

Uploads go through `App\Services\MediaLibrary` to the `public` disk, one folder per content type, with the disk-relative path in the existing `image`/`photo` column. **Render them with `media_url()`, never `asset()`** — the disk is only reachable through the `storage:link` symlink. Replacing or removing an image deletes the old file; so does deleting the row.

Chamber hours are edited on the doctor's page as their own little forms (`DoctorScheduleController`), because HTML forbids nesting a form inside another. Overlapping windows on the same weekday are rejected: two windows over the same minutes would generate a slot twice, and the unique index would then bounce the second booking with nothing a patient could act on.

Front-desk bookings are deliberately **laxer than the public form** — no 30-day window, no 60-minute lead time, any time accepted rather than only the published grid. Those constraints exist to protect an unattended web form; staff can see the consultant's actual day. The unique index still applies, so the desk cannot double-book a minute.

Deletes that would take data with them are refused rather than cascaded: a department with doctors, a doctor with appointments, your own account, the last account.

### Notifications

Three emails, all queued, all routed through `App\Services\AppointmentNotifier` so the website, the front desk and the status buttons cannot drift apart on who gets told what:

| When | To | Mailable |
|---|---|---|
| A booking is created (site or desk) | the patient, if they gave an address | `AppointmentBooked` |
| A booking arrives from the website | `setting('appointment_email')`, falling back to `setting('email')` | `NewAppointmentAlert` |
| The desk confirms or cancels | the patient | `AppointmentStatusChanged` |

Deliberate omissions: the desk gets no alert for a booking it took itself, and `pending`/`completed` never email the patient — one is where a booking starts, the other is bookkeeping after a visit that already happened. Re-clicking a status the booking already has is a no-op, so nobody gets told twice.

**Appointments carry a `locale`.** A confirmation is sent days later by whichever staff member happens to click the button, quite possibly one working in the other language — without the stored locale a patient who booked in Bangla would be confirmed in English. It is validated against `available_locales` on the way out, same rule as `SetLocale`. The front-desk form asks for it directly, since a phone booking has no request locale to infer from.

**Carbon again.** `Mailable::withLocale()` moves the translator only, so `PresentsAppointment::alignCarbonLocale()` sets Carbon's locale inside `envelope()` and `content()`. Without it a Bangla email prints English month names mid-sentence. There is a test that renders under a deliberately mismatched Carbon locale.

Nothing in the notifier is allowed to throw: the booking is what matters, and a mail server having a bad afternoon must not turn a successful booking into a 500. Failures are logged and swallowed — which is also why a silent worker is worth checking for.

Email templates live in `resources/views/mail/`, with plain-text alternatives under `mail/text/`. They are table-based with inline styles on purpose — Outlook and most webmail strip `<style>` blocks and ignore flex and grid — so the design system does not apply there. Field labels come from `appointment.confirmed.*` rather than a mail-only set, so the email and the confirmation page cannot drift.

### Data model

`Department` 1—n `Doctor` 1—n `DoctorSchedule`; `Appointment` belongs to both `Doctor` and `Department` (denormalised at write time from the doctor). `Service`, `HealthPackage`, `Testimonial`, `Post`, `ContactMessage` are standalone.

Models use `$guarded = []` with a `casts()` method. Slug is the route key on every public-facing model. Scopes are consistent: `active()`, `ordered()`, plus `published()`/`latestFirst()` on `Post` and `search()` on `Doctor`.

`Setting` is a key/value store cached forever under `settings.all`, busted on save/delete. **Read it via the global `setting('key')` helper** (`app/Support/helpers.php`, registered in composer `autoload.files`). Its accessors are named `cachedMap()`/`config()` — deliberately *not* `all()`/`get()`, which `Model::__callStatic` forwards to the query builder.

Content lives in seeders, not migrations, and every seeder uses `updateOrCreate` keyed on slug, so re-running is safe and non-destructive.

## Localisation

**No user-facing string belongs in a template.** Everything renders through `__()` / `trans_choice()` against `lang/<locale>/<domain>.php`. Domains: `common`, `nav`, `home`, `departments`, `doctors`, `services`, `packages`, `posts`, `appointment`, `pages` (about/emergency/international/contact), `forms` (validation messages and attribute names, referenced from `app/Http/Requests/`), `mail` (notification emails — field labels are reused from `appointment.confirmed.*`, only email-specific copy lives here), `admin` (the staff panel — `admin.fields.*` doubles as the validation attribute names via `AdminFormRequest::attributes()`, so a label added there improves the error messages too).

Rule of thumb for where a key goes: used on more than one page → `common`; used on one page → that page's file.

`config('app.available_locales')` is the single source of truth for which locales exist — it drives the switcher, the `hreflang` tags, the route guard and the tests. Adding a locale means adding a key there and a directory under `lang/`.

`SetLocale` middleware (appended to the `web` group, so it runs after the session starts) resolves the locale as: session choice → `Accept-Language` → `config('app.locale')`. **Anything outside `available_locales` is discarded** at every step, so a tampered session value or header cannot point the translator at an arbitrary path — there is a test for this.

`lang/en` and `lang/bn` are both complete — 936 keys across 13 domains, full parity. Laravel still falls back per key, so a future English-only key renders English rather than a raw key rather than breaking the page.

**All Bangla needs native-speaker review before launch.** It was written without one.

Three tests keep the locales honest and will fail the moment a key is added to one locale only:

- every locale has a file per domain
- the locales define **exactly** the same key set, in both directions
- **every `:placeholder` survives translation** — a dropped `:count` or `:name` leaves a literal gap mid-sentence that no page-level test would catch

When adding a UI string, add it to *both* locales in the same change.

### Database content

Content is translated too, through a `translations` JSON column shaped as `{"<locale>": {"<column>": "<value>"}}` on departments, doctors, services, health_packages, testimonials, posts and settings. The fallback locale stays in the ordinary columns, so every pre-existing query still works untouched.

Models opt in via `use HasTranslations` plus `protected array $translatable = [...]`. Reads are **transparent** — `$doctor->name` returns Bangla under a Bangla request — because the trait overrides `getAttributeValue()`. Relations resolve through `getAttribute()` and never reach it, so eager loading is unaffected. `untranslated('name')` gets the stored value back when you need it.

Three traps this design has, all of which have bitten and now have regression tests:

1. **Partial column selects silently drop `translations`** and everything falls back to English with no error. `Department::…->get(['name','slug'])` broke the entire nav this way. Select whole rows for translatable models.
2. **`toArray()`/JSON serialisation bypasses the accessors** — it reads raw attributes. `AppointmentController@doctors` therefore maps its response by hand rather than serialising models.
3. **Settings cache per locale** (`settings.all.{locale}`), and `Setting::flushCache()` clears every locale. A single shared key would serve whichever locale warmed it first to everybody.

Deliberately **not** translated, and correct as-is: `slug` (route keys must stay stable so URLs don't fork per locale), doctor `qualifications` (MBBS, FCPS, MRCP are formal post-nominals that stay Latin in Bangla usage), phone/email/URL settings, and the numeric statistics. `stat_patients_yearly` *is* translated because Bangla groups by lakh (৪,০০,০০০) rather than by thousand.

Category slugs (`executive`, `health-tips`) are enum-ish, not content — they resolve through `category_label('packages', $slug)` against `lang/*/{packages,posts}.categories`, falling back to a title-cased slug for a category with no label yet.

`Doctor::search()` matches the base column **OR** the active locale's translation — deliberately not `COALESCE`, because a visitor browsing in Bangla still routinely types a consultant's name in English. `translatedColumn()` (COALESCE, for sorting) and `translationExpression()` (no fallback, for searching) are separate for that reason.

Ordering still sorts on the base column. At this data volume that is invisible; a `JSON_EXTRACT` in `ORDER BY` would cost the index for no real gain.

Bangla content lives in `database/seeders/Translations/`, keyed by slug and idempotent like the English seeders.

**Dates need two things set, not one.** Carbon keeps its own locale independently of the app locale, so `SetLocale` sets `Carbon::setLocale()` and `CarbonImmutable::setLocale()` as well — without that, month and weekday names stay English on an otherwise Bangla page. Always use `translatedFormat()` (never `format()`) where a month or weekday **name** is rendered; `format()` stays correct for machine formats like `H:i` and `Y-m-d`. Weekday labels in the chamber schedule come from `DoctorSchedule::dayLabel()`, not the `DAYS` constant — that constant is English-only and exists for seeding and internal reference. Dates in the Alpine booking component format via `document.documentElement.lang`.

## Design system

The look is the product. Deep navy (`navy-*`) + teal accent (`teal-*`) on near-white surfaces, generous whitespace, `rounded-[1.25rem]` cards, soft shadows.

**`urgent-*` (red) is reserved exclusively for emergency and ambulance affordances.** Do not use it for generic errors elsewhere in the UI or the emergency signal loses its meaning. The one documented exception is **destructive actions inside `/admin`** (`btn-danger`, the danger zone): the panel carries no emergency affordance for red to compete with, and a delete button is the one place staff genuinely need a stop colour.

Panel utilities (`admin-card`, `admin-nav-item`, `admin-th`, `badge-*`, `locale-tab`, `input-sm`) live at the bottom of `app.css` and follow the same `@utility` rule. Its Blade components are under `resources/views/components/admin/` — `translatable` (one field per locale, all following a single `tab` at form level), `image-field`, `toggle`, `select`, `section`, `list-header`, `translation-state`, `danger-zone`.

**Never pass null as the second argument to `@section`.** Blade reads a null there as "capture until `@endsection`" and swallows the rest of the page. `@section('meta_description', $model->summary)` did exactly that the moment the panel made it possible to save a row with no summary; the four public show pages now coalesce.

Tokens and component classes are defined in `resources/css/app.css`. Component classes (`btn`, `btn-primary`, `card`, `input`, `shell`, `section`, `eyebrow`, …) are declared with Tailwind 4's **`@utility`, not `@layer components`** — this is required, because `@layer components` classes cannot be `@apply`-ed by other classes in v4 and the build fails with "Cannot apply unknown utility class". Follow that pattern when adding new component classes, and define a class before anything that applies it.

Reusable Blade components are in `resources/views/components/`: `icon` (inline Lucide paths keyed by name — add new icons to the `$paths` array), `doctor-card`, `department-card`, `package-card`, `post-card`, `section-heading`, `page-hero`, `doctor-avatar`, `rating`, `article-body`.

Article bodies use a markdown-lite convention (`## heading`, `- bullet`, `**bold**`) rendered by `x-article-body`, which escapes first and re-introduces only bold via the `inline_markup()` helper. Do not render post bodies with raw `{!! !!}`.

Scroll reveals: add `class="reveal"` and an IntersectionObserver in `resources/js/app.js` adds `reveal-in`. Motion is disabled under `prefers-reduced-motion`.

## Conventions worth keeping

- Bangladeshi mobile validation is a shared regex in both form requests: `/^(?:\+?88)?01[3-9]\d{8}$/`.
- Money is stored as integer BDT (no minor units) and rendered `৳{{ number_format(...) }}`.
- Public POST routes are rate-limited (`throttle:10,1`); the admin login is throttled twice over — `throttle:10,1` on the route and five attempts per email+IP inside `LoginRequest`.
- `/admin` sends `noindex, nofollow` and is excluded from the sitemap-ish `hreflang` block, which only the public layout emits.
- Copy is written plainly and avoids marketing superlatives — claims in seeded content are specific and checkable (response times, staffing ratios) by design. Keep that voice.
- `.idea/` is untracked and stays that way.

## Not built yet

Patient portal, diagnostics test catalogue with pricing, and **SMS** — email notifications exist, but a Bangladeshi patient is far more likely to read an SMS, and phone is the only contact field the booking form requires. `AppointmentNotifier` is where a second channel would hook in.

There is also no reminder before the appointment: the emails fire on booking and on a status change, nothing is scheduled.

Localisation is complete — UI, database content and the panel itself, with tests asserting full coverage. What remains is **native-speaker review of all the Bangla**, which was written without one; that now includes `lang/*/admin.php`.

The panel has **one role**: everyone who can sign in can do everything. `UserController` and `AdminFormRequest::authorize()` are where a `role` column and a Gate would go.
