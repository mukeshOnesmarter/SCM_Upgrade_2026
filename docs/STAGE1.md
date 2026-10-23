# Stage 1: SCM on Laravel 13

One Smarter, Inc. | 23 October 2026 | Written by Claude for Mukesh and Vikram. Design: SCM_V3_Upgrade_Design_Note.md (SC2, SC7b).

## What stage 1 is

All of the live SCM code (scm.flexee.org, from Mukesh's zip of 23 October 2026), moved onto Laravel 13 and PHP 8.4, with the security work done. Every page, decision, report and job the live site has is here. The figures must match the live recording (`tests/Baseline/scm_baseline_v1.json`); the test that checks this is `php artisan test --group=baseline`.

Stage 1 doesn't fix the quarter close or the accounting yet. Those are stage 2, so each figure they move can be seen against this one.

## How to run it

```
composer install
cp .env.example .env
php artisan key:generate
# set DB_* for a local MySQL 8.0 database
php artisan migrate
php artisan db:seed                     # reference data
php artisan scm:create-admin you@flexee.org --first=You --super
php artisan db:seed --class=DemoSeeder  # optional: demo faculty and students
npm ci && npm run build
php artisan test
php artisan test --group=baseline       # rebuilds the test database; a few minutes
```

The demo accounts' password is `DEMO_PASSWORD` from `.env`. If it's empty, the seeder makes one and prints it once.

## What moved to Laravel 13

| Old (Laravel 8) | Now (Laravel 13) |
|---|---|
| `app/Http/Kernel.php` middleware list | `bootstrap/app.php`, same names |
| `app/Console/Kernel.php` schedule | `routes/console.php` |
| `RouteServiceProvider` | `bootstrap/app.php` (routes/web.php still loads admin, faculty and students) |
| Stock middleware (`TrustProxies`, `VerifyCsrfToken` and others) | Laravel's own |
| OpenSpout 3 for Excel | OpenSpout 5, behind `app/Support/Spreadsheet` with OpenSpout 3's names, so the exports are unchanged |
| Mailjet's PHP client | Laravel's mailer with Mailjet's Send API (`MAIL_MAILER=mailjet`). `MailjetService::send()` keeps its signature |
| Carbon 2 date differences | Carbon 3 returns signed, fractional days. All 17 `diffInDays`/`diffInSeconds` calls now ask for whole positive days, as before |
| `Arr::random` (Laravel 8: seeded by `mt_srand`) | `App\Support\SimRandom::pick`, which draws exactly what Laravel 8 did. Laravel 13's `Arr::random` can't be seeded |
| `users`, `password_resets`, jobs and cache tables | Laravel 13's jobs, cache and sessions tables; `password_resets` kept (SCM's three password brokers use it). No `users` table |
| Laravel 8 language files | Laravel 13's own |

The app's time zone stays `America/New_York`, as the live site's is, so the figures match. Stage 2 stores times in UTC and shows each in the course's zone.

## Security work

| Was | Now |
|---|---|
| `/clear-cache`, `/run-storage-link`, `/cron-jobs/*`, `/testMail`, `/emailtemplate`, `/mock/alplayer` open to anyone | Removed. The deadline close and reminders run only from the scheduler (`simulation:cron-jobs`) |
| `/student/simulation/debug/kpi`, `/export` and `/dashboardDump` showed any team's scorecard to anyone | Removed, with `DebugKpiController` and its view |
| `/admin/run-setup` ran `migrate:fresh` and the seeders | Removed, with `/admin/pending-migrations`. The deploy runs migrations. On staging and production, `migrate:fresh`, `db:wipe` and the like refuse to run |
| Any faculty member could open any course, team or student by its number | `faculty.course` middleware on every faculty course, research-study, student-profile and mimic page: the course must be one they teach, and the student must be in it |
| Sign-in didn't check the account's status | Only `Active` accounts sign in. Admins now have a status too |
| The session ID stayed the same after sign-in | A new session ID at sign-in |
| Unlimited sign-in attempts | 10 a minute per address, on all three sign-in forms, and 6 a minute for password-reset emails |
| `{role}` in the password-reset addresses took any word | Only `admin`, `faculty` or `student` |
| Seeders created real people's accounts (vikram@, mukesh@, chuck@flexee.org, and three faculty) with fixed passwords | No accounts in `DatabaseSeeder`. `php artisan scm:create-admin` makes an admin. `DemoSeeder` makes demo accounts, marked `is_test`, with no password in the code, and refuses to run on production |
| Every student a faculty member added, and every faculty account an admin added, got the same password, written in the code | Each new account gets a long random password nobody knows, and an email with a link to set their own ("Set your FLEXEE password") |
| "Setup Test Team" enrolled 30 random accounts with ids 3 to 200, which on a live site are real students | It enrolls test students (`is_test`) only |
| Invite passwords were stored readable | Stored as a hash. Nothing read them back |
| `dd($e)` in three error handlers showed the full error to students | The error is reported to the log, and the page says something went wrong |
| Bare Laravel error pages | Plain-English 403, 404, 419 and 500 pages with a link to the person's own home |
| `.env`, `vendor/`, `node_modules/`, `GoDaddy/` committed | `.gitignore` blocks them, and server notes, dumps, zips and keys |

