<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Surat Izin / Sakit - SCP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5" style="max-width: 600px;">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0 fw-bold">Form Pengajuan Surat Izin / Sakit</h5>
            </div>
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('permits.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status Pengajuan</label>
                        <select name="status" class="form-select" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="izin" {{ old('status') == 'izin' ? 'selected' : '' }}>Izin</option>
                            <option value="sakit" {{ old('status') == 'sakit' ? 'selected' : '' }}>Sakit</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Keterangan / Alasan</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Contoh: Demam tinggi / Ada kepentingan keluarga mendesak" required>{{ old('notes') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Unggah Bukti Surat (JPG, PNG, PDF - Max 2MB)</label>
                        <input type="file" name="proof_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                        <small class="text-muted">Lampirkan foto surat dokter (jika sakit) atau surat izin orang tua/wali.</small>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-2 border-top">
                        <a href="{{ url('/dashboard') }}" class="btn btn-secondary">Kembali ke Dashboard</a>
                        <button type="submit" class="btn btn-primary fw-bold">Kirim Surat Izin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>