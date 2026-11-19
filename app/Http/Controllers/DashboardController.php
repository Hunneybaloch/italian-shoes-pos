<?php

namespace App\\Http\\Controllers;

use App\\Models\\Branch;
use App\\Models\\BranchStock;
use App\\Models\\Product;
use App\\Models\\Sale;
use App\\Models\\User;
use Illuminate\\Http\\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user=$request->user();
        $owner=$user->hasRole('owner');
        $branchId=$owner?null:$user->branch_id;
        $today=now()->toDateString();

        $salesToday=(float)Sale::where('status','completed')->whereDate('sold_at',$today)
            ->when($branchId,fn($q)=>$q->where('branch_id',$branchId))->sum('total_amount');
        $branchCount=$owner?Branch::count():($branchId?1:0);
        $productCount=Product::where('is_active',true)->count();
        $employeeCount=User::where('is_active',true)->whereHas('roles',fn($q)=>$q->whereIn('slug',['branch-manager','cashier','stock-manager']))
            ->when($branchId,fn($q)=>$q->where('branch_id',$branchId))->count();
        $stockUnits=(int)BranchStock::when($branchId,fn($q)=>$q->where('branch_id',$branchId))->sum('quantity');
        $lowStockCount=BranchStock::whereColumn('quantity','<=','reorder_level')
            ->when($branchId,fn($q)=>$q->where('branch_id',$branchId))->count();
        $stockValue=(float)BranchStock::join('product_variants','product_variants.id','=','branch_stocks.product_variant_id')
            ->when($branchId,fn($q)=>$q->where('branch_stocks.branch_id',$branchId))
            ->selectRaw('COALESCE(SUM(branch_stocks.quantity * product_variants.cost_price),0) as value')->value('value');

        $branches=Branch::when($branchId,fn($q)=>$q->whereKey($branchId))->orderBy('name')->get()
            ->map(function($branch)use($today){
                $branch->today_sales=(float)Sale::where('branch_id',$branch->id)->where('status','completed')->whereDate('sold_at',$today)->sum('total_amount');
                $branch->stock_units=(int)BranchStock::where('branch_id',$branch->id)->sum('quantity');
                return $branch;
            });

        return view('dashboard',compact('user','owner','salesToday','branchCount','productCount','employeeCount','stockUnits','lowStockCount','stockValue','branches'));
    }
}
