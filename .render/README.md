# Render Docker deploy

This repo includes a production-oriented Docker setup for Render.

## Render settings
- Create a **Web Service**
- Environment: **Docker**
- No build/start commands needed (uses `Dockerfile`)

## Required environment variables
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY=base64:...` (generate locally: `php artisan key:generate --show`)
- `APP_URL=https://<your-service>.onrender.com`

Database (choose one):
- MySQL/MariaDB: `DB_CONNECTION=mysql` + `DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD`
- Postgres: `DB_CONNECTION=pgsql` + `DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD`

## Migrations
Run manually from the Render Shell:
- `php artisan migrate --force`
