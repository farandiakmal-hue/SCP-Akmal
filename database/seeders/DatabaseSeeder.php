<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'role_name' => 'Waka Kurikulum'],
            ['id' => 2, 'role_name' => 'Guru'],
            ['id' => 3, 'role_name' => 'Wali Kelas'],
            ['id' => 4, 'role_name' => 'Guru BK'],
            ['id' => 5, 'role_name' => 'Siswa'],
            ['id' => 6, 'role_name' => 'Orang Tua'],
        ]);
        DB::table('classes')->insert([
            ['id' => 1, 'class_name' => 'X RPL 1'],
            ['id' => 2, 'class_name' => 'XI RPL 1'],
            ['id' => 3, 'class_name' => 'XII RPL 1'],
            ['id' => 4, 'class_name' => 'XII RPL 2'],
            ['id' => 5, 'class_name' => 'XII TKJ 1'],
        ]);

        DB::table('rooms')->insert([
            ['id' => 1, 'room_name' => 'Lab RPL 1'],
            ['id' => 2, 'room_name' => 'Lab RPL 2'],
            ['id' => 3, 'room_name' => 'Lab TKJ'],
            ['id' => 4, 'room_name' => 'Ruang Teori 1'],
        ]);

        DB::table('users')->insert([
            [
                'id' => 7, 'role_id' => 6, 'username' => 'ortu1',
                'password' => Hash::make('123456'), 'fullname' => 'Orang Tua Budi', 'class_id' => null,
            ],
            [
                'id' => 2, 'role_id' => 1, 'username' => 'waka',
                'password' => Hash::make('123456'), 'fullname' => 'Waka Kurikulum', 'class_id' => null,
            ],
            [
                'id' => 3, 'role_id' => 2, 'username' => 'guru1',
                'password' => Hash::make('123456'), 'fullname' => 'Guru Pengampu A', 'class_id' => null,
            ],
            [
                'id' => 4, 'role_id' => 5, 'username' => 'siswa1',
                'password' => Hash::make('123456'), 'fullname' => 'Siswa Budi', 'class_id' => 3, 'parent_id' => 7,
            ],
            [
                'id' => 5, 'role_id' => 3, 'username' => 'walikelas1',
                'password' => Hash::make('123456'), 'fullname' => 'Wali Kelas XII RPL 1', 'class_id' => 3,
            ],
            [
                'id' => 8, 'role_id' => 2, 'username' => 'guru2',
                'password' => Hash::make('123456'), 'fullname' => 'Guru Pengampu B', 'class_id' => null,
            ],
            [
                'id' => 6, 'role_id' => 4, 'username' => 'gurubk1',
                'password' => Hash::make('123456'), 'fullname' => 'Guru BK', 'class_id' => null,
            ],
        ]);

        DB::table('system_configurations')->insert([
            'id' => 1,
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
        ]);

        DB::table('schedules')->insert([
            'id' => 1,
            'teacher_id' => 3,
            'class_id' => 3,
            'room_id' => 1,
            'subject' => 'Pemrograman Web (PWPB)',
            'day_of_week' => 'kamis',
            'start_time' => '07:15:00',
            'end_time' => '09:35:00',
            'cycle_type' => 'Putaran 1',
        ]);
    }
}
