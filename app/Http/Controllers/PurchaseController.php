<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $purchases = Purchase::with(['branch', 'supplier', 'items.productVariant.product'])
            ->when(! $user->hasRole('owner'), fn ($q) => $q->where('branch_id', $user->branch_id))
            ->latest()->paginate(20);
        return view('purchases.index', compact('purchases'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $branches = $user->hasRole('owner') ? Branch::where('is_active', true)->orderBy('name')->get() : Branch::whereKey($user->branch_id)->get();
        return view('purchases.create', [
            'branches' => $branches,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'variants' => ProductVariant::with('product')->where('is_active', true)->orderBy('sku')->get(),
        ]);
    }

    public function store(Request $request, StockService $stockService)
    {
        $user = $request->user();
        $data = $request->validate([
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('is_active', true)],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers','id')->where('is_active',true)],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['required', 'date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_variant_id' => ['required', 'integer', 'distinct', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:9999999999'],
        ]);
        abort_unless($user->hasRole('owner') || $user->branch_id === (int) $data['branch_id'], 403);
        $purchase = DB::transaction(function () use ($data, $user, $stockService) {
            $subtotal = collect($data['items'])->sum(fn ($i) => (float) $i['unit_cost'] * (int) $i['quantity']);
            $discount = (float) ($data['discount_amount'] ?? 0);
            if ($discount > $subtotal) {
                abort(422, 'Discount cannot exceed the purchase subtotal.');
            }
            $purchase = Purchase::create([
                'branch_id' => $data['branch_id'], 'supplier_id' => $data['supplier_id'] ?? null,
                'created_by' => $user->id, 'invoice_number' => $data['invoice_number'] ?? null,
                'status' => 'received', 'purchase_date' => $data['purchase_date'],
                'received_at' => now(), 'subtotal' => $subtotal, 'discount_amount' => $discount,
                'total_amount' => $subtotal - $discount, 'notes' => $data['notes'] ?? null,
            ]);
            foreach ($data['items'] as $item) {
                $variant = ProductVariant::whereKey($item['product_variant_id'])->where('is_active', true)->firstOrFail();
                $line = round((float) $item['unit_cost'] * (int) $item['quantity'], 2);
                $purchase->items()->create([
                    'product_variant_id' => $variant->id, 'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'], 'line_total' => $line,
                ]);
                $stockService->change((int) $data['branch_id'], $variant->id, (int) $item['quantity'], $user->id, 'purchase_received', 'purchase', $purchase->id, 'Purchase received');
                $variant->update(['cost_price' => $item['unit_cost']]);
            }
            return $purchase;
        });
        return redirect()->route('purchases.index')->with('success', 'Purchase recorded and stock received successfully.');
    }
}
