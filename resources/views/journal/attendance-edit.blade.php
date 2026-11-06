@extends('layouts.app')
@section('title', 'Edit Presensi')

@section('content')
    <div class="scp-page-head d-flex justify-content-between align-items-start">
        <div>
            <h1 class="scp-page-title">Edit Daftar Hadir</h1>
            <div class="scp-page-sub">
                {{ $journal->schedule->subject ?? '-' }} &middot; {{ $journal->schedule->class->class_name ?? '-' }}
                &middot; {{ \Carbon\Carbon::parse($journal->date)->format('d M Y') }}
            </div>
        </div>
        <a href="{{ route('journal.show', $journal->id) }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
    </div>

    <form action="{{ route('journal.attendance.update', $journal->id) }}" method="POST">
        @csrf @method('PUT')
        <div class="scp-card">
            <div class="scp-card-header">Presensi Siswa</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th style="width:60px">No</th><th>Nama</th><th style="width:240px">Status</th></tr></thead>
                    <tbody>
                        @forelse($students as $i => $s)
                            @php $row = $rows->get($s->id); @endphp
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $s->fullname }}</td>
                                <td>
                                    @if($row && $row->source === 'sync_izin')
                                        @include('partials.status', ['type' => 'attendance', 'value' => $row->status])
                                        <div class="small text-secondary mt-1">Dari izin yang disetujui (otomatis)</div>
                                    @else
                                        <select name="attendance[{{ $s->id }}]" class="form-select form-select-sm">
                                            @foreach(['hadir' => 'Hadir', 'sakit' => 'Sakit', 'izin' => 'Izin', 'izin_tengah_jam' => 'Izin Tengah Jam', 'dispensasi' => 'Dispensasi', 'alpha' => 'Alpha'] as $val => $lbl)
                                                <option value="{{ $val }}" @selected(old('attendance.' . $s->id, $row->status ?? 'hadir') == $val)>{{ $lbl }}</option>
                                            @endforeach
                                        </select>
                                        @unless($row)
                                            <div class="small text-secondary mt-1">Belum tercatat pada jurnal ini</div>
                                        @endunless
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="scp-empty">Belum ada siswa terdaftar di kelas ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-scp">Simpan Perubahan</button>
            <a href="{{ route('journal.show', $journal->id) }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
@endsection
