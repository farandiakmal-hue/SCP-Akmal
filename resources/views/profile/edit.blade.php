@extends('layouts.app')
@section('title', 'Profil Saya')

@section('content')
    <h5 class="fw-bold mb-3">Update Profil</h5>

    <form action="{{ route('profile.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="scp-card h-100">
                    <div class="p-3">
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Nama Lengkap</label>
                            <input type="text" name="fullname" class="form-control" value="{{ old('fullname', $user->fullname) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Username</label>
                            <input type="text" name="username" class="form-control" value="{{ old('username', $user->username) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Email (untuk reset password)</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" placeholder="nama@email.com">
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-medium">Role</label>
                            <input type="text" class="form-control" value="{{ $user->roleLabel() }}" disabled>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="scp-card h-100">
                    <div class="p-3">
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Password Saat Ini</label>
                            <input type="password" name="current_password" class="form-control" placeholder="Isi hanya jika ingin ganti password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Password Baru</label>
                            <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ganti">
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-medium">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-scp mt-3">Simpan Perubahan</button>
    </form>
@endsection