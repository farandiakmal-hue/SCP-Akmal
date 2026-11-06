@extends('layouts.app')
@section('title', 'Presensi')

@section('content')
    @php
        use App\Models\Role;
        $user = auth()->user();
        $labels = ['hadir' => 'Hadir', 'sakit' => 'Sakit', 'izin' => 'Izin', 'izin_tengah_jam' => 'Izin Tengah Jam', 'dispensasi' => 'Dispensasi', 'alpha' => 'Alpha'];
    @endphp

    <div class="scp-page-head">
    <h1 class="scp-page-title">Presensi Siswa</h1>
    <div class="scp-page-sub">
        @foreach($students as $st)
            {{ $st->fullname }} &middot; Kelas {{ $st->classRoom->class_name ?? 'belum diatur' }}@if(!$loop->last) &nbsp;|&nbsp; @endif
        @endforeach
    </div>
    </div>

    <div class="scp-card mb-3">
        <div class="p-3">
            <form method="GET" action="{{ route('attendance.mine') }}" class="row g-2 align-items-end">
                @if($user->isRole(Role::ORTU))
                    <div class="col-md-3">
                        <label class="form-label">Siswa (Anak)</label>
                        <select name="student_id" class="form-select form-select-sm">
                            <option value="">Semua anak</option>
                            @foreach($children as $c)
                                <option value="{{ $c->id }}" @selected(request('student_id') == $c->id)>{{ $c->fullname }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label">Bulan</label>
                    <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach($labels as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-scp btn-sm" type="submit">Terapkan</button>
                    <a href="{{ route('attendance.mine') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="scp-card scp-stat">
                <div class="scp-stat-label">Persentase Hadir</div>
                <div class="scp-stat-value">{{ $percentHadir !== null ? $percentHadir . '%' : '-' }}</div>
                <div class="scp-stat-note">dari {{ $total }} pertemuan</div>
            </div>
        </div>
        @foreach($labels as $key => $label)
            <div class="col-6 col-md-3">
                <div class="scp-card scp-stat">
                    <div class="scp-stat-label">{{ $label }}</div>
                    <div class="scp-stat-value">{{ $summary[$key] ?? 0 }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="scp-card">
        <div class="scp-card-header">Riwayat Kehadiran</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        @if($user->isRole(Role::ORTU))<th>Siswa</th>@endif
                        <th>Mapel</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $a)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($a->date)->format('d M Y') }}</td>
                            @if($user->isRole(Role::ORTU))<td>{{ $a->student->fullname ?? '-' }}</td>@endif
                            <td class="small">{{ $a->journal->schedule->subject ?? '-' }}</td>
                            <td>@include('partials.status', ['type' => 'attendance', 'value' => $a->status])</td>
                            <td class="small text-secondary">{{ $a->source === 'sync_izin' ? 'Dari izin yang disetujui' : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="scp-empty">Belum ada data kehadiran. Presensi muncul setelah guru mengisi jurnal untuk kelas siswa ini pada jadwal yang sesuai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
