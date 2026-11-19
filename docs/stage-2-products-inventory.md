# Stage 2 — Products & Inventory

## Purpose

Build one centrally managed shoe catalogue with branch-specific stock. The Owner can monitor all branches; branch employees are restricted to their assigned branch by server-side authorization.

## Agreed scope

1. Categories and brands.
2. Shoe products/models.
3. Size and colour variants.
4. Unique SKU and optional barcode for each variant.
5. Stock quantity and reorder threshold per branch and variant.
6. Suppliers and purchase receiving.
7. Inter-branch stock transfers.
8. Low-stock reporting.

## Data design

- `categories`, `brands`: shared catalogue lookup data.
- `products`: central shoe model, linked to a category and optional brand. Soft deletion preserves historical references.
- `product_variants`: size/colour combination, unique SKU, optional unique barcode, cost and selling prices.
- `branch_stocks`: one row per branch and variant, with a unique branch/variant pair.
- `stock_movements`: append-only quantity history tied to branch, variant and user.
- `suppliers`, `purchases`, `purchase_items`: supplier purchasing and branch receiving.
- `stock_transfers`, `stock_transfer_items`: auditable movement between two different branches.

## Implemented functionality

- Category and brand creation, editing, activation and deactivation.
- Product list with search by product/model/SKU/barcode, category filter and active-status filter.
- Product creation and editing with multiple size/colour variants.
- Required unique SKU, optional unique barcode, cost price and selling price in PKR.
- Product activation/deactivation instead of destructive deletion.
- Branch stock ledger with transaction-protected stock adjustments.
- Per-branch reorder thresholds and low-stock filtering.
- Supplier directory with create, edit and activate/deactivate.
- Purchase receiving with supplier invoice, variants, quantities, unit costs and discounts. Stock and movement ledger update in one transaction.
- Inter-branch transfer request, source approval/dispatch and destination receipt.
- Server-side permission middleware and branch isolation.
- Automated tests for catalogue, stock integrity, purchase receiving, transfers and role restrictions.

## Data integrity rules

- All money is stored as decimal and displayed in PKR.
- Shoe stock is stored as whole units; negative stock is not allowed.
- A variant is identified by product + size + colour.
- SKU is mandatory and globally unique. Barcode is optional and globally unique when supplied.
- Stock is always branch-specific; no shared unallocated stock bucket.
- Purchase receiving and transfer dispatch/receipt update stock and append movement records in database transactions.
- Historical transactions and movements must not be physically deleted.
- No sample branches, employees, suppliers, purchases or stock are seeded.

## Safety

New migrations are additive. They do not drop, truncate or reset existing tables or records. No real branch or product data is inserted by migrations.

## Validation

The complete development branch has automated coverage for inventory operations. Latest GitHub Actions validation passed on SQLite and MySQL 8.4. Final manual testing on the actual shop environment remains required.
