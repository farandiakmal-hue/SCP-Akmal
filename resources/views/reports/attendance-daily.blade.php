@extends('layouts.app')
@section('title', 'Rekap Presensi Harian')

@section('content')
    @php
        use App\Models\Role;
        $user = auth()->user();
    @endphp

    <div class="scp-page-head d-flex justify-content-between align-items-center">
        <div>
            <h1 class="scp-page-title">Rekap Presensi Harian</h1>
            <div class="scp-page-sub">{{ \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('l, d F Y') }}</div>
        </div>
        <a href="{{ route('reports.attendance.daily.export', request()->query()) }}" class="btn btn-sm btn-outline-secondary">Export CSV</a>
    </div>

    <div class="scp-card mb-3">
        <div class="p-3">
            <form action="{{ route('reports.attendance.daily') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm">
                </div>
                @if(!$user->isRole(Role::WALI_KELAS))
                    <div class="col-md-3">
                        <label class="form-label">Kelas</label>
                        <select name="class_id" class="form-select form-select-sm">
                            <option value="">Semua kelas</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->class_name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-4">
                    <label class="form-label">Cari mapel{{ $user->isRole(Role::GURU) ? '' : ' / guru' }}</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Kata kunci">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-scp btn-sm" type="submit">Tampilkan</button>
                    <a href="{{ route('reports.attendance.daily') }}" class="btn btn-outline-secondary btn-sm">Hari ini</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3"><div class="scp-card scp-stat"><div class="scp-stat-label">Pertemuan (jurnal)</div><div class="scp-stat-value">{{ $rows->count() }}</div></div></div>
        <div class="col-6 col-md-3"><div class="scp-card scp-stat"><div class="scp-stat-label">Jumlah Hadir</div><div class="scp-stat-value">{{ $totals['hadir'] }}</div><div class="scp-stat-note">dari {{ $totals['total'] }} data presensi</div></div></div>
        <div class="col-6 col-md-3"><div class="scp-card scp-stat"><div class="scp-stat-label">Tidak Hadir</div><div class="scp-stat-value">{{ $totals['total'] - $totals['hadir'] }}</div><div class="scp-stat-note">sakit, izin, dispensasi, alpha</div></div></div>
        <div class="col-6 col-md-3"><div class="scp-card scp-stat"><div class="scp-stat-label">Persentase Hadir</div><div class="scp-stat-value">{{ $totals['persen'] !== null ? $totals['persen'] . '%' : '-' }}</div></div></div>
    </div>

    <div class="scp-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Jam</th><th>Kelas</th><th>Mapel</th>
                        @if(!$user->isRole(Role::GURU))<th>Guru</th>@endif
                        <th class="text-center">Hadir</th><th class="text-center">Sakit</th><th class="text-center">Izin</th>
                        <th class="text-center">Dispensasi</th><th class="text-center">Alpha</th><th class="text-center">Total</th><th class="text-center">% Hadir</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $j)
                        <tr>
                            <td>
                                {{ substr($j->schedule->start_time,0,5) }}-{{ substr($j->schedule->end_time,0,5) }}
                                @if($lbl = \App\Support\JamSesi::label($j->schedule->start_time, $j->schedule->end_time))<div class="small text-secondary">{{ $lbl }}</div>@endif
                            </td>
                            <td>{{ $j->schedule->class->class_name ?? '-' }}</td>
                            <td>{{ $j->schedule->subject ?? '-' }}</td>
                            @if(!$user->isRole(Role::GURU))<td>{{ $j->schedule->teacher->fullname ?? '-' }}</td>@endif
                            <td class="text-center"><span class="scp-badge is-success">{{ $j->hadir_count }}</span></td>
                            <td class="text-center">{{ $j->sakit_count }}</td>
                            <td class="text-center">{{ $j->izin_count + $j->izin_tengah_jam_count }}</td>
                            <td class="text-center">{{ $j->dispensasi_count }}</td>
                            <td class="text-center">@if($j->alpha_count)<span class="scp-badge is-danger">{{ $j->alpha_count }}</span>@else 0 @endif</td>
                            <td class="text-center">{{ $j->total_count }}</td>
                            <td class="text-center">{{ $j->total_count > 0 ? round(($j->hadir_count / $j->total_count) * 100) . '%' : '-' }}</td>
                            <td class="text-end"><a href="{{ route('journal.show', $j->id) }}" class="btn btn-sm btn-outline-secondary">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="scp-empty">Belum ada jurnal/presensi pada tanggal dan filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
