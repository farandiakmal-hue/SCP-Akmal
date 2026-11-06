{{-- Tombol + modal Import Jadwal (khusus Waka) --}}
<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#importModal-schedules">
    <i class="bi bi-upload"></i> Import Jadwal
</button>

<div class="modal fade" id="importModal-schedules" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" action="{{ route('schedule.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fs-6">Import Jadwal dari Excel/CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Berkas Excel / CSV</label>
                    <input type="file" name="file" class="form-control form-control-sm" accept=".xlsx,.csv,.txt" required>
                    <div class="form-text">
                        Kolom: <code>cycle, class, day, start, end, subject, teacher, room</code>.
                        <code>teacher</code> = NIP/username atau nama lengkap guru; <code>room</code> kosong = ruang kelas.
                        <a href="{{ route('master.import.template', 'schedules') }}">Unduh template</a>.
                        Import Kelas, Ruangan, dan Guru lebih dulu.
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Putaran bawaan</label>
                        <select name="default_cycle" class="form-select form-select-sm">
                            <option value="Putaran 1" @selected(($config->active_cycle ?? 'Putaran 1') === 'Putaran 1')>Putaran 1</option>
                            <option value="Putaran 2" @selected(($config->active_cycle ?? '') === 'Putaran 2')>Putaran 2</option>
                        </select>
                        <div class="form-text">Dipakai bila kolom cycle kosong.</div>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Tahun ajaran</label>
                        <input type="text" name="academic_year" class="form-control form-control-sm" value="2026/2027" pattern="\d{4}/\d{4}" required>
                    </div>
                    <div class="col-2">
                        <label class="form-label">Smt</label>
                        <select name="semester" class="form-select form-select-sm">
                            <option value="1" selected>1</option>
                            <option value="2">2</option>
                        </select>
                    </div>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="create_rooms" value="1" id="cr-sched" checked>
                    <label class="form-check-label" for="cr-sched">Buat ruangan otomatis bila belum ada</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="set_active" value="1" id="sa-sched" checked>
                    <label class="form-check-label" for="sa-sched">Jadikan tahun ajaran &amp; semester ini yang aktif</label>
                </div>
                <div class="form-text mt-2">Jadwal yang sudah ada persis sama dilewati, jadi aman diimport ulang. Jadwal yang bentrok ditolak dan dilaporkan.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-scp btn-sm">Import</button>
            </div>
        </form>
    </div>
</div>
