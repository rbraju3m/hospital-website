# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**RBR Hospital** — a public-facing hospital website built on **Laravel 13** with **Blade + Tailwind CSS 4 + Alpine.js**, backed by **MySQL 8**. Phase 1 (complete) is the public site, a working online appointment engine, and a full i18n layer. Phase 2 is complete: the **staff panel at `/admin`**, booking notifications by SMS and email, the **diagnostics price list**, and the **patient portal at `/portal`**. Every feature the site describes now exists behind it.

Phase 3 is the presentation layer: **Site controls** (what the public site shows, switched from the panel), **stand-in photography** for content nobody has uploaded a picture for, an image-led redesign of the public pages, and the **photo gallery** at `/gallery`.

Not to be confused with `/var/www/html/c-hospital-website`, a separate older Laravel 11 hospital site using the ftco Bootstrap theme. This project shares nothing with it.

## Timezone

`config('app.timezone')` is **`Asia/Dhaka`**, not Laravel's default UTC, and timestamps are stored in that zone.

This is load-bearing. Chamber hours, the 30-day booking window, the 60-minute same-day lead time, "today's appointments" on the dashboard and the day-before reminder are all reasoned about in local time. On UTC the application ran six hours behind the wall clock, so between 6pm and midnight Dhaka it offered a date that had already passed and showed the wrong day's list.

The trade is that datetimes are stored as Dhaka local rather than UTC — the simpler choice for a single-country site, and only safe to make because there was no production data. Changing it later would reinterpret every existing timestamp.

## Runtime versions — this matters

| Context | PHP | Why |
|---|---|---|
| CLI, Composer, artisan, tests | **8.3** (`php8.3`) | `vendor/` was installed under 8.3 |
| Apache (mod_php) | 8.4 | system-wide `php8.4.load`, not per-vhost |

Both satisfy Laravel 13's `^8.3`. Always invoke CLI work as `php8.3` so Composer post-scripts run on the same runtime that built `vendor/`.

**`pdo_sqlite` is not installed.** Laravel's default `:memory:` test database will not work — `phpunit.xml` is pointed at a dedicated MySQL schema (`hospital_site_test`) instead. Don't "fix" it back to SQLite.

## Common commands

```bash
# Serve (dev). The -d flags matter: the CLI runtime caps uploads at 2M/8M,
# which is well under what a phone photograph weighs.
php8.3 -d upload_max_filesize=32M -d post_max_size=64M artisan serve --host=127.0.0.1 --port=8321
npm run dev                       # Vite HMR alongside the above

# Assets — required after any CSS/JS/Blade class change if not running `npm run dev`
npm run build

# Database
php8.3 artisan migrate
php8.3 artisan migrate:fresh --seed        # rebuild + reseed (seeders are idempotent)
php8.3 artisan db:seed --class=DoctorSeeder

# Staff panel
php8.3 artisan admin:create                        # prompts for name / email / password
php8.3 artisan admin:create --name=… --email=… --password=… --role=front_desk
                                                   # --role: administrator (default), front_desk, editor
php8.3 artisan storage:link                        # required once, or uploads 404

# Reminders
php8.3 artisan appointments:remind --dry-run        # who would be reminded, sends nothing
php8.3 artisan appointments:remind                  # what the scheduler runs at 18:00
php8.3 artisan appointments:remind --date=2026-09-01 --force
php8.3 artisan schedule:list

# Queue — notifications are queued; without a worker they never send
php8.3 artisan queue:work                          # dev; see deploy/hospital-queue.service for prod
php8.3 artisan queue:work --stop-when-empty        # drain and exit
php8.3 artisan queue:failed                        # anything that gave up after 3 tries
php8.3 artisan queue:restart                       # after deploying code

# Tests (452 feature tests)
vendor/bin/phpunit
vendor/bin/phpunit --filter test_the_same_slot_cannot_be_booked_twice
vendor/bin/phpunit tests/Feature/AppointmentBookingTest.php
vendor/bin/phpunit tests/Feature/Admin           # the staff panel
vendor/bin/phpunit --filter AppointmentNotificationTest # the emails
vendor/bin/phpunit --filter 'Sms|Reminder'       # SMS and the day-before reminder
vendor/bin/phpunit tests/Feature/Portal          # the patient portal
vendor/bin/phpunit --filter PortalAppointmentChange # a patient moving or cancelling their own booking
vendor/bin/phpunit --filter Diagnostics          # the price list
vendor/bin/phpunit --filter LocalisationTest      # UI strings
vendor/bin/phpunit --filter ContentTranslationTest # database content
vendor/bin/phpunit --filter 'SiteFeature|SiteControl'  # the site's on/off switches
vendor/bin/phpunit --filter DemoImage             # stand-in photography
vendor/bin/phpunit --filter Gallery                # the photo gallery, public and panel
vendor/bin/phpunit --filter AdminListControl       # drag-to-reorder and the live switches
vendor/bin/phpunit --filter PublicPages            # every public page, and the editorial markup language
vendor/bin/phpunit --filter AdminFormPageTest      # every panel create/edit screen renders
vendor/bin/phpunit --filter PanelNavigation        # the panel's menu registry, its icon rail and the Ctrl+K palette
vendor/bin/phpunit --filter TranslationGaps        # the menu's count of untranslated content
vendor/bin/phpunit --filter PanelSearch            # finding a record from the palette
vendor/bin/phpunit --filter StaffRole              # the three roles, and what each one is refused
vendor/bin/phpunit --filter NotificationLog        # the record of what was sent to whom
vendor/bin/phpunit --filter HomeLayout             # the three home layouts and the slider
vendor/bin/phpunit --filter AdminSlide             # managing slides in the panel

# Composer — invoke through php8.3 so post-autoload scripts use the right runtime
php8.3 /usr/bin/composer require some/package
php8.3 /usr/bin/composer dump-autoload

php8.3 artisan route:list --except-vendor
php8.3 artisan view:clear && php8.3 artisan config:clear
```

## Deployment state — nothing is installed yet

The application is complete; the machine it runs on is not set up. Everything below is written and waiting, and **three of the five fail silently**, which is why they are listed together rather than discovered one at a time.

| What | Where | If it is missing |
|---|---|---|
| Apache vhost | `deploy/hospital.local.conf` | Site served by `artisan serve` only. DocumentRoot **must** be `public/` — pointing Apache at the project root produces a directory listing that exposes `.env`. |
| Queue worker | `deploy/hospital-queue.service` | **Silent.** Every email and SMS queues into `jobs` and never sends. Bookings still succeed and nothing errors. |
| Scheduler cron | `deploy/hospital-scheduler.cron` | **Silent.** The day-before reminder never runs at all. |
| SMTP credentials | `.env` `MAIL_*` | **Silent-ish.** `MAIL_MAILER=log` writes mail to `storage/logs/laravel.log` instead of sending it. |
| SMS gateway | `.env` `SMS_*` | **Silent-ish.** `SMS_DRIVER=log` does the same for text messages. |

The queue worker is the one that used to be invisible. It is not any more: `/admin/notifications` records every message when it is dispatched and marks it sent when the transport takes it, so a machine with no worker shows a growing list of messages stuck at **queued** and a band at the top of the page saying so.

Each deploy file carries its install commands in a header comment. They need `sudo`, which this environment does not have without a password, so the user runs them.

Also outstanding on the box, not in the repo: the seeded admin account still has the password it was created with, and the dev database holds a handful of fake bookings, patients and documents from testing.

If someone reports that "notifications stopped arriving", check the worker and the cron before anything in the code.

## Architecture

### Request flow

