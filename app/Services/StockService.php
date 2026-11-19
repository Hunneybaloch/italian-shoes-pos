<?php

namespace App\\Services;

use App\\Models\\BranchStock;
use App\\Models\\StockMovement;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\ValidationException;

class StockService
{
    public function change(int $branchId, int $variantId, int $delta, ?int $userId, string $type, ?string $referenceType = null, ?int $referenceId = null, ?string $notes = null): BranchStock
    {
        if ($delta === 0) {
            throw ValidationException::withMessages(['quantity' => 'Stock change cannot be zero.']);
        }

        DB::table('branch_stocks')->insertOrIgnore([
            'branch_id' => $branchId,
            'product_variant_id' => $variantId,
            'quantity' => 0,
            'reorder_level' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stock = BranchStock::query()
            ->where('branch_id', $branchId)
            ->where('product_variant_id', $variantId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($stock->quantity + $delta < 0) {
            throw ValidationException::withMessages(['quantity' => 'Insufficient stock. Negative stock is not allowed.']);
        }

        $stock->quantity += $delta;
        $stock->save();

        StockMovement::create([
            'branch_id' => $branchId,
            'product_variant_id' => $variantId,
            'user_id' => $userId,
            'movement_type' => $type,
            'quantity_change' => $delta,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
        ]);

        return $stock;
    }
}
