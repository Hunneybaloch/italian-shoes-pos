<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashTransaction;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user=$request->user();
        $expenses=Expense::with(['branch','creator'])
            ->when(!$user->hasRole('owner'),fn($q)=>$q->where('branch_id',$user->branch_id))
            ->latest('expense_date')->paginate(25);
        $branches=$user->hasRole('owner')?Branch::where('is_active',true)->orderBy('name')->get():collect();
        return view('expenses.index',compact('expenses','branches'));
    }

    public function store(Request $request)
    {
        $user=$request->user();
        $data=$request->validate([
            'branch_id'=>['required','integer',Rule::exists('branches','id')->where('is_active',true)],
            'category'=>['required','string','max:100'],
            'description'=>['required','string','max:255'],
            'amount'=>['required','numeric','gt:0','max:9999999999'],
            'payment_method'=>['required',Rule::in(['cash','card','bank_transfer','mobile_wallet','other'])],
            'reference'=>['nullable','string','max:120'],
            'expense_date'=>['required','date'],
        ]);
        abort_unless($user->hasRole('owner') || $user->branch_id===(int)$data['branch_id'],403);
        \Illuminate\Support\Facades\DB::transaction(function() use($data,$user){
            $expense=Expense::create($data+['created_by'=>$user->id]);
            if($data['payment_method']==='cash'){
                CashTransaction::create([
                    'branch_id'=>$data['branch_id'],'created_by'=>$user->id,'direction'=>'out',
                    'type'=>'expense','amount'=>$data['amount'],'payment_method'=>'cash',
                    'reference_type'=>'expense','reference_id'=>$expense->id,
                    'description'=>$data['description'],'transacted_at'=>now(),
                ]);
            }
        });
        return back()->with('success','Expense saved.');
    }
}
