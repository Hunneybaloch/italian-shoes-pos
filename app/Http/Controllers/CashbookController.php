<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashTransaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CashbookController extends Controller
{
    public function index(Request $request)
    {
        $data=$request->validate([
            'from'=>['nullable','date'],
            'to'=>['nullable','date','after_or_equal:from'],
            'branch_id'=>['nullable','integer','exists:branches,id'],
        ]);
        $user=$request->user();
        $branchId=$user->hasRole('owner')?($data['branch_id']??null):$user->branch_id;
        $from=$data['from']??now()->startOfMonth()->toDateString();
        $to=$data['to']??now()->toDateString();
        $transactions=CashTransaction::with(['branch','creator'])
            ->whereBetween('transacted_at',[$from.' 00:00:00',$to.' 23:59:59'])
            ->when($branchId,fn($q)=>$q->where('branch_id',$branchId))
            ->latest('transacted_at')->paginate(40)->withQueryString();
        $cashIn=(float)CashTransaction::where('direction','in')->whereBetween('transacted_at',[$from.' 00:00:00',$to.' 23:59:59'])->when($branchId,fn($q)=>$q->where('branch_id',$branchId))->sum('amount');
        $cashOut=(float)CashTransaction::where('direction','out')->whereBetween('transacted_at',[$from.' 00:00:00',$to.' 23:59:59'])->when($branchId,fn($q)=>$q->where('branch_id',$branchId))->sum('amount');
        $branches=$user->hasRole('owner')?Branch::where('is_active',true)->orderBy('name')->get():collect();
        return view('cashbook.index',compact('transactions','cashIn','cashOut','from','to','branches','branchId'));
    }

    public function store(Request $request)
    {
        $user=$request->user();
        $data=$request->validate([
            'branch_id'=>['required','integer',Rule::exists('branches','id')->where('is_active',true)],
            'direction'=>['required',Rule::in(['in','out'])],
            'amount'=>['required','numeric','gt:0','max:9999999999'],
            'description'=>['required','string','max:255'],
        ]);
        abort_unless($user->hasRole('owner') || $user->branch_id===(int)$data['branch_id'],403);
        CashTransaction::create([
            'branch_id'=>$data['branch_id'],'created_by'=>$user->id,
            'direction'=>$data['direction'],'type'=>'cash_adjustment','amount'=>$data['amount'],
            'payment_method'=>'cash','description'=>$data['description'],'transacted_at'=>now(),
        ]);
        return back()->with('success','Cashbook adjustment recorded.');
    }
}
