@extends('auth.guest')
@section('title', 'Verifikasi OTP')

@section('content')
    <form action="{{ route('password.verify.submit') }}" method="POST">
        @csrf
        <p class="small text-secondary">Masukkan kode 6 digit yang dikirim ke <strong>{{ $maskedEmail }}</strong>. Kode berlaku 10 menit.</p>
        <div class="mb-3">
            <label class="form-label small fw-medium">Kode OTP</label>
            <input type="text" name="otp" class="form-control text-center fs-4" inputmode="numeric"
                   pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="••••••" required autofocus>
        </div>
        <button type="submit" class="btn btn-scp w-100">Verifikasi</button>
        <div class="text-center mt-3 small">
            Belum menerima kode? <a href="{{ route('password.forgot') }}" class="lupa-link">Kirim ulang</a>
        </div>
    </form>
@endsection