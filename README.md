# School Class Progress (SCP)

Platform presensi, jurnal mengajar guru, dan perizinan siswa untuk pembelajaran Teaching Factory
dengan siklus jadwal blok 2 mingguan. Laravel 12 + MySQL/MariaDB, tampilan Mobile-First (Bootstrap 5).

## Instalasi
```
composer install
cp .env.example .env        # sesuaikan DB_*; atau pakai .env yang sudah ada
php artisan key:generate    # hanya jika APP_KEY kosong
php artisan migrate         # WAJIB (termasuk migrasi penyelarasan skema)
php artisan db:seed         # database kosong
# ATAU bila database sudah berisi data (tidak menimpa data lama):
php artisan db:seed --class=LengkapiDataSeeder
php artisan serve
```
Foto jurnal dan dokumen izin dilayani lewat rute aplikasi, jadi `storage:link` tidak wajib.

## Akun demo (password `123456`) (untuk waka passwordnya 1234567)
| Username | Peran |
|---|---|
| waka (database lama: waka1) | Waka Kurikulum |
| guru1| Guru |
| walikelas1 | Wali Kelas |
| gurubk1 | Guru BK |
| siswa1 | Siswa |
| ortu1 | Orang Tua |

## Fitur & aturan bisnis inti
1. **Anti-tabrakan jadwal**: ruang, kelas, atau guru yang beririsan pada putaran & hari sama ditolak
   (`Start_A < End_B AND End_A > Start_B`).
2. **Siklus blok**: Waka memilih Putaran 1/2; jadwal, dashboard, dan alert mengikuti putaran aktif.
3. **Lock Schedule**: setelah dikunci, Guru tidak bisa tambah/ubah/hapus jadwal (UI & endpoint).
4. **Sinkronisasi izin**: izin *Approved* mengisi presensi Izin/Sakit saat Guru membuat jurnal pada hari tsb.
5. **Alert jurnal**: jadwal hari ini yang lewat > 30 menit tanpa jurnal muncul di dashboard (tanpa cron).

Jam sesi (Sesi 1 mulai 07:15, 35 menit/sesi, istirahat 10:00-10:20 & 11:45-12:40, pulang 15:30)
diatur di `config/jam_sesi.php`.

## Lupa password (kode lewat email)
Pengguna memasukkan username/email di halaman **Lupa password?** → kode 6 digit dikirim ke email terdaftar
(berlaku 10 menit, sekali pakai, maksimal 5 percobaan) → salin kode, buat password baru.
- Email diisi di menu **Profil** (pengguna sendiri) atau **Master > Pengguna** (Waka).
- **Uji lokal tanpa email asli:** biarkan `MAIL_MAILER=log` di `.env`; kode muncul di `storage/logs/laravel.log`
  (cari tulisan "Kode reset password" / angka 6 digit pada isi email).
- **Email sungguhan:** isi `MAIL_*` di `.env` (contoh SMTP Gmail ada di `.env.production.example`), lalu `php artisan config:clear`.
- Jalankan `php artisan migrate` agar kolom email dan tabel kode dibuat.

## Tampilan
Seluruh gaya (warna, bentuk, tombol, badge, tabel, form, alert) ada di satu berkas: `public/css/scp.css`.
Ubah warna/bentuk cukup di blok `:root` pada berkas itu.

## Git
```
git init
git add .
git commit -m "feat: SCP - presensi, jurnal, perizinan, blok jadwal"
```
