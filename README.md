# FLEXEE Supply Chain Management

The Laravel 13 rebuild of FLEXEE 1.0 SCM. Stage 1: all of the live code on Laravel 13 and PHP 8.4, with the security work done. What changed and why: `docs/STAGE1.md`. Rules for working on it: `CLAUDE.md`.

## Run it locally

You need PHP 8.4, Composer, Node 22 and MySQL 8.0.

```
composer install                        # first time: commit the composer.lock it makes
cp .env.example .env
php artisan key:generate
# set DB_DATABASE, DB_USERNAME and DB_PASSWORD in .env
php artisan migrate
php artisan db:seed                     # reference data only
php artisan scm:create-admin you@flexee.org --first=You --super
php artisan db:seed --class=DemoSeeder  # optional: demo faculty and students
npm ci && npm run build
php artisan serve
```

## Test it

```
php artisan test                        # 33 tests
php artisan test --group=baseline       # the live figures, then every page; a few minutes
```

The baseline test rebuilds the database named in `DB_DATABASE`, so run it against a test database, never one with data you need:

```
DB_DATABASE=scm_test php artisan test --group=baseline
```
