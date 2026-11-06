@extends('layouts.app')
@section('title', 'Manajemen Data - Kelas')

@section('content')
    <h1 class="scp-page-title">Manajemen Data</h1>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link" href="{{ route('users.index') }}">Pengguna</a></li>
        <li class="nav-item"><a class="nav-link active" href="{{ route('classes.index') }}">Kelas</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('rooms.index') }}">Ruangan</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('import.index') }}">Import</a></li>
    </ul>

    <div class="scp-card mb-4">
        <div class="scp-card-header">Tambah Kelas</div>
        <div class="p-3">
            <form action="{{ route('master.classes.store') }}" method="POST" class="row g-2">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Nama Kelas</label>
                    <input type="text" name="class_name" class="form-control form-control-sm" placeholder="Nama kelas, misal XII RPL 1" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-scp btn-sm w-100" type="submit">Tambah</button>
                </div>
            </form>
        </div>
    </div>

    <div class="scp-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Nama Kelas</th><th></th></tr></thead>
                <tbody>
                    @forelse($classes as $c)
                        <tr>
                            <td>{{ $c->class_name }}</td>
                            <td class="text-end">
                                <form action="{{ route('master.classes.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Hapus kelas ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="scp-empty">Belum ada kelas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
