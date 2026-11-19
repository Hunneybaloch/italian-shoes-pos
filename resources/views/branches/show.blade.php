<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $branch->name }} | Italian Shoes POS</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fa; color: #1b2940; }
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; flex-shrink: 0; background: #132b46; color: #fff; padding: 27px 18px; }
        .logo { font-size: 21px; font-weight: 800; letter-spacing: .5px; padding: 5px 12px 32px; }
        .logo span { color: #d9b46b; }
        .nav-label { color: #8194aa; font-size: 10px; font-weight: 700; letter-spacing: 1.4px; padding: 13px 12px; }
        .nav a, .logout { display: block; width: 100%; text-align: left; text-decoration: none; color: #c5d1df; padding: 12px; border: 0; border-radius: 8px; font: inherit; font-size: 13px; background: transparent; margin-bottom: 5px; cursor: pointer; }
        .nav a.active, .nav a:hover, .logout:hover { background: #23496e; color: #fff; }
        .main { flex: 1; min-width: 0; }
        .topbar { min-height: 75px; background: #fff; display: flex; align-items: center; justify-content: space-between; gap: 15px; padding: 15px 35px; border-bottom: 1px solid #e8ecf2; }
        .topbar h2 { font-size: 18px; }
        .owner { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; }
        .avatar { width: 36px; height: 36px; border-radius: 50%; background: #e8eff7; color: #173d65; display: grid; place-items: center; font-weight: 800; }
        .content { padding: 32px 35px; max-width: 1300px; margin: auto; }
        .back { display: inline-block; color: #1c568b; text-decoration: none; font-size: 13px; font-weight: 700; margin-bottom: 18px; }
        .heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 15px; margin-bottom: 24px; }
        h1 { font-size: 25px; margin-bottom: 7px; }
        .muted { color: #8591a2; font-size: 13px; line-height: 1.6; }
        .status { display: inline-block; border-radius: 20px; padding: 6px 10px; font-size: 11px; font-weight: 700; }
        .active { color: #21834e; background: #e9f7ef; }
        .inactive { color: #a45b18; background: #fff3e5; }
        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }
        .panel { background: #fff; border: 1px solid #e9edf3; border-radius: 12px; padding: 22px; box-shadow: 0 3px 12px rgba(20,40,70,.025); }
        .panel.wide { grid-column: 1 / -1; }
        .panel h3 { font-size: 15px; margin-bottom: 17px; }
        .detail { margin-bottom: 16px; }
        .detail:last-child { margin-bottom: 0; }
        .label { display: block; color: #8591a2; font-size: 11px; margin-bottom: 5px; }
        .value { color: #25364c; font-size: 13px; font-weight: 700; overflow-wrap: anywhere; }
        .code { display: inline-block; background: #f1f4f8; color: #52647a; padding: 6px 9px; border-radius: 5px; font-size: 12px; font-weight: 700; }
        .manager-card { background: #f7f9fc; border: 1px solid #edf1f6; border-radius: 9px; padding: 15px; }
        .manager-name { font-weight: 800; margin-bottom: 5px; }
        .manager-meta { color: #718096; font-size: 12px; line-height: 1.8; overflow-wrap: anywhere; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 550px; }
        th { text-align: left; color: #7b8798; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; padding: 12px; border-bottom: 1px solid #e8ecf2; }
        td { padding: 14px 12px; border-bottom: 1px solid #eef1f5; font-size: 12px; }
        tbody tr:last-child td { border-bottom: 0; }
        .role { display: inline-block; background: #edf2f8; color: #254d75; border-radius: 20px; padding: 5px 8px; font-size: 10px; font-weight: 700; margin: 2px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 8px; padding: 11px 15px; background: #173d65; color: #fff; text-decoration: none; font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; }
        .btn:hover { background: #102b49; }
        .btn-light { background: #edf2f8; color: #254d75; }
        .btn-light:hover { background: #e1eaf4; }
        .btn-warning { background: #fff3e5; color: #9a5618; }
        .btn-warning:hover { background: #ffe9cf; }
        .actions { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; }
        .alert { padding: 12px 15px; border-radius: 8px; margin-bottom: 18px; font-size: 13px; }
        .success { color: #17663c; background: #e9f7ef; border: 1px solid #ccebd8; }
        .empty { padding: 20px; color: #8591a2; font-size: 13px; text-align: center; }
        @media(max-width: 950px) { .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media(max-width: 800px) { .layout { display: block; } .sidebar { width: 100%; padding: 15px; } .logo { padding-bottom: 12px; } .nav { display: flex; flex-wrap: wrap; } .nav-label { display: none; } .nav a, .logout { width: auto; } .topbar { padding: 15px 18px; } .content { padding: 22px 16px; } }
        @media(max-width: 580px) { .grid { grid-template-columns: 1fr; } .panel.wide { grid-column: auto; } .heading { flex-direction: column; } }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="logo">ITALIAN <span>SHOES</span></div>
        <div class="nav-label">MAIN MENU</div>
        <nav class="nav">
            <a href="{{ route('dashboard') }}">▦ &nbsp; Dashboard</a>
            <a href="{{ route('branches.index') }}" class="active">▣ &nbsp; Branch Management</a>
            <a href="#">♙ &nbsp; User Management</a>
            <a href="#">◈ &nbsp; Products</a>
            <a href="#">▤ &nbsp; Inventory</a>
            <a href="#">▣ &nbsp; Sales & POS</a>
            <a href="#">◷ &nbsp; Expenses</a>
            <a href="#">▥ &nbsp; Reports</a>
            <a href="#">⚙ &nbsp; Settings</a>
        </nav>
        <div class="nav-label">ACCOUNT</div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" type="submit">↪ &nbsp; Logout</button></form>
    </aside>
    <main class="main">
        <header class="topbar">
            <h2>Branch Details</h2>
            <div class="owner"><div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div><span>{{ auth()->user()->name }}</span></div>
        </header>
        <section class="content">
            <a class="back" href="{{ route('branches.index') }}">← Back to Branches</a>
            @if (session('success'))<div class="alert success">{{ session('success') }}</div>@endif
            <div class="heading">
                <div>
                    <h1>{{ $branch->name }}</h1>
                    <p class="muted">Branch profile and its assigned employees.</p>
                </div>
                <div class="actions">
                    <span class="status {{ $branch->is_active ? 'active' : 'inactive' }}">{{ $branch->is_active ? 'Active' : 'Inactive' }}</span>
                    <a class="btn btn-light" href="{{ route('branches.edit', $branch) }}">Edit Branch</a>
                    <form method="POST" action="{{ route('branches.toggle-status', $branch) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn {{ $branch->is_active ? 'btn-warning' : '' }}" type="submit">{{ $branch->is_active ? 'Deactivate Branch' : 'Activate Branch' }}</button>
                    </form>
                </div>
            </div>

            @php($manager = $branch->users->first(fn ($user) => $user->hasRole('branch-manager')))
            <div class="grid">
                <div class="panel">
                    <h3>Branch Information</h3>
                    <div class="detail"><span class="label">Branch Name</span><div class="value">{{ $branch->name }}</div></div>
                    <div class="detail"><span class="label">Branch Code</span><span class="code">{{ $branch->code }}</span></div>
                    <div class="detail"><span class="label">Address</span><div class="value">{{ $branch->address ?: 'Not provided' }}</div></div>
                    <div class="detail"><span class="label">Phone</span><div class="value">{{ $branch->phone ?: 'Not provided' }}</div></div>
                </div>

                <div class="panel">
                    <h3>Branch Manager</h3>
                    @if ($manager)
                        <div class="manager-card">
                            <div class="manager-name">{{ $manager->name }}</div>
                            <div class="manager-meta">
                                Username: {{ $manager->username }}<br>
                                Email: {{ $manager->email }}<br>
                                Account: {{ $manager->is_active ? 'Active' : 'Inactive' }}
                            </div>
                        </div>
                    @else
                        <p class="muted">No Branch Manager is currently assigned.</p>
                    @endif
                </div>

                <div class="panel">
                    <h3>Branch Summary</h3>
                    <div class="detail"><span class="label">Total Employees</span><div class="value">{{ $branch->users->count() }}</div></div>
                    <div class="detail"><span class="label">Created On</span><div class="value">{{ $branch->created_at?->format('d M Y') }}</div></div>
                    <div class="detail"><span class="label">Last Updated</span><div class="value">{{ $branch->updated_at?->format('d M Y') }}</div></div>
                </div>

                <div class="panel wide">
                    <h3>Employees Assigned to This Branch</h3>
                    @if ($branch->users->count())
                        <div class="table-wrap">
                            <table>
                                <thead><tr><th>Employee</th><th>Username</th><th>Email</th><th>Role</th><th>Account Status</th></tr></thead>
                                <tbody>
                                @foreach ($branch->users as $employee)
                                    <tr>
                                        <td><strong>{{ $employee->name }}</strong></td>
                                        <td>{{ $employee->username }}</td>
                                        <td>{{ $employee->email }}</td>
                                        <td>@forelse ($employee->roles as $role)<span class="role">{{ $role->name }}</span>@empty<span class="muted">No role</span>@endforelse</td>
                                        <td><span class="status {{ $employee->is_active ? 'active' : 'inactive' }}">{{ $employee->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty">No employees have been assigned to this branch yet.</div>
                    @endif
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>
