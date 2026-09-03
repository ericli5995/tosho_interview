# THINK ENGINEERING — 製品デモサイト (take-home)

A small product-demonstration website with a public front end and an admin
back office (小后台) for uploading products and their images.

Built **without frameworks**: PHP 8.2 + MySQL, hand-rolled router / autoloader /
PDO layer. Front end is server-rendered PHP with **Vue 3** for the product
search and the admin image uploader, and **jQuery** for small niceties. No npm,
no Composer — the two JS libraries are vendored with a SHA-256 lockfile.

Only files with these extensions: `.php`, `.html`, `.css`, `.js`, `.sql`.

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

## Requirements

- **PHP 8.2** with extensions: `pdo_mysql`, `gd` (with JPEG/PNG/WebP), `fileinfo`, `mbstring`
- **MySQL 8** (or MariaDB 10.4+)
- **Apache 2.4** with `mod_rewrite` for production; the PHP built-in server is fine for local dev
- No Composer / npm required

---

## Local setup (macOS, no Docker)

### 1. Install PHP and MySQL

```bash
brew install php@8.2 mysql
brew link php@8.2 --force --overwrite
php -v                                              # expect 8.2.x
php -m | grep -E 'pdo_mysql|gd|fileinfo|mbstring'   # all four must print
brew services start mysql                           # background MySQL on :3306
```

### 2. Create the databases

```bash
mysql -u root <<'SQL'
CREATE DATABASE tosho_dev  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE tosho_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SQL
```

A fresh Homebrew MySQL has user `root` with an empty password.

### 3. Configure

```bash
cp config/.env.example config/.env
# edit config/.env if your MySQL user/password differ from root / (empty)
```

### 4. Migrate + seed + create an admin

```bash
php bin/migrate.php --seed
php bin/create-admin.php admin@example.com "password123"
```

`--seed` loads 10 demo motors. `bin/migrate.php --fresh --seed` rebuilds from scratch.

### 5. Writable storage + the media symlink

```bash
mkdir -p storage/{uploads/products,sessions,cache,logs}
chmod -R u+rwX storage
ln -sfn ../storage/uploads public/media     # uploaded images are served from here
```

Uploaded files live in `storage/uploads/` (outside the web root); `public/media`
is a symlink so Apache/PHP can serve them as static files while the originals
stay non-executable.

### 6. Run

```bash
php -S localhost:8080 -t public public/router.php
```

- Public site: <http://localhost:8080>
- Product search: <http://localhost:8080/products/search>
- Admin: <http://localhost:8080/admin/login>

`router.php` serves real files under `public/` (including `media/`) and sends
everything else to `public/index.php` — the same behaviour Apache gets from
`public/.htaccess`.

### 7. Front-end libraries (optional)

They are already committed. To re-fetch and verify them:

```bash
php bin/vendor-sync.php
```

---

## Running under Apache 2.4 (recommended before submitting)

Homebrew's `php@8.2` no longer ships `mod_php` (`libphp.so`), so PHP runs via
**php-fpm + `mod_proxy_fcgi`** — the same pattern real production servers use.

```bash
brew install httpd
brew services start php@8.2   # php-fpm, listens on 127.0.0.1:9000 by default
```

Edit `/opt/homebrew/etc/httpd/httpd.conf`, uncomment:

- `LoadModule rewrite_module lib/httpd/modules/mod_rewrite.so`
- `LoadModule proxy_module lib/httpd/modules/mod_proxy.so`
- `LoadModule proxy_fcgi_module lib/httpd/modules/mod_proxy_fcgi.so`

then add at the end: `Include /opt/homebrew/etc/httpd/extra/tosho.conf`

Copy `config/apache/vhost.conf.example` to that path (adjust the project path),
then `brew services start httpd`. Same URLs as above (`:8080`).

The vhost needs `AllowOverride All` (so `public/.htaccess` drives the rewrite).
See the example file for the `<FilesMatch "\.php$"> SetHandler proxy:fcgi://...`
block that hands `.php` requests to php-fpm.

---

## Tests

```bash
# get PHPUnit once (single PHAR, no Composer)
curl -sSfL -o tools/phpunit.phar https://phar.phpunit.de/phpunit-11.phar

# unit tests (no database needed)
php tools/phpunit.phar --testsuite unit

# feature tests (need the test database)
DB_TEST_DSN="mysql:host=127.0.0.1;dbname=tosho_test;charset=utf8mb4" \
DB_TEST_USER=root DB_TEST_PASS= \
php tools/phpunit.phar --testsuite feature
```

Feature tests rebuild the `tosho_test` schema from `sql/migrations/` on each run
and self-skip if `DB_TEST_DSN` is not set.

---

## Project layout

```
public/            Apache DocumentRoot: index.php (front controller), router.php,
                   .htaccess, assets/ (css, js, vendored vue+jquery), media -> ../storage/uploads
src/               PSR-4 App\ -> src/
  Core/            Autoloader, Config, Router, Request, Response, View, Database, Repository, App, Controller
  Security/        Auth, Csrf, Password
  Http/            Controllers (public + Admin\), Middleware (RequireAuth, VerifyCsrf)
  Service/         ProductService, ImageUploadService, ProductSearchService, SearchCriteria
  Repository/      Product, ProductImage, Category, AdminUser  (hand-written SQL)
  Entity/          plain data holders
  Validation/      Validator
  Support/         helpers.php, Paginator
templates/         server-rendered PHP views (layouts, partials, public pages, admin pages)
config/            app.php, database.php, routes.php, .env.example, apache/vhost.conf.example
sql/               migrations/001-005, schema.sql, seed.sql
bin/               migrate.php, create-admin.php, vendor-sync.php, gc-sessions.php
storage/           writable: uploads/, sessions/, cache/, logs/  (git-ignored)
tests/             PHPUnit Unit + Feature
```

## Notes on architecture

- **Layering without a framework:** Controller (HTTP) → Service (use-case, owns
  the transaction) → Repository (aggregate + SQL) → PDO. Repositories map rows to
  Entities; there is no separate DAO layer — one repository per aggregate talks
  to PDO directly.
- **Uploads:** `ImageUploadService` validates the real MIME type, caps the size,
  gives the file a random name, and re-encodes it through GD (stripping metadata
  / any embedded payload) into `_original`, `_medium` and `_thumb` variants under
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
