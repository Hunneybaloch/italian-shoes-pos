<?php

namespace App\\Http\\Controllers;

use App\\Models\\Branch;
use App\\Models\\BranchStock;
use App\\Models\\ProductVariant;
use App\\Models\\StockMovement;
use App\\Services\\StockService;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\Rule;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branchId = $user->hasRole('owner') ? $request->integer('branch_id') : $user->branch_id;
        $stocks = BranchStock::with(['branch', 'productVariant.product'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->boolean('low_stock'), fn ($q) => $q->whereColumn('quantity', '<=', 'reorder_level'))
            ->orderBy('branch_id')->paginate(25)->withQueryString();
        $branches = $user->hasRole('owner') ? Branch::orderBy('name')->get() : collect();
        return view('inventory.index', compact('stocks', 'branches', 'branchId'));
    }

    public function adjust(Request $request, StockService $stockService)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('is_active', true)],
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'direction' => ['required', Rule::in(['in', 'out'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'reorder_level' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'notes' => ['required', 'string', 'max:1000'],
        ]);
        $user = $request->user();
        abort_unless($user->hasRole('owner') || $user->branch_id === (int) $data['branch_id'], 403);
        $variant = ProductVariant::whereKey($data['product_variant_id'])->where('is_active', true)->firstOrFail();
        DB::transaction(fn () => $stockService->change(
            (int) $data['branch_id'], $variant->id,
            $data['direction'] === 'in' ? (int) $data['quantity'] : -(int) $data['quantity'],
            $user->id, 'adjustment', null, null, $data['notes']
        );
        if (array_key_exists('reorder_level', $data) && $data['reorder_level'] !== null) {
            $stock->update(['reorder_level' => (int) $data['reorder_level']]);
        }
        });
        return back()->with('success', 'Stock adjustment saved and recorded in the stock ledger.');
    }

    public function history(Request $request)
    {
        $user = $request->user();
        $movements = StockMovement::with(['branch', 'productVariant.product', 'user'])
            ->when(! $user->hasRole('owner'), fn ($q) => $q->where('branch_id', $user->branch_id))
            ->latest()->paginate(30);
        return view('inventory.history', compact('movements'));
    }
}
