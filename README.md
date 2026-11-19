# Italian Shoes POS & Branch Management System

A Laravel-based, multi-branch footwear Point of Sale and inventory management system for **Italian Shoes, Quetta, Balochistan, Pakistan**.

The development branch contains the complete implementation work for the six planned stages. The work remains in draft pull requests and has not been merged into `main`.

## Main modules

1. **Foundation:** secure login, Owner account creation, branches, role-based permissions and branch-scoped access.
2. **Products:** shoe categories, brands, models, sizes, colours, unique SKUs and optional barcodes.
3. **Inventory:** branch stock, stock adjustments, reorder thresholds, stock ledger, suppliers, purchase receiving and inter-branch transfers.
4. **POS and sales:** barcode/SKU search, cart, customer selection, discount, configured tax, payment, change, sales history and printable receipts.
5. **Customers and returns:** customer directory, partial returns, refunds, exchanges and replacement stock handling.
6. **Accounting:** expenses, supplier payments, cashbook, cash movement ledger, business reports, inventory valuation and sales CSV export.
7. **Administration:** user/employee management, branch-scoped role assignment, system settings, activity logs and database backup/restore scripts.

## Roles

- **Owner:** system-wide access, all branches, employee accounts and system settings.
- **Branch Manager:** authorized management of the assigned branch, including branch staff and branch operations.
- **Cashier:** permitted POS, sales and customer operations in the assigned branch.
- **Stock Manager:** permitted catalogue, inventory, purchasing and transfer operations in the assigned branch.

Permissions and branch access are checked server-side. Hiding a menu item is not used as the only security control.

## Technology and requirements

- Laravel 13
- PHP 8.4.1 or newer (the committed dependency lock currently requires this; CI uses PHP 8.5)
- Composer
- Node.js 22 and npm
- MySQL 8.0+ or supported MariaDB for the target installation
- Currency: PKR
- Time zone: Asia/Karachi

The GitHub CI pipeline tests migrations and feature tests on both SQLite and MySQL 8.4. Production should use MySQL/MariaDB and HTTPS. A successful CI run is not a substitute for the final live test on the shop's actual hardware.

## Local installation (Windows / XAMPP)

1. Install a compatible PHP runtime (8.4.1+), Composer, Node.js 22 and MySQL/MariaDB.
2. Create a new empty database, for example `italian_shoes_pos`.
3. Open the project folder in VS Code.
4. Copy `.env.example` to `.env`:

   PowerShell:
   ```powershell
   Copy-Item .env.example .env
   ```

5. Edit `.env` and set the database name, username, password, application URL and `APP_TIMEZONE=Asia/Karachi`.
6. Install PHP dependencies:
   ```bash
   composer install
   ```
7. Generate the application key:
   ```bash
   php artisan key:generate
   ```
8. Apply migrations and seed only the reference roles/permissions:
   ```bash
   php artisan migrate --seed
   ```
9. Create the first Owner account interactively:
   ```bash
   php artisan app:create-owner
   ```
   Enter the Owner name, username, email and password when prompted. Passwords must be at least 5 characters. The command refuses to create a second initial Owner.
10. Install and build frontend assets:
    ```bash
    npm ci
    npm run build
    ```
11. Start Laravel:
    ```bash
    php artisan serve
    ```
12. Open `http://127.0.0.1:8000` and sign in with the Owner credentials created in step 9.

**Important:** Run this on a new, empty test database first. Do not use `php artisan migrate:fresh`, do not drop existing tables, and do not point the application at a database containing unrelated or important records. The setup does not insert demo branches, employees, products, customers, purchases, stock or sales.

## GitHub Codespaces

Codespaces uses its own isolated SQLite database. It does not use your XAMPP MySQL database.

For an existing Codespace, switch to the development branch and run:

```bash
git fetch origin stage-6-admin-security
git checkout stage-6-admin-security
bash scripts/codespaces-setup.sh
php artisan app:create-owner
```

For a new Codespace, create it from the `stage-6-admin-security` branch, then run the setup script and Owner command above.

Run automated tests again with:

```bash
php artisan test
```

## Automated validation

Latest GitHub Actions run for the complete development branch:
- SQLite: **37 tests passed, 160 assertions**
- MySQL 8.4: **37 tests passed, 160 assertions**
- Composer validation, frontend build and shell script syntax checks passed.

See the GitHub Actions workflow at `.github/workflows/laravel-ci.yml`.

## Backup and restore

Read `docs/deployment-and-backup.md` before production deployment.

Create a compressed database backup:

```bash
bash scripts/backup-database.sh
```

Restore is destructive and requires explicit confirmation:

```bash
bash scripts/restore-database.sh --confirm storage/app/private/backups/your-backup.sql.gz
```

Keep encrypted backup copies outside the application server. Perform a restore drill on a separate staging database.

## User guide

See [docs/full-system-user-guide.md](docs/full-system-user-guide.md) for step-by-step instructions for branches, employees, catalogue, inventory, purchases, POS, returns, cashbook, reports and backups.

## Before going live

- [ ] Install on a clean staging database using the intended MySQL/MariaDB version.
- [ ] Create the Owner account and configure the store settings.
- [ ] Create each branch and its first Branch Manager.
- [ ] Create the remaining employees with the correct role and branch.
- [ ] Enter real categories, brands, products and variants.
- [ ] Record opening stock using approved stock adjustments or purchases.
- [ ] Test barcode scanner and receipt printer on the actual shop computers.
- [ ] Verify cash handling, payment terminal, returns and exchanges.
- [ ] Verify branch access with each role.
- [ ] Complete a backup and restore drill.
- [ ] Owner approves the final live test before production use.

## Development workflow

All six development stages are kept in draft pull requests. Nothing has been merged into `main`. The final review and full live test are still required before production use.