Public controllers live under `app/Http/Controllers/Web/` (thin — query, pass to view). Views are organised as `resources/views/pages/<area>/<action>.blade.php`, all extending `layouts/site.blade.php`.

The staff panel mirrors that: `app/Http/Controllers/Admin/`, views under `resources/views/admin/<area>/`, extending `admin/layouts/app.blade.php`. The patient portal mirrors it again under `Portal/` and `portal/`. Both sets of routes live in their own file — **`routes/admin.php`** and **`routes/portal.php`** — loaded by the `then:` callback in `bootstrap/app.php` inside the `web` group, so session, CSRF and `SetLocale` all apply and both are bilingual for the same reasons the site is.

`redirectGuestsTo()` branches on the path: a patient bounced to the staff login would be asking IT for an account that does not exist.

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

#### Three roles, cut along the lines the menu already draws

`users.role` holds `administrator`, `front_desk` or `editor`, and `App\Support\StaffRoles` says what each one reaches: the desk gets appointments, the inbox, portal accounts and patient documents; the editor gets the content; site controls, site settings and staff accounts are administrator-only. An editor has no business reading a patient's mobile number, and a receptionist has no business taking the public site down.

- **Administrator is a wildcard; everyone else is a list.** A section added next year is reachable by an administrator the moment it exists, and denied to the other two until somebody grants it deliberately. Forgetting to grant something is a support request; forgetting to deny it is a medical record in front of the wrong person.
- **The check is on the group, not per route.** `staff` middleware wraps the whole `/admin` group and reads the section off the *route name* (`admin.doctors.store` → `doctors`, with `site` aliased to `site_controls`), so a resource added without a thought for roles is closed rather than open. Compare `feature:<key>`, which is declared per route — that one is guarding a page from the public, and being wrong shows a visitor a page.
- **It answers 403, not 404.** The section exists and their colleague uses it every day; pretending otherwise would have them reporting a bug instead of asking for access.
- **The same question is asked in five places, all through `User::canReach()`**: the route middleware, `AdminFormRequest::authorize()`, the menu, the palette's list, and `PanelSearch`. A hole in any one makes the other four decoration — the palette is the one to watch, because an editor typing a mobile number into a box that sits on every screen would otherwise be handed the patient, their bookings and their documents.
- **`ListController` is the exception that proves the shape.** One route serves eight listings, so its check is made in the controller against the list it was given. `ManagedLists` names its lists the way `PanelNavigation` names its sections and `StaffRoles` names its grants — one vocabulary, and a test asserts the three line up.
- **The dashboard is assembled from the halves the role can see**, gated in the controller rather than only the template, so an editor's home page does not query every patient booked in today before deciding not to show them.
- **Three refusals, all about not locking the panel**: you cannot delete yourself, delete the last account, or delete the last administrator — and you cannot change *your own* role. That last looks like a nuisance and is not: an administrator who demotes themselves loses the staff screen in the same request, and the way back is a database client. The field is left off your own form, and the controller ignores it in the payload too.
- Existing accounts became administrators in the migration; `admin:create` still defaults to administrator, because the first account has nowhere else to create the second one from.

#### The menu is a registry, and it collapses

`App\Support\PanelNavigation` holds the fifteen items in four groups, and nothing else decides what is in the menu. The sidebar renders it; the quick-search palette will search it. Two lists would drift the moment somebody adds a section to one of them — and a link that exists but cannot be found is worse than one that does not exist at all.

- **An item's `key` is its label key.** `admin.nav.<key>`, and a group's heading is `admin.nav.group_<heading>`, so a section cannot ship with a label in one locale and a raw key in the other. `PanelNavigationTest` reads the registry and asserts the route exists, both locales name it, the icon is one the icon component actually draws, and the page answers — the icon check parses the component's own list rather than rendering it, because `x-icon` falls back to `activity` for a name it does not know and a typo would otherwise look like a plausible icon.
- **The badge counts are queried once.** The composer is bound to `admin.layouts.app` rather than to the sidebar: an `@include` inherits the parent view's data, so every partial that renders the menu shares one resolved copy.
- **The collapsed rail is CSS, and its state is settled before the first paint.** `panel-rail` on `<html>` is written by the inline head script from `localStorage`, for exactly the reason the sidebar's off-canvas state is plain CSS: Alpine boots after the first paint, and a menu that starts 18rem wide and snaps to 4.5rem is worse than one that never collapses. The rules live in one block (`admin-sidebar` in `app.css`) and are confined to `lg` — below it the sidebar is the drawer, where a strip of unlabelled icons would be a menu nobody can read on the device with the least room to guess.
- **A collapsed label is `sr-only`, not `display: none`.** It stays in the link's accessible name and is drawn as a tooltip on hover. That tooltip is **one fixed node in the layout**, not something rendered inside the link: the nav scrolls, and an overflow container clips its own children the moment they reach past 4.5rem.
- **The watchdog un-rails.** The 1.5s check that drops `has-js` when the bundle never reports in drops `panel-rail` too — the tooltips are the bundle's job, and without them a collapsed menu is fifteen unlabelled icons.

**Ctrl+K opens the palette** (⌘K on a Mac — the modifier is rendered as `Ctrl` and corrected by `app.js`, because the server cannot know what keyboard is in front of it). It searches the same registry: every section, plus the "add one" screen for the eleven sections that have one.

- **A `create` route's name is also its label key.** `admin.doctors.create` names the screen *and* the words already on the button that opens it — "Add doctor", "Write article", "Book for a patient". No second set of strings to write or translate, and `PanelNavigationTest` asserts both halves so a row cannot ship reading `admin.doctors.create`.
- **The hotkey yields to the markup editor.** Ctrl/Cmd+K is already "insert link" in the prose editor, which `preventDefault`s from a handler on the textarea — so it has run by the time the palette's window handler does, and the palette checks `event.defaultPrevented` rather than special-casing the editor. Widen one and the other still holds.
- **Rows are real links.** Middle-click opens a section in a tab, like anything else on the page; Enter follows the highlighted one.

**Untranslated content is counted on the menu.** `App\Support\TranslationGaps` badges a section in amber when it holds rows nobody has translated. The tests prove the locale *files* are complete; nothing proves the same of the content, and a missing translation falls back rather than breaking, so the only way anybody finds one is by being told.

- **It cannot be a query, so it is cached.** `missingTranslations()` skips a field blank in the source too, and `JSON_EXTRACT` cannot tell that apart from a real gap — the same reason `?untranslated=` runs in PHP. Counting it means walking whole tables, so it is cached forever and dropped by `HasTranslations` on save and delete: in the model, not the controllers, because a seeder or a console command can leave the number just as wrong.
- **Cached per section, read in one call.** Saving a doctor invalidates the doctors count and nothing else — one key for all eight would charge the next page load with a full recount after every save in the panel, and the recount cannot go to the queue because this box has no worker running (the badge would never come back). `Cache::many()` fetches the eight in one round trip, because the cache store here is the database and the menu renders on every page.
- **Amber, and never on the same row as the teal one.** Work waiting on the desk and a sentence nobody has written yet are different news; collapsed to the rail there is only room for one dot, so the sidebar renders the second with an `elseif` — and a test asserts no item can declare both. The figure is `text-amber-950` rather than `text-navy-950`: the navy ramp inverts in dark mode, and a light number on a light chip reads in one theme only.

