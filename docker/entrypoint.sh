#!/bin/sh
# Container entrypoint: wait for MySQL, provision storage + schema, then hand
# off to Apache. Idempotent - safe to run on every start.
set -e
cd /var/www/html

echo "[entrypoint] waiting for MySQL at ${DB_HOST}:${DB_PORT:-3306} ..."
until php -r '
    try {
        new PDO(
            "mysql:host=" . getenv("DB_HOST") . ";port=" . (getenv("DB_PORT") ?: "3306") . ";dbname=" . getenv("DB_NAME"),
            getenv("DB_USER"),
            getenv("DB_PASS")
        );
    } catch (Throwable $e) {
        exit(1);
    }
' >/dev/null 2>&1; do
    sleep 2
done
echo "[entrypoint] MySQL is up."

# Writable storage (named volumes may be empty on first boot) + media symlink.
mkdir -p storage/uploads/products storage/sessions storage/cache storage/logs
ln -sfn ../storage/uploads public/media
chown -R www-data:www-data storage

echo "[entrypoint] running migrations ..."
php bin/migrate.php

if [ "${APP_SEED:-true}" = "true" ]; then
    php bin/migrate.php --seed-if-empty
fi

if [ -n "${ADMIN_EMAIL:-}" ] && [ -n "${ADMIN_PASSWORD:-}" ]; then
    php bin/create-admin.php "$ADMIN_EMAIL" "$ADMIN_PASSWORD" "${ADMIN_NAME:-Administrator}"
fi

echo "[entrypoint] ready -> ${APP_URL:-http://localhost:8080}"
exec "$@"
