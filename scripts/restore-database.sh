#!/usr/bin/env bash
set -euo pipefail
ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"
if [[ "$1" != "--confirm" || -z "$2" ]]; then
  echo "Usage: bash scripts/restore-database.sh --confirm storage/app/private/backups/backup.sql.gz" >&2
  echo "WARNING: This overwrites data in the configured database." >&2
  exit 2
fi
BACKUP_DIR="$ROOT_DIR/storage/app/private/backups"
BACKUP_PATH="$(realpath "$2")"
EXPECTED_PREFIX="$(realpath -m "$BACKUP_DIR")/"
case "$BACKUP_PATH" in "$EXPECTED_PREFIX"*) ;; *) echo "Refusing file outside private backup directory." >&2; exit 1 ;; esac
if [[ ! -f "$BACKUP_PATH" ]]; then echo "Backup file not found." >&2; exit 1; fi
if [[ -f .env ]]; then
  eval "$(php -r '
    require "vendor/autoload.php";
    Dotenv\\Dotenv::createImmutable(__DIR__)->safeLoad();
    foreach (["DB_HOST","DB_PORT","DB_DATABASE","DB_USERNAME","DB_PASSWORD"] as $key) {
      $value = $_ENV[$key] ?? $_SERVER[$key] ?? "";
      echo "export ".$key."=".escapeshellarg($value)."\\n";
    }
  ')"
fi
if [[ -z "$DB_HOST" || -z "$DB_DATABASE" || -z "$DB_USERNAME" ]]; then echo "DB_HOST, DB_DATABASE and DB_USERNAME are required." >&2; exit 1; fi
if ! command -v mysql >/dev/null 2>&1; then echo "mysql client is not installed." >&2; exit 1; fi
echo "You are about to overwrite database: $DB_DATABASE"
read -r -p "Type the exact database name to continue: " CONFIRM
if [[ "$CONFIRM" != "$DB_DATABASE" ]]; then echo "Restore cancelled."; exit 1; fi
if [[ -z "$DB_PORT" ]]; then DB_PORT=3306; fi
gzip -dc "$BACKUP_PATH" | MYSQL_PWD="$DB_PASSWORD" mysql --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" "$DB_DATABASE"
echo "Restore completed. Verify application and data immediately."
