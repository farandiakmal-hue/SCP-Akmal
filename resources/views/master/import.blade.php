<div class="scp-card mb-4">
    <div class="scp-card-header">Import Data (CSV)</div>
    <div class="p-3">
        <form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-6">
                <label class="form-label small">File CSV</label>
                <input type="file" name="file" accept=".csv,.txt" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-3">
                <button class="btn btn-scp btn-sm w-100" type="submit">Import</button>
            </div>
            <div class="col-md-3">
                <a href="{{ route('master.template', $type) }}" class="btn btn-sm btn-outline-secondary w-100">Unduh Template</a>
            </div>
        </form>
        <div class="form-text mt-2">{{ $hint }}</div>

        @if(session('import_errors'))
            <div class="alert alert-warning small mt-3 mb-0">
                <div class="fw-semibold mb-1">Baris yang dilewati:</div>
                <ul class="mb-0">
                    @foreach(session('import_errors') as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>