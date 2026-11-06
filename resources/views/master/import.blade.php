@extends('layouts.app')
@section('title', 'Manajemen Data - Import')

@section('content')
    <h1 class="scp-page-title">Manajemen Data</h1>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link" href="{{ route('users.index') }}">Pengguna</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('classes.index') }}">Kelas</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('rooms.index') }}">Ruangan</a></li>
        <li class="nav-item"><a class="nav-link active" href="{{ route('import.index') }}">Import</a></li>
    </ul>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @php $report = session('report'); $token = session('import_token'); @endphp

    @if($report)
        <div class="scp-card mb-4">
            <div class="scp-card-header">
                {{ $report['dry'] ? 'Hasil pemeriksaan (belum disimpan)' : 'Hasil impor' }} &mdash; {{ $report['label'] }}
            </div>
            <div class="p-3">
                @if($report['aborted'])
                    <div class="alert alert-danger mb-0">{{ $report['aborted'] }}</div>
                @else
                    <div class="row g-2 mb-3 text-center">
                        <div class="col-6 col-md-2"><div class="border rounded p-2"><div class="fs-4">{{ $report['total'] }}</div><small class="text-muted">baris dibaca</small></div></div>
                        <div class="col-6 col-md-2"><div class="border rounded p-2"><div class="fs-4 text-success">{{ $report['created'] }}</div><small class="text-muted">{{ $report['dry'] ? 'akan ditambah' : 'ditambah' }}</small></div></div>
                        <div class="col-6 col-md-2"><div class="border rounded p-2"><div class="fs-4">{{ $report['updated'] }}</div><small class="text-muted">{{ $report['dry'] ? 'akan diperbarui' : 'diperbarui' }}</small></div></div>
                        <div class="col-6 col-md-2"><div class="border rounded p-2"><div class="fs-4">{{ $report['skipped'] }}</div><small class="text-muted">sudah ada (dilewati)</small></div></div>
                        <div class="col-12 col-md-2"><div class="border rounded p-2"><div class="fs-4 {{ $report['error_count'] ? 'text-danger' : '' }}">{{ $report['error_count'] }}</div><small class="text-muted">baris bermasalah</small></div></div>
                    </div>

                    @foreach($report['warnings'] as $w)
                        <div class="alert alert-warning py-2 small">{{ $w }}</div>
                    @endforeach

                    @if($report['error_count'])
                        <p class="small mb-1 fw-medium">Baris bermasalah (tidak akan disimpan){{ $report['error_count'] > count($report['errors']) ? ' — ' . count($report['errors']) . ' pertama' : '' }}:</p>
                        <div class="table-responsive mb-3" style="max-height:260px;overflow:auto">
                            <table class="table table-sm mb-0">
                                <thead><tr><th style="width:90px">Baris</th><th>Masalah</th></tr></thead>
                                <tbody>
                                    @foreach($report['errors'] as $e)
                                        <tr><td>{{ $e['line'] }}</td><td>{{ $e['message'] }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if(count($report['preview']))
                        <p class="small mb-1 fw-medium">Contoh data yang akan masuk (10 baris pertama):</p>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    @foreach($report['preview'] as $row)
                                        <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if($report['dry'] && $token)
                        <form action="{{ route('import.run') }}" method="POST" class="row g-2 align-items-end border-top pt-3">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <input type="hidden" name="type" value="{{ $report['type'] }}">
                            <input type="hidden" name="mode" value="import">
                            @if($report['update_existing'])<input type="hidden" name="update_existing" value="1">@endif
                            @if($report['type'] === 'pengguna')
                                <div class="col-md-4">
                                    <label class="form-label">Password awal (untuk akun tanpa password di file)</label>
                                    <input type="text" name="default_password" class="form-control form-control-sm" minlength="6" placeholder="min. 6 karakter">
                                </div>
                            @endif
                            <div class="col-md-4">
                                <button class="btn btn-scp btn-sm" type="submit" @disabled($report['created'] + $report['updated'] === 0)>Impor sekarang ({{ $report['created'] + $report['updated'] }} baris)</button>
                            </div>
                        </form>
                    @elseif(!$report['dry'])
                        <div class="alert alert-success mb-0">Selesai. Baris yang valid sudah disimpan. Baris bermasalah (jika ada) bisa diperbaiki di file lalu diimpor ulang; data yang sudah ada otomatis dilewati.</div>
                    @endif
                @endif
            </div>
        </div>
    @endif

    <div class="scp-card mb-4">
        <div class="scp-card-header">Import dari file CSV</div>
        <div class="p-3">
            <p class="small text-muted mb-3">
                Urutan impor: <strong>1. Kelas</strong> &rarr; <strong>2. Ruangan</strong> &rarr; <strong>3. Pengguna</strong> &rarr; <strong>4. Jadwal</strong>.
                Klik <em>Periksa</em> dulu untuk melihat hasilnya tanpa menyimpan, lalu lanjutkan dengan <em>Impor sekarang</em>.
                File dari Excel dengan pemisah koma (,) atau titik koma (;) sama-sama bisa dibaca.
            </p>
            <form action="{{ route('import.run') }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
                @csrf
                <input type="hidden" name="mode" value="check">
                <div class="col-md-4">
                    <label class="form-label">Jenis data</label>
                    <select name="type" class="form-select form-select-sm" required>
                        @foreach($types as $k => $label)
                            <option value="{{ $k }}" @selected(old('type') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">File CSV</label>
                    <input type="file" name="file" accept=".csv,.txt" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="update_existing" value="1" id="upd">
                        <label class="form-check-label small" for="upd">Perbarui data yang sudah ada (khusus Pengguna)</label>
                    </div>
                </div>
                <div class="col-12">
                    <button class="btn btn-scp btn-sm" type="submit">Periksa dulu</button>
                </div>
            </form>
        </div>
    </div>

    <div class="scp-card">
        <div class="scp-card-header">Format kolom &amp; template</div>
        <div class="p-3 small">
            @foreach($types as $k => $label)
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $label }}</strong>
                        <a href="{{ route('import.template', $k) }}">Unduh template</a>
                    </div>
                    <code>{{ implode(', ', $columns[$k]) }}</code>
                </div>
            @endforeach
            <ul class="mb-0 text-muted">
                <li>Role pengguna: <code>siswa</code>, <code>guru</code>, <code>wali_kelas</code>, <code>guru_bk</code>, <code>waka</code>, <code>ortu</code>. Siswa dan wali kelas wajib punya kelas.</li>
                <li>Kolom <code>password</code> boleh kosong; akun baru memakai &ldquo;Password awal&rdquo; yang Anda isi saat impor.</li>
                <li>Jadwal: <code>day</code> senin&ndash;sabtu, jam format <code>HH:MM</code>, tidak boleh melewati jam istirahat, dan tidak boleh bentrok (guru/kelas/ruangan).</li>
            </ul>
        </div>
    </div>
@endsection