**The account block sits at the foot of the menu**, and is the only copy: it replaced the topbar dropdown rather than joining it, because the same two links twice on one screen is a second thing to keep in step rather than a convenience. It opens upwards, is wider than the collapsed rail (nothing in the sidebar clips it), and collapsed it is an avatar — so the person is named by the button's `aria-label` and the rail tooltip, where the name itself is off the screen.
- **Matching is words, not fuzz.** Every word typed has to appear in the row, ranked by whether the label starts with the first word or merely contains it. Staff type the name of the thing they want, and a fuzzy matcher's second guess arriving first is worse than no second guess.

**Past two characters it also searches the records.** `App\Support\PanelSearch` behind `admin.search` answers a doctor by name, a booking by reference, a patient by mobile — twelve sections, four rows each, ~35ms against the dev data.

- **A whitelist, like `ManagedLists`, not a lookup.** The term arrives from the browser; the model, the columns it may match and the screen it opens are declared in the class. "Search anything" is otherwise a way to read a column nobody meant to publish.
- **The menu never waits for the network.** Local matches render first and records arrive underneath, so typing `doct` and pressing Enter reaches the doctors screen at the same speed whether or not the endpoint is having a slow afternoon.
- **Stale answers are dropped twice** — the in-flight request is aborted, and a response whose term no longer matches the box is discarded. Without the second check a fast typist who backspaces can be shown results for a word they already deleted.
- **`%` and `_` are escaped.** They are LIKE wildcards, so a term containing one would quietly match far more than was asked for — `%%` would return the four most recent rows of every table.
- **Records match the base column or the active locale's translation**, the same rule as `Doctor::search()` and for the same reason. The source text lives in the ordinary columns, so English keeps working in the Bangla panel.
- Capped at four rows per section: a palette is a way to reach one record, not a report. Somebody who wants all of them wants the listing and its filters.

Chamber hours are edited on the doctor's page as their own little forms (`DoctorScheduleController`), because HTML forbids nesting a form inside another. Overlapping windows on the same weekday are rejected: two windows over the same minutes would generate a slot twice, and the unique index would then bounce the second booking with nothing a patient could act on.

Front-desk bookings are deliberately **laxer than the public form** — no 30-day window, no 60-minute lead time, any time accepted rather than only the published grid. Those constraints exist to protect an unattended web form; staff can see the consultant's actual day. The unique index still applies, so the desk cannot double-book a minute.

Deletes that would take data with them are refused rather than cascaded: a department with doctors, a doctor with appointments, your own account, the last account.

### The patient portal

`/portal`, on its own guard (`patient`) against its own table. **Two guards rather than one table with a role column**: a mistake in one login path then cannot become a way into the other, and nothing a patient does can reach `/admin`. The admin routes say `auth:web` rather than bare `auth` so the guard is stated instead of inherited from config — a test signs in as a patient and confirms the panel still refuses them.

**The mobile number is the identity**, as the service page always said. It is stored on `patients` in the national ten-digit form (`1712345678`) so lookups are exact, and `Patient::appointments()` is a query rather than a relation because appointments keep the number exactly as it was typed and `Rules::BD_MOBILE` allows three spellings of it. `PhoneNumber::variants()` enumerates them; widen the regex and widen that.

Sign-in is a password, chosen over a one-time code. The gap that leaves is that email is optional here, so **recovery is a six-digit code by SMS** (`PasswordResetCodes`) — hashed at rest, single use, ten minutes, five wrong guesses and it burns. Asking for a code answers identically whether or not the number has an account.

#### Changing a booking from the portal

A patient may move or cancel an upcoming booking themselves, behind
`behaviour_portal_changes`. Off, the portal goes back to showing records and
pointing at the desk — and the routes close with the buttons, so a bookmarked
reschedule page is not a way round the decision.

- **The same rules as the public form.** The published grid, the 30-day window,
  the 60-minute lead, the availability re-check at submit time and the unique
  index behind it. A slot reachable from here is one they could have booked in
  the first place, so nothing about a consultant's day arrives through a door
  the booking rules do not watch.
- **A move goes back to `pending`.** The desk agreed to a time, and this is not
  that time; somebody there has to look at the new one.
- **Same doctor only.** Moving to a different consultant is a new booking, not
  a reschedule, and the desk should hear about it as one.
- **The desk is told; the patient is not.** They are the one who just did it,
  and the portal has already said so on the screen in front of them — the
  mirror image of the desk getting no alert for a booking it took itself.
- **`cancelled_by` and `rescheduled_at` say who did what**, and the panel shows
  it on the booking. `status` alone says a booking was cancelled and not by
  whom, and the desk's next move differs: a slot the patient gave up is one to
  offer somebody else, a slot the desk cancelled is a patient somebody may
  still need to ring.
- **Somebody else's booking answers 404, not 403.** A reference is short enough
  to guess at, and "wrong but real" is worth more to somebody guessing than
  "not found". Ownership is the phone number in every spelling it is accepted
  in, the same rule `Patient::appointments()` reads by.
- The reschedule page renders the consultant's chamber days server-side and
  fetches only one day's times, so a patient with no JavaScript still sees
  which days exist.

#### Patient documents

Reports, prescriptions and bills, published by staff from the panel and keyed by **mobile number rather than patient id** — a lab report exists before the patient gets round to registering, and should be waiting rather than needing re-attaching.

Files live on the **private disk** (`storage/app/private`), never the public one, and are streamed by a controller that checks who is asking. This is the part to be careful with: a guessable URL to somebody's biopsy result is not a mistake that can be walked back. Stored names are random, uploads are restricted to PDF/JPG/PNG, and replacing or deleting a document removes the old file — an orphan on that disk is still a medical record sitting on a server.

#### Signed confirmation links

`appointment.confirmed` carries a patient's name, phone, age and gender, and a booking reference is short enough to enumerate. The route now requires a **valid signature**; the link in the confirmation email is generated with `URL::signedRoute()` so it keeps working, and a guessed one gets a 403. Note that route-model binding runs before the signature check, so a made-up reference 404s rather than 403s.

### The home page has three layouts

`resources/views/pages/home/` holds `classic`, `slider` and `compact`; `App\Support\HomeLayouts` says which is on air, and the panel picks it at the top of Site controls. `HomeController` renders `HomeLayouts::view()`.

- **Each layout has its own hero.** `compact` shares nothing above the fold with `classic` — a band rather than a stage, a fifth of the height, no photograph and no booking form. It first shipped reordering the bands under the same hero, which was too quiet to read as a different layout at all; a test now asserts the three differ above the fold as well as in their order.
- **A layout decides the order and the top of the page, never what a band says.** All twelve bands live in `pages/home/bands/` and are `@include`d by all three, so a change to the doctors strip lands on whichever layout is showing. `classic` is the page exactly as it was before the split.
- **The registry is the source of truth, same rule as `SiteFeatures`.** A `home_layout` setting naming a layout that has been removed — or hand-edited into the table — falls back to `classic` rather than blanking the one page on the site that always has to render. `SiteControlController` also refuses to *write* a value the registry does not know, so the fallback is a safety net rather than a daily occurrence.
- **Every band still answers to its own switch** in whichever layout is showing, and no layout is a subset — a test asserts all three render every band.
- **`slider` falls back to the classic hero when there are no slides.** Somebody switching the layout before writing the slides is a Tuesday, and an empty band at the top of the home page is worse than the hero they had.

#### The slider

`/admin/slides`. Ordinary translated content — drag to reorder, switch a row live, per-locale words — with an eyebrow, a headline, a sentence and at most two buttons.

