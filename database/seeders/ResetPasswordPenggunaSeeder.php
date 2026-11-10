<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Mengatur password default untuk pengguna hasil import CSV
 * (kolom password di CSV kosong, jadi password di database tidak valid).
 *
 * Akun demo dari DatabaseSeeder (waka, guru1, dst.) tidak disentuh.
 *
 *   php artisan db:seed --class=ResetPasswordPenggunaSeeder
 */
class ResetPasswordPenggunaSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = '123456'; // ganti kalau mau password default lain

        $akunDemo = ['waka', 'guru1', 'guru2', 'siswa1', 'walikelas1', 'gurubk1', 'ortu1'];

        $jumlah = DB::table('users')
            ->whereNotIn('username', $akunDemo)
            ->update([
                'password'   => Hash::make($defaultPassword),
                'updated_at' => now(),
            ]);

        $this->command->info("Password {$jumlah} pengguna diatur ke: {$defaultPassword}");
    }
}