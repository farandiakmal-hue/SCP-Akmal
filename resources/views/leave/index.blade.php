@extends('layouts.app')
@section('title', 'Perizinan')

@section('content')
    @php
        use App\Models\Role;
        $user = auth()->user();
    @endphp

    <h1 class="scp-page-title">Perizinan Siswa</h1>

    @if($user->isPortalUser())
        <div class="scp-card mb-4">
            <div class="scp-card-header">Ajukan Surat Izin / Sakit</div>
            <div class="p-3">
                <form action="{{ route('leave.store') }}" method="POST" enctype="multipart/form-data" class="row g-2">
                    @csrf
                    @if($user->isRole(Role::ORTU))
                        <div class="col-md-3">
                            <label class="form-label">Siswa (Anak)</label>
                            <select name="student_id" class="form-select form-select-sm" required>
                                @foreach($children as $c)
                                    <option value="{{ $c->id }}">{{ $c->fullname }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">Jenis</label>
                        <select name="type" class="form-select form-select-sm" required>
                            <option value="sakit">Sakit</option>
                            <option value="izin">Izin</option>
                            <option value="izin_tengah_jam">Izin Tengah Jam</option>
                            <option value="dispensasi">Dispensasi</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Mulai</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Selesai</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bukti Dokumen</label>
                        <input type="file" name="document" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-scp btn-sm w-100" type="submit">Kirim</button>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alasan</label>
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
                            <td>
                                @include('partials.status', ['type' => 'leave', 'value' => $r->status])
                                @if($r->verification_note && in_array($r->status, ['rejected', 'revisi']))
                                    <div class="small text-secondary mt-1" style="max-width:220px;white-space:normal;">
                                        <strong>Alasan:</strong> {{ $r->verification_note }}
                                    </div>
                                @endif
                            </td>
                            @if($user->isRole(Role::GURU_BK, Role::WALI_KELAS))
                                <td class="text-end">
                                    @if(in_array($r->status, ['pending', 'revisi']))
                                        <form action="{{ route('leave.verify', [$r->id, 'approved']) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success">Approve</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal" data-bs-target="#reasonModal"
                                                data-action="{{ route('leave.verify', [$r->id, 'rejected']) }}"
                                                data-title="Tolak Izin" data-student="{{ $r->student->fullname ?? '' }}">Tolak</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal" data-bs-target="#reasonModal"
                                                data-action="{{ route('leave.verify', [$r->id, 'revisi']) }}"
                                                data-title="Minta Revisi Izin" data-student="{{ $r->student->fullname ?? '' }}">Revisi</button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="7" class="scp-empty">Belum ada pengajuan izin.</td></tr>
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
                    @include('partials.status', ['type' => 'attendance', 'value' => $st, 'suffix' => ': ' . $rows->count()])
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
                                <td>@include('partials.status', ['type' => 'attendance', 'value' => $a->status])</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="scp-empty">Belum ada data kehadiran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($user->isRole(Role::GURU_BK, Role::WALI_KELAS))
        <div class="modal fade" id="reasonModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="" id="reasonForm" class="modal-content" style="border-radius:var(--scp-radius);">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fs-6 fw-bold" id="reasonTitle">Alasan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="small text-secondary mb-2">Siswa: <strong id="reasonStudent">-</strong></div>
                        <label class="form-label">Alasan (akan dilihat siswa/orang tua)</label>
                        <textarea name="alasan" class="form-control form-control-sm" rows="3" minlength="5" maxlength="500" required placeholder="Contoh: Dokumen tidak terbaca / surat dokter belum dilampirkan"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-scp">Kirim Keputusan</button>
                    </div>
                </form>
            </div>
        </div>
        @push('scripts')
        <script>
            document.getElementById('reasonModal').addEventListener('show.bs.modal', function (e) {
                var b = e.relatedTarget;
                document.getElementById('reasonForm').action = b.dataset.action;
                document.getElementById('reasonTitle').textContent = b.dataset.title;
                document.getElementById('reasonStudent').textContent = b.dataset.student || '-';
                this.querySelector('textarea').value = '';
            });
        </script>
        @endpush
    @endif
@endsection
