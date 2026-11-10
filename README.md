# School Class Progress (SCP)

Presensi, jurnal mengajar, dan perizinan siswa (Laravel 12, MySQL).

## Instalasi
```
composer install
cp .env.example .env        # lalu sesuaikan DB_*, atau pakai .env yang ada
php artisan key:generate    # hanya jika APP_KEY kosong
php artisan migrate         # WAJIB, termasuk migrasi penyelarasan skema
php artisan db:seed         # database kosong
# ATAU, jika database sudah berisi data:
php artisan db:seed --class=LengkapiDataSeeder
php artisan storage:link    # agar foto jurnal & dokumen izin tampil
php artisan serve
```

## Akun demo (password 123456)
waka, guru1, guru2, walikelas1, gurubk1, siswa1, ortu1
