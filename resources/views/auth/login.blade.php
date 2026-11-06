<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - School Class Progress</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/scp.css') }}?v={{ @filemtime(public_path('css/scp.css')) }}">
</head>
<body class="scp-auth">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-11 col-sm-8 col-md-5 col-lg-4">
                <div class="text-center mb-4">
                    <img src="{{ asset('img/logo-smkn1bantul.png') }}" alt="Logo SMK Negeri 1 Bantul" class="scp-login-logo">
                    <div class="scp-page-title mb-0" style="font-size:1.5rem">School Class Progress</div>
                    <div class="scp-page-sub">Presensi &amp; Jurnal Sekolah</div>
                </div>
                <div class="scp-login-card p-4">
                    @if(session('success'))
                        <div class="alert alert-success py-2" role="alert">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger py-2" role="alert">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('login') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                        </div>
                        <button type="submit" class="btn btn-scp w-100">Masuk</button>
                        <div class="text-center mt-3">
                            <a href="{{ route('forgot.show') }}" class="small">Lupa password?</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
