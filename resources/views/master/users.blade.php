@extends('layouts.app')
@section('title', 'Manajemen Data - Pengguna')

@section('content')
    <h1 class="scp-page-title">Manajemen Data</h1>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link active" href="{{ route('users.index') }}">Pengguna</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('classes.index') }}">Kelas</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('rooms.index') }}">Ruangan</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('import.index') }}">Import</a></li>
    </ul>

    <div class="scp-card mb-4">
        <div class="scp-card-header">Tambah Pengguna</div>
        <div class="p-3">
            <form action="{{ route('master.users.store') }}" method="POST" class="row g-2">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="fullname" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Role</label>
                    <select name="role_id" class="form-select form-select-sm" required>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}">{{ $r->role_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kelas (jika siswa)</label>
                    <select name="class_id" class="form-select form-select-sm">
                        <option value="">-</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email (opsional)</label>
                    <input type="email" name="email" class="form-control form-control-sm" placeholder="untuk reset password">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Orang Tua (jika siswa)</label>
                    <select name="parent_id" class="form-select form-select-sm">
                        <option value="">-</option>
                        @foreach($parents as $p)
                            <option value="{{ $p->id }}">{{ $p->fullname }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button class="btn btn-scp btn-sm w-100" type="submit">Tambah</button>
                </div>
            </form>
        </div>
    </div>

    <div class="scp-card mb-3">
        <form method="GET" action="{{ route('users.index') }}" class="row g-2 p-3">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Cari nama, username, atau email">
            </div>
            <div class="col-md-3">
                <select name="role_id" class="form-select form-select-sm">
                    <option value="">Semua role</option>
                    @foreach($roles as $r)
                        <option value="{{ $r->id }}" @selected(request('role_id') == $r->id)>{{ $r->role_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="class_id" class="form-select form-select-sm">
                    <option value="">Semua kelas</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->class_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button class="btn btn-scp btn-sm flex-fill" type="submit">Filter</button>
                <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="scp-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Nama</th><th>Username</th><th>Role</th><th>Kelas</th><th></th></tr></thead>
                <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td>{{ $u->fullname }}</td>
                            <td>{{ $u->username }}</td>
                            <td><span class="scp-badge is-muted">{{ $u->role->role_name ?? '-' }}</span></td>
                            <td>{{ $u->classRoom->class_name ?? '-' }}</td>
                            <td class="text-end">
                                <form action="{{ route('master.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Hapus pengguna ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">{{ $users->total() }} pengguna</small>
        {{ $users->links() }}
    </div>
@endsection