- **Two buttons, and only the ones with an address.** A slide is read in about four seconds: one idea, one thing to do about it, and a quieter alternative. A label with no URL is not rendered.
- **Button links go through `Rules::LINK`** — http(s), `mailto:`, `tel:`, root-relative, anchor. The same allowlist the markup editor applies, and for the same reason: `javascript:` in a field a staff member fills in runs in a visitor's browser.
- **Slides cross-fade rather than slide.** At full width a strip moving sideways drags the headline out from under the reader's eye; a fade reads as a change of subject.
- **One `h1` on the page**, on the slide that is showing when it loads; the rest are paragraphs wearing the same size. They are alternates of one heading, not sections of the document, and three `h1`s would say the page is about three things.
- **Content before animation.** Every slide is in the markup and the first is visible before `app.js` runs — `html:not(.js-ready) .hero-slide[data-slide-hidden]` takes the rest out, so a page with no JavaScript is one static hero rather than a column of stacked panels.
- **Autoplay defers to the visitor**: it stops on hover, on focus, while the tab is hidden, and entirely under `prefers-reduced-motion` or the Site controls motion switch — the controls keep working, so reduced motion means no motion rather than no slider.
- **`reducedMotion()` is declared at the top of `app.js` now.** Alpine starts components synchronously, and a `const` further down the module is still in its temporal dead zone when `heroSlider.init()` runs.
- **The listing says when slides are not on the site.** They only reach a visitor while the slider layout is chosen, and staff would otherwise spend an afternoon wondering why.
- Slides seed with no image and fall back to stand-in hero photography, so the layout is a working slider the moment it is picked.

### Site controls — the switches behind the public site

`/admin/site-controls`. Every switch is a `settings` row in the **`features`** group holding `"1"` or `"0"`, read through `feature('key')` (`app/Support/helpers.php`) and declared in **`App\Support\SiteFeatures`**. Four groups: whole *areas* of the site, *home* page bands, header/footer *chrome*, and visitor *behaviour* (booking, forms, payment, stand-in images, motion, maintenance).

Three decisions worth keeping:

- **The registry is the source of truth, not the database.** A key with no row falls back to its declared default, so a fresh database — or a key added after the seeder last ran — behaves as "on" rather than silently blanking a section. `SiteFeatureSeeder` writes the rows with `firstOrCreate`, so re-seeding never re-enables something staff switched off.
- **Hiding a link and closing its route are one action.** `feature:<key>` (`EnsureFeatureEnabled`, aliased in `bootstrap/app.php`) is on the public route groups in `routes/web.php` and `routes/portal.php`; a switched-off area answers **404**. Hiding the navigation alone would leave the page reachable from a bookmark, a search result or a confirmation email sent last month. Staff signed in on the `web` guard pass through, so a page can be checked before it goes back on air.
- **The form writes every key, not the ticked ones.** An unchecked checkbox posts nothing, so `SiteFeatures::store()` walks the registry rather than the payload — otherwise switching something off would be a no-op. It also means a crafted payload cannot create a setting that is not on the page.

`behaviour_maintenance` is the one inverted switch (default off). `MaintenanceGate` wraps only the public routes: the panel, the portal and the payment callbacks stay up, because the work that takes the site down is usually being done in the panel. It answers 503 with a `Retry-After` and a notice carrying the hotline and ambulance numbers — the one thing a visitor must never lose.

`behaviour_animations` renders `no-motion` on `<html>`, which shares the `prefers-reduced-motion` rules in `app.css` and is read live by `reducedMotion()` in `app.js`. A visitor whose device asks for less motion gets it regardless of the switch.

Templates guard with `feature()` directly. Anywhere a list of links is built (header, footer, mobile bar, home quick actions) it is **filtered** rather than conditionally rendered per item, and the layout counts what survived — five tiles across six columns leaves a hole.

### Stand-in photography

The public site is image-led — a consultant card, a department header and an article cover all read as broken without a picture — but a hospital rarely has its own photography ready on day one. `App\Support\DemoImages` fills every empty image slot from `public/images/demo/` (8 clinical portraits, 22 ward/theatre/equipment covers, 3 wide banners).

Call sites go through **`image_url($path, $set, $seed, $group)`** (upload first, then stand-in, then nothing) or **`doctor_photo($doctor)`** for consultants. Both return `null` when `behaviour_demo_images` is off, which is what lets each template fall back to its icon or initials treatment without a second condition.

- **Picks are stable and non-repeating.** A *numeric* seed (a row id) walks the set one image at a time; hashing a slug scatters, and scattering means the same face twice in one row of four. `$group` offsets the walk per content type so a department and an article sharing an id do not share a photograph.
- **Portraits follow `doctors.gender`.** The pools in `PORTRAIT_POOLS` never overlap: a consultant is never shown a photograph of somebody of the other gender, which is the difference between a placeholder and a mistake.
- **Testimonials are the deliberate exception.** They get an initials tile unless somebody uploads a real photograph — a face that is not theirs, against their name and their words, is a different claim from a stock ward behind a department heading.
- The counts in `SETS` are **declared, not globbed**, so rendering never touches the filesystem — and a test asserts every declared file exists, because a deleted one would otherwise only surface as a broken image.
- `x-admin.image-field` previews what the site will actually render and labels it `Stand-in` when it is not an upload, so "no picture" and "a picture nobody chose" are distinguishable in the panel.

### The photo gallery

`/gallery` lists **albums**; `/gallery/<slug>` shows one album's photographs in a grid that opens a lightbox. Two tables: `gallery_albums` (translatable title, summary, description, a cover) and `gallery_photos` (a file, a translatable caption, a sort order), the photos cascading with their album.

- **A photograph's file is nullable, and that is deliberate.** An empty `path` renders stand-in imagery through `GalleryPhoto::url()`, which is what lets the seeded albums ship as a working gallery before anybody has uploaded anything. The panel still requires a file when a photo is added by hand — only the seeder creates rows without one. With `behaviour_demo_images` off, a photo with no upload has nothing to show, so the album page **drops** it rather than rendering an empty frame; an album where every photo drops out reads as empty.
- **One list drives the grid, the viewer and the thumbnails.** `$slides` is built once in the view, so a tile and the slide it opens cannot drift apart after a filter.
- **The viewer is `Alpine.data('galleryLightbox')` in `resources/js/app.js`, not an inline script.** Arrow keys, Home/End, Escape, `F` for fullscreen, swipe, click-to-zoom around the point clicked, a thumbnail strip, neighbour preloading, and focus handed back to the tile it opened from. Tiles are real `<a href>` links to the file, so the grid still opens photographs with the viewer unavailable.
- **The album's photo screen has no Save button.** It is a media manager: files upload as they are dropped, a caption saves as it is typed (debounced), an order saves as it is dragged, the cover and a deletion save on the click. Every action is one small JSON write on `GalleryPhotoController`, and the grid is rendered from an array so a photograph appears the moment its upload finishes rather than after a reload.
- **Files go up one request each.** A batch large enough to pass `post_max_size` arrives with its body discarded, CSRF token included, and reads as an expired page. One at a time makes that impossible and buys a per-picture progress bar for free.
- **Endpoint URLs carry an `__ID__` placeholder** the browser swaps per tile — one route generated once. Generating it with a literal `0` looks identical in the markup and sends every write to photograph zero.
- **The cover holds a copy of a photograph's path.** Deleting that photograph clears the album's `image`, or the row points at a file that is no longer there. Promoting a photograph to cover, on the other hand, **does not delete the cover the album had before**: that is a file somebody deliberately uploaded, and one click on a star must not be able to destroy it. Replacing or clearing a cover is what the image field on the album form is for, and that does delete. Only an uploaded file can become a cover — a stand-in has no path, and writing one would freeze today's placeholder into the row.
- **`GalleryPhoto` has no `is_active`.** A photograph has no URL of its own to leave dangling, so hiding one and deleting one are the same act.
- **`GalleryPhoto::recent()` is shared** by the home band and the About strip, so the two cannot disagree about what "recent" means.
- Photographs are managed on the album's own page — their own little forms, for the same reason chamber hours are. Captions there are written out per locale by hand rather than through `x-admin.translatable`: that component reads `old()`, and every card on the page posts the same field names, so one rejected caption would repopulate all of them.
- Uploads take **many files at once** (`photos[]`, capped per submission) and continue the existing sort order rather than restarting at zero.
- Deleting an album deletes its photographs' **files** in the controller. The rows cascade; the files would not, and an orphan on the public disk is invisible from the panel forever after.

