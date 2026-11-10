<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - School Class Progress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; }
        body { background: #f4f5f7; }
        .login-card { background: #fff; border: 1px solid #e3e5e9; border-radius: .5rem; }
        .btn-scp { background: #33363d; color: #fff; border-radius: .3rem; }
        .btn-scp:hover { background: #24262b; color: #fff; }

        /* Tautan teks: "Kembali ke login", "Kirim ulang", "Lupa password?" */
        .lupa-link {
            color: #33363d;
            font-weight: 500;
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: color .15s, border-color .15s;
        }
        .lupa-link:hover { color: #0a77ad; border-bottom-color: currentColor; }
        .lupa-link:focus-visible { outline: 2px solid #0a77ad; outline-offset: 3px; border-radius: 2px; }

        @media (max-width: 767.98px) {
            .form-control { font-size: 16px; min-height: 46px; }
            .btn { min-height: 46px; }
        }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100 py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-11 col-sm-8 col-md-5 col-lg-4">
                <div class="text-center mb-4">
                    <img src="{{ asset('img/logo-smk.png') }}" alt="Logo SMK Negeri 1 Bantul"
                         style="width:90px; height:90px; object-fit:contain;" class="mb-2">
                    <div class="fw-bold fs-4">School Class Progress</div>
                    <div class="text-secondary small">@yield('subtitle', 'Reset Password')</div>
                </div>
                <div class="login-card shadow-sm p-4">
                    @if(session('success'))
                        <div class="alert alert-success py-2 fs-6">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger py-2 fs-6">{{ session('error') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger py-2 fs-6 mb-3">
                            @foreach($errors->all() as $e)
                                <div>{{ $e }}</div>
                            @endforeach
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </div>
    </div>
</body>
</html>