## Defects fixed that change no figure

1. **The student KPI page crashed.** It called `FlexScorecardService::generateBenchmarks()`, which didn't exist. It exists now, using the same cache and calculation as the stored benchmarks.
2. **The licence check allowed two extra students.** It subtracted 2, left over from the removed baseline and AI firms.
3. **Demand read the first firm's increment.** `Firm::find($id)->value(...)` queried the whole table. It reads this firm's now. Every course has one firm, so no figure changes.
4. **No command ran without Mailjet keys.** Mail settings are read only when an email is sent.
5. **The sender was a personal address** (mukesh@flexee.org). It's `MAIL_FROM_ADDRESS` now.
6. **Four pages failed on the live site too.** The baseline test opens every page as the person it's for, and found them:
   - `/admin/courses`: its "Create" button built a route name that doesn't exist. It falls back to the course-creation page now.
   - `/admin/courses/{id}/forecast-demand` pointed at a method that was never written. The route is removed.
   - The admin faculty list and the faculty profile page failed for anyone without a profile row. They show the page without the profile details now.
   - The 14 faculty "research-study" addresses were an unfinished placeholder: they returned JSON from a service whose methods were never written, and no page linked to them. They're removed.
7. **The Stripe checkout's return addresses pointed at routes that don't exist.** Fixed. The Authorize.Net code, which recorded no payment and had routes named "razorpay", is removed. Stage 4 rebuilds student payment.

## Found while testing the port

- **Carbon 3 refuses numbers given as text.** The close button added `SIMULATION_SYNCHRONIZATION_TIME` minutes, read from `.env` as the text "45". The setting is a number now. Without this, every close by the button failed.
- **`require_once` for the route files** meant a second boot of the app (every test does one) had no admin, faculty or student pages. They're loaded with `require`.
- **PHP 8.4 rounds a few values differently from 8.3.** The first recording ran on PHP 8.3 and differed from the rebuild by one cent in one team's Q1 net income. Run on PHP 8.4, the live code gives exactly the rebuild's figures. The live server runs PHP 8.4, so the PHP 8.4 recording is the reference (`tests/Baseline/scm_baseline_v1.json`).

## Kept as they were, for stage 2

- The quarter-close defects (design note, gaps 1 to 5): saves into a closed quarter, the team count that's never set, the two job chains that don't wait for each other, the missed-day close, and reminders that depend on the deadline's minute.
- The accounting defects (design note, defects 1 to 4): the balance sheet total, cash, Q0, and the overall score.
- The analysis server's text, still shown as HTML (stage 3).

## Tests

| Test | What it checks |
|---|---|
| `tests/Feature/FoundationsTest.php` | Home page and build line, `noindex`, Mailjet transport, one queue worker under a lock, no destructive commands on live sites |
| `tests/Feature/SecurityTest.php` | Every item in the security table above |
| `tests/Baseline/BaselineTest.php` | The fixed course's figures equal the live recording (0 differences), then every GET page opens for the person it's for with no server error. Its one intended difference is listed in the test (`withIntendedChanges`): the new "student added" message and the three set-password emails |

All 33 feature tests pass on MySQL 8.0 and on SQLite, and the baseline test passes on MySQL 8.0, all on PHP 8.4.27.
