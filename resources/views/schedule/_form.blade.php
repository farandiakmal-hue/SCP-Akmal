{{-- Form jadwal bersama (tambah & edit). Variabel: $action, $method ('POST'|'PUT'), $submit, $classes, $rooms, opsional $schedule, $sesiMulai, $sesiSelesai --}}
<form action="{{ $action }}" method="POST" class="row g-2">
    @csrf
    @if(($method ?? 'POST') !== 'POST') @method($method) @endif
    <div class="col-md-3">
        <label class="form-label">Mapel</label>
        <input type="text" name="subject" class="form-control form-control-sm" required value="{{ old('subject', $schedule->subject ?? '') }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Kelas</label>
        <select name="class_id" class="form-select form-select-sm" required>
            @foreach($classes as $c)
                <option value="{{ $c->id }}" @selected(old('class_id', $schedule->class_id ?? null) == $c->id)>{{ $c->class_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Ruang</label>
        <select name="room_id" class="form-select form-select-sm" required>
            @foreach($rooms as $r)
                <option value="{{ $r->id }}" @selected(old('room_id', $schedule->room_id ?? null) == $r->id)>{{ $r->room_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Hari</label>
        <select name="day_of_week" class="form-select form-select-sm" required>
            @foreach(['senin','selasa','rabu','kamis','jumat','sabtu'] as $d)
                <option value="{{ $d }}" @selected(old('day_of_week', $schedule->day_of_week ?? null) == $d)>{{ ucfirst($d) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Sesi Mulai</label>
        <select name="sesi_mulai" id="sesiMulai" class="form-select form-select-sm" required>
            @foreach(\App\Support\JamSesi::all() as $sesi)
                <option value="{{ $sesi['no'] }}" data-start="{{ $sesi['start'] }}" data-end="{{ $sesi['end'] }}" @selected(old('sesi_mulai', $sesiMulai ?? null) == $sesi['no'])>Sesi {{ $sesi['no'] }} ({{ $sesi['start'] }}-{{ $sesi['end'] }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Sesi Selesai</label>
        <select name="sesi_selesai" id="sesiSelesai" class="form-select form-select-sm" required>
            @foreach(\App\Support\JamSesi::all() as $sesi)
                <option value="{{ $sesi['no'] }}" data-start="{{ $sesi['start'] }}" data-end="{{ $sesi['end'] }}" @selected(old('sesi_selesai', $sesiSelesai ?? null) == $sesi['no'])>Sesi {{ $sesi['no'] }} ({{ $sesi['start'] }}-{{ $sesi['end'] }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <span class="small text-secondary" id="sesiPreview"></span>
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-scp btn-sm w-100" type="submit">{{ $submit ?? 'Simpan' }}</button>
    </div>
</form>

@push('scripts')
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
@endpush
