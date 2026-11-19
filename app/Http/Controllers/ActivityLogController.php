<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user=$request->user();
        $logs=ActivityLog::with(['user','branch'])
            ->when(!$user->hasRole('owner'),fn($q)=>$q->where('branch_id',$user->branch_id))
            ->when($request->filled('module'),fn($q)=>$q->where('module',$request->module))
            ->when($request->filled('search'),fn($q)=>$q->where('description','like','%'.$request->search.'%'))
            ->latest()->paginate(40)->withQueryString();
        return view('activity-logs.index',compact('logs'));
    }
}
