@extends('layouts.app')
@section('title', 'Detail Jurnal')

@section('content')
    @php
        $labels = ['hadir' => 'Hadir', 'sakit' => 'Sakit', 'izin' => 'Izin', 'izin_tengah_jam' => 'Izin Tengah Jam', 'dispensasi' => 'Dispensasi', 'alpha' => 'Alpha'];
        $summary = $attendances->groupBy('status')->map->count();
    @endphp

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="scp-page-title mb-0">Detail Jurnal & Presensi</h1>
        <div class="d-flex gap-2">
            @if($canEdit)
                <a href="{{ route('journal.attendance.edit', $journal->id) }}" class="btn btn-sm btn-scp">Edit Presensi</a>
            @endif
            <a href="{{ route('journal.index') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
        </div>
    </div>

    <div class="scp-card mb-3">
        <div class="p-3">
            <div class="fw-semibold">{{ $journal->schedule->subject ?? '-' }}
                <span class="text-secondary fw-normal">· {{ $journal->schedule->class->class_name ?? '-' }}</span>
            </div>
            <div class="small text-secondary mb-2">
                {{ \Carbon\Carbon::parse($journal->date)->format('d M Y') }}
                · {{ \Carbon\Carbon::parse($journal->schedule->start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($journal->schedule->end_time)->format('H:i') }}
                · {{ $journal->schedule->teacher->fullname ?? '-' }}
            </div>
            <div class="small">{{ $journal->topic_description }}</div>
            <a href="{{ route('journal.photo', $journal->id) }}" target="_blank">
                <img src="{{ route('journal.photo', $journal->id) }}" alt="Foto bukti kegiatan"
                     class="img-fluid rounded border mt-3" style="max-height:320px;">
            </a>
            <div class="small text-secondary mt-1">Ketuk foto untuk memperbesar.</div>
        </div>
    </div>

    <div class="scp-card">
        <div class="scp-card-header">Presensi Siswa</div>
        <div class="p-3 border-bottom d-flex flex-wrap gap-2">
            @foreach($labels as $key => $label)
                @include('partials.status', ['type' => 'attendance', 'value' => $key, 'suffix' => ': ' . ($summary[$key] ?? 0)])
            @endforeach
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>No</th><th>Nama Siswa</th><th>Status</th><th>Keterangan</th></tr></thead>
                <tbody>
                    @forelse($attendances as $i => $a)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $a->student->fullname ?? '-' }}</td>
                            <td>@include('partials.status', ['type' => 'attendance', 'value' => $a->status])</td>
                            <td class="small text-secondary">{{ $a->source === 'sync_izin' ? 'Otomatis dari izin yang disetujui' : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="scp-empty">Belum ada data presensi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
