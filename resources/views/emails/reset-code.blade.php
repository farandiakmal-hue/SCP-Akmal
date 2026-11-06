<!DOCTYPE html>
<html lang="id">
<body style="margin:0;padding:24px;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#24262b;">
    <div style="max-width:440px;margin:0 auto;background:#ffffff;border:1px solid #e3e5e9;border-radius:8px;padding:24px;">
        <h2 style="margin:0 0 12px;font-size:18px;">Reset Password</h2>
        <p style="margin:0 0 16px;font-size:14px;line-height:1.5;">Halo {{ $name }}, gunakan kode berikut untuk membuat password baru di School Class Progress:</p>
        <div style="text-align:center;margin:20px 0;">
            <span style="display:inline-block;font-size:32px;letter-spacing:8px;font-weight:700;background:#f4f5f7;border:1px solid #e3e5e9;border-radius:8px;padding:12px 20px;">{{ $code }}</span>
        </div>
        <p style="margin:0 0 8px;font-size:13px;color:#6b7078;line-height:1.5;">Kode berlaku {{ $minutes }} menit dan hanya dapat dipakai sekali. Salin kode lalu tempel pada halaman reset password.</p>
        <p style="margin:0;font-size:13px;color:#6b7078;line-height:1.5;">Jika Anda tidak meminta reset password, abaikan email ini dan jangan berikan kode kepada siapa pun.</p>
    </div>
</body>
</html>