### Writing prose in the panel

`x-admin.translatable type="richtext"` puts a toolbar over the textarea — bold, italic, link, heading, subheading, bullet and numbered lists, quote, divider, and a preview that renders the text the way the site will. Ctrl/Cmd+B, +I and +K are wired to the first three. It is **not** a WYSIWYG, and that is the point: the public site renders a deliberately small markup language through `x-article-body`, which escapes everything first and re-introduces only what it recognises. Storing HTML instead would mean trusting whatever an editor pasted, on pages a patient reads.

`renderMarkupLite()` in `app.js` mirrors `article-body.blade.php` block for block, and `inlineMarkup()` mirrors `inline_markup()`. If one changes, the other changes with it — a preview that lies is worse than no preview.

Three details in the editor that are there for a reason:

- **Buttons carry `@mousedown.prevent`**, so pressing one never moves the caret out of the text it is about to act on.
- **Edits go through `document.execCommand('insertText')`** rather than assigning to `value`. Assigning is simpler and throws away the browser's undo stack, which is exactly the kind of thing that makes an editor feel broken.
- **Links take a scheme allowlist** (`http(s)`, `mailto:`, `tel:`, root-relative, anchor) and one level of nested parentheses in the address — `[x](javascript:alert(1))` would otherwise end the match early, leave a stray bracket in the sentence, and only be *half* rejected.

Applied to department, service and package descriptions, doctor `about`, and post `body`. **Those first four used to split on newlines and print `##` and `-` literally** while the panel's help text promised they worked, so they now render through `x-article-body` too.

### Every panel listing reorders and publishes in place

Two endpoints, both JSON, both driven from the table: `POST admin/lists/{list}/order` and `PATCH admin/lists/{list}/{id}/toggle`. Drag a row by its handle to set `sort_order` — which is the order the public site renders — or flip the switch in the row to show or hide the record without opening it.

- **`App\Support\ManagedLists` is a whitelist, not a lookup.** The list name arrives from the browser; "any model, any column" is how a listing endpoint becomes a way to flip a row on the users table. `posts` is deliberately listed as **not** sortable: articles are ordered by publication date, and a hand-sorted news list would be a second, contradictory answer to what comes first.
- **Reordering renumbers from the block's own lowest position**, not from 1 — the listings are paginated, and page two starting again at 1 would shuffle it in among page one.
- **The row is only draggable while the handle is held.** A permanently draggable row swallows text selection and turns every link in it into a drag.
- Both are additive: `sort_order` and the visibility toggle are still on the edit form, so a browser that cannot do this loses a convenience, not a capability.
- The listings show **Live** rather than "Published", and translation state is a **compact** chip beside the switch — it only appears when a locale is actually missing something. A chip per language on every row was a column's worth of noise saying "fine, fine, fine"; the `?untranslated=<locale>` filter is still the way to find gaps deliberately.

### Uploads fail at PHP's limits, not the application's

`MediaLibrary::MAX_KILOBYTES` is what the application would like; `MediaLibrary::maxKilobytes()` is what this machine will actually accept, and validation and every help string use the second one. This matters because PHP rejects an oversized file *before* Laravel sees it — the file simply does not arrive and the form comes back saying the field is required.

Worse, a batch over `post_max_size` reaches PHP with its body discarded, CSRF token included, which surfaces as **"page expired"** rather than as anything to do with photographs. `PostTooLargeException` is therefore rendered back to the form as a sentence naming the real limit.

**On this box the CLI runtime is the tight one:** `php8.3` has `upload_max_filesize = 2M` and `post_max_size = 8M`, while Apache's `php.ini` is set to 5G. So uploads that work through Apache fail under `artisan serve`. Start the dev server with `-d upload_max_filesize=32M -d post_max_size=64M` (the command above) rather than editing `/etc/php/8.3/cli/php.ini`, which needs sudo.

**Pictures are shrunk in the browser before they are sent** (`initUploadShrinking` in `resources/js/app.js`, switched on per field with `data-compress`). A phone photograph is 4–8 MB and a screenshot is often a 1.5 MB PNG; resized to a 2400px edge and re-encoded as JPEG they land at a couple of hundred kilobytes, which is both an upload that fits and a page that loads. Three things in there are load-bearing:

- **The original is kept whenever this cannot improve on it** — an image that is already smaller, a GIF or SVG (neither survives a canvas), or a browser without `createImageBitmap`/`DataTransfer`.
- **Transparency is filled white first.** JPEG has no alpha, and without the fill a transparent PNG comes out black.
- **A form submitted mid-shrink is held and released**, or it would send exactly the originals this exists to avoid.

`max_file_uploads` bounds a batch; total weight is PHP's own business. Dividing `post_max_size` by the per-file ceiling was the obvious thing to do and was wrong — it prices every picture at the worst case, so three 200 KB photographs were refused against an 8 MB budget.

### The primary navigation is one line

The header menu must never wrap, at any width where it is visible. Two things enforce that and both matter:

- **The number of items that can reach the bar is capped in the template** (`partials/header.blade.php`). Departments, four primary links and a **More** overflow menu — six triggers. Everything else lives one click away rather than on a second row. Filtering by Site controls removes items; it never adds them.
- **Nothing in the bar can grow.** `nav-link` is `whitespace-nowrap` with padding that tightens below `xl`; the logo's tagline and the "Find a doctor" label only appear at `2xl`, because they are the widest parts of the two blocks flanking the nav. Below `lg` the bar becomes the drawer.

Adding a link means putting it in `$more`, not in `$primary`. The gallery is the worked example: it sits in the overflow menu and the footer, never in the bar.

### Notifications

Two channels, both queued, both routed through `App\Services\AppointmentNotifier` so the website, the front desk and the status buttons cannot drift apart on who gets told what:

| When | Email | SMS |
|---|---|---|
| A booking is created (site or desk) | the patient, if they gave an address | the patient — always |
| A booking arrives from the website | `setting('appointment_email')` → `setting('email')` | `setting('desk_sms_number')`, if it is a mobile |
| The desk confirms or cancels | the patient | the patient |
| 6pm the day before | the patient | the patient |

**The two channels are not equivalent.** Email is optional on the booking form; phone is required. SMS is therefore the only channel that reaches every patient, and the one that matters if only one works.

Deliberate omissions: the desk gets no alert for a booking it took itself, and `pending`/`completed` never email the patient — one is where a booking starts, the other is bookkeeping after a visit that already happened. Re-clicking a status the booking already has is a no-op, so nobody gets told twice.

**Appointments carry a `locale`.** A confirmation is sent days later by whichever staff member happens to click the button, quite possibly one working in the other language — without the stored locale a patient who booked in Bangla would be confirmed in English. It is validated against `available_locales` on the way out, same rule as `SetLocale`. The front-desk form asks for it directly, since a phone booking has no request locale to infer from.

