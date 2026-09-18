# Laravel conversion deployment

## Fresh install

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan optimize
```

Point the web server document root to `public/`. Never expose the repository root, `.env`, `storage`, or `database` directly.

## Existing Gadget 50 database

Back up the database first. Configure the same database in `.env`, then run:

```bash
php artisan migrate
php artisan gadget50:import-legacy
php artisan optimize:clear
php artisan optimize
```

The importer uses `updateOrInsert`, does not drop tables, and preserves existing IDs and rows. Do not run `migrate:fresh` against production.

## Required checks

```bash
php artisan route:list
php artisan migrate:status
php artisan optimize:clear
php artisan test
```

Logout is POST-only and must be submitted through a CSRF-protected form. Public URLs are named Laravel routes and do not expose `.php` files.
