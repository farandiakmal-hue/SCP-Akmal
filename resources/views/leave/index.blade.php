@extends('layouts.app')
@section('title', 'Perizinan')

@section('content')
    @php
        use App\Models\Role;
        $user = auth()->user();
        $statusBadge = fn($s) => match($s) {
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'revisi'   => 'bg-warning text-dark',
            default    => 'bg-secondary',
        };
    @endphp

    <h5 class="fw-bold mb-3">Perizinan Siswa</h5>

    @if($user->isPortalUser())
        <div class="scp-card mb-4">
            <div class="scp-card-header">Ajukan Surat Izin / Sakit</div>
            <div class="p-3">
                <form action="{{ route('leave.store') }}" method="POST" enctype="multipart/form-data" class="row g-2">
                    @csrf
                    @if($user->isRole(Role::ORTU))
                        <div class="col-md-3">
                            <label class="form-label small">Siswa (Anak)</label>
                            <select name="student_id" class="form-select form-select-sm" required>
                                @foreach($children as $c)
                                    <option value="{{ $c->id }}">{{ $c->fullname }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label small">Jenis</label>
                        <select name="type" class="form-select form-select-sm" required>
                            <option value="sakit">Sakit</option>
                            <option value="izin">Izin</option>
                            <option value="izin_tengah_jam">Izin Tengah Jam</option>
                            <option value="dispensasi">Dispensasi</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Mulai</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Selesai</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Bukti Dokumen</label>
                        <input type="file" name="document" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-scp btn-sm w-100" type="submit">Kirim</button>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Alasan</label>
                        <textarea name="reason" class="form-control form-control-sm" rows="2" required></textarea>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="scp-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        @if(!$user->isRole(Role::SISWA))<th>Siswa</th>@endif
                        <th>Jenis</th>
                        <th>Tanggal</th>
                        <th>Alasan</th>
                        <th>Dokumen</th>
                        <th>Status</th>
                        @if($user->isRole(Role::GURU_BK, Role::WALI_KELAS))<th></th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $r)
                        <tr>
                            @if(!$user->isRole(Role::SISWA))<td>{{ $r->student->fullname ?? '-' }}</td>@endif
                            <td>{{ ucfirst(str_replace('_',' ', $r->type)) }}</td>
                            <td class="small">{{ \Carbon\Carbon::parse($r->start_date)->format('d/m/y') }} - {{ \Carbon\Carbon::parse($r->end_date)->format('d/m/y') }}</td>
                            <td class="small">{{ \Illuminate\Support\Str::limit($r->reason, 40) }}</td>
                            <td><a href="{{ route('leave.document', $r->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Lihat</a></td>
                            <td><span class="badge {{ $statusBadge($r->status) }}">{{ ucfirst($r->status) }}</span></td>
                            @if($user->isRole(Role::GURU_BK, Role::WALI_KELAS))
    <td class="text-end text-nowrap">
        @foreach(['approved' => ['Approve', 'btn-outline-success'], 'rejected' => ['Tolak', 'btn-outline-danger'], 'revisi' => ['Revisi', 'btn-outline-warning']] as $st => [$lbl, $cls])
            @if($r->status !== $st)
                <form action="{{ route('leave.verify', [$r->id, $st]) }}" method="POST" class="d-inline"
                      @if($r->status !== 'pending') onsubmit="return confirm('Ubah status izin ini menjadi {{ $lbl }}?')" @endif>
                    @csrf
                    <button class="btn btn-sm {{ $cls }}">{{ $lbl }}</button>
                </form>
            @endif
        @endforeach
    </td>
@endif
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">Belum ada pengajuan izin.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($user->isPortalUser())
        <div class="scp-card mt-4">
            <div class="scp-card-header">Rekap Kehadiran</div>
            <div class="p-3 border-bottom d-flex flex-wrap gap-2">
                @forelse($attendances->groupBy('status') as $st => $rows)
                    <span class="badge badge-soft">{{ ucfirst(str_replace('_',' ',$st)) }}: {{ $rows->count() }}</span>
                @empty
                    <span class="small text-secondary">Belum ada data kehadiran.</span>
                @endforelse
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>Tanggal</th>@if($user->isRole(Role::ORTU))<th>Siswa</th>@endif<th>Mapel</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $a)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($a->date)->format('d M Y') }}</td>
                                @if($user->isRole(Role::ORTU))<td>{{ $a->student->fullname ?? '-' }}</td>@endif
                                <td class="small">{{ $a->journal->schedule->subject ?? '-' }}</td>
                                <td><span class="badge badge-soft">{{ ucfirst(str_replace('_',' ',$a->status)) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-secondary py-4">Belum ada data kehadiran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
