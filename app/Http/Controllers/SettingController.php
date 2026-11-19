<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index()
    {
        $settings=SystemSetting::all()->keyBy('key');
        return view('settings.index',compact('settings'));
    }

    public function update(Request $request)
    {
        $data=$request->validate([
            'store_name'=>['required','string','max:160'],
            'store_phone'=>['nullable','string','max:30'],
            'store_address'=>['nullable','string','max:255'],
            'receipt_footer'=>['nullable','string','max:500'],
            'default_tax_rate'=>['required','numeric','min:0','max:100'],
            'currency'=>['required','string','size:3','in:PKR'],
            'timezone'=>['required','string','in:Asia/Karachi'],
        ]);
        DB::transaction(function()use($data,$request){
            foreach($data as $key=>$value){
                SystemSetting::updateOrCreate(['key'=>$key],[
                    'value'=>$value,'group'=>'general','updated_by'=>$request->user()->id,
                ]);
            }
            ActivityLog::create([
                'user_id'=>$request->user()->id,'action'=>'settings.updated','module'=>'settings',
                'description'=>'Updated general store and receipt settings.',
                'properties'=>['keys'=>array_keys($data)],
                'ip_address'=>$request->ip(),'user_agent'=>$request->userAgent(),
            ]);
        });
        return back()->with('success','System settings saved.');
    }
}