**Carbon again.** `Mailable::withLocale()` moves the translator only, so `PresentsAppointment::alignCarbonLocale()` sets Carbon's locale inside `envelope()` and `content()`. Without it a Bangla email prints English month names mid-sentence. There is a test that renders under a deliberately mismatched Carbon locale.

Nothing in the notifier is allowed to throw: the booking is what matters, and a mail server or a gateway having a bad afternoon must not turn a successful booking into a 500. Failures are logged and swallowed — which is also why a silent worker is worth checking for.

#### SMS

`config/sms.php` picks a driver: `log` (the default — writes to the log, so a checkout with no credentials still exercises the whole path), `discard`, or `http`.

The `http` driver is deliberately generic. Most Bangladeshi gateways (Alpha SMS, BulkSMSBD, MIMSMS, Elitbuzz, Reve) are one GET or POST carrying an API key, a number and the text, so `SMS_PARAMS="api_key=:key,to=:to,msg=:text"` names whatever this provider calls its parameters and switching provider is an `.env` change. `SMS_SUCCESS` matters more than it looks: local gateways routinely answer **200 OK with the failure in the body**, so a status code alone proves nothing.

The driver is named **`discard`, not `null`** — dotenv reads the literal string `"null"` as PHP `null`, so `SMS_DRIVER=null` would resolve to no driver at all.

`PhoneNumber` normalises to `8801712345678`. Note the two ways of writing the same number: the country code is 880 with a ten-digit subscriber number, while the form's validation regex reads it as `88` plus the national `01712345678`. Both give the same digits. `isMobile()` rejects the hospital's own published lines — they are 96xx corporate numbers that look valid and cannot receive an SMS, and trying would fail once per booking forever.

**Message text is rendered when the job is queued, not when it is sent.** The payload therefore carries a finished string, so a message cannot come out in the wrong language because the worker was in a different locale, and it survives a template being edited in between.

#### The notification log

`/admin/notifications`, and `App\Models\NotificationLog` behind it. Every email and SMS is written down when it is dispatched and updated when the transport actually took it, because until this existed the answer to "was the patient told?" was `reminded_at` and a hope.

- **`queued` vs `sent` is the whole point.** A row is written at dispatch; `sent` means the gateway or the mail server accepted it. On a box whose queue worker was never started, *every* row stays `queued` for ever — which is the silent failure in the deployment table above, finally visible. The listing bands a warning when anything has been waiting more than half an hour.
- **`sent` is not delivery.** No gateway here reports back, and the screen says so rather than implying a tick means a phone.
- **Correlation is a header for mail and a constructor argument for SMS.** `SendSms` carries the row id and marks it sent — or `failed`, from the job's `failed()` hook, which is the only place in the application that can say an SMS definitively did not go. A queued mailable has nothing to hold a reference on, so `RecordsDelivery` puts the id in `X-Notification-Log` and `RecordMailDelivery` reads it back off `MessageSent`. Matching on address and subject instead would merge two reminders sent to one patient on one evening.
- **A mailable that gives up stays `queued`.** Laravel's own job wraps it, and there is nothing to correlate a failure against; that is a known edge rather than a claim of completeness.
- **The SMS body is stored verbatim; an email's is not.** One is the record of what was said and is 160 characters at worst; the other is a page of HTML nobody would read in a listing, so only the subject is kept — read off the finished message, in the locale it was rendered in.
- **The portal's reset code is logged without the code.** Staff read this screen, and a six-digit code sitting in a listing is a way into somebody's records. The type says what it was; `body` stays null.
- **Logging is not allowed to cost a booking.** `NotificationLog::queued()` swallows and reports its own failures, for the same reason the notifier does — there is a test that books an appointment with the table dropped.
- **Ninety days, pruned at 3am** (`Prunable` + `model:prune` in `routes/console.php`). Long enough to answer "was I told?" about a visit that has happened, short enough that a row per message ever sent does not become the largest table here.
- Read-only, and no resend button: a record you can edit is not a record, and a second confirmation lands on a patient who already has one. The desk can re-trigger one by moving the status.
- Front desk and administrators only — it is a list of patients' numbers and what they were told.

#### The day-before reminder

`appointments:remind`, scheduled daily at 18:00 (Dhaka). Evening rather than the small hours on purpose: an overnight SMS is read in the morning at best, and a patient who cannot make it still has the evening to call the desk.

**Confirmed appointments only.** A booking still at `pending` is one the desk has not agreed to, and telling a patient to come tomorrow for a slot nobody secured is worse than saying nothing — the command counts those and reports them instead, where the desk can act.

`reminded_at` on the appointment makes a second run a no-op, because cron double-fires and failed runs get repeated by hand. `--force` overrides it for the morning after a gateway outage; `--dry-run` lists who would be reminded and marks nothing; `--date=` targets a specific day.

A booking made after 6pm for the next day gets no reminder. They just booked; they know.

Keep `lang/*/sms.php` short. Operators bill per segment: 160 characters in Latin, but **70 in Bangla**, because a single Bangla character forces the whole message into UCS-2. Today every English template is one segment and every Bangla one is two, and `config('sms.segment_warning')` is set to 2 so drifting past that has to be a decision.

**Watch the punctuation, not just the length.** The GSM alphabet is small: one character outside it — an em dash, a curly quote, an ellipsis — switches the whole message to UCS-2 and costs three segments where it cost one. An em dash in the English reminder did exactly that, which is why the test asserts the fallback-locale templates stay GSM-7 as well as short.

Email templates live in `resources/views/mail/`, with plain-text alternatives under `mail/text/`. They are table-based with inline styles on purpose — Outlook and most webmail strip `<style>` blocks and ignore flex and grid — so the design system does not apply there. Field labels come from `appointment.confirmed.*` rather than a mail-only set, so the email and the confirmation page cannot drift.

### Data model

`Department` 1—n `Doctor` 1—n `DoctorSchedule`; `Appointment` belongs to both `Doctor` and `Department` (denormalised at write time from the doctor). `Service`, `HealthPackage`, `Testimonial`, `Post`, `DiagnosticTest`, `ContactMessage` are standalone.

**`DiagnosticTest` is deliberately unrelated to `HealthPackage`**, whose `tests` column stays a free-text JSON list. The package copy reads as prose ("Complete blood count & ESR") and mapping it onto catalogue rows is a judgement call per package rather than a migration. Joining them later is a pivot table plus a content pass, not a schema problem.

A "request this test" form writes a `ContactMessage` rather than anything of its own: it is an enquiry, not a booking. Nothing is scheduled and nothing is charged, so the desk needs to read it, not query it — and it lands in the inbox they already watch. Like the appointment desk alert, it is written in the fallback locale because it is staff-facing, and it carries the base test name because that is what the counter and the report call it.

Models use `$guarded = []` with a `casts()` method. Slug is the route key on every public-facing model. Scopes are consistent: `active()`, `ordered()`, plus `published()`/`latestFirst()` on `Post` and `search()` on `Doctor`.

`Setting` is a key/value store cached forever under `settings.all`, busted on save/delete. **Read it via the global `setting('key')` helper** (`app/Support/helpers.php`, registered in composer `autoload.files`). Its accessors are named `cachedMap()`/`config()` — deliberately *not* `all()`/`get()`, which `Model::__callStatic` forwards to the query builder.

Content lives in seeders, not migrations, and every seeder uses `updateOrCreate` keyed on slug, so re-running is safe and non-destructive.

## Localisation

