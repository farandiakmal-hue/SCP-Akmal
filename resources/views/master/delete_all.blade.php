<div class="scp-card mb-4 border-danger">
    <div class="scp-card-header text-danger">Hapus Semua Data</div>
    <div class="p-3">
        <div class="small text-secondary mb-3">{{ $warning }}</div>

        <form action="{{ $action }}" method="POST" class="row g-2 align-items-end"
              onsubmit='return confirm(@json($label . " akan dihapus permanen. Lanjutkan?"))'>
            @csrf
            @foreach(($filters ?? []) as $k => $v)
                @if(filled($v))
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endif
            @endforeach
            <div class="col-md-4">
                <label class="form-label small">Ketik <strong>HAPUS</strong> untuk konfirmasi</label>
                <input type="text" name="confirm" class="form-control form-control-sm" autocomplete="off" required>
            </div>
            <div class="col-md-5">
                <button class="btn btn-danger btn-sm w-100" type="submit">{{ $label }}</button>
            </div>
        </form>

        @error('confirm')
            <div class="text-danger small mt-2">{{ $message }}</div>
        @enderror
    </div>
</div>