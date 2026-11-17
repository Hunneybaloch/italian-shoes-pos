<?php

namespace App\\Http\\Controllers;

use App\\Models\\ActivityLog;
use App\\Models\\Branch;
use App\\Models\\Role;
use App\\Models\\User;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\Rule;

class BranchController extends Controller
{
    /**
     * Display the branches registered in the system.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $branches = Branch::query()
            ->with(['users.roles'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($branchQuery) use ($search) {
                    $branchQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%');
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('is_active', $status === 'active');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('branches.index', compact('branches'));
    }

    /**
     * Show the form for creating a branch and its first manager.
     */
    public function create()
    {
        return view('branches.create');
    }

    /**
     * Create a branch and its first Branch Manager atomically.
     */
    public function store(Request $request)
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
            'username' => trim((string) $request->input('username')),
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:branches,code'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'manager_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:5', 'confirmed'],
        ]);

        $branch = DB::transaction(function () use ($validated, $request) {
            $managerRole = Role::query()
                ->where('slug', 'branch-manager')
                ->where('is_active', true)
                ->firstOrFail();

            $branch = Branch::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'address' => $validated['address'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'is_active' => true,
            ]);

            $manager = User::create([
                'name' => $validated['manager_name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'branch_id' => $branch->id,
                'is_active' => true,
            ]);

            $manager->roles()->attach($managerRole->id);

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'branch_id' => $branch->id,
                'action' => 'branch.created',
                'module' => 'branches',
                'description' => 'Created branch ' . $branch->name . ' and its first Branch Manager.',
                'properties' => [
                    'branch_code' => $branch->code,
                    'manager_user_id' => $manager->id,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $branch;
        });

        return redirect()
            ->route('branches.show', $branch)
            ->with('success', 'Branch and its first Branch Manager were created successfully.');
    }

    /**
     * Display one branch and its assigned users.
     */
    public function show(Branch $branch)
    {
        $branch->load(['users.roles']);

        return view('branches.show', compact('branch'));
    }

    /**
     * Show the form for editing a branch.
     */
    public function edit(Branch $branch)
    {
        return view('branches.edit', compact('branch'));
    }

    /**
     * Update branch details without changing its employees or records.
     */
    public function update(Request $request, Branch $branch)
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:20',
                'alpha_dash',
                Rule::unique('branches', 'code')->ignore($branch->id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $branch->update($validated);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'branch_id' => $branch->id,
            'action' => 'branch.updated',
            'module' => 'branches',
            'description' => 'Updated branch ' . $branch->name . '.',
            'properties' => ['branch_code' => $branch->code],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('branches.show', $branch)
            ->with('success', 'Branch details updated successfully.');
    }

    /**
     * Activate or deactivate a branch without deleting historical data.
     */
    public function toggleStatus(Request $request, Branch $branch)
    {
        $branch->update(['is_active' => ! $branch->is_active]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'branch_id' => $branch->id,
            'action' => $branch->is_active ? 'branch.activated' : 'branch.deactivated',
            'module' => 'branches',
            'description' => ($branch->is_active ? 'Activated' : 'Deactivated') . ' branch ' . $branch->name . '.',
            'properties' => ['branch_code' => $branch->code],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with(
            'success',
            $branch->is_active ? 'Branch activated successfully.' : 'Branch deactivated successfully.'
        );
    }
}
