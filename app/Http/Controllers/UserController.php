<?php

namespace App\\Http\\Controllers;

use App\\Models\\ActivityLog;
use App\\Models\\Branch;
use App\\Models\\Role;
use App\\Models\\User;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\Rule;

class UserController extends Controller
{
    private array $roleSlugs = ['owner','branch-manager','cashier','stock-manager'];

    public function index(Request $request)
    {
        $actor=$request->user();
        $users=User::with(['branch','roles'])
            ->when(!$actor->hasRole('owner'),fn($q)=>$q->where('branch_id',$actor->branch_id)->whereDoesntHave('roles',fn($r)=>$r->where('slug','owner')))
            ->when($request->filled('search'),fn($q)=>$q->where(fn($w)=>$w->where('name','like','%'.$request->search.'%')->orWhere('username','like','%'.$request->search.'%')->orWhere('email','like','%'.$request->search.'%')))
            ->latest()->paginate(25)->withQueryString();
        return view('employees.index',compact('users'));
    }

    public function create(Request $request)
    {
        $actor=$request->user();
        $roles=Role::where('is_active',true)->whereIn('slug',$actor->hasRole('owner')?$this->roleSlugs:['cashier','stock-manager'])->orderBy('name')->get();
        $branches=$actor->hasRole('owner')?Branch::where('is_active',true)->orderBy('name')->get():collect();
        return view('employees.create',compact('roles','branches'));
    }

    public function store(Request $request)
    {
        $actor=$request->user();
        $allowed=$actor->hasRole('owner')?$this->roleSlugs:['cashier','stock-manager'];
        $data=$request->validate([
            'name'=>['required','string','max:255'],
            'username'=>['required','string','max:100','alpha_dash','unique:users,username'],
            'email'=>['required','email','max:255','unique:users,email'],
            'password'=>['required','string','min:5','confirmed'],
            'role'=>['required',Rule::in($allowed)],
            'branch_id'=>[$actor->hasRole('owner')?'nullable':'prohibited','integer','exists:branches,id'],
            'is_active'=>['nullable','boolean'],
        ]);
        $role=Role::where('slug',$data['role'])->where('is_active',true)->firstOrFail();
        $branchId=$actor->hasRole('owner')
            ? ($data['role']==='owner'?null:($data['branch_id']??null))
            : $actor->branch_id;
        abort_if($data['role']!=='owner' && !$branchId,422,'A branch must be assigned to branch employees.');
        if($branchId) abort_unless(Branch::whereKey($branchId)->where('is_active',true)->exists(),422,'Select an active branch.');

        $user=DB::transaction(function()use($data,$role,$branchId,$actor,$request){
            $user=User::create([
                'name'=>$data['name'],'username'=>$data['username'],'email'=>strtolower($data['email']),
                'password'=>$data['password'],'branch_id'=>$branchId,'is_active'=>$request->boolean('is_active',true),
            ]);
            $user->roles()->attach($role->id);
            ActivityLog::create([
                'user_id'=>$actor->id,'branch_id'=>$branchId,'action'=>'employee.created','module'=>'employees',
                'description'=>'Created user '.$user->username.' with role '.$role->name.'.',
                'properties'=>['created_user_id'=>$user->id,'role'=>$role->slug],
                'ip_address'=>$request->ip(),'user_agent'=>$request->userAgent(),
            ]);
            return $user;
        });
        return redirect()->route('employees.index')->with('success','User '.$user->name.' created successfully.');
    }

    public function edit(Request $request, User $employee)
    {
        $this->authorizeEmployee($request->user(),$employee);
        $actor=$request->user();
        $roles=Role::where('is_active',true)->whereIn('slug',$actor->hasRole('owner')?$this->roleSlugs:['cashier','stock-manager'])->orderBy('name')->get();
        $branches=$actor->hasRole('owner')?Branch::where('is_active',true)->orderBy('name')->get():collect();
        return view('employees.edit',compact('employee','roles','branches'));
    }

    public function update(Request $request, User $employee)
    {
        $actor=$request->user();
        $this->authorizeEmployee($actor,$employee);
        $allowed=$actor->hasRole('owner')?$this->roleSlugs:['cashier','stock-manager'];
        $data=$request->validate([
            'name'=>['required','string','max:255'],
            'username'=>['required','string','max:100','alpha_dash',Rule::unique('users','username')->ignore($employee->id)],
            'email'=>['required','email','max:255',Rule::unique('users','email')->ignore($employee->id)],
            'password'=>['nullable','string','min:5','confirmed'],
            'role'=>['required',Rule::in($allowed)],
            'branch_id'=>[$actor->hasRole('owner')?'nullable':'prohibited','integer','exists:branches,id'],
        ]);
        $branchId=$actor->hasRole('owner')?($data['role']==='owner'?null:($data['branch_id']??null)):$actor->branch_id;
        abort_if($data['role']!=='owner' && !$branchId,422,'A branch must be assigned to branch employees.');
        if($branchId) abort_unless(Branch::whereKey($branchId)->where('is_active',true)->exists(),422,'Select an active branch.');
        $role=Role::where('slug',$data['role'])->where('is_active',true)->firstOrFail();

        DB::transaction(function()use($employee,$data,$branchId,$role,$actor,$request){
            $changes=['name'=>$data['name'],'username'=>$data['username'],'email'=>strtolower($data['email']),'branch_id'=>$branchId];
            if(!empty($data['password'])) $changes['password']=$data['password'];
            $employee->update($changes);
            $employee->roles()->sync([$role->id]);
            ActivityLog::create([
                'user_id'=>$actor->id,'branch_id'=>$branchId,'action'=>'employee.updated','module'=>'employees',
                'description'=>'Updated user '.$employee->username.'.',
                'properties'=>['updated_user_id'=>$employee->id,'role'=>$role->slug],
                'ip_address'=>$request->ip(),'user_agent'=>$request->userAgent(),
            ]);
        });
        return redirect()->route('employees.index')->with('success','User details updated.');
    }

    public function toggle(Request $request, User $employee)
    {
        $actor=$request->user();
        $this->authorizeEmployee($actor,$employee);
        abort_if($actor->id===$employee->id,422,'You cannot deactivate your own account.');
        $employee->update(['is_active'=>!$employee->is_active]);
        ActivityLog::create([
            'user_id'=>$actor->id,'branch_id'=>$employee->branch_id,
            'action'=>$employee->is_active?'employee.activated':'employee.deactivated','module'=>'employees',
            'description'=>($employee->is_active?'Activated':'Deactivated').' user '.$employee->username.'.',
            'properties'=>['updated_user_id'=>$employee->id],
            'ip_address'=>$request->ip(),'user_agent'=>$request->userAgent(),
        ]);
        return back()->with('success','User status updated.');
    }

    private function authorizeEmployee(User $actor, User $employee): void
    {
        abort_if($actor->id===$employee->id,403,'Use your account settings to change your own account.');
        if(!$actor->hasRole('owner')){
            abort_unless($employee->branch_id===$actor->branch_id && !$employee->hasRole('owner') && !$employee->hasRole('branch-manager'),403);
        }
    }
}
