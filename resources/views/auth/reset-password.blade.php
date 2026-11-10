@extends('auth.guest')
@section('title', 'Password Baru')

@section('content')
    <form action="{{ route('password.reset.submit') }}" method="POST">
        @csrf
        <p class="small text-secondary">Buat password baru untuk akun Anda (minimal 6 karakter).</p>
        <div class="mb-3">
            <label class="form-label small fw-medium">Password Baru</label>
            <input type="password" name="password" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-medium">Konfirmasi Password Baru</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-scp w-100">Simpan Password</button>
    </form>
@endsection