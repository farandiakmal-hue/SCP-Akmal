@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    @php
        use App\Models\Role;
        $user = auth()->user();
    @endphp

    <div class="mb-4">
        <h5 class="fw-bold mb-0">Halo, {{ $user->fullname }}</h5>
        <div class="text-secondary small">{{ $user->roleLabel() }} &middot; Putaran aktif: <strong>{{ $config->active_cycle ?? '-' }}</strong></div>
    </div>

    @if($user->isRole(Role::WAKA))
        <div class="scp-card mb-4">
            <div class="scp-card-header">Pengaturan Siklus & Jadwal</div>
            <div class="p-3">
                <form action="{{ route('config.update') }}" method="POST" class="d-flex flex-wrap align-items-end gap-3 mb-3">
                    @csrf
                    <div>
                        <label class="form-label small fw-medium mb-1">Putaran Aktif</label>
                        <select name="active_cycle" class="form-select form-select-sm" style="min-width:160px" onchange="this.form.submit()">
                            <option value="Putaran 1" {{ $config->active_cycle == 'Putaran 1' ? 'selected' : '' }}>Putaran 1</option>
                            <option value="Putaran 2" {{ $config->active_cycle == 'Putaran 2' ? 'selected' : '' }}>Putaran 2</option>
                        </select>
                    </div>
                </form>

                <form action="{{ route('config.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="toggle_lock" value="1">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge {{ $config->is_schedule_locked ? 'bg-danger' : 'bg-success' }}">
                            {{ $config->is_schedule_locked ? 'Terkunci' : 'Terbuka' }}
                        </span>
                        <span class="small text-secondary">Guru {{ $config->is_schedule_locked ? 'tidak bisa' : 'bisa' }} menambah/mengubah jadwal.</span>
                        <button class="btn btn-sm {{ $config->is_schedule_locked ? 'btn-outline-success' : 'btn-outline-danger' }}" type="submit">
                            {{ $config->is_schedule_locked ? 'Buka Kunci' : 'Kunci Jadwal' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($user->isRole(Role::GURU_BK, Role::WALI_KELAS) && $pendingLeaveCount > 0)
        <div class="scp-card mb-4">
            <div class="p-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-medium">Pengajuan izin menunggu verifikasi</div>
                    <div class="small text-secondary">{{ $pendingLeaveCount }} pengajuan belum diverifikasi</div>
                </div>
                <a href="{{ route('leave.index') }}" class="btn btn-sm btn-scp">Lihat</a>
            </div>
        </div>
    @endif

    @if($user->isRole(Role::GURU, Role::WAKA))
        <div class="scp-card mb-4">
            <div class="scp-card-header">
                Pengingat Jurnal Belum Diisi
                @if($user->isRole(Role::WAKA)) <span class="text-secondary fw-normal small">(seluruh guru)</span> @endif
            </div>
            <div class="p-3">
                @if($alerts->isEmpty())
                    <div class="text-secondary small">Tidak ada jadwal yang terlambat mengisi jurnal hari ini. </div>
                @else
                    @foreach($alerts as $a)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <div class="fw-medium">{{ $a->subject }} &mdash; {{ $a->class->class_name ?? '-' }}</div>
                                <div class="small text-secondary">
                                    {{ ucfirst($a->day_of_week) }}, {{ substr($a->start_time,0,5) }}-{{ substr($a->end_time,0,5) }}
                                    @if($user->isRole(Role::WAKA)) &middot; {{ $a->teacher->fullname ?? '-' }} @endif
                                </div>
                            </div>
                            <span class="badge bg-warning text-dark">Belum Jurnal</span>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        @if($user->isRole(Role::GURU))
            <a href="{{ route('schedule.index') }}" class="btn btn-scp btn-sm">Lihat Jadwal Saya</a>
        @endif
    @endif

    @if($user->isRole(Role::SISWA, Role::ORTU))
        <div class="scp-card">
            <div class="p-3">
                <div class="fw-medium mb-1">Rekap Kehadiran & Perizinan</div>
                <div class="small text-secondary mb-3">Lihat rekap kehadiran atau ajukan surat izin/sakit.</div>
                <a href="{{ route('leave.index') }}" class="btn btn-scp btn-sm">Buka Halaman Izin</a>
            </div>
        </div>
    @endif
@endsection
