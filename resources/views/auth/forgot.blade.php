<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - School Class Progress</title>
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
                    <div class="scp-page-title mb-0" style="font-size:1.5rem">Lupa Password</div>
                    <div class="scp-page-sub">Kode verifikasi dikirim ke email terdaftar</div>
                </div>
                <div class="scp-login-card p-4">
                    @if($errors->any())
                        <div class="alert alert-danger py-2" role="alert">{{ $errors->first() }}</div>
                    @endif
                    <form action="{{ route('forgot.send') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Username atau Email</label>
                            <input type="text" name="identifier" class="form-control" placeholder="Masukkan username atau email" value="{{ old('identifier') }}" required autofocus>
                            <div class="form-text">Kode 6 digit akan dikirim ke email yang terdaftar pada akun Anda.</div>
                        </div>
                        <button type="submit" class="btn btn-scp w-100">Kirim Kode</button>
                        <div class="text-center mt-3 d-flex justify-content-center gap-3">
                            <a href="{{ route('reset.show') }}" class="small">Sudah punya kode?</a>
                            <a href="{{ route('login') }}" class="small">Kembali masuk</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
