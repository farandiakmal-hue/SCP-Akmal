@extends('layouts.app')
@section('title', 'Laporan Izin Harian')

@section('content')
    @php
        use App\Models\Role;
        $user = auth()->user();
        $jenis = ['sakit' => 'Sakit', 'izin' => 'Izin', 'izin_tengah_jam' => 'Izin Tengah Jam', 'dispensasi' => 'Dispensasi'];
        $byType = $leaves->groupBy('type')->map->count();
    @endphp

    <div class="scp-page-head d-flex justify-content-between align-items-center">
        <div>
            <h1 class="scp-page-title">Laporan Izin Siswa Harian</h1>
            <div class="scp-page-sub">{{ \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('l, d F Y') }}</div>
        </div>
        <a href="{{ route('reports.leave.daily.export', request()->query()) }}" class="btn btn-sm btn-outline-secondary">Export CSV</a>
    </div>

    <div class="scp-card mb-3">
        <div class="p-3">
            <form action="{{ route('reports.leave.daily') }}" method="GET" class="row g-2 align-items-end">
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
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="approved" @selected($status === 'approved')>Disetujui (Approved)</option>
                        <option value="pending" @selected($status === 'pending')>Menunggu (Pending)</option>
                        <option value="rejected" @selected($status === 'rejected')>Ditolak (Rejected)</option>
                        <option value="revisi" @selected($status === 'revisi')>Revisi</option>
                        <option value="semua" @selected($status === 'semua')>Semua status</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-scp btn-sm" type="submit">Tampilkan</button>
                    <a href="{{ route('reports.leave.daily') }}" class="btn btn-outline-secondary btn-sm">Hari ini</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3"><div class="scp-card scp-stat"><div class="scp-stat-label">Total Siswa</div><div class="scp-stat-value">{{ $leaves->count() }}</div></div></div>
        @foreach($jenis as $key => $label)
            <div class="col-6 col-md-3"><div class="scp-card scp-stat"><div class="scp-stat-label">{{ $label }}</div><div class="scp-stat-value">{{ $byType[$key] ?? 0 }}</div></div></div>
        @endforeach
    </div>

    <div class="scp-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr><th>No</th><th>Siswa</th><th>Kelas</th><th>Jenis</th><th>Periode</th><th>Alasan</th><th>Dokumen</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($leaves as $i => $l)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $l->student->fullname ?? '-' }}</td>
                            <td>{{ $l->student->classRoom->class_name ?? '-' }}</td>
                            <td>{{ $jenis[$l->type] ?? ucfirst(str_replace('_', ' ', $l->type)) }}</td>
                            <td class="small">{{ \Carbon\Carbon::parse($l->start_date)->format('d/m/y') }} - {{ \Carbon\Carbon::parse($l->end_date)->format('d/m/y') }}</td>
                            <td class="small" style="min-width:160px;white-space:normal;">{{ \Illuminate\Support\Str::limit($l->reason, 60) }}</td>
                            <td><a href="{{ route('leave.document', $l->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Lihat</a></td>
                            <td>
                                @include('partials.status', ['type' => 'leave', 'value' => $l->status])
                                @if($l->verification_note && in_array($l->status, ['rejected', 'revisi']))
                                    <div class="small text-secondary mt-1" style="max-width:200px;white-space:normal;">Alasan: {{ $l->verification_note }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="scp-empty">Tidak ada siswa yang izin pada tanggal dan filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
