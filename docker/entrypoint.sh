#!/bin/sh
set -e

echo "==> Waiting for PostgreSQL..."
until php -r "new PDO('pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    echo "    PostgreSQL not ready — retrying in 2s..."
    sleep 2
done
echo "==> PostgreSQL is ready."

echo "==> Fixing migrations sequence if needed..."
php -r "try { \$dsn = 'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'); \$pdo = new PDO(\$dsn, getenv('DB_USERNAME'), getenv('DB_PASSWORD')); \$seq = \$pdo->query(\"SELECT pg_get_serial_sequence('migrations','id')\")->fetchColumn(); if (\$seq) { \$max = \$pdo->query(\"SELECT COALESCE(MAX(id),0) FROM migrations\")->fetchColumn(); \$next = (int)\$max + 1; \$pdo->exec(\"SELECT setval('\" . \$seq . \"', \" . \$next . \", false)\"); echo 'migrations sequence set to ' . \$next . "\n"; } } catch (Exception \$e) { echo 'could not adjust migrations sequence: ' . \$e->getMessage() . "\n"; }"

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Checking if seed is needed..."
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tail -1 || echo "0")
if [ "$USER_COUNT" = "0" ]; then
    echo "==> Seeding production data..."
    php artisan db:seed --class=ProductionDataSeeder --force
else
    echo "==> Data already present (${USER_COUNT} users found), skipping seed."
fi

echo "==> Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Starting services..."
exec "$@"
