<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Management | Italian Shoes POS</title>
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
        .content { padding: 32px 35px; max-width: 1400px; margin: auto; }
        .page-heading { display: flex; justify-content: space-between; align-items: center; gap: 18px; margin-bottom: 25px; }
        h1 { font-size: 25px; margin-bottom: 6px; }
        .muted { color: #8591a2; font-size: 13px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; border: 0; border-radius: 8px; padding: 12px 17px; background: #173d65; color: #fff; font: inherit; font-size: 13px; font-weight: 700; cursor: pointer; }
        .btn:hover { background: #102b49; }
        .panel { background: #fff; border: 1px solid #e9edf3; border-radius: 12px; padding: 22px; box-shadow: 0 3px 12px rgba(20,40,70,.025); }
        .filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
        .filters input, .filters select { min-height: 42px; border: 1px solid #dce2eb; border-radius: 8px; padding: 0 12px; font: inherit; font-size: 13px; background: #fff; }
        .filters input { flex: 1; min-width: 190px; }
        .btn-light { background: #edf2f8; color: #254d75; }
        .btn-light:hover { background: #e1eaf4; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 650px; }
        th { text-align: left; color: #7b8798; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; padding: 13px 12px; border-bottom: 1px solid #e8ecf2; }
        td { padding: 16px 12px; border-bottom: 1px solid #eef1f5; font-size: 13px; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: 0; }
        .branch-name { font-weight: 700; color: #1b2940; }
        .code { display: inline-block; background: #f1f4f8; color: #52647a; padding: 5px 8px; border-radius: 5px; font-size: 11px; font-weight: 700; }
        .status { display: inline-block; border-radius: 20px; padding: 5px 9px; font-size: 11px; font-weight: 700; }
        .active { color: #21834e; background: #e9f7ef; }
        .inactive { color: #a45b18; background: #fff3e5; }
        .view-link { color: #1c568b; text-decoration: none; font-weight: 700; }
        .view-link:hover { text-decoration: underline; }
        .empty { text-align: center; padding: 48px 15px; color: #8591a2; }
        .empty strong { display: block; color: #35465c; font-size: 16px; margin-bottom: 8px; }
        .empty p { font-size: 13px; margin-bottom: 18px; }
        .alert { padding: 12px 15px; border-radius: 8px; margin-bottom: 18px; font-size: 13px; }
        .success { color: #17663c; background: #e9f7ef; border: 1px solid #ccebd8; }
        .pagination { padding-top: 18px; }
        .pagination nav { display: flex; justify-content: space-between; gap: 10px; align-items: center; }
        .pagination a, .pagination span { color: #254d75; font-size: 12px; text-decoration: none; }
        @media(max-width: 800px) { .layout { display: block; } .sidebar { width: 100%; padding: 15px; } .logo { padding-bottom: 12px; } .nav { display: flex; flex-wrap: wrap; } .nav-label { display: none; } .nav a, .logout { width: auto; } .topbar { padding: 15px 18px; } .content { padding: 22px 16px; } }
        @media(max-width: 520px) { .page-heading { align-items: flex-start; flex-direction: column; } .panel { padding: 14px; } }
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
            <h2>Branch Management</h2>
            <div class="owner"><div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div><span>{{ auth()->user()->name }}</span></div>
        </header>
        <section class="content">
            <div class="page-heading">
                <div><h1>Branches</h1><p class="muted">Create and manage your business branches from one place.</p></div>
                <a class="btn" href="{{ route('branches.create') }}">＋ Add New Branch</a>
            </div>

            @if (session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif

            <div class="panel">
                <form class="filters" method="GET" action="{{ route('branches.index') }}">
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search by branch name or code...">
                    <select name="status" aria-label="Filter by status">
                        <option value="">All statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                    <button class="btn" type="submit">Apply Filters</button>
                    <a class="btn btn-light" href="{{ route('branches.index') }}">Reset</a>
                </form>

                @if ($branches->count())
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Branch</th><th>Branch Code</th><th>Manager</th><th>Employees</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                            @foreach ($branches as $branch)
                                @php($manager = $branch->users->first(fn ($user) => $user->hasRole('branch-manager')))
                                <tr>
                                    <td><div class="branch-name">{{ $branch->name }}</div><div class="muted">{{ $branch->address ?: 'No address added' }}</div></td>
                                    <td><span class="code">{{ $branch->code }}</span></td>
                                    <td>{{ $manager?->name ?? 'Not assigned' }}</td>
                                    <td>{{ $branch->users->count() }}</td>
                                    <td><span class="status {{ $branch->is_active ? 'active' : 'inactive' }}">{{ $branch->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td><a class="view-link" href="{{ route('branches.show', $branch) }}">View details →</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination">{{ $branches->links() }}</div>
                @else
                    <div class="empty">
                        <strong>No branches found</strong>
                        <p>No branch records match your search. Add your first branch to get started.</p>
                        <a class="btn" href="{{ route('branches.create') }}">＋ Create First Branch</a>
                    </div>
                @endif
            </div>
        </section>
    </main>
</div>
</body>
</html>
