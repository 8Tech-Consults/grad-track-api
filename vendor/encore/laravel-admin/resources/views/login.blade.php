<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>RUFORUM - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap and FontAwesome -->
    <link rel="stylesheet" href="{{ admin_asset('vendor/laravel-admin/AdminLTE/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ admin_asset('vendor/laravel-admin/font-awesome/css/font-awesome.min.css') }}">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #99793C;
            --secondary-color: #CACC79;
            --bg-gradient-start: #f5f6fa;
            --bg-gradient-end: #e0e6ed;
            --input-border: #ccc;
            --text-color: #333;
        }
        * { box-sizing: border-box; }
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Source Sans Pro', sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            color: var(--text-color);
        }
        .container-flex {
            display: flex;
            flex-wrap: nowrap;
            min-height: 100vh;
        }
        .welcome-panel {
            flex: 1 1 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }
        .welcome-panel img {
            max-width: 120px;
            margin-bottom: 1rem;
            border-radius: 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .welcome-panel h1 {
            font-size: 2rem;
            margin: 0.5rem 0;
            font-weight: 600;
        }
        .welcome-panel p {
            font-size: 1rem;
            line-height: 1.4;
            max-width: 300px;
        }
        .login-panel {
            flex: 1 1 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: #fff;
        }
        .login-card {
            background: #ffffff;
            padding: 2rem 3rem;
            border-radius: 1rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 380px;
            transition: transform 0.3s;
        }
        .login-card:hover {
            transform: translateY(-4px);
        }
        .login-card h2 {
            margin-bottom: 2rem;
            font-weight: 600;
            color: var(--primary-color);
            text-align: center;
        }
        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .form-group label {
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
            display: block;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--input-border);
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(153,121,60,0.25);
        }
        .btn-login {
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            transition: background 0.3s, transform 0.2s;
        }
        .btn-login:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        .forgot-link {
            display: block;
            text-align: right;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: var(--primary-color);
            transition: color 0.3s;
        }
        .forgot-link:hover {
            color: var(--secondary-color);
            text-decoration: none;
        }
        @media (max-width: 768px) {
            .container-flex { flex-wrap: wrap; }
            .welcome-panel { display: none; }
            .login-panel { flex: 1 1 100%; padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="container-flex">
        <div class="welcome-panel pc-only">
            <img src="https://www.kab.ac.ug/wp-content/uploads/2023/02/Regional-Universities-Forum-for-Capacity-Building-in-Agriculture-RUFORUM.jpg" alt="RUFORUM Logo">
            <h1>Welcome to RUFORUM</h1>
            <p>Empowering universities across Africa with agricultural education, research, and innovation.</p>
        </div>
        <div class="login-panel">
            <div class="login-card">
                <h2>Admin Login</h2>
                <form action="{{ url('auth/login') }}" method="post">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="username">{{ trans('admin.username') }}</label>
                        <input type="text" name="username" id="username" class="form-control" placeholder="Enter username" value="{{ old('username') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="password">{{ trans('admin.password') }}</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
                    </div>

                    <a href="javascript:;" class="forgot-link">Forgot password?</a>
                    <button type="submit" class="btn btn-login mt-3">{{ trans('admin.login') }}</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ admin_asset('vendor/laravel-admin/AdminLTE/plugins/jQuery/jQuery-2.1.4.min.js') }}"></script>
    <script src="{{ admin_asset('vendor/laravel-admin/AdminLTE/bootstrap/js/bootstrap.min.js') }}"></script>
</body>
</html>
