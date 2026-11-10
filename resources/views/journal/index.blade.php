@extends('layouts.app')
@section('title', 'Riwayat Jurnal')

@section('content')
    <h5 class="fw-bold mb-3">Riwayat Jurnal</h5>

    <div class="scp-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Mapel</th>
                        <th>Kelas</th>
                        <th>Guru</th>
                        <th>Materi</th>
                        <th>Foto</th>
                        <th>Presensi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($journals as $j)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($j->date)->format('d M Y') }}</td>
                            <td>{{ $j->schedule->subject ?? '-' }}</td>
                            <td>{{ $j->schedule->class->class_name ?? '-' }}</td>
                            <td>{{ $j->schedule->teacher->fullname ?? '-' }}</td>
                            <td class="small">{{ \Illuminate\Support\Str::limit($j->topic_description, 60) }}</td>
                            <td>
                                <a href="{{ route('journal.photo', $j->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Lihat</a>
                            </td>
                            <td>
                                <a href="{{ route('journal.show', $j->id) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">Belum ada jurnal.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($journals->hasPages())
            <div class="p-3 border-top">{{ $journals->links() }}</div>
        @endif
    </div>
@endsection
