@extends('layouts.app')
@section('title', 'Profil Saya')

@section('content')
    <h1 class="scp-page-title">Update Profil</h1>

    <div class="scp-card" style="max-width: 480px;">
        <div class="p-3">
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="fullname" class="form-control form-control-sm" value="{{ old('fullname', $user->fullname) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control form-control-sm" value="{{ old('username', $user->username) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email (untuk reset password)</label>
                    <input type="email" name="email" class="form-control form-control-sm" value="{{ old('email', $user->email) }}" placeholder="nama@email.com">
                    <div class="form-text">Kode reset password akan dikirim ke email ini bila Anda lupa password.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control form-control-sm" value="{{ $user->roleLabel() }}" disabled>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="form-label">Password Saat Ini</label>
                    <input type="password" name="current_password" class="form-control form-control-sm" placeholder="Isi hanya jika ingin ganti password">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password" class="form-control form-control-sm" placeholder="Kosongkan jika tidak ganti">
                </div>
                <div class="mb-3">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" class="form-control form-control-sm">
                </div>
                <button type="submit" class="btn btn-scp">Simpan Perubahan</button>
            </form>
        </div>
    </div>
@endsection