**No user-facing string belongs in a template.** Everything renders through `__()` / `trans_choice()` against `lang/<locale>/<domain>.php`. Domains: `common`, `nav`, `home`, `departments`, `doctors`, `services`, `packages`, `posts`, `gallery` (the photo gallery), `appointment`, `pages` (about/emergency/international/contact), `forms` (validation messages and attribute names, referenced from `app/Http/Requests/`), `mail` (notification emails — field labels are reused from `appointment.confirmed.*`, only email-specific copy lives here), `sms` (six one-line templates; see the segment note above before lengthening one), `diagnostics` (the price list), `portal` (the patient portal — patient-facing wording, so it has its own appointment status labels rather than reusing the panel's), `admin` (the staff panel — `admin.fields.*` doubles as the validation attribute names via `AdminFormRequest::attributes()`, so a label added there improves the error messages too).

Rule of thumb for where a key goes: used on more than one page → `common`; used on one page → that page's file.

`config('app.available_locales')` is the single source of truth for which locales exist — it drives the switcher, the `hreflang` tags, the route guard and the tests. Adding a locale means adding a key there and a directory under `lang/`.

`SetLocale` middleware (appended to the `web` group, so it runs after the session starts) resolves the locale as: session choice → `Accept-Language` → `config('app.locale')`. **Anything outside `available_locales` is discarded** at every step, so a tampered session value or header cannot point the translator at an arbitrary path — there is a test for this.

`lang/en` and `lang/bn` are both complete — 1,377 keys across 17 domains, full parity. Laravel still falls back per key, so a future English-only key renders English rather than a raw key rather than breaking the page.

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

Panel utilities (`admin-card`, `admin-form`, `admin-nav-item`, `admin-th`, `badge-*`, `locale-tab`, `input-sm`) live at the bottom of `app.css` and follow the same `@utility` rule. Its Blade components are under `resources/views/components/admin/` — `translatable` (one field per locale, all following a single `tab` at form level), `form-layout`, `image-field`, `toggle`, `select`, `section`, `list-header`, `translation-state`, `danger-zone`.

**Editing screens are two columns from `xl` up.** `x-admin.form-layout` puts the record itself in the main column and an `aside` slot beside it — the picture, the publish switches, the SEO fields: what decides how the row appears rather than what it says. `admin-form` is the width the form sits in. Two things to know before adding a section to the aside:

- **Its field grid must be single-column.** The aside is about 21rem wide, but `sm:grid-cols-2` still measures the *viewport*, so a section moved across without changing its grid crushes two fields into that column. `x-admin.image-field` is the exception — it is an `@container` and measures its own box.
- **A mistyped slot name loses the whole column silently.** The page still renders; the switches that publish the record are simply gone. `AdminFormPageTest` renders every create and edit screen and asserts the aside arrived, because nothing else in the suite renders these pages at all.

The topbar reserves the height of both lines whether or not a page sets `@section('subheading')`, and `html.admin-shell` reserves the scrollbar gutter. Both exist so the layout does not jump as staff move through the menu.

**`x-cloak` is `display: none` until Alpine boots, and Alpine boots from a module script — i.e. after the first paint.** Anything cloaked that is *supposed to be visible on load* therefore blinks in on every navigation. The sidebar did exactly that, and shoved the content sideways as it arrived; its off-canvas state is now plain CSS (`admin-drawer`, with Alpine only adding `is-open`), so it paints with the page. Same reason `x-admin.translatable` cloaks only the locale panes that start *closed*: cloaking the open one blanked every field on the form until Alpine caught up. Cloak what should start hidden, never what should start visible.

**Never pass null as the second argument to `@section`.** Blade reads a null there as "capture until `@endsection`" and swallows the rest of the page. `@section('meta_description', $model->summary)` did exactly that the moment the panel made it possible to save a row with no summary; the four public show pages now coalesce.

Tokens and component classes are defined in `resources/css/app.css`. Component classes (`btn`, `btn-primary`, `card`, `input`, `shell`, `section`, `eyebrow`, …) are declared with Tailwind 4's **`@utility`, not `@layer components`** — this is required, because `@layer components` classes cannot be `@apply`-ed by other classes in v4 and the build fails with "Cannot apply unknown utility class". Follow that pattern when adding new component classes, and define a class before anything that applies it.

Reusable Blade components are in `resources/views/components/`: `icon` (inline Lucide paths keyed by name — add new icons to the `$paths` array), `doctor-card`, `department-card`, `package-card`, `post-card`, `section-heading`, `page-hero`, `doctor-avatar`, `rating`, `article-body`.

Article bodies use a markdown-lite convention (`## heading`, `- bullet`, `**bold**`) rendered by `x-article-body`, which escapes first and re-introduces only bold via the `inline_markup()` helper. Do not render post bodies with raw `{!! !!}`.

### Dark mode

The site and the panel both carry it, and it is a **palette swap rather than a `dark:` class on every element**. The templates use `text-navy-*` 524 times against `bg-navy-9xx` 41 times, so `.dark` inverts the navy ramp — which is really the *text* ramp — and the whole site flips correctly, leaving a countable set of exceptions.

Three rules to keep it working:

- **Navy as a *background* must name its dark shade explicitly.** Heroes, the footer, the panel sidebar and scrims over photographs carry `dark:bg-navy-100` / `dark:bg-navy-50` — the other end of the inverted ramp — so they stay dark in both themes. Adding a new navy surface without that gives you a light hero in dark mode.
- **White is never remapped.** `text-white` sits on those same dark surfaces 163 times and has to stay white; `bg-white` is patched to `dark:bg-navy-100` instead. Low-alpha white (`bg-white/10`) is glass on a navy hero and is left alone.
- **Shadows and the two "teal as text" shades are theme-dependent** and are overridden in the `.dark` block: a navy glow on a navy surface is nothing, and `teal-700` on a dark surface is about 2:1.

The theme is applied by an inline script in the `<head>` of both layouts, before first paint — anything later flashes the other theme on every navigation. Nothing is stored until somebody actually chooses, so a visitor with no preference follows their device, and `x-theme-toggle` reads its state from `<html>` rather than from a server-rendered value.

### Motion

Motion is part of the design system, not decoration bolted onto pages. Tokens live in `@theme`: one easing curve (`--ease-out-expo`) does almost all the work, with `--ease-spring` reserved for affordances that should feel physical. Durations are `--duration-fast|base|slow`. Reach for an existing utility before writing a bespoke transition.

- **Content must never need JavaScript to become visible.** `.reveal` starts at opacity 0 and `.reveal-clip` starts fully clipped, so anything that stops `app.js` running takes the content with it — a gallery whose photographs are served, sized and in the markup, showing an empty page. The inline head script stamps `has-js` before first paint (so nothing flashes visible then hides), `app.js` stamps `js-ready` as its first act, and a 1.5s watchdog drops `has-js` if the bundle never reported in. `html:not(.has-js) .reveal` then renders everything in its final state. JavaScript switches the animation **on**; it does not switch the content off.
- **Scroll reveals** — add `class="reveal"` and the IntersectionObserver in `resources/js/app.js` adds `reveal-in`. Direction variants: `reveal-left`, `reveal-right`, `reveal-zoom`. **These are nested inside `@utility reveal`, not declared as sibling utilities** — siblings carry equal specificity and Tailwind does not emit custom utilities in source order, so `.reveal.reveal-in` (0,2,0) is what guarantees revealed content actually appears. Do not flatten them.
- **Staggering** — put `data-reveal-stagger="70"` on the grid rather than an inline `transition-delay` per card; `app.js` writes an increasing `--reveal-delay` on each child, capped at 8 steps so a long list does not end in a two-second wait. **The children still need `class="reveal"` rendered server-side** — JS adds it as a fallback, but an element that paints visible and is then hidden by JS flashes, which is worse than not animating it.
- **Entrance, not reveal** — `anim-fade-up`, `anim-fade-in`, `anim-scale-in` fire on load with `--anim-delay`. Use these above the fold, where an IntersectionObserver would fire immediately anyway.
- **Counting figures** — `data-countup` on the element. The tween restores the server-rendered string verbatim at the end, so a formatted or suffixed value can never be mangled, and a non-numeric one is left alone.
- **Surfaces** — `card-interactive` lifts and tracks a teal spotlight from the pointer (`--mx`/`--my`, written by a delegated handler). Use it only where the card is clickable; `card-hover` is the non-clickable version, because a lift reads as an invitation to click. `card-zoom` scales a cover image inside one, `card-arrow` nudges a trailing arrow.
- **Buttons** — `.btn` carries the press, the icon transition and a white sheen swept on hover by one `::after`; it is invisible on the light variants, so there is no per-variant duplication. `btn-nudge` moves the trailing icon.
- **Photography** — `media-frame` is the standard clipped, hairlined image box (its `<img>` scales on hover); `media-scrim` is the gradient that keeps white text readable over a photograph; `media-badge` is a frosted label floating on one. `ken-burns` is the very slow push on a hero image — 28 seconds, so it reads as depth rather than as something animating. `reveal reveal-clip` wipes an image up from its own bottom edge instead of sliding it, because an image that moves drags the layout's eye with it.
- **Parallax** — `data-parallax="0.1"` on a decorative layer; `initParallax()` in `app.js` offsets it against the scroll on the shared rAF pass, skips anything off screen, and clears the transform outright when motion is not wanted. Keep the factor low: it should separate the layer from the text over it, not read as an effect.
- **Proportion** — `meter` is the horizontal bar (a `<span>` inside, width as a percentage, `bar-grow` on reveal); the dashboard's week-ahead columns use the vertical twin, `bar-rise`, with the fill absolutely positioned inside a full-height track so the percentage height is definite.
- **Decoration** — `hero-grid` and `orb` are the drifting grid and glow behind the navy heroes. `pulse-dot` is a live indicator, `skeleton` a loading placeholder.
- **Panel affordances** — `nav-link`/`nav-link-active` carry the header's underline indicator (always present at zero scale, so hover draws half a rule and the current section holds a full one). `switch` is the Site controls toggle: the visually hidden input sits **immediately before** the track, which is what lets the checked state be expressed in plain CSS rather than through a peer variant reaching across nesting levels.

`prefers-reduced-motion` is honoured in three places and all three matter: a blanket rule zeroing every duration, an `!important` reset on `.reveal` (it has to beat the direction variants), and a live check in `app.js` that reveals everything immediately and re-settles the page if the setting is turned on mid-visit. Anything new must survive the setting being on — and must survive `.no-motion`, the Site controls switch that shares those rules.

## Conventions worth keeping

- Bangladeshi mobile validation lives once, in `App\Support\Rules::BD_MOBILE`, and is used by every form request that takes a number. `App\Sms\PhoneNumber` normalises the same three accepted forms for the gateway.
- Money is stored as integer BDT (no minor units) and rendered `৳{{ number_format(...) }}`.
- Public POST routes are rate-limited (`throttle:10,1`); the admin login is throttled twice over — `throttle:10,1` on the route and five attempts per email+IP inside `LoginRequest`.
- `/admin` sends `noindex, nofollow` and is excluded from the sitemap-ish `hreflang` block, which only the public layout emits.
- Copy is written plainly and avoids marketing superlatives — claims in seeded content are specific and checkable (response times, staffing ratios) by design. Keep that voice.
- Notification text is rendered at dispatch, never inside the job, so a queued payload is a finished string rather than a template plus a locale.
- Every image position on the public site goes through `image_url()` or `doctor_photo()`, never `media_url()` directly — that is the one place "there is no picture" is answered.
- **`media_url()` returns uploads host-less** (`/storage/…`). The public disk builds its URLs from `APP_URL`, which pins every upload to one hostname; reached by any other name — `artisan serve` on 127.0.0.1, the LAN address, a staging alias — those images 404 while the root-relative stand-ins carry on working, so only the real photographs vanish. A URL on another host (a CDN) is left absolute.
- Anything guarded by Site controls is guarded in **both** places: the link is filtered out of the template *and* the route carries `feature:<key>`.
- `.idea/` is untracked and stays that way.

## Not built yet

Nothing the site claims. What is missing is what it has never promised:

- **No lab results system upstream.** Reports reach the portal because a staff member uploads a file, not because an analyser wrote one. That is the honest version of "download reports online", but it is a manual step somebody has to remember.
- **No delivery receipts.** The notification log records what was dispatched and what the gateway accepted; what it cannot record is what arrived. No Bangladeshi gateway in `config/sms.php` reports back, and adding one means a callback route per provider — the log has a `status` column with room for it.

## The one thing to do before launch

**Native-speaker review of all the Bangla.** It was written without one and now spans every locale file — the UI, all seeded content, the staff panel, the emails, six SMS templates, 23 clinical test descriptions, the photo gallery, the panel's editor and the whole patient portal. The tests prove *coverage*, not *correctness*: they check that both locales define the same keys and keep the same placeholders, and cannot tell whether a sentence is right.

Two places carry a consequence beyond the editorial:

- **Diagnostics preparation instructions** (`database/seeders/Translations/DiagnosticTestTranslationSeeder.php`) — a mistranslated fasting window sends somebody home unfasted and wastes their trip.
- **The emergency symptom list** (`lang/bn/pages.php`) — advice about when not to wait.

Everything else in Bangla is worth reviewing; those two are worth reviewing first.

## Where this stopped (2026-08-25)

Everything described above is built and tested — 452 tests. What follows is the state a new session should know rather than rediscover.

**Patients can move and cancel their own bookings from the portal (2026-08-25)** — see *Changing a booking from the portal* above. `behaviour_portal_changes` turns it off.

**The home page's layouts and the slider landed on 2026-08-25** — the user asked for both, chose whole-page templates over band reordering, and chose the slider as the hero rather than a band lower down. See *The home page has three layouts* above. The live setting is still `classic`, so their home page has not changed until they pick another.

**The notification log landed on 2026-08-25** — `/admin/notifications`, which is also where a missing queue worker stops being invisible. See *The notification log* above.

**Staff roles landed on 2026-08-25** — three of them, cut along the menu's own groups. See *Three roles, cut along the lines the menu already draws* above.

**The panel menu redesign is done** — the registry (`App\Support\PanelNavigation`), the collapsible icon rail, the Ctrl+K palette, the account block and the untranslated-content badges. Ctrl+K then grew a second half: `App\Support\PanelSearch` finds the records themselves. See *The menu is a registry, and it collapses* above.

**Two things waiting on the user, not on code:**

- The Apache vhost, queue worker and scheduler cron are still not installed (`deploy/`, all need sudo). Three of the five deployment gaps fail *silently* — see the table near the top.
- One album is missing its cover image. I deleted the file (`gallery/92-v81zkdp9.png`) on 2026-08-24 while exercising the cover endpoint against the live dev database; the behaviour that allowed it is fixed and tested, but the file is gone and has to be re-uploaded. Do not run write endpoints against their data without saying so first.

**Start the dev server with the upload limits raised** — `php8.3 -d upload_max_filesize=32M -d post_max_size=64M artisan serve …`. The CLI runtime caps at 2M/8M while Apache is at 5G, and the failure mode is a batch of photographs uploading as nothing at all.
