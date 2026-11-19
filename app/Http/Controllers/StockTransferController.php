<?php

namespace App\\Http\\Controllers;

use App\\Models\\Branch;
use App\\Models\\ProductVariant;
use App\\Models\\StockTransfer;
use App\\Services\\StockService;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Str;
use Illuminate\\Validation\\Rule;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $transfers = StockTransfer::with(['fromBranch', 'toBranch', 'items.productVariant.product'])
            ->when(! $user->hasRole('owner'), fn ($q) => $q->where(fn ($w) => $w->where('from_branch_id', $user->branch_id)->orWhere('to_branch_id', $user->branch_id)))
            ->latest()->paginate(20);
        return view('transfers.index', compact('transfers'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        return view('transfers.create', [
            'branches' => $user->hasRole('owner') ? $branches : $branches->where('id', '!=', $user->branch_id),
            'sourceBranches' => $user->hasRole('owner') ? $branches : $branches->where('id', $user->branch_id),
            'variants' => ProductVariant::with('product')->where('is_active', true)->orderBy('sku')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'from_branch_id' => ['required', 'integer', 'different:to_branch_id', Rule::exists('branches', 'id')->where('is_active', true)],
            'to_branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_variant_id' => ['required', 'integer', 'distinct', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);
        abort_unless($user->hasRole('owner') || $user->branch_id === (int) $data['from_branch_id'], 403);
        $transfer = DB::transaction(function () use ($data, $user) {
            $transfer = StockTransfer::create([
                'transfer_number' => 'TRF-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'from_branch_id' => $data['from_branch_id'], 'to_branch_id' => $data['to_branch_id'],
                'requested_by' => $user->id, 'status' => 'pending', 'notes' => $data['notes'] ?? null,
            ]);
            foreach ($data['items'] as $item) $transfer->items()->create($item);
            return $transfer;
        });
        return redirect()->route('transfers.index')->with('success', 'Transfer request created. Stock is not deducted until approval.');
    }

    public function approve(Request $request, StockTransfer $transfer, StockService $stockService)
    {
        $user = $request->user();
        abort_unless($user->hasRole('owner') || $user->branch_id === $transfer->from_branch_id, 403);
        DB::transaction(function () use ($transfer, $user, $stockService) {
            $transfer = StockTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            abort_unless($transfer->status === 'pending', 422, 'Only pending transfers can be approved.');
            foreach ($transfer->items as $item) {
                $stockService->change($transfer->from_branch_id, $item->product_variant_id, -$item->quantity, $user->id, 'transfer_out', 'stock_transfer', $transfer->id, 'Approved transfer '.$transfer->transfer_number);
            }
            $transfer->update(['status' => 'in_transit', 'approved_by' => $user->id, 'approved_at' => now()]);
        });
        return back()->with('success', 'Transfer approved; source stock deducted and items are in transit.');
    }

    public function receive(Request $request, StockTransfer $transfer, StockService $stockService)
    {
        $user = $request->user();
        abort_unless($user->hasRole('owner') || $user->branch_id === $transfer->to_branch_id, 403);
        DB::transaction(function () use ($transfer, $user, $stockService) {
            $transfer = StockTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            abort_unless($transfer->status === 'in_transit', 422, 'Only in-transit transfers can be received.');
            foreach ($transfer->items as $item) {
                $stockService->change($transfer->to_branch_id, $item->product_variant_id, $item->quantity, $user->id, 'transfer_in', 'stock_transfer', $transfer->id, 'Received transfer '.$transfer->transfer_number);
                $item->update(['received_quantity' => $item->quantity]);
            }
            $transfer->update(['status' => 'received', 'received_by' => $user->id, 'received_at' => now()]);
        });
        return back()->with('success', 'Transfer received and destination stock updated.');
    }
}
