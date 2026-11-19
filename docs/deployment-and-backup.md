# Deployment, database and backup guide

## Application requirements

- PHP 8.4.1 or newer (CI currently uses PHP 8.5).
- Composer.
- Node.js 22 and npm for building frontend assets.
- MySQL 8.0+ or a supported MariaDB release for deployment.
- Laravel-required PHP extensions, including PDO MySQL, OpenSSL, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, Fileinfo and cURL.
- HTTPS enabled for real deployments.

CI uses isolated SQLite for fast tests. A successful SQLite run does not prove MySQL/MariaDB compatibility. Run migrations and feature tests against an empty staging database using the same engine/version intended for production before go-live.

## Initial production setup

1. Create a database and a dedicated database user with access only to that database.
2. Upload the project outside the web root where possible; configure the web server document root to Laravel's `public/` directory.
3. Copy `.env.example` to `.env` and set `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, database credentials and timezone `Asia/Karachi`.
4. Run `composer install --no-dev --optimize-autoloader`.
5. Run `php artisan key:generate` once for a new installation.
6. Run `php artisan migrate --force`. Never use `migrate:fresh` on a database containing business records.
7. Run `npm ci` and `npm run build`, or deploy the built `public/build` assets.
8. Set writable permissions for `storage/` and `bootstrap/cache/`.
9. Run `php artisan config:cache`, `php artisan route:cache` and `php artisan view:cache`.
10. Configure HTTPS, scheduled tasks and a monitored backup schedule.
11. Create the initial Owner account using the installation process. Do not create demo branches, products, stock, sales or customers.

Never commit production `.env` credentials.

## Database backup

The backup script uses `mysqldump` and writes compressed SQL files under `storage/app/private/backups`, outside the public directory:

```bash
bash scripts/backup-database.sh
```

The script reads database variables from `.env`. Ensure `mysqldump` and `gzip` are installed. Restrict access to the backup directory and copy encrypted backups to separate secure storage. A backup kept only on the same server is not sufficient protection.

Recommended policy:
- Daily automated backup, with more frequent backups if transaction volume requires it.
- Keep daily and weekly restore points.
- Encrypt off-server copies.
- Monitor backup success and disk space.
- Perform restore drills on a separate staging database.

## Restore (destructive)

Restore overwrites the configured target database. Use only after confirming the target database and selected backup file:

```bash
bash scripts/restore-database.sh --confirm storage/app/private/backups/your-backup.sql.gz
```

The script requires the exact database name to be typed before import and refuses files outside the private backup directory. Do not restore over production unless the business owner has approved a maintenance window and the current database has first been backed up.

## Go-live checklist

- [ ] MySQL/MariaDB migration run on a clean staging database.
- [ ] All feature tests pass on the target database engine.
- [ ] Owner account created securely.
- [ ] Branches and employees assigned the correct role and branch.
- [ ] HTTPS and production debug settings verified.
- [ ] Backup and restore drill completed.
- [ ] Receipt printer and barcode scanner tested on actual shop hardware.
- [ ] Cash drawer and payment terminal workflow verified.
- [ ] Owner signs off after full end-to-end testing.
