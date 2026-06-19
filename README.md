# Spin Wheel App

Laravel + SQLite app for multi-campaign QR-based spin wheels.

## Features

- One shared admin area for managing multiple campaigns
- Per-campaign public play URL and QR code
- Mobile-first branded spin page using the provided `quayso/` assets
- No immediate duplicate spin result when a campaign has at least 2 active items
- SQLite storage with no extra infrastructure required

## Local setup

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
```

Optional for local development:

```bash
php artisan serve
```

## Admin login

Admin access uses shared credentials from env values:

- `ADMIN_USERNAME`
- `ADMIN_PASSWORD`

Defaults in `.env.example`:

- Username: `admin`
- Password: `change-me`

## Main routes

- `/admin/login`
- `/admin/campaigns`
- `/play/{token}`
- `/play/{token}/spin`

## Deploy

- Point the web root at `public/`
- Ensure `storage/` and `bootstrap/cache/` are writable
- Create `database/database.sqlite` on the server if it does not exist
- Run `php artisan migrate --force` during deploy
- Run `npm install && npm run build` during deploy or ship built assets
- Set a real `APP_URL`, `ADMIN_USERNAME`, and `ADMIN_PASSWORD`

## Test

```bash
php artisan test
npm run build
php artisan route:list
```
