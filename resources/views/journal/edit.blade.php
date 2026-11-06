@extends('layouts.app')
@section('title', 'Edit Jurnal')

@section('content')
    <div class="scp-page-head d-flex justify-content-between align-items-start">
        <div>
            <h1 class="scp-page-title">Edit Jurnal</h1>
            <div class="scp-page-sub">
                {{ $journal->schedule->subject ?? '-' }} &middot; {{ $journal->schedule->class->class_name ?? '-' }}
                &middot; {{ \Carbon\Carbon::parse($journal->date)->format('d M Y') }}
            </div>
        </div>
        <a href="{{ route('journal.show', $journal->id) }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
    </div>

    <div class="alert alert-info py-2">Jurnal hanya dapat diubah pada hari yang sama dengan tanggal jurnal.</div>

    <form action="{{ route('journal.update', $journal->id) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="scp-card">
            <div class="scp-card-header">Jurnal Mengajar</div>
            <div class="p-3">
                <div class="mb-3">
                    <label class="form-label">Uraian Materi / Kegiatan</label>
                    <textarea name="topic_description" class="form-control form-control-sm" rows="3" required>{{ old('topic_description', $journal->topic_description) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Foto Bukti Saat Ini</label>
                    <div>
                        <a href="{{ route('journal.photo', $journal->id) }}" target="_blank">
                            <img src="{{ route('journal.photo', $journal->id) }}" alt="Foto bukti" class="img-fluid rounded border" style="max-height:200px;">
                        </a>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Ganti Foto (opsional)</label>
                    <input type="file" name="photo" accept="image/jpeg,image/png" capture="environment" class="form-control form-control-sm">
                    <div class="form-text">Format JPG/PNG, maksimal 2MB. Kosongkan bila foto tidak diganti.</div>
                </div>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-scp">Simpan Perubahan</button>
            <a href="{{ route('journal.show', $journal->id) }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
@endsection
