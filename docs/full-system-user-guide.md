# Italian Shoes POS — User Guide

This guide describes the implemented workflows. Use a clean staging database for the first run; do not enter real business records until the Owner has completed the live acceptance test.

## 1. First-time Owner setup

1. Install the application using the README.
2. Run `php artisan migrate --seed`.
3. Run `php artisan app:create-owner` and create the initial system-wide Owner login.
4. Sign in at `/login` using the username or email and password.
5. Open **Settings** and enter the store name, phone, address, receipt footer and default tax rate.
6. Create the business branches from **Branches**.

The Owner has no branch assignment and can view all branches.

## 2. Branches and employees

1. Open **Branches → Add Branch**.
2. Enter branch name, unique branch code, address and phone.
3. Create the first Branch Manager in the same form. Branch and first manager are created together.
4. Open **Employees → Create User** to add Cashiers and Stock Managers, or additional Branch Managers.
5. Assign each branch employee to exactly one active branch.
6. Use **Edit** or **Activate/Deactivate** to manage employee accounts.

Branch Managers can create and manage Cashier and Stock Manager accounts only for their own branch. They cannot create Owners or access other branches.

## 3. Product catalogue

1. Open **Products**.
2. Add the product categories and brands.
3. Open **Manage Categories & Brands** to rename, activate or deactivate them.
4. Choose **Add Product**.
5. Enter the shoe model name, category, optional brand and model code.
6. Add one or more variants. Each variant needs a size, colour, unique SKU, cost price and selling price. Barcode is optional.
7. Save the product.

Do not reuse SKUs or barcodes. Deactivate products that should no longer be sold; historical references remain.

## 4. Suppliers and purchases

1. Open **Suppliers** and create supplier records.
2. Open **Purchases → Record Purchase**.
3. Select the receiving branch, supplier, invoice number and purchase date.
4. Add each product variant, received quantity and unit cost.
5. Enter any purchase discount and save.
6. The system updates the receiving branch's stock and appends stock movement records in the same transaction.

Supplier payments are recorded separately under **Supplier Payments**. Each payment reduces the outstanding balance for the selected purchase. Overpayment is rejected.

## 5. Inventory and transfers

- **Inventory** shows on-hand stock by branch and variant.
- **Low stock only** filters items at or below their configured reorder level.
- **Adjust Stock** records a reasoned stock-in or stock-out operation and appends a stock movement.
- **Stock Ledger** shows the history of inventory movements.
- **Transfers** allows a branch to request stock from another branch.
- The source branch approves and dispatches the transfer. Source stock is deducted at approval.
- The destination branch confirms receipt. Destination stock is added only after receipt.

All stock changes use transactions and reject negative stock.

## 6. Point of Sale

1. Open **POS / New Sale**.
2. The Owner selects the branch. A branch employee is automatically limited to their assigned branch.
3. Scan a barcode or enter an SKU or product name in the search box.
4. Add the matching size/colour variant to the cart.
5. Adjust quantities within available stock.
6. Select a customer if applicable; otherwise use Walk-in Customer.
7. Apply a discount if authorized and review the configured tax rate.
8. Select payment method and enter the amount received.
9. Complete the sale. The system calculates totals server-side, checks stock and writes the sale, payment, stock deduction and ledger movement in one transaction.
10. Print the receipt from the receipt page.

The receipt number is generated automatically. The browser's displayed totals are not trusted by the server.

## 7. Customers, returns and exchanges

- Use **Customers** to add, search, edit and activate/deactivate customer records.
- From **Sales History**, choose **Return / Exchange** for a completed sale.
- Select the original sale items and quantities being returned.
- Enter a reason and refund method.
- For an exchange, select replacement variants and quantities. Any additional balance must be paid before the exchange is completed.
- Returned items are added back to stock; replacement items are deducted.
- A sale item cannot be returned beyond the original sold quantity less previous completed returns.

Returns and exchanges are transactional and are recorded in the return register and stock ledger.

## 8. Expenses, cashbook and reports

- **Expenses** records expense category, description, amount, date and payment method.
- Cash expenses automatically create cash-out entries.
- **Cashbook** displays cash in/out from cash sales, refunds, expenses, supplier payments and manual adjustments.
- **Supplier Payments** tracks paid amounts and outstanding balances on purchases.
- **Reports** filters by date and branch, and displays sales, returned/exchanged value, expenses, estimated profit, cash movement, inventory valuation and low stock.
- **Export Sales CSV** downloads the filtered sales register.

Gross and net profit are estimates based on sale item cost snapshots and returned item quantities. Inventory valuation uses current on-hand quantity multiplied by current variant cost price. Confirm accounting treatment with the business accountant before using the figures for statutory reporting.

## 9. Activity logs and settings

- **Activity Logs** shows recorded administrative actions and branch-scoped history.
- **Settings** is Owner-only and controls store identity, receipt text, default tax rate, PKR currency and Asia/Karachi timezone.
- Employee passwords are stored as hashes; password minimum is 5 characters as agreed.

## 10. Backup and restore

Read `docs/deployment-and-backup.md`. Backups must be stored outside the public web root and copied to encrypted off-server storage. Restore overwrites the configured database and must only be performed with Owner approval after a current backup is secured.

## 11. Acceptance test checklist

Before go-live, test at least:

- Owner login and logout.
- Create each branch and its first manager.
- Branch Manager creates a Cashier and Stock Manager.
- Confirm users cannot access other branches by changing URLs or request data.
- Create categories, brands, products and several size/colour variants.
- Receive a supplier purchase and verify branch stock and ledger.
- Adjust stock and verify insufficient stock is rejected.
- Transfer stock through request, approval and receipt.
- Complete cash and non-cash POS sales.
- Verify barcode/SKU search, discount, configured tax, change and printed receipt.
- Process a partial refund and an exchange; verify stock and cash ledger.
- Record an expense and supplier payment.
- Check cashbook, reports and CSV export.
- Deactivate a user and confirm login is rejected.
- Create a database backup and restore it to a separate staging database.
- Test the receipt printer and barcode scanner on the actual shop hardware.
