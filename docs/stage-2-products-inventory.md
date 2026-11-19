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

## Data integrity rules

- All money is stored as decimal and displayed in PKR.
- Shoe stock is stored as whole units; negative stock is not allowed.
- A variant is identified by product + size + colour.
- SKU is mandatory and globally unique. Barcode is optional and globally unique when supplied.
- Stock is always branch-specific; no shared unallocated stock bucket.
- Purchase receiving and transfer dispatch/receipt must update stock and append movement records in database transactions.
- Historical transactions and movements must not be physically deleted.
- No sample branches, employees, suppliers, purchases or stock are seeded.

## Implementation sequence

A. Catalogue and variant management.
B. Branch stock ledger and safe stock adjustments.
C. Suppliers and purchase receiving.
D. Inter-branch transfer workflow.
E. Low-stock views, role restrictions and full regression tests.

## Safety

New migrations are additive. They do not drop, truncate or reset existing tables or records. No real branch or product data is inserted by migrations.
