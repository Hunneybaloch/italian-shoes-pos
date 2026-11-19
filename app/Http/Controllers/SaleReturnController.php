<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaleReturnController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $returns = SaleReturn::with(['sale','branch','processor','items'])
            ->when(!$user->hasRole('owner'),fn($q)=>$q->where('branch_id',$user->branch_id))
            ->latest()->paginate(25);
        return view('returns.index',compact('returns'));
    }

    public function create(Request $request, Sale $sale)
    {
        $user=$request->user();
        abort_unless($user->hasRole('owner') || $user->branch_id===$sale->branch_id,403);
        $sale->load('items');
        $variants=ProductVariant::with('product')->where('is_active',true)->orderBy('sku')->get();
        return view('returns.create',compact('sale','variants'));
    }

    public function store(Request $request, Sale $sale, StockService $stockService)
    {
        $user=$request->user();
        abort_unless($user->hasRole('owner') || $user->branch_id===$sale->branch_id,403);
        $data=$request->validate([
            'return_type'=>['required',Rule::in(['refund','exchange'])],
            'reason'=>['required','string','max:500'],
            'refund_method'=>['nullable',Rule::in(['cash','card','bank_transfer','mobile_wallet','other'])],
            'items'=>['required','array','min:1','max:100'],
            'items.*.sale_item_id'=>['required','integer','distinct','exists:sale_items,id'],
            'items.*.quantity'=>['required','integer','min:1','max:10000'],
            'replacement_items'=>['nullable','array','max:100'],
            'replacement_items.*.product_variant_id'=>['required','integer','distinct','exists:product_variants,id'],
            'replacement_items.*.quantity'=>['required','integer','min:1','max:10000'],
            'payment_method'=>['required_if:return_type,exchange','nullable',Rule::in(['cash','card','bank_transfer','mobile_wallet','other'])],
            'amount_paid'=>['required_if:return_type,exchange','nullable','numeric','min:0','max:9999999999'],
        ]);

        $return=DB::transaction(function() use($sale,$data,$user,$stockService){
            $lockedSale=Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            $selected=collect($data['items']);
            $returnValue=0.0;
            $resolved=[];
            foreach($selected as $row){
                $saleItem=$lockedSale->items()->whereKey($row['sale_item_id'])->firstOrFail();
                $already=(int)DB::table('sale_return_items')
                    ->join('sale_returns','sale_returns.id','=','sale_return_items.sale_return_id')
                    ->where('sale_return_items.sale_item_id',$saleItem->id)
                    ->where('sale_returns.status','completed')->sum('sale_return_items.quantity');
                $qty=(int)$row['quantity'];
                abort_if($qty > ($saleItem->quantity-$already),422,'Return quantity exceeds the quantity originally sold minus previous returns.');
                $effectiveUnit = (float) $lockedSale->subtotal > 0
                    ? (float) $saleItem->unit_price * ((float) $lockedSale->total_amount / (float) $lockedSale->subtotal)
                    : 0;
                $returnValue += $effectiveUnit * $qty;
                $resolved[]=['sale_item'=>$saleItem,'quantity'=>$qty,'effective_unit'=>$effectiveUnit];
            }
            $returnValue=round($returnValue,2);
            $replacementRows=$data['replacement_items']??[];
            $replacementVariants=ProductVariant::whereIn('id',collect($replacementRows)->pluck('product_variant_id'))->where('is_active',true)->with('product')->get()->keyBy('id');
            $replacementTotal=0.0;
            foreach($replacementRows as $row){
                $v=$replacementVariants->get($row['product_variant_id']);
                abort_if(!$v || !$v->product->is_active,422,'Replacement item is unavailable.');
                $replacementTotal+=(float)$v->selling_price*(int)$row['quantity'];
            }
            $replacementTotal=round($replacementTotal,2);
            if($data['return_type']==='refund'){
                abort_if($replacementRows,422,'Replacement items are only used for exchanges.');
                $refund=$returnValue;
                $refundMethod=$data['refund_method']??'cash';
            } else {
                abort_if(!$replacementRows,422,'Select at least one replacement item for an exchange.');
                $refund=max(0,round($returnValue-$replacementTotal,2));
                $refundMethod=$refund>0?($data['refund_method']??'cash'):null;
                $extraDue=max(0,round($replacementTotal-$returnValue,2));
                abort_if($extraDue>0 && (float)($data['amount_paid']??0)<$extraDue,422,'Collect the exchange balance before completing the exchange.');
            }

            $record=SaleReturn::create([
                'return_number'=>'RET-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'sale_id'=>$lockedSale->id,'branch_id'=>$lockedSale->branch_id,'processed_by'=>$user->id,
                'return_type'=>$data['return_type'],'status'=>'completed','reason'=>$data['reason'],
                'returned_value'=>$returnValue,'refund_amount'=>$refund,'refund_method'=>$refundMethod,'processed_at'=>now(),
            ]);
            if ($refund > 0 && $refundMethod === 'cash') {
                CashTransaction::create([
                    'branch_id' => $lockedSale->branch_id, 'created_by' => $user->id,
                    'direction' => 'out', 'type' => 'sale_refund', 'amount' => $refund,
                    'payment_method' => 'cash', 'reference_type' => 'sale_return', 'reference_id' => $record->id,
                    'description' => 'Cash refund '.$record->return_number, 'transacted_at' => now(),
                ]);
            }
            foreach($resolved as $entry){
                $item=$entry['sale_item'];$qty=$entry['quantity'];
                $record->items()->create([
                    'sale_item_id'=>$item->id,'product_variant_id'=>$item->product_variant_id,
                    'quantity'=>$qty,'unit_refund'=>$entry['effective_unit'],'restocked'=>true,
                ]);
                $stockService->change($lockedSale->branch_id,$item->product_variant_id,$qty,$user->id,'sale_return','sale_return',$record->id,'Return '.$record->return_number);
            }

            if($data['return_type']==='exchange'){
                $paid=max(0,$replacementTotal-$returnValue);
                $replacementSale=Sale::create([
                    'receipt_number'=>'EXC-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                    'branch_id'=>$lockedSale->branch_id,'customer_id'=>$lockedSale->customer_id,
                    'cashier_id'=>$user->id,'status'=>'completed','subtotal'=>$replacementTotal,
                    'discount_amount'=>0,'tax_amount'=>0,'total_amount'=>$replacementTotal,
                    'amount_paid'=>$replacementTotal,'change_amount'=>0,
                    'notes'=>'Replacement sale for '.$record->return_number,'sold_at'=>now(),
                ]);
                foreach($replacementRows as $row){
                    $v=$replacementVariants->get($row['product_variant_id']);$qty=(int)$row['quantity'];
                    $stockService->change($lockedSale->branch_id,$v->id,-$qty,$user->id,'exchange_out','sale',$replacementSale->id,'Exchange '.$record->return_number);
                    $replacementSale->items()->create([
                        'product_variant_id'=>$v->id,'product_name'=>$v->product->name,'sku'=>$v->sku,
                        'size'=>$v->size,'color'=>$v->color,'quantity'=>$qty,
                        'unit_price'=>$v->selling_price,'unit_cost'=>$v->cost_price,
                        'line_total'=>round((float)$v->selling_price*$qty,2),
                    ]);
                }
                if($returnValue>0){
                    $replacementSale->payments()->create(['method'=>'exchange_credit','amount'=>min($returnValue,$replacementTotal),'reference'=>$record->return_number,'paid_at'=>now()]);
                }
                if($paid>0){
                    $replacementSale->payments()->create(['method'=>$data['payment_method'],'amount'=>$paid,'reference'=>null,'paid_at'=>now()]);
                    if ($data['payment_method'] === 'cash') {
                        CashTransaction::create([
                            'branch_id' => $lockedSale->branch_id, 'created_by' => $user->id,
                            'direction' => 'in', 'type' => 'exchange_balance', 'amount' => $paid,
                            'payment_method' => 'cash', 'reference_type' => 'sale', 'reference_id' => $replacementSale->id,
                            'description' => 'Cash exchange balance '.$record->return_number, 'transacted_at' => now(),
                        ]);
                    }
                }
            }

            return $record;
        });

        return redirect()->route('returns.index')->with('success','Return / exchange '.$return->return_number.' completed.');
    }
}
