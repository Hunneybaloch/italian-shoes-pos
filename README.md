# Italian Shoes – Footwear POS & ERP

A centralized, web-based Point of Sale and Enterprise Resource Planning system for **Italian Shoes, Quetta, Balochistan, Pakistan**.

The system is designed for three initial branches, with the ability to add more branches later.

## Project goals

- Let the Owner monitor all branches remotely from one dashboard.
- Give each employee access only to their assigned branch and permitted functions.
- Provide a simple interface for shop staff.
- Build a reliable foundation for footwear products, sizes, variants, inventory, sales, returns and accounting.

## Implemented technology

| Area | Current implementation |
|---|---|
| Application | Laravel 13 web application |
| PHP | PHP 8.4.1+ required by the currently locked Symfony packages (development uses PHP 8.5) |
| Database | MySQL/MariaDB for the intended deployment |
| Codespaces development | Isolated SQLite database |
| Frontend build | Vite, Tailwind CSS |
| Currency | PKR |
| Time zone | Asia/Karachi |
| Branches | Owner-created; expandable |
| Authentication | Username or email and password |
| Employee assignment | One assigned branch per employee |
| Hosting | To be selected after compatibility and cost review |

Codespaces uses SQLite because its prebuilt PHP runtime may not include the MySQL PDO extension. The production database remains MySQL/MariaDB; the Codespaces database is local to that development environment.

## User roles

- **Owner:** System-wide access to all branches, users, branches and settings.
- **Branch Manager:** Access to the assigned branch and authorized branch operations.
- **Cashier:** Access to permitted sales operations for the assigned branch.
- **Stock Manager:** Access to permitted inventory operations for the assigned branch.

Permissions must be enforced on the server, not only by hiding interface elements.

## Planned development stages

1. Foundation and system design
2. Products and inventory management
3. POS and sales management
4. Returns, exchanges and customer management
5. Accounting and business reports
6. Advanced features, security and deployment

## Stage 1 scope

Stage 1 covers project setup, database foundation, authentication, roles and permissions, branch assignment, audit logging, and owner/branch dashboard foundations.

Products, stock operations, purchasing, POS transactions, returns and accounting remain out of scope until their later stages.

## Development and approval workflow

**Plan → Design → Develop → Test → Analyze → Fix → Retest → Owner approval**

Each stage must be tested and approved before moving to the next stage.

## GitHub Codespaces quick start

Open this repository in GitHub Codespaces. In the Codespaces terminal, run this single command:

```bash
bash scripts/codespaces-setup.sh
```

The script will:

1. Check PHP, Composer, Node.js, npm and SQLite support.
2. Create a Codespaces-only `.env` from `.env.codespaces.example` if one does not already exist.
3. Create the local SQLite database file.
4. Install Composer and npm dependencies.
5. Generate an application key if one is missing.
6. Apply pending migrations and seed the reference roles and permissions.
7. Build the frontend assets.
8. Run the automated Laravel tests.
9. Start the development server on port 8000.

Codespaces should forward port 8000. Open the forwarded URL to view the application.

**Safety:** The setup script refuses to migrate if an existing `.env` does not use SQLite. It does not run `migrate:fresh`, drop tables, or create real branches or employee accounts. It never uses the XAMPP database.

To run tests again later:

```bash
bash scripts/codespaces-test.sh
```

## Automated GitHub checks

The workflow in `.github/workflows/laravel-ci.yml` checks Composer configuration, installs PHP dependencies, applies migrations and reference seed data to an isolated SQLite database, builds frontend assets and runs the feature tests. MySQL/MariaDB deployment compatibility still requires a separate staging verification.

## Local XAMPP setup

For local Windows development, use the existing project `.env` and XAMPP MySQL configuration. Do not copy the Codespaces SQLite configuration into the XAMPP environment unless you intentionally want a separate local SQLite database.

## Security notes

- Never commit `.env`, passwords, API keys, database credentials, or production data.
- Use hashed passwords and server-side authorization.
- Enforce branch-level data access on every relevant request.
- Preserve audit history for important operations.
- Use database transactions for operations that must succeed or fail together.
- Keep development and test databases separate from production data.

**Dependency note:** `composer.json` currently declares `^8.3`, but the committed lock file contains Symfony 8.1 packages that require PHP 8.4.1 or newer. CI and Codespaces use PHP 8.5 to match the lock file. The declared minimum and lock file should be reconciled before supporting PHP 8.3.
