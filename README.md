# THINK ENGINEERING — 製品デモサイト (take-home)

A small product-demonstration website with a public front end and an admin
back office (小后台) for uploading products and their images.

Built **without a framework**: PHP 8.2 + MySQL with a hand-rolled router /
PDO layer. Server-rendered PHP templates, **Vue 3** for the product search and
the admin image uploader, **jQuery** for small niceties. Dependencies are managed
by Composer (PHP) and npm (the two JS libraries, copied as-is — no bundler).

---

## Quick start (Docker — one command)

Requires [Docker Desktop](https://www.docker.com/products/docker-desktop/).

```bash
docker compose up --build
```

Then:

| URL | What |
| --- | --- |
| <http://localhost:8080> | public site |
| <http://localhost:8080/products/search> | 製品検索 |
| <http://localhost:8080/admin/login> | admin — `admin@example.com` / `password123` |

First boot builds the image (Composer + npm run inside the build), starts MySQL,
runs the migrations, seeds 10 demo motors (only if the catalogue is empty) and
creates the admin user. Uploads and the database persist in named volumes, so
`docker compose down` / `up` keeps your data; `docker compose down -v` wipes it.

Useful:

```bash
docker compose exec app php bin/migrate.php --fresh --seed   # reset the catalogue
docker compose logs -f app                                   # entrypoint + Apache logs
```

---

## What is implemented

| Area | Status |
| --- | --- |
| 製品検索 (product search) | **Full** — Vue UI + `/products/search.json`, filters by keyword / motor type / ø diameter / voltage, sorting, pagination |
| Product detail page | **Full** — image gallery + representative spec table |
| Top page | Featured-product panel styled after the mockup |
| Admin: login / logout | **Full** — session auth, CSRF on every POST |
| Admin: product CRUD | **Full** — create / edit / delete, spec table editor |
| Admin: image upload | **Full** — multi-file, drag & drop, preview, reorder, pick primary; server re-encodes with GD into 3 sizes |
| 製品情報 / 技術情報 / 会社情報 / お問い合わせ | Placeholder pages (out of scope per the brief) |

---

## Running without Docker (optional)

Requirements: PHP 8.2 (`pdo_mysql`, `gd`, `fileinfo`, `mbstring`), MySQL 8,
[Composer](https://getcomposer.org), Node 18+ (only to fetch the two JS files).

```bash
brew install php@8.2 mysql composer node        # macOS
brew services start mysql
mysql -u root -e "CREATE DATABASE tosho_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

composer install                                 # PHP deps + autoloader
npm install                                      # copies vue/jquery into public/assets/js/vendor/
cp config/.env.example config/.env               # edit if your MySQL creds differ

php bin/migrate.php --seed
php bin/create-admin.php admin@example.com "password123"

mkdir -p storage/{uploads/products,sessions,cache,logs}
ln -sfn ../storage/uploads public/media          # uploaded images are served from here

php -S localhost:8080 -t public public/router.php
```

To run behind a local Apache 2.4 instead of `php -S`, see
`config/apache/vhost.conf.example` (php-fpm + mod_proxy_fcgi).

---

## Project layout

```
public/            Apache DocumentRoot: index.php (front controller), router.php (php -S),
                   .htaccess, assets/ (css, js; js/vendor/ is generated), media -> ../storage/uploads
src/               PSR-4 App\ -> src/  (Composer autoload)
  Core/            Config, Router, Request, Response, View, Database, Repository, App, Controller
  Security/        Auth, Csrf, Password
  Http/            Controllers (public + Admin\), Middleware (RequireAuth, VerifyCsrf)
  Service/         ProductService, ImageUploadService, ProductSearchService, SearchCriteria
  Repository/      Product, ProductImage, Category, AdminUser  (hand-written SQL)
  Entity/          plain data holders
  Validation/      Validator
  Support/         helpers.php (autoloaded via composer "files"), Paginator
templates/         server-rendered PHP views (layouts, partials, public pages, admin pages)
config/            app.php, database.php, routes.php, .env.example, apache/vhost.conf.example
sql/               migrations/001-005, schema.sql, seed.sql
bin/               migrate.php, create-admin.php, gc-sessions.php, copy-assets.js
docker/            apache.conf, php.ini, entrypoint.sh
storage/           writable: uploads/, sessions/, cache/, logs/  (git-ignored)
Dockerfile, docker-compose.yml, composer.json, package.json
```

## Notes on architecture

- **Layering without a framework:** Controller (HTTP) → Service (use-case, owns
  the transaction) → Repository (aggregate + SQL) → PDO. Repositories map rows to
  Entities; there is no separate DAO layer — one repository per aggregate talks
  to PDO directly.
- **Dependencies:** Composer provides the PSR-4 autoloader — the app has no
  third-party PHP packages. npm holds the two JS libraries;
  `bin/copy-assets.js` copies their dist builds into `public/assets/js/vendor/`
  (no webpack/vite — Vue's global build is loaded with a `<script>` tag).
- **Uploads:** `ImageUploadService` validates the real MIME type, caps the size,
  gives the file a random name, and re-encodes it through GD (stripping metadata
  / any embedded payload) into original, `_medium` and `_thumb` variants under
  `storage/uploads/products/{id}/`. The `product_images` row stores only relative
  paths. Deleting a product cascades the rows and removes the files.
- **Security:** all `/admin` routes behind session auth; all POSTs behind a
  per-session CSRF token; `storage/uploads/.htaccess` disables script execution;
  output escaped via `e()`; SQL always parameterised.
- **Sessions:** stored in `storage/sessions/`. Because that is a custom
  `save_path`, the OS session-gc cron does not apply — `index.php` enables PHP's
  probabilistic GC (~1% of requests, `gc_maxlifetime` 1440s). In production,
  disable that (`session.gc_probability = 0`) and schedule `bin/gc-sessions.php`
  instead (see the file header for a crontab example), or use a php-fpm pool /
  Redis session handler.
- **Container:** multi-stage `Dockerfile` (node → composer → `php:8.2-apache`).
  `docker/entrypoint.sh` waits for MySQL, provisions `storage/` + the media
  symlink, runs migrations, seeds once, creates the admin, then execs Apache.
