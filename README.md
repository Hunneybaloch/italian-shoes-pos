# Italian Shoes – Footwear POS & ERP

A centralized, web-based Point of Sale and Enterprise Resource Planning system for **Italian Shoes, Quetta, Balochistan, Pakistan**.

The initial deployment is designed for three branches, with the ability to add more branches later.

## Project goals

- Let the owner monitor all branches remotely from one dashboard.
- Give each employee access only to their assigned branch and permitted functions.
- Provide a simple interface for shop staff.
- Build a reliable foundation for footwear products, sizes, variants, inventory, sales, returns and accounting.

## Approved architecture

| Area | Decision |
|---|---|
| Application | Web-based application |
| Backend | Laravel 13 (planned) |
| Runtime | PHP 8.4+ (verify hosting compatibility before deployment) |
| Database | MySQL-compatible relational database |
| Frontend | HTML, CSS, JavaScript and Bootstrap 5 |
| Currency | PKR |
| Time zone | Asia/Karachi |
| Branches | Three initially; expandable |
| Employee login | Email and password |
| Employee assignment | One assigned branch per employee |
| Owner dashboard | Consolidated view with branch filters |
| Hosting | To be selected after compatibility and cost review |

Technology versions must be rechecked against the chosen development environment and hosting provider before installation or deployment.

## User roles

- **Owner:** Access to all branches, consolidated reports, users, branches and system settings.
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

Products, stock operations, purchasing, POS transactions, returns and accounting are intentionally out of scope until their later stages.

## Development and approval workflow

**Plan → Design → Develop → Test → Analyze → Fix → Retest → Owner approval**

Each stage must be tested and approved before moving to the next stage.

## Local setup

The Laravel application scaffold will be added after the local PHP and Composer environment is installed and verified. Do not commit local environment files or credentials.

## Security notes

- Never commit `.env`, passwords, API keys, database credentials, or production data.
- Use hashed passwords and server-side authorization.
- Enforce branch-level data access on every relevant request.
- Preserve audit history for important operations.
- Use database transactions for operations that must succeed or fail together.
