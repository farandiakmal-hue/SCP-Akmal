# Panduan Hosting SCP

Dua jalur: **A. Shared hosting (cPanel)** — paling mudah dan murah; **B. Tunnel sementara** — untuk demo dari komputer sendiri.
Foto jurnal dan dokumen izin dilayani lewat rute aplikasi, jadi **tidak perlu `storage:link`/symlink**.

## A. Shared hosting (cPanel)
Syarat paket: PHP 8.2 atau lebih baru, MySQL/MariaDB, SSL gratis (Let's Encrypt), file manager/phpMyAdmin.

1. **Pilih PHP 8.2+** di cPanel (*Select PHP Version* / *MultiPHP Manager*) dan aktifkan ekstensi: mbstring, pdo_mysql, openssl, fileinfo, ctype, xml, curl.
2. **Buat database + user** (*MySQL Databases*), tambahkan user ke database dengan semua hak. Catat nama database, user, password.
3. **Siapkan file database** dari komputer lokal (sudah `migrate` + `db:seed`):
   `C:\xampp\mysql\bin\mysqldump.exe -u root db_scp_uk2026 > scp.sql`
   (atau Export di phpMyAdmin). Lalu **Import** `scp.sql` di phpMyAdmin hosting.
4. **Unggah proyek**: zip folder `scp_app` (sertakan `vendor`, **tanpa** `.env` lokal), unggah ke `/home/USERNAME/scp_app` (di LUAR `public_html`) dan ekstrak.
5. **Pindahkan isi folder `public`** ke `public_html` (atau subdomain, mis. `public_html/scp`), termasuk `.htaccess`.
6. **Edit `public_html/index.php`**: ubah dua jalur menjadi
   ```php
   require __DIR__.'/../scp_app/vendor/autoload.php';
   $app = require_once __DIR__.'/../scp_app/bootstrap/app.php';
   ```
   (dan jalur `maintenance.php` bila ada, ke `/../scp_app/storage/framework/maintenance.php`).
7. **Buat `.env`** di `/home/USERNAME/scp_app/.env` dari `.env.production.example` (isi DB_*, APP_URL, `APP_DEBUG=false`).
   `APP_KEY`: gunakan nilai dari `.env` lokal Anda (baris `APP_KEY=base64:...`).
8. **Izin tulis**: folder `storage` dan `bootstrap/cache` harus *writable* (permission 775).
9. **Aktifkan SSL** (*SSL/TLS Status* > *Run AutoSSL*) lalu buka `https://domain-anda`.
10. Bila hosting punya **Terminal/SSH**, langkah 3 bisa diganti: `php artisan migrate --force && php artisan db:seed --class=LengkapiDataSeeder --force`.

Setelah login pertama: **ganti password semua akun demo** (`123456`).

## B. Tunnel HTTPS sementara (demo dari komputer sendiri)
Komputer harus tetap menyala dan `php artisan serve` berjalan.
```
php artisan serve
winget install --id Cloudflare.cloudflared
cloudflared tunnel --url http://localhost:8000
```
Salin alamat `https://....trycloudflare.com` yang muncul, buka di HP. Alamat berubah setiap tunnel dijalankan ulang.

## Daftar periksa sebelum publik
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] password akun demo sudah diganti
- [ ] `.env` tidak ikut terunggah ke tempat publik (mis. GitHub)
- [ ] folder `storage` bisa ditulis; unggah foto jurnal berhasil
- [ ] cadangan database berkala (export dari phpMyAdmin)
