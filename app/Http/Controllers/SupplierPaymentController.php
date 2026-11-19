<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\Purchase;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupplierPaymentController extends Controller
{
    public function index(Request $request)
    {
        $user=$request->user();
        $payments=SupplierPayment::with(['supplier','purchase','branch'])
            ->when(!$user->hasRole('owner'),fn($q)=>$q->where('branch_id',$user->branch_id))
            ->latest('paid_at')->paginate(25);
        $purchases=Purchase::with(['supplier','branch','items'])
            ->whereNotNull('supplier_id')
            ->when(!$user->hasRole('owner'),fn($q)=>$q->where('branch_id',$user->branch_id))
            ->latest()->get()->map(function($purchase){
                $paid=(float)SupplierPayment::where('purchase_id',$purchase->id)->sum('amount');
                $purchase->paid_amount=$paid;
                $purchase->due_amount=max(0,(float)$purchase->total_amount-$paid);
                return $purchase;
            })->filter(fn($p)=>$p->due_amount>0);
        return view('supplier-payments.index',compact('payments','purchases'));
    }

    public function store(Request $request, Purchase $purchase)
    {
        $user=$request->user();
        abort_unless($user->hasRole('owner') || $user->branch_id===$purchase->branch_id,403);
        abort_if(!$purchase->supplier_id,422,'This purchase has no supplier.');
        $data=$request->validate([
            'amount'=>['required','numeric','gt:0','max:9999999999'],
            'payment_method'=>['required',Rule::in(['cash','card','bank_transfer','mobile_wallet','other'])],
            'reference'=>['nullable','string','max:120'],
        ]);
        DB::transaction(function()use($purchase,$data,$user){
            $locked=Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            $paid=(float)SupplierPayment::where('purchase_id',$locked->id)->sum('amount');
            $due=max(0,(float)$locked->total_amount-$paid);
            abort_if((float)$data['amount']>$due,422,'Payment cannot exceed the outstanding purchase balance.');
            $payment=SupplierPayment::create([
                'supplier_id'=>$locked->supplier_id,'purchase_id'=>$locked->id,'branch_id'=>$locked->branch_id,
                'created_by'=>$user->id,'amount'=>$data['amount'],'payment_method'=>$data['payment_method'],
                'reference'=>$data['reference']??null,'paid_at'=>now(),
            ]);
            if($data['payment_method']==='cash'){
                CashTransaction::create([
                    'branch_id'=>$locked->branch_id,'created_by'=>$user->id,'direction'=>'out',
                    'type'=>'supplier_payment','amount'=>$data['amount'],'payment_method'=>'cash',
                    'reference_type'=>'supplier_payment','reference_id'=>$payment->id,
                    'description'=>'Supplier payment '.$locked->supplier?->name,'transacted_at'=>now(),
                ]);
            }
        });
        return back()->with('success','Supplier payment recorded.');
    }
}
