@extends('layouts.app')
@section('title', 'Jadwal')

@section('content')
    @php
        use App\Models\Role;
        $user = auth()->user();
        $locked = $config->is_schedule_locked ?? false;
    @endphp

    <div class="scp-page-head d-flex justify-content-between align-items-start">
        <div>
            <h1 class="scp-page-title">Jadwal Mengajar</h1>
            <div class="scp-page-sub">Putaran aktif: {{ $config->active_cycle ?? '-' }}</div>
        </div>
        @if($locked)
            <span class="scp-badge is-danger"><i class="bi bi-lock-fill"></i> Jadwal Terkunci</span>
        @endif
    </div>

    @if($user->isRole(Role::GURU) && !$locked)
        <div class="scp-card mb-4">
            <div class="scp-card-header">Tambah Jadwal Baru</div>
            <div class="p-3">
                @include('schedule._form', ['action' => route('schedule.store'), 'method' => 'POST', 'submit' => 'Simpan'])
            </div>
        </div>
    @elseif($user->isRole(Role::GURU) && $locked)
        <div class="alert alert-warning py-2">Jadwal sedang dikunci oleh Waka Kurikulum. Anda tidak bisa menambah jadwal baru.</div>
    @endif

    <div class="scp-card mb-3">
        <div class="p-3">
            <form action="{{ route('schedule.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Cari</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Mapel{{ $user->isRole(Role::GURU) ? '' : ' atau nama guru' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Hari</label>
                    <select name="day" class="form-select form-select-sm">
                        <option value="">Semua hari</option>
                        @foreach(['senin','selasa','rabu','kamis','jumat','sabtu'] as $d)
                            <option value="{{ $d }}" @selected(request('day') == $d)>{{ ucfirst($d) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kelas</label>
                    <select name="class_id" class="form-select form-select-sm">
                        <option value="">Semua kelas</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-scp btn-sm" type="submit">Cari</button>
                    <a href="{{ route('schedule.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="scp-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th>Mapel</th>
                        <th>Kelas</th>
                        <th>Ruang</th>
                        @if(!$user->isRole(Role::GURU))<th>Guru</th>@endif
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $s)
                        <tr>
                            <td>{{ ucfirst($s->day_of_week) }}</td>
                            <td>
                                {{ substr($s->start_time,0,5) }}-{{ substr($s->end_time,0,5) }}
                                @if($label = \App\Support\JamSesi::label($s->start_time, $s->end_time))
                                    <div class="small text-secondary">{{ $label }}</div>
                                @endif
                            </td>
                            <td>{{ $s->subject }}</td>
                            <td>{{ $s->class->class_name ?? '-' }}</td>
                            <td>{{ $s->room->room_name ?? '-' }}</td>
                            @if(!$user->isRole(Role::GURU))<td>{{ $s->teacher->fullname ?? '-' }}</td>@endif
                            <td class="text-end">
                                @if($user->isRole(Role::GURU) && $s->teacher_id == $user->id)
                                    <a href="{{ route('journal.create', $s->id) }}" class="btn btn-sm btn-outline-secondary">Isi Jurnal</a>
                                    @if(!$locked)
                                        <a href="{{ route('schedule.edit', $s->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        <form action="{{ route('schedule.destroy', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus jadwal ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="scp-empty">{{ request()->hasAny(['q', 'day', 'class_id']) ? 'Tidak ada jadwal yang cocok dengan pencarian.' : 'Belum ada jadwal.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
