@extends('layouts.app')
@section('title', 'Jadwal')

@section('content')
    @php
        use App\Models\Role;
        $user = auth()->user();
        $locked = $config->is_schedule_locked ?? false;
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold mb-0">Jadwal Mengajar</h5>
            <div class="small text-secondary">Putaran aktif: {{ $config->active_cycle ?? '-' }}</div>
        </div>
        @if($locked)
            <span class="badge bg-danger">Jadwal Terkunci</span>
        @endif
    </div>

    @if($user->isRole(Role::GURU) && !$locked)
    @if(!$user->isRole(Role::GURU))
    <form method="GET" action="{{ route('schedule.index') }}" class="d-flex align-items-center gap-2 mb-3">
        <label class="small fw-medium mb-0">Hari</label>
        <select name="hari" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="semua" @selected($hari === 'semua')>Semua hari</option>
            @foreach(['senin','selasa','rabu','kamis','jumat','sabtu'] as $d)
                <option value="{{ $d }}" @selected($hari === $d)>{{ ucfirst($d) }}</option>
            @endforeach
        </select>
    </form>
@endif
        <div class="scp-card mb-4">
            <div class="scp-card-header">Tambah Jadwal Baru</div>
            <div class="p-3">
                <form action="{{ route('schedule.store') }}" method="POST" class="row g-2">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label small">Mapel</label>
                        <input type="text" name="subject" class="form-control form-control-sm" required value="{{ old('subject') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Kelas</label>
                        <select name="class_id" class="form-select form-select-sm" required>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Ruang</label>
                        <select name="room_id" class="form-select form-select-sm" required>
                            @foreach($rooms as $r)
                                <option value="{{ $r->id }}">{{ $r->room_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Hari</label>
                        <select name="day_of_week" class="form-select form-select-sm" required>
                            @foreach(['senin','selasa','rabu','kamis','jumat','sabtu'] as $d)
                                <option value="{{ $d }}">{{ ucfirst($d) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Sesi Mulai</label>
                        <select name="sesi_mulai" id="sesiMulai" class="form-select form-select-sm" required>
                            @foreach(\App\Support\JamSesi::all() as $sesi)
                                <option value="{{ $sesi['no'] }}" data-start="{{ $sesi['start'] }}" data-end="{{ $sesi['end'] }}" @selected(old('sesi_mulai') == $sesi['no'])>Sesi {{ $sesi['no'] }} ({{ $sesi['start'] }}-{{ $sesi['end'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Sesi Selesai</label>
                        <select name="sesi_selesai" id="sesiSelesai" class="form-select form-select-sm" required>
                            @foreach(\App\Support\JamSesi::all() as $sesi)
                                <option value="{{ $sesi['no'] }}" data-start="{{ $sesi['start'] }}" data-end="{{ $sesi['end'] }}" @selected(old('sesi_selesai') == $sesi['no'])>Sesi {{ $sesi['no'] }} ({{ $sesi['start'] }}-{{ $sesi['end'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <span class="small text-secondary" id="sesiPreview"></span>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-scp btn-sm w-100" type="submit">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @elseif($user->isRole(Role::GURU) && $locked)
        <div class="alert alert-warning py-2 small">Jadwal sedang dikunci oleh Waka Kurikulum. Anda tidak bisa menambah jadwal baru.</div>
    @endif

    <div class="scp-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th>Mapel</th>
                        <th>Kelas</th>
                        <th>Ruang</th>
                        @if(!$user->isRole(Role::GURU))<th>Guru</th>@endif
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $s)
                        <tr>
                            <td>{{ ucfirst($s->day_of_week) }}</td>
                            <td>
                                {{ substr($s->start_time,0,5) }}-{{ substr($s->end_time,0,5) }}
                                @if($label = \App\Support\JamSesi::label($s->start_time, $s->end_time))
                                    <div class="small text-secondary">{{ $label }}</div>
                                @endif
                            </td>
                            <td>{{ $s->subject }}</td>
                            <td>{{ $s->class->class_name ?? '-' }}</td>
                            <td>{{ $s->room->room_name ?? '-' }}</td>
                            @if(!$user->isRole(Role::GURU))<td>{{ $s->teacher->fullname ?? '-' }}</td>@endif
                            <td class="text-end">
                                @if($user->isRole(Role::GURU) && $s->teacher_id == $user->id)
                                    <a href="{{ route('journal.create', $s->id) }}" class="btn btn-sm btn-outline-secondary">Isi Jurnal</a>
                                    @if(!$locked)
                                        <form action="{{ route('schedule.destroy', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus jadwal ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">Belum ada jadwal.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        (function () {
            var m = document.getElementById('sesiMulai'), e = document.getElementById('sesiSelesai'), pv = document.getElementById('sesiPreview');
            if (!m || !e || !pv) return;
            function update() {
                var a = m.options[m.selectedIndex], b = e.options[e.selectedIndex];
                if (parseInt(e.value) < parseInt(m.value)) { pv.textContent = 'Sesi selesai tidak boleh sebelum sesi mulai.'; return; }
                pv.textContent = 'Jam jadwal: ' + a.dataset.start + ' - ' + b.dataset.end;
            }
            m.addEventListener('change', function () { if (parseInt(e.value) < parseInt(m.value)) e.value = m.value; update(); });
            e.addEventListener('change', update);
            update();
        })();
    </script>
@endsection
