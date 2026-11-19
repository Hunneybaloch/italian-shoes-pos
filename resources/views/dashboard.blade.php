
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard | Italian Shoes POS</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f4f6fa;
            color: #1b2940;
        }

        .layout { display: flex; min-height: 100vh; }

        .sidebar {
            width: 250px;
            background: #132b46;
            color: white;
            padding: 27px 18px;
            flex-shrink: 0;
        }

        .logo {
            font-size: 21px;
            font-weight: 800;
            letter-spacing: .5px;
            padding: 5px 12px 32px;
        }

        .logo span { color: #d9b46b; }

        .nav-label {
            color: #8194aa;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.4px;
            padding: 13px 12px;
        }

        .nav a {
            display: block;
            text-decoration: none;
            color: #c5d1df;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .nav a.active, .nav a:hover {
            background: #23496e;
            color: white;
        }

        .main { flex: 1; min-width: 0; }

        .topbar {
            height: 75px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 35px;
            border-bottom: 1px solid #e8ecf2;
        }

        .topbar h2 { font-size: 18px; }

        .owner {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 600;
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e8eff7;
            color: #173d65;
            display: grid;
            place-items: center;
            font-weight: 800;
        }

        .content { padding: 32px 35px; }

        .welcome {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            gap: 15px;
        }

        .welcome h1 { font-size: 25px; margin-bottom: 7px; }
        .welcome p { color: #8591a2; font-size: 13px; }

        .status {
            background: #e9f7ef;
            color: #21834e;
            padding: 9px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .card {
            background: white;
            border: 1px solid #e9edf3;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 3px 12px rgba(20,40,70,.025);
        }

        .stat-label { color: #8591a2; font-size: 12px; }
        .stat-value { font-size: 27px; font-weight: 800; margin: 13px 0 7px; }
        .stat-note { font-size: 11px; color: #9aa5b3; }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .lower-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 20px;
        }

        .empty {
            text-align: center;
            padding: 35px 15px;
            color: #98a3b2;
            font-size: 13px;
        }

        .empty strong {
            display: block;
            color: #4b5b70;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .quick-links {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .quick-links a {
            text-decoration: none;
            background: #f4f7fb;
            color: #254d75;
            padding: 16px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .quick-links a:hover { background: #e7eff8; }

        .logout {
            border: none;
            background: transparent;
            color: #c5d1df;
            padding: 12px;
            cursor: pointer;
            font-family: inherit;
            font-size: 13px;
            width: 100%;
            text-align: left;
        }

        @media(max-width: 1000px) {
            .stats { grid-template-columns: repeat(2, 1fr); }
        }

        @media(max-width: 700px) {
            .layout { display: block; }
            .sidebar { width: 100%; padding: 15px; }
            .logo { padding-bottom: 12px; }
            .nav { display: flex; flex-wrap: wrap; }
            .nav-label { display: none; }
            .nav a, .logout { width: auto; }
            .topbar { padding: 0 18px; }
            .content { padding: 22px 16px; }
            .stats { grid-template-columns: 1fr 1fr; gap: 10px; }
            .card { padding: 16px; }
            .lower-grid { grid-template-columns: 1fr; }
            .welcome h1 { font-size: 20px; }
        }

        @media(max-width: 400px) {
            .stats { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

<div class="layout">

    <aside class="sidebar">
        <div class="logo">ITALIAN <span>SHOES</span></div>

        <div class="nav-label">MAIN MENU</div>

        <nav class="nav">
            <a href="{{ route('dashboard') }}" class="active">▦ &nbsp; Dashboard</a>
            <a href="{{ route('branches.index') }}">▣ &nbsp; Branch Management</a>
            <a href="#">♙ &nbsp; User Management</a>
            <a href="{{ route('products.index') }}">◈ &nbsp; Products</a>
            <a href="{{ route('inventory.index') }}">▤ &nbsp; Inventory</a>
            <a href="{{ route('purchases.index') }}">▣ &nbsp; Purchases</a>
            <a href="#">◷ &nbsp; Expenses</a>
            <a href="#">▥ &nbsp; Reports</a>
            <a href="#">⚙ &nbsp; Settings</a>
        </nav>

        <div class="nav-label">ACCOUNT</div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="logout" type="submit">↪ &nbsp; Logout</button>
        </form>
    </aside>

    <main class="main">

        <header class="topbar">
            <h2>Owner Dashboard</h2>

            <div class="owner">
                <div class="avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <span>{{ auth()->user()->name }}</span>
            </div>
        </header>

        <section class="content">

            <div class="welcome">
                <div>
                    <h1>Welcome, {{ auth()->user()->name }}!</h1>
                    <p>Here's your business overview.</p>
                </div>
                <span class="status">● System Online</span>
            </div>

            <div class="stats">

                <div class="card">
                    <div class="stat-label">Total Sales Today</div>
                    <div class="stat-value">PKR 0</div>
                    <div class="stat-note">No sales recorded yet</div>
                </div>

                <div class="card">
                    <div class="stat-label">Total Branches</div>
                    <div class="stat-value">0</div>
                    <div class="stat-note">Branches not added yet</div>
                </div>

                <div class="card">
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value">0</div>
                    <div class="stat-note">Products not added yet</div>
                </div>

                <div class="card">
                    <div class="stat-label">Total Employees</div>
                    <div class="stat-value">0</div>
                    <div class="stat-note">Employees not added yet</div>
                </div>

            </div>

            <div class="lower-grid">

                <div class="card">
                    <div class="section-title">Branch Performance</div>
                    <div class="empty">
                        <strong>No branches added yet</strong>
                        Create your first branch to start monitoring
                        branch-wise sales and performance.
                    </div>
                </div>

                <div class="card">
                    <div class="section-title">Quick Actions</div>

                    <div class="quick-links">
                        <a href="{{ route('branches.create') }}">＋ Add Branch</a>
                        <a href="#">＋ Add Employee</a>
                        <a href="{{ route('products.create') }}">＋ Add Product</a>
                        <a href="#">▤ View Reports</a>
                    </div>
                </div>

            </div>

        </section>
    </main>

</div>

</body>
</html>
