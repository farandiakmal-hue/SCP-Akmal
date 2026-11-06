@extends('layouts.app')
@section('title', 'Rekap Presensi')

@section('content')
    <div class="scp-page-head d-flex justify-content-between align-items-center">
        <h1 class="scp-page-title mb-0">Rekap Presensi</h1>
        <a href="{{ route('reports.attendance.export', request()->query()) }}" class="btn btn-sm btn-outline-secondary">Export CSV</a>
    </div>

    <div class="scp-card mb-4">
        <div class="p-3">
            <form action="{{ route('reports.attendance') }}" method="GET" class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach(['hadir','sakit','izin','izin_tengah_jam','dispensasi','alpha'] as $st)
                            <option value="{{ $st }}" {{ request('status')==$st?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-scp btn-sm w-100" type="submit">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="scp-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr><th>Tanggal</th><th>Siswa</th><th>Status</th><th>Mapel</th><th>Sumber</th></tr>
                </thead>
                <tbody>
                    @forelse($attendances as $a)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($a->date)->format('d M Y') }}</td>
                            <td>{{ $a->student->fullname ?? '-' }}</td>
                            <td>@include('partials.status', ['type' => 'attendance', 'value' => $a->status])</td>
                            <td class="small">{{ $a->journal->schedule->subject ?? '-' }}</td>
                            <td class="small text-secondary">{{ $a->source == 'sync_izin' ? 'Otomatis (izin)' : 'Manual guru' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="scp-empty">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
