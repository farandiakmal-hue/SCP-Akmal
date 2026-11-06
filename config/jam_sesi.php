<?php

/*
 | Jam sesi pelajaran SMK Negeri 1 Bantul (sesuai jadwal FET 2026/2027).
 | - 10 jam pelajaran @45 menit, mulai 07:15 dan selesai 15:30.
 | - Istirahat diturunkan dari celah antar-jam pada jadwal FET:
 |   09:30-09:45 (antara jam ke-3 dan ke-4) dan 12:00-12:30 (antara jam ke-6 dan ke-7).
 |   Bila waktu istirahat resmi berbeda, ubah daftar 'istirahat' di bawah.
 | Form jadwal dan pengecekan istirahat mengikuti daftar ini.
 */
return [
    'sekolah' => ['mulai' => '07:15', 'selesai' => '15:30'],

    'istirahat' => [
        ['Istirahat 1', '09:30', '09:45'],
        ['Istirahat 2', '12:00', '12:30'],
    ],

    'sesi' => [
        1  => ['07:15', '08:00'],
        2  => ['08:00', '08:45'],
        3  => ['08:45', '09:30'],
        4  => ['09:45', '10:30'],
        5  => ['10:30', '11:15'],
        6  => ['11:15', '12:00'],
        7  => ['12:30', '13:15'],
        8  => ['13:15', '14:00'],
        9  => ['14:00', '14:45'],
        10 => ['14:45', '15:30'],
    ],
];
