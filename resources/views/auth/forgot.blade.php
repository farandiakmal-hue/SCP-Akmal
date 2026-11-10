@extends('auth.guest')
@section('title', 'Lupa Password')

@section('content')
    <form action="{{ route('password.send') }}" method="POST">
        @csrf
        <p class="small text-secondary">Masukkan username Anda. Kode OTP 6 digit akan dikirim ke email yang terdaftar di akun.</p>
        <div class="mb-3">
            <label class="form-label small fw-medium">Username</label>
            <input type="text" name="username" value="{{ old('username') }}" class="form-control" placeholder="Masukkan username" required autofocus>
        </div>
        <button type="submit" class="btn btn-scp w-100">Kirim Kode OTP</button>
        <div class="text-center mt-3"><a href="{{ route('login') }}" class="small lupa-link">Kembali ke login</a></div>
    </form>
@endsection