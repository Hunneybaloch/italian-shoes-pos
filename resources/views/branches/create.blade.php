<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Branch | Italian Shoes POS</title>
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
        .content { padding: 32px 35px; max-width: 1100px; margin: auto; }
        .heading { margin-bottom: 24px; }
        h1 { font-size: 25px; margin-bottom: 6px; }
        .muted { color: #8591a2; font-size: 13px; line-height: 1.6; }
        .back { display: inline-block; color: #1c568b; text-decoration: none; font-size: 13px; font-weight: 700; margin-bottom: 15px; }
        .panel { background: #fff; border: 1px solid #e9edf3; border-radius: 12px; padding: 26px; box-shadow: 0 3px 12px rgba(20,40,70,.025); margin-bottom: 18px; }
        .section-title { font-size: 16px; font-weight: 800; margin-bottom: 5px; }
        .section-note { color: #8591a2; font-size: 12px; margin-bottom: 20px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
        .field { min-width: 0; }
        .field.full { grid-column: 1 / -1; }
        label { display: block; font-size: 12px; font-weight: 700; margin-bottom: 8px; color: #35465c; }
        label .required { color: #c2413a; }
        input { width: 100%; height: 46px; border: 1px solid #dce2eb; border-radius: 8px; padding: 0 13px; font: inherit; font-size: 13px; outline: none; background: #fff; }
        input:focus { border-color: #1c568b; box-shadow: 0 0 0 3px rgba(28,86,139,.09); }
        input::placeholder { color: #a2adba; }
        .help { display: block; color: #8a96a5; font-size: 11px; margin-top: 6px; }
        .error-box { background: #fff0f0; border: 1px solid #ffd0d0; color: #b42318; border-radius: 8px; padding: 13px 16px; font-size: 12px; margin-bottom: 18px; }
        .error-box ul { padding-left: 18px; margin-top: 5px; }
        .actions { display: flex; justify-content: flex-end; gap: 10px; padding-top: 5px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 8px; padding: 12px 18px; background: #173d65; color: #fff; text-decoration: none; font: inherit; font-size: 13px; font-weight: 700; cursor: pointer; }
        .btn:hover { background: #102b49; }
        .btn-light { background: #edf2f8; color: #254d75; }
        .btn-light:hover { background: #e1eaf4; }
        .notice { border-left: 3px solid #d6ad63; background: #fffaf0; color: #765a25; padding: 13px 15px; border-radius: 6px; font-size: 12px; line-height: 1.6; margin-top: 18px; }
        @media(max-width: 800px) { .layout { display: block; } .sidebar { width: 100%; padding: 15px; } .logo { padding-bottom: 12px; } .nav { display: flex; flex-wrap: wrap; } .nav-label { display: none; } .nav a, .logout { width: auto; } .topbar { padding: 15px 18px; } .content { padding: 22px 16px; } }
        @media(max-width: 560px) { .form-grid { grid-template-columns: 1fr; } .field.full { grid-column: auto; } .panel { padding: 18px; } .actions { flex-direction: column-reverse; } .actions .btn { width: 100%; } }
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
            <h2>Create New Branch</h2>
            <div class="owner"><div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div><span>{{ auth()->user()->name }}</span></div>
        </header>
        <section class="content">
            <a class="back" href="{{ route('branches.index') }}">← Back to Branches</a>
            <div class="heading">
                <h1>Add a Branch</h1>
                <p class="muted">Enter the branch information and create its first Branch Manager account.</p>
            </div>

            @if ($errors->any())
                <div class="error-box"><strong>Please correct the following:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <form method="POST" action="{{ route('branches.store') }}">
                @csrf
                <div class="panel">
                    <h3 class="section-title">Branch Information</h3>
                    <p class="section-note">Basic details used to identify this branch in the system.</p>
                    <div class="form-grid">
                        <div class="field">
                            <label for="name">Branch Name <span class="required">*</span></label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="e.g. Quetta Main Branch" maxlength="150" required>
                        </div>
                        <div class="field">
                            <label for="code">Branch Code <span class="required">*</span></label>
                            <input id="code" name="code" type="text" value="{{ old('code') }}" placeholder="e.g. QTA-MAIN" maxlength="20" pattern="[A-Za-z0-9_-]+" required>
                            <small class="help">Must be unique. Letters, numbers, hyphens and underscores only.</small>
                        </div>
                        <div class="field">
                            <label for="address">Branch Address <span class="muted">(optional)</span></label>
                            <input id="address" name="address" type="text" value="{{ old('address') }}" maxlength="255" placeholder="Enter branch address">
                        </div>
                        <div class="field">
                            <label for="phone">Branch Phone <span class="muted">(optional)</span></label>
                            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="30" placeholder="Enter contact number">
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <h3 class="section-title">First Branch Manager Account</h3>
                    <p class="section-note">This account will automatically receive the Branch Manager role and access to this branch.</p>
                    <div class="form-grid">
                        <div class="field full">
                            <label for="manager_name">Manager Full Name <span class="required">*</span></label>
                            <input id="manager_name" name="manager_name" type="text" value="{{ old('manager_name') }}" maxlength="255" placeholder="Enter manager's full name" required>
                        </div>
                        <div class="field">
                            <label for="username">Username <span class="required">*</span></label>
                            <input id="username" name="username" type="text" value="{{ old('username') }}" maxlength="100" pattern="[A-Za-z0-9_-]+" autocomplete="username" placeholder="Create a login username" required>
                            <small class="help">Must be unique across the whole system.</small>
                        </div>
                        <div class="field">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" placeholder="manager@example.com" required>
                        </div>
                        <div class="field">
                            <label for="password">Password <span class="required">*</span></label>
                            <input id="password" name="password" type="password" minlength="5" autocomplete="new-password" placeholder="Minimum 5 characters" required>
                            <small class="help">Use at least 5 characters.</small>
                        </div>
                        <div class="field">
                            <label for="password_confirmation">Confirm Password <span class="required">*</span></label>
                            <input id="password_confirmation" name="password_confirmation" type="password" minlength="5" autocomplete="new-password" placeholder="Enter the same password again" required>
                        </div>
                    </div>
                    <div class="notice">The branch and its first manager account will be saved together. If either creation fails, neither record will be saved. The manager will be assigned to this branch automatically.</div>
                </div>

                <div class="actions">
                    <a class="btn btn-light" href="{{ route('branches.index') }}">Cancel</a>
                    <button class="btn" type="submit">Create Branch & Manager</button>
                </div>
            </form>
        </section>
    </main>
</div>
</body>
</html>
