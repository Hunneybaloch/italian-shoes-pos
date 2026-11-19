<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashTransaction;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\BranchStock;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{

    public function export(Request $request)
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
        $query=Sale::with(['branch','customer','cashier'])->where('status','completed')
            ->whereBetween('sold_at',[$from.' 00:00:00',$to.' 23:59:59'])
            ->when($branchId,fn($q)=>$q->where('branch_id',$branchId))
            ->orderBy('sold_at');
        return response()->streamDownload(function()use($query){
            $output=fopen('php://output','w');
            $safeText=fn($value)=>is_string($value) && preg_match('/^[=+@\\-\\t\\r]/',$value) ? "'".$value : $value;
            fputcsv($output,['Receipt','Date','Branch','Customer','Cashier','Subtotal PKR','Discount PKR','Tax PKR','Total PKR','Paid PKR','Change PKR','Status']);
            foreach($query->cursor() as $sale){
                fputcsv($output,[$safeText($sale->receipt_number),$sale->sold_at,$safeText($sale->branch?->name),$safeText($sale->customer?->name??'Walk-in'),$safeText($sale->cashier?->name),$sale->subtotal,$sale->discount_amount,$sale->tax_amount,$sale->total_amount,$sale->amount_paid,$sale->change_amount,$safeText($sale->status)]);
            }
            fclose($output);
        },'italian-shoes-sales-'.$from.'-to-'.$to.'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }

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

        $sales=Sale::where('status','completed')->whereBetween('sold_at',[$from.' 00:00:00',$to.' 23:59:59'])
            ->when($branchId,fn($q)=>$q->where('branch_id',$branchId));
        $saleCount=(clone $sales)->count();
        $grossSales=(float)(clone $sales)->sum('total_amount');
        $returns=SaleReturn::where('status','completed')->whereBetween('processed_at',[$from.' 00:00:00',$to.' 23:59:59'])
            ->when($branchId,fn($q)=>$q->where('branch_id',$branchId));
        $refunds=(float)(clone $returns)->sum('returned_value');
        $expenses=(float)Expense::whereBetween('expense_date',[$from,$to])->when($branchId,fn($q)=>$q->where('branch_id',$branchId))->sum('amount');
        $cogs=(float)DB::table('sale_items')->join('sales','sales.id','=','sale_items.sale_id')
            ->where('sales.status','completed')->whereBetween('sales.sold_at',[$from.' 00:00:00',$to.' 23:59:59'])
            ->when($branchId,fn($q)=>$q->where('sales.branch_id',$branchId))
            ->selectRaw('COALESCE(SUM(sale_items.unit_cost * sale_items.quantity),0) as total')->value('total');
        $returnedCost=(float)DB::table('sale_return_items')->join('sale_returns','sale_returns.id','=','sale_return_items.sale_return_id')
            ->join('sale_items','sale_items.id','=','sale_return_items.sale_item_id')
            ->where('sale_returns.status','completed')->whereBetween('sale_returns.processed_at',[$from.' 00:00:00',$to.' 23:59:59'])
            ->when($branchId,fn($q)=>$q->where('sale_returns.branch_id',$branchId))
            ->selectRaw('COALESCE(SUM(sale_items.unit_cost * sale_return_items.quantity),0) as total')->value('total');
        $grossProfit=round(($grossSales-$refunds)-($cogs-$returnedCost),2);
        $netProfit=round($grossProfit-$expenses,2);
        $cashIn=(float)CashTransaction::where('direction','in')->whereBetween('transacted_at',[$from.' 00:00:00',$to.' 23:59:59'])->when($branchId,fn($q)=>$q->where('branch_id',$branchId))->sum('amount');
        $cashOut=(float)CashTransaction::where('direction','out')->whereBetween('transacted_at',[$from.' 00:00:00',$to.' 23:59:59'])->when($branchId,fn($q)=>$q->where('branch_id',$branchId))->sum('amount');
        $dailySales=(clone $sales)->selectRaw('DATE(sold_at) as day, SUM(total_amount) as total')->groupBy('day')->orderBy('day')->get();
        $branches=$user->hasRole('owner')?Branch::orderBy('name')->get():collect();
        $stockValue=(float)BranchStock::query()->join('product_variants','product_variants.id','=','branch_stocks.product_variant_id')
            ->when($branchId,fn($q)=>$q->where('branch_stocks.branch_id',$branchId))
            ->selectRaw('COALESCE(SUM(branch_stocks.quantity * product_variants.cost_price),0) as value')->value('value');
        $lowStock=BranchStock::with(['branch','productVariant.product'])->whereColumn('quantity','<=','reorder_level')
            ->when($branchId,fn($q)=>$q->where('branch_id',$branchId))->orderBy('quantity')->limit(30)->get();
        return view('reports.index',compact('from','to','branches','branchId','saleCount','grossSales','refunds','expenses','grossProfit','netProfit','cashIn','cashOut','dailySales','stockValue','lowStock'));
    }
}
