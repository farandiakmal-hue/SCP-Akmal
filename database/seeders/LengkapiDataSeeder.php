<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder idempotent untuk database yang SUDAH berisi data (tanpa menimpa data lama).
 * Menambah role Orang Tua, akun Wali Kelas, Guru BK, Guru 2, Orang Tua,
 * dan menghubungkan siswa1 <-> ortu1 serta walikelas1 <-> kelas siswa1.
 *
 *   php artisan db:seed --class=LengkapiDataSeeder
 */
class LengkapiDataSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [1 => 'Waka Kurikulum', 2 => 'Guru', 3 => 'Wali Kelas', 4 => 'Guru BK', 5 => 'Siswa', 6 => 'Orang Tua'];
        foreach ($roles as $id => $name) {
            DB::table('roles')->updateOrInsert(['id' => $id], ['role_name' => $name]);
        }

        $classId = DB::table('users')->where('username', 'siswa1')->value('class_id')
            ?? DB::table('classes')->value('id');

        $accounts = [
            ['walikelas1', 3, 'Wali Kelas', $classId],
            ['gurubk1',    4, 'Guru BK', null],
            ['guru2',      2, 'Guru Pengampu B', null],
            ['ortu1',      6, 'Orang Tua Budi', null],
        ];

        foreach ($accounts as [$username, $roleId, $fullname, $cls]) {
            if (!DB::table('users')->where('username', $username)->exists()) {
                DB::table('users')->insert([
                    'username'   => $username,
                    'role_id'    => $roleId,
                    'fullname'   => $fullname,
                    'class_id'   => $cls,
                    'password'   => Hash::make('123456'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $ortuId = DB::table('users')->where('username', 'ortu1')->value('id');
        DB::table('users')->where('username', 'siswa1')->whereNull('parent_id')->update(['parent_id' => $ortuId]);
        DB::table('users')->where('username', 'walikelas1')->whereNull('class_id')->update(['class_id' => $classId]);
    }
}
