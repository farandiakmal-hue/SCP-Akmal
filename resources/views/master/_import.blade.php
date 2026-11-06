{{-- Tombol + modal Import CSV. Pemakaian: @include('master._import', ['type' => 'users'|'classes'|'rooms']) --}}
@php
    $cfg = [
        'users'   => ['Pengguna', route('master.users.import')],
        'classes' => ['Kelas', route('master.classes.import')],
        'rooms'   => ['Ruangan', route('master.rooms.import')],
    ][$type];
    $modalId = 'importModal-' . $type;
@endphp

<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
    <i class="bi bi-upload"></i> Import Excel
</button>

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" action="{{ $cfg[1] }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fs-6">Import {{ $cfg[0] }} dari Excel/CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Berkas Excel / CSV</label>
                    <input type="file" name="file" class="form-control form-control-sm" accept=".xlsx,.csv,.txt" required>
                    <div class="form-text">
                        @if($type === 'users')
                            Kolom: <code>fullname, username, password, role, class, email</code>. Hanya <code>fullname</code> dan <code>username</code> yang wajib.
                        @else
                            Satu nama per baris. Nama yang sudah ada dilewati.
                        @endif
                        <a href="{{ route('master.import.template', $type) }}">Unduh template</a>.
                        Data dibaca dari sheet pertama; baris pertama harus judul kolom.
                    </div>
                </div>

                @if($type === 'users')
                    <div class="mb-3">
                        <label class="form-label">Role bawaan <span class="text-muted">(bila kolom role kosong)</span></label>
                        <select name="default_role" class="form-select form-select-sm" required>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}" @selected($r->id == \App\Models\Role::GURU)>{{ $r->role_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password bawaan <span class="text-muted">(bila kolom password kosong)</span></label>
                        <input type="text" name="default_password" class="form-control form-control-sm" minlength="6" placeholder="minimal 6 karakter" autocomplete="off">
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="create_classes" value="1" id="cc-{{ $type }}" checked>
                        <label class="form-check-label" for="cc-{{ $type }}">Buat kelas otomatis bila belum ada</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="update_existing" value="1" id="ue-{{ $type }}">
                        <label class="form-check-label" for="ue-{{ $type }}">Perbarui data bila username sudah ada <span class="text-muted">(password tidak diubah kecuali diisi di CSV)</span></label>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-scp btn-sm">Import</button>
            </div>
        </form>
    </div>
</div>
