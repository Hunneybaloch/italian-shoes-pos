<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branches = $user->hasRole('owner') ? Branch::where('is_active', true)->orderBy('name')->get() : collect();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $defaultTaxRate = (float) (SystemSetting::where('key', 'default_tax_rate')->value('value') ?? 0);
        $branchId = $user->hasRole('owner') ? $request->integer('branch_id') : $user->branch_id;
        return view('pos.index', compact('branches', 'customers', 'branchId', 'defaultTaxRate'));
    }

    public function search(Request $request)
    {
        $data = $request->validate([
            'q' => ['required','string','max:100'],
            'branch_id' => ['required','integer', 'exists:branches,id'],
        ]);
        $user = $request->user();
        abort_unless($user->hasRole('owner') || $user->branch_id === (int) $data['branch_id'], 403);
        $term = trim($data['q']);
        $variants = ProductVariant::with('product')
            ->where('is_active', true)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->where(function ($q) use ($term) {
                $q->where('sku', $term)->orWhere('barcode', $term)
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', '%'.$term.'%')->orWhere('model_code', 'like', '%'.$term.'%'));
            })->limit(15)->get()
            ->map(function ($v) use ($data) {
                $qty = (int) (DB::table('branch_stocks')->where('branch_id', $data['branch_id'])->where('product_variant_id', $v->id)->value('quantity') ?? 0);
                return ['id'=>$v->id,'name'=>$v->product->name,'size'=>$v->size,'color'=>$v->color,'sku'=>$v->sku,'barcode'=>$v->barcode,'price'=>(float)$v->selling_price,'stock'=>$qty];
            });
        return response()->json($variants);
    }

    public function store(Request $request, StockService $stockService)
    {
        $user = $request->user();
        $data = $request->validate([
            'branch_id' => ['required','integer',Rule::exists('branches','id')->where('is_active',true)],
            'customer_id' => ['nullable','integer',Rule::exists('customers','id')->where('is_active',true)],
            'discount_amount' => ['nullable','numeric','min:0','max:9999999999'],
            'tax_amount' => ['nullable','numeric','min:0','max:9999999999'],
            'payment_method' => ['required',Rule::in(['cash','card','bank_transfer','mobile_wallet','other'])],
            'payment_reference' => ['nullable','string','max:120'],
            'amount_paid' => ['required','numeric','min:0','max:9999999999'],
            'notes' => ['nullable','string','max:2000'],
            'items' => ['required','array','min:1','max:100'],
            'items.*.product_variant_id' => ['required','integer','distinct','exists:product_variants,id'],
            'items.*.quantity' => ['required','integer','min:1','max:10000'],
        ]);
        abort_unless($user->hasRole('owner') || $user->branch_id === (int) $data['branch_id'], 403);

        $sale = DB::transaction(function () use ($data, $user, $stockService) {
            $variants = ProductVariant::whereIn('id', collect($data['items'])->pluck('product_variant_id'))->where('is_active', true)->with('product')->get()->keyBy('id');
            abort_if($variants->count() !== count($data['items']), 422, 'One or more products are unavailable.');
            $subtotal = 0.0;
            foreach ($data['items'] as $item) {
                $v = $variants->get($item['product_variant_id']);
                abort_if(! $v->product->is_active, 422, 'One or more products are inactive.');
                $subtotal += (float) $v->selling_price * (int) $item['quantity'];
            }
            $subtotal = round($subtotal, 2);
            $discount = round((float) ($data['discount_amount'] ?? 0), 2);
            $tax = round((float) ($data['tax_amount'] ?? 0), 2);
            abort_if($discount > $subtotal, 422, 'Discount cannot exceed the subtotal.');
            $total = round($subtotal - $discount + $tax, 2);
            $paid = round((float) $data['amount_paid'], 2);
            abort_if($paid < $total, 422, 'Amount paid must cover the sale total.');

            $sale = Sale::create([
                'receipt_number' => 'POS-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'branch_id' => $data['branch_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'cashier_id' => $user->id,
                'status' => 'completed',
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'amount_paid' => $paid,
                'change_amount' => round($paid - $total, 2),
                'notes' => $data['notes'] ?? null,
                'sold_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $variant = $variants->get($item['product_variant_id']);
                $qty = (int) $item['quantity'];
                $stockService->change((int)$data['branch_id'], $variant->id, -$qty, $user->id, 'sale', 'sale', $sale->id, 'POS sale '.$sale->receipt_number);
                $sale->items()->create([
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'sku' => $variant->sku,
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'quantity' => $qty,
                    'unit_price' => $variant->selling_price,
                    'unit_cost' => $variant->cost_price,
                    'line_total' => round((float)$variant->selling_price * $qty, 2),
                ]);
            }
            $sale->payments()->create([
                'method' => $data['payment_method'],
                'amount' => $total,
                'reference' => $data['payment_reference'] ?? null,
                'paid_at' => now(),
            ]);
            if ($data['payment_method'] === 'cash') {
                CashTransaction::create([
                    'branch_id' => $data['branch_id'], 'created_by' => $user->id,
                    'direction' => 'in', 'type' => 'sale', 'amount' => $total,
                    'payment_method' => 'cash', 'reference_type' => 'sale', 'reference_id' => $sale->id,
                    'description' => 'Cash received for '.$sale->receipt_number, 'transacted_at' => now(),
                ]);
            }
            return $sale;
        });

        return redirect()->route('sales.receipt', $sale)->with('success', 'Sale completed successfully.');
    }

    public function sales(Request $request)
    {
        $user = $request->user();
        $sales = Sale::with(['branch','customer','cashier'])
            ->when(! $user->hasRole('owner'), fn ($q) => $q->where('branch_id', $user->branch_id))
            ->latest('sold_at')->paginate(25);
        return view('sales.index', compact('sales'));
    }

    public function receipt(Request $request, Sale $sale)
    {
        $user = $request->user();
        abort_unless($user->hasRole('owner') || $user->branch_id === $sale->branch_id, 403);
        $sale->load(['branch','customer','cashier','items','payments']);
        $settings = SystemSetting::pluck('value', 'key');
        return view('sales.receipt', compact('sale', 'settings'));
    }
}
