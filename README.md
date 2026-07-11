# PrintPandora

A Laravel + React (Inertia) e-commerce application.

## Project overview

PrintPandora is a full-stack print-commerce platform built as a Laravel monolith with an Inertia-powered React frontend. It combines a public marketing/storefront site, authenticated customer workflows, and an internal admin panel in one codebase.

### Core capabilities

- **Storefront + content:** Home, static marketing pages, product catalog/detail pages, blog pages, and SEO sitemap routes.
- **Shopping flow:** Session-based cart, checkout, order creation, and PayPal order create/capture integration.
- **Customer account:** Dashboard, profile/security settings, order history, and support tickets with threaded replies.
- **Growth features:** Referral links and affiliate tracking, commission accrual, and payout request flows.
- **Admin operations:** Filament resources for products, product categories, blog posts, orders, users, tickets, and dashboard widgets.

### Tech stack

- **Backend:** PHP 8.4, Laravel 13, Inertia Laravel, Fortify authentication.
- **Frontend:** React 19 + TypeScript, Vite 8, Tailwind CSS 4, Radix UI primitives.
- **Data/services:** SQLite by default, session cart service, PayPal API service, content JSON loader shared through Inertia middleware.
- **Tooling:** PHPUnit, PHPStan (Larastan), Laravel Pint, ESLint, Prettier.

### Repository layout

```text
app/
  Http/Controllers/     # Storefront, checkout, dashboard, settings, support flows
  Filament/             # Admin panel resources and widgets
  Models/               # Domain entities (products, orders, tickets, affiliates, posts, etc.)
  Services/             # Cart and PayPal integrations
  Support/              # Shared helpers (e.g., hardcoded content loader)
resources/js/
  pages/                # Inertia pages (shop, auth, settings, blog, dashboard)
routes/
  web.php               # Public/customer route map
  settings.php          # Authenticated settings routes
content/
  hardcoded-content.json
  product-options/      # Product-specific option definitions used by product pages
```

## Docker development

This project includes a Docker setup for consistent local development. The container runs Apache + PHP 8.4, Node.js, and the Vite dev server together, with your local source code synced into the container.

### Requirements

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows: use the WSL2 backend)
- Git

### Quick start

1. Copy the environment file if you haven't already:

   ```bash
   cp .env.example .env
   ```

2. Build and start the container:

   ```bash
   docker compose up --build -d
   ```

3. Open the application:

   - App: http://localhost:8080
   - Vite dev server: http://localhost:5173

The first build installs Composer and npm dependencies automatically, creates the SQLite database file if it is missing, and runs migrations.

### Useful commands

```bash
# Start the environment
docker compose up -d

# Stop the environment
docker compose down

# View logs
docker compose logs -f app

# Run artisan commands
docker compose exec app php artisan <command>

# Run npm commands
docker compose exec app npm <command>

# Run composer commands
docker compose exec app composer <command>

# Rebuild the image after changing the Dockerfile
docker compose up --build -d
```

### Port configuration

Ports are read from `.env`:

- `APP_PORT` — the port exposed for the Apache server (default: `8080`)
- `VITE_PORT` — the port exposed for the Vite dev server (default: `5173`)

Change them in `.env` and restart the container with `docker compose up -d`.

### How file syncing works

- Your project root is bind-mounted into the container at `/var/www/html`, so changes you make locally appear inside the container immediately.
- `vendor/` and `node_modules/` are stored in Docker named volumes instead of being synced from Windows. This keeps file-watching and dependency installs fast.
- The SQLite database (`database/database.sqlite`) and uploaded images (`public/images/product-options/uploads/`) live inside the bind-mounted project, so they persist across container restarts.
