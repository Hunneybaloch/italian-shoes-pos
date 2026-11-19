<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::query()
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('name','like','%'.$request->search.'%')->orWhere('phone','like','%'.$request->search.'%')->orWhere('email','like','%'.$request->search.'%')))
            ->latest()->paginate(25)->withQueryString();
        return view('customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'=>['required','string','max:160'],
            'phone'=>['nullable','string','max:30'],
            'email'=>['nullable','email','max:255'],
            'address'=>['nullable','string','max:255'],
            'notes'=>['nullable','string','max:2000'],
        ]);
        Customer::create($data + ['is_active'=>true]);
        return back()->with('success','Customer added successfully.');
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'=>['required','string','max:160'],
            'phone'=>['nullable','string','max:30'],
            'email'=>['nullable','email','max:255'],
            'address'=>['nullable','string','max:255'],
            'notes'=>['nullable','string','max:2000'],
        ]);
        $customer->update($data);
        return back()->with('success','Customer updated.');
    }

    public function toggle(Customer $customer)
    {
        $customer->update(['is_active'=>!$customer->is_active]);
        return back()->with('success','Customer status updated.');
    }
}
