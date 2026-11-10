@extends('layouts.app')
@section('title', 'Rekap Presensi Harian')

@section('content')
    <h5 class="fw-bold mb-3">Rekap Presensi Harian</h5>

    <div class="scp-card mb-3">
        <div class="p-3">
            <form method="GET" action="{{ route('reports.daily') }}" class="row g-2">
                <div class="col-md-4">
                    <label class="form-label small">Tanggal</label>
                    <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Kelas</label>
                    <select name="class_id" class="form-select form-select-sm">
                        <option value="">Semua kelas</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button class="btn btn-scp btn-sm flex-fill" type="submit">Filter</button>
                    <a href="{{ route('reports.daily') }}" class="btn btn-sm btn-outline-secondary">Hari ini</a>
                </div>
            </form>
        </div>
    </div>

    <div class="scp-card">
        <div class="scp-card-header">
            {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Jam</th>
                        <th>Mapel</th>
                        <th>Kelas</th>
                        <th class="text-center">Hadir</th>
                        <th class="text-center">Sakit</th>
                        <th class="text-center">Izin</th>
                        <th class="text-center">Dispensasi</th>
                        <th class="text-center">Alpha</th>
                        <th class="text-center">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($journals as $j)
                        <tr>
                            <td>{{ substr($j->schedule->start_time, 0, 5) }}-{{ substr($j->schedule->end_time, 0, 5) }}</td>
                            <td>{{ $j->schedule->subject ?? '-' }}</td>
                            <td>{{ $j->schedule->class->class_name ?? '-' }}</td>
                            <td class="text-center">{{ $j->hadir_count }}</td>
                            <td class="text-center">{{ $j->sakit_count }}</td>
                            <td class="text-center">{{ $j->izin_count }}</td>
                            <td class="text-center">{{ $j->dispensasi_count }}</td>
                            <td class="text-center">{{ $j->alpha_count }}</td>
                            <td class="text-center fw-semibold">{{ $j->total_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('journal.show', $j->id) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-secondary py-4">Belum ada jurnal pada tanggal ini.</td></tr>
                    @endforelse
                </tbody>
                @if($journals->count() > 1)
                    <tfoot>
                        <tr class="fw-semibold">
                            <td colspan="3" class="text-end">Jumlah</td>
                            <td class="text-center">{{ $journals->sum('hadir_count') }}</td>
                            <td class="text-center">{{ $journals->sum('sakit_count') }}</td>
                            <td class="text-center">{{ $journals->sum('izin_count') }}</td>
                            <td class="text-center">{{ $journals->sum('dispensasi_count') }}</td>
                            <td class="text-center">{{ $journals->sum('alpha_count') }}</td>
                            <td class="text-center">{{ $journals->sum('total_count') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection