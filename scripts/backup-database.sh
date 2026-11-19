#!/usr/bin/env bash
set -euo pipefail
ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"
if [[ -f .env ]]; then
  eval "$(php -r '
    require "vendor/autoload.php";
    Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
    foreach (["DB_HOST","DB_PORT","DB_DATABASE","DB_USERNAME","DB_PASSWORD"] as $key) {
      $value = $_ENV[$key] ?? $_SERVER[$key] ?? "";
      echo "export ".$key."=".escapeshellarg($value)."\n";
    }
  ')"
fi
if [[ -z "$DB_HOST" || -z "$DB_DATABASE" || -z "$DB_USERNAME" ]]; then echo "DB_HOST, DB_DATABASE and DB_USERNAME are required." >&2; exit 1; fi
if ! command -v mysqldump >/dev/null 2>&1; then echo "mysqldump is not installed." >&2; exit 1; fi
BACKUP_DIR="$ROOT_DIR/storage/app/private/backups"
mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"
umask 077
STAMP="$(date +%Y%m%d_%H%M%S)"
OUTPUT="$BACKUP_DIR/$DB_DATABASE-$STAMP.sql.gz"
if [[ -z "$DB_PORT" ]]; then DB_PORT=3306; fi
MYSQL_PWD="$DB_PASSWORD" mysqldump --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" --single-transaction --routines --triggers --events --default-character-set=utf8mb4 "$DB_DATABASE" | gzip > "$OUTPUT"
echo "Database backup created: $OUTPUT"
echo "Keep an encrypted copy outside this server. Never place backups in public/."
