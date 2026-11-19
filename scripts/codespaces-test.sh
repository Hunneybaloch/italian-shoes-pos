#!/usr/bin/env bash
set -Eeuo pipefail

cd "$(dirname "$0")/.."

if [[ ! -f .env ]]; then
    echo "Missing .env. Run: bash scripts/codespaces-setup.sh"
    exit 1
fi

if ! grep -Eq '^DB_CONNECTION="?sqlite"?$' .env; then
    echo "Safety stop: this test script only runs with DB_CONNECTION=sqlite in .env."
    echo "No database was changed."
    exit 1
fi

if ! php -r 'exit(extension_loaded("pdo_sqlite") ? 0 : 1);'; then
    echo "PHP extension pdo_sqlite is required."
    exit 1
fi

php artisan test
