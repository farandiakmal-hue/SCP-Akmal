<?php

/*
 | Jam sesi pelajaran sekolah.
 | - Sesi 1 mulai 07:15, tiap sesi 35 menit, berlanjut sampai jam pulang 15:30.
 | - Istirahat: 10:00-10:20 dan 11:45-12:40 (sesi tidak boleh melewati istirahat,
 |   sehingga sesi 5, 8, dan 13 lebih pendek).
 | Ubah daftar di bawah bila jam sekolah berubah; form jadwal ikut menyesuaikan.
 */
return [
    'sekolah' => ['mulai' => '07:15', 'selesai' => '15:30'],

    'istirahat' => [
        ['Istirahat 1', '10:00', '10:20'],
        ['Istirahat 2', '11:45', '12:40'],
    ],

    'sesi' => [
        1  => ['07:15', '07:50'],
        2  => ['07:50', '08:25'],
        3  => ['08:25', '09:00'],
        4  => ['09:00', '09:35'],
        5  => ['09:35', '10:00'],
        6  => ['10:20', '10:55'],
        7  => ['10:55', '11:30'],
        8  => ['11:30', '11:45'],
        9  => ['12:40', '13:15'],
        10 => ['13:15', '13:50'],
        11 => ['13:50', '14:25'],
        12 => ['14:25', '15:00'],
        13 => ['15:00', '15:30'],
    ],
];
