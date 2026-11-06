<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - School Class Progress</title>
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
                    <div class="scp-page-title mb-0" style="font-size:1.5rem">Reset Password</div>
                    <div class="scp-page-sub">Salin kode dari email, lalu buat password baru</div>
                </div>
                <div class="scp-login-card p-4">
                    @if(session('info'))
                        <div class="alert alert-info py-2" role="alert">{{ session('info') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger py-2" role="alert">{{ session('error') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger py-2" role="alert">{{ $errors->first() }}</div>
                    @endif
                    <form action="{{ route('reset.update') }}" method="POST" autocomplete="off">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Username atau Email</label>
                            <input type="text" name="identifier" class="form-control" value="{{ old('identifier', session('identifier')) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kode 6 Digit</label>
                            <input type="text" name="code" class="form-control" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                                   autocomplete="one-time-code" placeholder="______" required
                                   style="letter-spacing:.4em;font-weight:700;text-align:center;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-scp w-100">Ubah Password</button>
                        <div class="text-center mt-3 d-flex justify-content-center gap-3">
                            <a href="{{ route('forgot.show') }}" class="small">Kirim ulang kode</a>
                            <a href="{{ route('login') }}" class="small">Kembali masuk</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
