@extends('layouts.app')
@section('title', 'Isi Jurnal')

@section('content')
    <h5 class="fw-bold mb-1">Isi Jurnal & Presensi</h5>
    <div class="small text-secondary mb-4">
        {{ $schedule->subject }} &middot; {{ $schedule->class->class_name ?? '-' }} &middot;
        {{ ucfirst($schedule->day_of_week) }} {{ substr($schedule->start_time,0,5) }}-{{ substr($schedule->end_time,0,5) }}
    </div>

    @if($alreadyFilled)
        <div class="alert alert-info py-2 small">Jurnal untuk jadwal ini hari ini sudah diisi.</div>
    @else
        <form action="{{ route('journal.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="schedule_id" value="{{ $schedule->id }}">
            <div class="scp-card mb-4">
    <div class="scp-card-header">Waktu Pengisian</div>
    <div class="p-3 row g-2">
        <div class="col-6">
            <label class="form-label small fw-medium">Tanggal</label>
            <input type="text" class="form-control" readonly
                   value="{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }}">
        </div>
        <div class="col-6">
            <label class="form-label small fw-medium">Jam</label>
            <input type="text" id="jamSekarang" class="form-control" readonly
                   value="{{ \Carbon\Carbon::now('Asia/Jakarta')->format('H:i') }}">
        </div>
    </div>
