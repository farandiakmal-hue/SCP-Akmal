@extends('layouts.app')
@section('title', 'Edit Jurnal')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="fw-bold mb-0">Edit Jurnal & Presensi</h5>
        <a href="{{ route('journal.show', $journal->id) }}" class="btn btn-sm btn-outline-secondary">Batal</a>
    </div>
    <div class="small text-secondary mb-3">
        {{ $journal->schedule->subject }} &middot; {{ $journal->schedule->class->class_name ?? '-' }}
        &middot; {{ \Carbon\Carbon::parse($journal->date)->format('d M Y') }}
    </div>

    <form action="{{ route('journal.update', $journal->id) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="scp-card mb-4">
            <div class="scp-card-header">Jurnal Mengajar</div>
            <div class="p-3">
                <div class="mb-3">
                    <label class="form-label small fw-medium">Uraian Materi / Kegiatan</label>
                    <textarea name="topic_description" class="form-control" rows="3" required>{{ old('topic_description', $journal->topic_description) }}</textarea>
                </div>
                <div class="mb-1">
                    <label class="form-label small fw-medium">Ganti Foto Bukti (opsional)</label>
                    <img src="{{ route('journal.photo', $journal->id) }}" alt="Foto saat ini"
                         class="img-fluid rounded border d-block mb-2" style="max-height:200px;">
                    <input type="file" name="photo" accept="image/*" class="form-control">
                    <div class="form-text">Kosongkan jika tidak ingin mengganti foto. JPG/PNG, maksimal 2MB.</div>
                </div>
            </div>
        </div>

        <div class="scp-card mb-4">
            <div class="scp-card-header">Presensi Siswa</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Nama</th><th style="width:220px">Status</th></tr></thead>
                    <tbody>
                        @foreach($attendances as $a)
                            <tr>
                                <td>{{ $a->student->fullname ?? '-' }}</td>
                                <td>
                                    @if($a->source === 'sync_izin')
                                        <span class="badge badge-soft">Izin/Sakit disetujui (otomatis)</span>
                                    @else
                                        <select name="attendance[{{ $a->student_id }}]" class="form-select form-select-sm">
                                            @foreach(['hadir'=>'Hadir','sakit'=>'Sakit','izin'=>'Izin','dispensasi'=>'Dispensasi','alpha'=>'Alpha'] as $val => $lbl)
                                                <option value="{{ $val }}" @selected($a->status === $val)>{{ $lbl }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <button type="submit" class="btn btn-scp">Simpan Perubahan</button>
    </form>
@endsection