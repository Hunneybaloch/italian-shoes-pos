
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Italian Shoes POS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            background: #f3f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
            color: #17243a;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1000px;
            min-height: 580px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #fff;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 20px 65px rgba(20, 39, 70, .10);
        }

        .brand-panel {
            background: linear-gradient(145deg, #101f38, #173d65);
            color: white;
            padding: 55px 45px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .brand-name {
            font-size: 27px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .brand-name span {
            color: #d6ad63;
        }

        .brand-content h1 {
            font-size: 36px;
            line-height: 1.3;
            margin-bottom: 18px;
        }

        .brand-content p {
            color: #c2d0df;
            line-height: 1.8;
            font-size: 14px;
        }

        .brand-footer {
            color: #a9bacd;
            font-size: 12px;
        }

        .form-panel {
            padding: 65px 55px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-panel h2 {
            font-size: 29px;
            margin-bottom: 9px;
        }

        .subtitle {
            color: #7b8798;
            font-size: 14px;
            margin-bottom: 35px;
        }

        .form-group {
            margin-bottom: 21px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 9px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            height: 49px;
            border: 1px solid #dce2eb;
            border-radius: 9px;
            padding: 0 14px;
            font-size: 14px;
            outline: none;
            font-family: inherit;
        }

        input:focus {
            border-color: #1c568b;
            box-shadow: 0 0 0 3px rgba(28, 86, 139, .09);
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap input {
            padding-right: 65px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: #1c568b;
            cursor: pointer;
            font-weight: 700;
            font-size: 12px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 5px 0 25px;
            font-size: 13px;
            color: #667386;
        }

        .remember input {
            accent-color: #173d65;
        }

        .login-btn {
            width: 100%;
            height: 50px;
            background: #173d65;
            color: white;
            border: none;
            border-radius: 9px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s;
        }

        .login-btn:hover {
            background: #102b49;
        }

        .error-box {
            background: #fff0f0;
            border: 1px solid #ffd0d0;
            color: #b42318;
            border-radius: 8px;
            padding: 12px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .form-footer {
            text-align: center;
            margin-top: 30px;
            color: #9aa3af;
            font-size: 12px;
        }

        @media (max-width: 700px) {
            body {
                padding: 15px;
            }

            .login-wrapper {
                grid-template-columns: 1fr;
            }

            .brand-panel {
                min-height: 210px;
                padding: 30px;
            }

            .brand-content {
                display: none;
            }

            .form-panel {
                padding: 38px 27px;
            }
        }
    </style>
</head>

<body>

<div class="login-wrapper">

    <div class="brand-panel">
        <div class="brand-name">ITALIAN <span>SHOES</span></div>

        <div class="brand-content">
            <h1>Manage Your Business With Confidence.</h1>
            <p>
                Welcome to the Italian Shoes management system.
                Monitor sales, inventory, branches and business
                performance from one centralized platform.
            </p>
        </div>

        <div class="brand-footer">
            © {{ date('Y') }} Italian Shoes. All rights reserved.
        </div>
    </div>

    <div class="form-panel">

        <h2>Welcome Back</h2>
        <p class="subtitle">Sign in to access your management dashboard.</p>

        @if ($errors->any())
            <div class="error-box">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf

            <div class="form-group">
                <label for="login">Username or Email</label>
                <input
                    type="text"
                    id="login"
                    name="login"
                    value="{{ old('login') }}"
                    placeholder="Enter your username or email"
                    autocomplete="username"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <div class="password-wrap">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword()"
                    >
                        SHOW
                    </button>
                </div>
            </div>

            <label class="remember">
                <input type="checkbox" name="remember" value="1">
                Remember me
            </label>

            <button type="submit" class="login-btn">
                Sign In to Dashboard →
            </button>
        </form>

        <div class="form-footer">
            Italian Shoes POS · Secure Management Portal
        </div>

    </div>

</div>

<script>
    function togglePassword() {
        const password = document.getElementById('password');
        const button = document.querySelector('.toggle-password');

        if (password.type === 'password') {
            password.type = 'text';
            button.textContent = 'HIDE';
        } else {
            password.type = 'password';
            button.textContent = 'SHOW';
        }
    }
</script>

</body>
</html>
