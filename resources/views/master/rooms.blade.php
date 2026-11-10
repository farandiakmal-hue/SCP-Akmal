@extends('layouts.app')
@section('title', 'Manajemen Data - Ruangan')

@section('content')
    <h5 class="fw-bold mb-3">Manajemen Data</h5>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link" href="{{ route('users.index') }}">Pengguna</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('classes.index') }}">Kelas</a></li>
        <li class="nav-item"><a class="nav-link active" href="{{ route('rooms.index') }}">Ruangan</a></li>
    </ul>

    @include('master.import', [
        'action' => route('master.rooms.import'),
        'type'   => 'rooms',
        'hint'   => 'Satu kolom: room_name. Nama ruangan yang sudah ada akan dilewati.',
    ])

    <div class="scp-card mb-4">
        <div class="scp-card-header">Tambah Ruangan</div>
        <div class="p-3">
            <form action="{{ route('master.rooms.store') }}" method="POST" class="row g-2">
                @csrf
                <div class="col-md-4">
                    <input type="text" name="room_name" class="form-control form-control-sm" placeholder="Nama ruangan, misal Lab RPL 1" required>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-scp btn-sm w-100" type="submit">Tambah</button>
                </div>
            </form>
        </div>
    </div>

    <div class="scp-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Nama Ruangan</th><th></th></tr></thead>
                <tbody>
                    @forelse($rooms as $r)
                        <tr>
                            <td>{{ $r->room_name }}</td>
                            <td class="text-end">
                                <form action="{{ route('master.rooms.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Hapus ruangan ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-secondary py-3">Belum ada ruangan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('master.delete_all', [
    'action'  => route('master.rooms.destroyAll'),
    'label'   => 'Hapus semua (' . $rooms->count() . ' ruangan)',
    'warning' => 'Menghapus semua ruangan. Jadwal yang memakai ruangan itu ikut terhapus beserta jurnal dan presensinya.',
])
    </div>
@endsection