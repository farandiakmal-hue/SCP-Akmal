@extends('layouts.app')
@section('title', 'Isi Jurnal')

@section('content')
    <div class="scp-page-head">
    <h1 class="scp-page-title">Isi Jurnal & Presensi</h1>
    <div class="scp-page-sub">
        {{ $schedule->subject }} &middot; {{ $schedule->class->class_name ?? '-' }} &middot;
        {{ ucfirst($schedule->day_of_week) }} {{ substr($schedule->start_time,0,5) }}-{{ substr($schedule->end_time,0,5) }}
    </div>
    </div>

    @if($alreadyFilled)
        <div class="alert alert-info py-2">Jurnal untuk jadwal ini hari ini sudah diisi.</div>
    @else
        <form action="{{ route('journal.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="schedule_id" value="{{ $schedule->id }}">

            <div class="scp-card mb-4">
                <div class="scp-card-header">Jurnal Mengajar</div>
                <div class="p-3">
                    <div class="mb-3">
                        <label class="form-label">Uraian Materi / Kegiatan</label>
                        <textarea name="topic_description" class="form-control form-control-sm" rows="3" required>{{ old('topic_description') }}</textarea>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Foto Bukti Kegiatan (Realtime)</label>
                        <input type="file" name="photo" accept="image/*" capture="environment" class="form-control form-control-sm" required>
                        <div class="form-text">Format JPG/PNG, maksimal 2MB.</div>
                    </div>
                </div>
            </div>

            <div class="scp-card mb-4">
                <div class="scp-card-header">Presensi Siswa</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr><th>Nama</th><th style="width:220px">Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse($students as $s)
                                <tr>
                                    <td>{{ $s->fullname }}</td>
                                    <td>
                                        @if(isset($approvedLeaveStudentIds[$s->id]))
                                            <span class="scp-badge is-info">Izin/Sakit disetujui (otomatis)</span>
                                        @else
                                            <select name="attendance[{{ $s->id }}]" class="form-select form-select-sm">
                                                <option value="hadir">Hadir</option>
                                                <option value="sakit">Sakit</option>
                                                <option value="izin">Izin</option>
                                                <option value="dispensasi">Dispensasi</option>
                                                <option value="alpha">Alpha</option>
                                            </select>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-secondary py-3">Belum ada siswa terdaftar di kelas ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <button type="submit" class="btn btn-scp">Simpan Jurnal & Presensi</button>
        </form>
    @endif
@endsection
