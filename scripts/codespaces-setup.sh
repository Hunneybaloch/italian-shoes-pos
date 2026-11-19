#!/usr/bin/env bash
set -Eeuo pipefail

cd "$(dirname "$0")/.."

echo "== Italian Shoes POS: Codespaces setup =="

for command in php composer node npm; do
    if ! command -v "$command" >/dev/null 2>&1; then
        echo "Required command not found: $command"
        exit 1
    fi
done

php -r 'exit(extension_loaded("pdo_sqlite") ? 0 : 1);' || {
    echo "PHP pdo_sqlite is missing. This Codespaces setup requires SQLite support."
    exit 1
}

if [[ ! -f .env ]]; then
    cp .env.codespaces.example .env
    echo "Created a Codespaces-only .env using SQLite."
elif ! grep -Eq '^DB_CONNECTION="?sqlite"?$' .env; then
    echo "Safety stop: existing .env does not use SQLite."
    echo "This script will not change or migrate a MySQL database."
    exit 1
fi

mkdir -p database
touch database/database.sqlite

if ! grep -Eq '^APP_KEY=base64:.+' .env; then
    php artisan key:generate
fi

echo "== Installing PHP dependencies =="
composer install --no-interaction --prefer-dist

echo "== Installing frontend dependencies =="
npm install --no-audit --no-fund

echo "== Clearing cached Laravel configuration =="
php artisan optimize:clear

echo "== Applying pending migrations and reference seed data =="
php artisan migrate --seed --force

echo "== Building frontend assets =="
npm run build

echo "== Running automated tests =="
bash scripts/codespaces-test.sh

echo
echo "Setup and tests completed. Starting Laravel on port 8000."
echo "Codespaces should forward port 8000 automatically."
echo "Press Ctrl+C to stop the development server."
exec php artisan serve --host=0.0.0.0 --port=8000