</div>

            <div class="scp-card mb-4">
                <div class="scp-card-header">Jurnal Mengajar</div>
                <div class="p-3">
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Uraian Materi / Kegiatan</label>
                        <textarea name="topic_description" class="form-control" rows="3" required>{{ old('topic_description') }}</textarea>
                    </div>
                                        <div class="mb-1">
                        <label class="form-label small fw-medium">Foto Bukti Kegiatan (Kamera Langsung)</label>

                        {{-- Input ini diisi otomatis oleh kamera, bukan dipilih manual --}}
                        <input type="file" name="photo" id="photoInput" class="d-none" accept="image/jpeg">

                        <div id="cameraBox" class="border rounded p-2 bg-light">
                            <video id="cameraVideo" class="w-100 rounded d-none" style="max-height:340px; background:#000;" autoplay playsinline muted></video>
                            <img id="cameraPreview" class="w-100 rounded d-none" style="max-height:340px; object-fit:contain;" alt="Hasil foto">

                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <button type="button" id="btnStartCamera" class="btn btn-sm btn-outline-secondary">Buka Kamera</button>
                                <button type="button" id="btnCapture" class="btn btn-sm btn-scp d-none">Ambil Foto</button>
                                <button type="button" id="btnSwitch" class="btn btn-sm btn-outline-secondary d-none">Ganti Kamera</button>
                                <button type="button" id="btnRetake" class="btn btn-sm btn-outline-secondary d-none">Foto Ulang</button>
                            </div>

                            {{-- Hanya tampil jika browser tidak mengizinkan akses kamera langsung (misalnya diakses lewat http://IP) --}}
                            <div id="fallbackWrap" class="mt-2 d-none">
                                <input type="file" id="photoFallback" accept="image/*" capture="environment" class="form-control form-control-sm">
                            </div>

                            <div id="cameraStatus" class="form-text mt-1">Mengaktifkan kamera...</div>
                        </div>
                        <canvas id="cameraCanvas" class="d-none"></canvas>
                    </div>
                </div>
            </div>

            <div class="scp-card mb-4">
                <div class="scp-card-header">Presensi Siswa</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr><th>Nama</th><th style="width:220px">Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse($students as $s)
                                <tr>
                                    <td>{{ $s->fullname }}</td>
                                    <td>
                                        @if(isset($approvedLeaveStudentIds[$s->id]))
                                            <span class="badge badge-soft">Izin/Sakit disetujui (otomatis)</span>
                                        @else
                                            <select name="attendance[{{ $s->id }}]" class="form-select form-select-sm">
                                                <option value="hadir">Hadir</option>
                                                <option value="sakit">Sakit</option>
                                                <option value="izin">Izin</option>
                                                <option value="dispensasi">Dispensasi</option>
                                                <option value="alpha">Alpha</option>
                                            </select>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-secondary py-3">Belum ada siswa terdaftar di kelas ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <button type="submit" class="btn btn-scp">Simpan Jurnal & Presensi</button>
        </form>
    @endif
    <script>
    (function () {
        var el = document.getElementById('jamSekarang');
        if (!el) return;
        setInterval(function () {
            var d = new Date();
            el.value = String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
        }, 1000);
    })();
</script>

<script>
(function () {
    var input    = document.getElementById('photoInput');
    var video    = document.getElementById('cameraVideo');
    var preview  = document.getElementById('cameraPreview');
    var canvas   = document.getElementById('cameraCanvas');
    var status   = document.getElementById('cameraStatus');
    var box      = document.getElementById('cameraBox');
    var fallback = document.getElementById('fallbackWrap');
    var fbInput  = document.getElementById('photoFallback');
    var btnStart = document.getElementById('btnStartCamera');
    var btnCap   = document.getElementById('btnCapture');
    var btnSw    = document.getElementById('btnSwitch');
    var btnRe    = document.getElementById('btnRetake');
    if (!input || !video) return;

    var label  = @json(($schedule->subject ?? '-') . ' - ' . ($schedule->class->class_name ?? '-'));
    var stream = null;
    var facing = 'environment';
    var previewUrl = null;

    function setStatus(msg, isError) {
        status.textContent = msg;
        status.className = 'form-text mt-1' + (isError ? ' text-danger' : '');
    }

    // state: idle | live | taken
    function ui(state) {
        btnStart.classList.toggle('d-none', state !== 'idle');
        btnCap.classList.toggle('d-none', state !== 'live');
        btnSw.classList.toggle('d-none', state !== 'live');
        btnRe.classList.toggle('d-none', state !== 'taken');
        video.classList.toggle('d-none', state !== 'live');
        preview.classList.toggle('d-none', state !== 'taken');
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        video.srcObject = null;
    }

    async function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            ui('idle');
            btnStart.classList.add('d-none');
            fallback.classList.remove('d-none');
            setStatus('Browser tidak mengizinkan kamera langsung di alamat ini. Gunakan kolom di bawah untuk membuka kamera perangkat.', true);
            return;
        }

        stopCamera();
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: facing, width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false
            });
            video.srcObject = stream;
            ui('live');
            setStatus('Arahkan kamera ke kegiatan kelas, lalu klik "Ambil Foto".');
        } catch (e) {
            ui('idle');
            setStatus('Kamera tidak bisa dibuka. Izinkan akses kamera lewat ikon gembok di address bar, lalu klik "Buka Kamera".', true);
        }
    }

    function saveFromSource(source, sw, sh, quality) {
        var scale = Math.min(1, 1280 / Math.max(sw, sh));
        var cw = Math.round(sw * scale), ch = Math.round(sh * scale);
        canvas.width = cw;
        canvas.height = ch;

        var ctx = canvas.getContext('2d');
        ctx.drawImage(source, 0, 0, cw, ch);

        // Cap waktu & identitas kelas di bagian bawah foto
        var stamp = new Date().toLocaleString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit',
            hour12: false, timeZone: 'Asia/Jakarta'
        }) + ' WIB';
        var fs  = Math.max(14, Math.round(cw / 45));
        var pad = Math.round(fs * 0.6);
        var barH = Math.round(fs * 2.5 + pad * 2);
        ctx.fillStyle = 'rgba(0,0,0,0.55)';
        ctx.fillRect(0, ch - barH, cw, barH);
        ctx.fillStyle = '#fff';
        ctx.font = 'bold ' + fs + 'px sans-serif';
        ctx.textBaseline = 'top';
        ctx.fillText(stamp, pad, ch - barH + pad);
        ctx.fillText(label, pad, ch - barH + pad + Math.round(fs * 1.25));

        canvas.toBlob(function (blob) {
            if (!blob) { setStatus('Gagal memproses foto, coba lagi.', true); return; }
            if (blob.size > 1900 * 1024 && quality > 0.5) {
                saveFromSource(source, sw, sh, quality - 0.2); // kecilkan lagi agar di bawah 2MB
                return;
            }

            var file = new File([blob], 'jurnal-' + Date.now() + '.jpg', { type: 'image/jpeg' });
            var dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;

            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = URL.createObjectURL(blob);
            preview.src = previewUrl;

            stopCamera();
            ui('taken');
            fallback.classList.add('d-none');
            setStatus('Foto tersimpan (' + Math.round(blob.size / 1024) + ' KB). Klik "Foto Ulang" jika ingin mengganti.');
        }, 'image/jpeg', quality);
    }

    btnStart.addEventListener('click', startCamera);

    btnCap.addEventListener('click', function () {
        if (!video.videoWidth || !video.videoHeight) {
            setStatus('Kamera belum siap, tunggu sebentar lalu coba lagi.', true);
            return;
        }
        saveFromSource(video, video.videoWidth, video.videoHeight, 0.85);
    });

    btnSw.addEventListener('click', function () {
        facing = facing === 'environment' ? 'user' : 'environment';
        startCamera();
    });

    btnRe.addEventListener('click', function () {
        input.value = '';
        if (previewUrl) { URL.revokeObjectURL(previewUrl); previewUrl = null; }
        startCamera();
    });

    // Cadangan: kamera perangkat lewat atribut capture (untuk akses tanpa HTTPS)
    fbInput.addEventListener('change', function () {
        var f = fbInput.files && fbInput.files[0];
        if (!f) return;
        var img = new Image();
        var url = URL.createObjectURL(f);
        img.onload = function () {
            saveFromSource(img, img.naturalWidth, img.naturalHeight, 0.85);
            URL.revokeObjectURL(url);
        };
        img.src = url;
    });

    // Jangan kirim form tanpa foto
    input.form.addEventListener('submit', function (e) {
        if (!input.files || !input.files.length) {
            e.preventDefault();
            setStatus('Foto bukti belum diambil. Ambil foto lebih dulu sebelum menyimpan jurnal.', true);
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    window.addEventListener('beforeunload', stopCamera);

    startCamera();
})();
</script>
@endsection
