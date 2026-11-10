<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Room;
use App\Models\Classes;
use App\Models\SystemConfiguration;
use Illuminate\Support\Facades\Hash;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        SystemConfiguration::create([
            'key'   => 'school_name',
            'value' => 'SMK Negeri 1',
        ]);
        SystemConfiguration::create([
            'key'   => 'academic_year',
            'value' => '2025/2026',
        ]);
        SystemConfiguration::create([
            'key'   => 'semester',
            'value' => 'Ganjil',
        ]);

        $rooms = [
            ['name' => 'Lab RPL 1', 'code' => 'LAB-RPL-01', 'capacity' => 36],
            ['name' => 'Lab RPL 2', 'code' => 'LAB-RPL-02', 'capacity' => 36],
            ['name' => 'Ruang Teori 1', 'code' => 'RT-01', 'capacity' => 40],
        ];
        foreach ($rooms as $room) {
            Room::create($room);
        }

        $classes = [
            ['name' => 'X RPL 1', 'code' => 'XRPL1'],
            ['name' => 'X RPL 2', 'code' => 'XRPL2'],
            ['name' => 'XI RPL 1', 'code' => 'XIRPL1'],
            ['name' => 'XI RPL 2', 'code' => 'XIRPL2'],
            ['name' => 'XII RPL 1', 'code' => 'XIIRPL1'],
            ['name' => 'XII RPL 2', 'code' => 'XIIRPL2'],
            ['name' => 'X TKJ 1', 'code' => 'XTKJ1'],
            ['name' => 'XI TKJ 1', 'code' => 'XITKJ1'],
            ['name' => 'XII TKJ 1', 'code' => 'XIITKJ1'],
        ];
        foreach ($classes as $item) {
            Classes::create($item);
        }

        User::create([
            'name'     => 'Administrator',
            'email'    => 'admin@gmail.com',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        User::create([
            'name'     => 'Guru Pengajar',
            'email'    => 'guru@gmail.com',
            'password' => Hash::make('password123'),
            'role'     => 'guru',
        ]);

        User::create([
            'name'     => 'Siswa Contoh',
            'email'    => 'siswa@gmail.com',
            'password' => Hash::make('password123'),
            'role'     => 'siswa',
        ]);
    }
}