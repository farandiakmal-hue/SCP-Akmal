<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Support\CsvTable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Import data master dari berkas CSV (Pengguna, Kelas, Ruangan).
 * Hanya untuk Waka, sama seperti MasterDataController.
 */
class MasterImportController extends Controller implements HasMiddleware
{
    // Maksimal baris galat yang ditampilkan agar halaman tetap ringan
    private const MAX_ERRORS_SHOWN = 40;

    public static function middleware(): array
    {
        return ['role:' . Role::WAKA];
    }

    // Nama kolom yang dikenali -> nama baku. Kunci ditulis huruf kecil tanpa spasi/simbol.
    private const USER_HEADERS = [
        'fullname' => 'fullname', 'nama' => 'fullname', 'namalengkap' => 'fullname', 'name' => 'fullname',
        'username' => 'username', 'user' => 'username', 'nip' => 'username', 'nis' => 'username',
        'password' => 'password', 'katasandi' => 'password',
        'role' => 'role', 'peran' => 'role',
        'class' => 'class', 'classname' => 'class', 'kelas' => 'class',
        'email' => 'email',
    ];

    private const SIMPLE_HEADERS = [
        'classname', 'namakelas', 'kelas', 'class',
        'roomname', 'namaruangan', 'ruangan', 'ruang', 'room', 'nama',
    ];

    // ------------------------------------------------------------------ PENGGUNA

    public function importUsers(Request $request)
    {
        $request->validate([
            'file'             => 'required|file|extensions:csv,txt,xlsx|max:4096',
            'default_role'     => 'required|exists:roles,id',
            'default_password' => 'nullable|string|min:6|max:100',
        ], [
            'file.extensions' => 'Berkas harus berformat Excel (.xlsx) atau CSV.',
            'file.max'        => 'Ukuran berkas maksimal 4 MB.',
        ]);

        try {
            $rows = CsvTable::read($request->file('file')->getRealPath());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        if (count($rows) < 2) {
            return back()->with('error', 'Berkas kosong atau hanya berisi judul kolom.');
        }

        // Petakan judul kolom -> indeks
        $map = [];
        foreach ($rows[0]['cells'] as $i => $head) {
            $k = preg_replace('/[^a-z0-9]/', '', strtolower($head));
            if (isset(self::USER_HEADERS[$k]) && !isset($map[self::USER_HEADERS[$k]])) {
                $map[self::USER_HEADERS[$k]] = $i;
            }
        }
        foreach (['fullname', 'username'] as $required) {
            if (!isset($map[$required])) {
                return back()->with('error', "Kolom \"$required\" tidak ditemukan pada baris pertama. Unduh template agar judul kolom sesuai.");
            }
        }

        $defaultRole      = (int) $request->default_role;
        $defaultPassword  = (string) $request->default_password;
        $createClasses    = $request->boolean('create_classes');
        $updateExisting   = $request->boolean('update_existing');

        @set_time_limit(300);

        $result = DB::transaction(function () use ($rows, $map, $defaultRole, $defaultPassword, $createClasses, $updateExisting) {
            // Peta pembanding (dibaca sekali agar tidak query berulang)
            $roleLookup = [];
            foreach (Role::all() as $role) {
                $roleLookup[preg_replace('/[^a-z0-9]/', '', strtolower($role->role_name))] = (int) $role->id;
                $roleLookup[(string) $role->id] = (int) $role->id;
            }
            $roleLookup += ['waka' => Role::WAKA, 'wali' => Role::WALI_KELAS, 'bk' => Role::GURU_BK, 'ortu' => Role::ORTU];

            $classes = [];
            foreach (Classes::all() as $c) {
                $classes[CsvTable::key($c->class_name)] = (int) $c->id;
            }

            $users = [];
            $emails = [];
            foreach (User::select('id', 'username', 'email')->get() as $u) {
                $users[strtolower($u->username)] = $u;
                if ($u->email) {
                    $emails[strtolower($u->email)] = (int) $u->id;
                }
            }

            $hashes = [];
            $seen = [];
            $created = $updated = $skipped = $classesMade = 0;
            $errors = [];

            $cell = function (array $cells, string $field) use ($map): string {
                return isset($map[$field], $cells[$map[$field]]) ? $cells[$map[$field]] : '';
            };

            foreach (array_slice($rows, 1) as $row) {
                $line  = $row['line'];
                $cells = $row['cells'];

                $fullname = CsvTable::clean($cell($cells, 'fullname'));
                $username = trim($cell($cells, 'username'));
                $plain    = $cell($cells, 'password');
                $email    = strtolower($cell($cells, 'email'));
                $roleText = $cell($cells, 'role');
                $classTxt = CsvTable::clean($cell($cells, 'class'));

                $fail = function (string $msg) use (&$errors, &$skipped, $line) {
                    $errors[] = "Baris $line: $msg";
                    $skipped++;
                };

                if ($fullname === '' || $username === '') {
                    $fail('nama atau username kosong.');
                    continue;
                }
                if (mb_strlen($fullname) > 100 || mb_strlen($username) > 50) {
                    $fail("nama (maks 100) atau username (maks 50) terlalu panjang ($username).");
                    continue;
                }
                $ukey = strtolower($username);
                if (isset($seen[$ukey])) {
                    $fail("username \"$username\" muncul dua kali di berkas.");
                    continue;
                }
                $seen[$ukey] = true;

                // Role
                $roleId = $defaultRole;
                if ($roleText !== '') {
                    $rk = preg_replace('/[^a-z0-9]/', '', strtolower($roleText));
                    if (!isset($roleLookup[$rk])) {
                        $fail("role \"$roleText\" tidak dikenal ($username).");
                        continue;
                    }
                    $roleId = $roleLookup[$rk];
                }

                // Email
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $fail("format email salah ($username).");
                    continue;
                }
                $existing = $users[$ukey] ?? null;
                if ($email !== '' && isset($emails[$email]) && (!$existing || $emails[$email] !== (int) $existing->id)) {
                    $fail("email $email sudah dipakai pengguna lain ($username).");
                    continue;
                }

                // Kelas (wajib untuk Siswa & Wali Kelas, sama seperti form tambah pengguna)
                $classId = null;
                if ($classTxt !== '') {
                    $ck = CsvTable::key($classTxt);
                    if (isset($classes[$ck])) {
                        $classId = $classes[$ck];
                    } elseif ($createClasses) {
                        $classId = (int) Classes::create(['class_name' => $classTxt])->id;
                        $classes[$ck] = $classId;
                        $classesMade++;
                    } else {
                        $fail("kelas \"$classTxt\" belum ada ($username).");
                        continue;
                    }
                }
                if (in_array($roleId, [Role::SISWA, Role::WALI_KELAS], true) && !$classId) {
                    $fail("kelas wajib diisi untuk Siswa/Wali Kelas ($username).");
                    continue;
                }

                if ($existing) {
                    if (!$updateExisting) {
                        $skipped++;
                        continue; // username sudah ada, dilewati tanpa dianggap galat
                    }
                    $data = ['fullname' => $fullname, 'role_id' => $roleId, 'class_id' => $classId];
                    if ($email !== '') {
                        $data['email'] = $email;
                    }
                    if ($plain !== '') {
                        if (strlen($plain) < 6) {
                            $fail("password minimal 6 karakter ($username).");
                            continue;
                        }
                        $data['password'] = $hashes[$plain] ??= Hash::make($plain);
                    }
                    $existing->update($data);
                    if ($email !== '') {
                        $emails[$email] = (int) $existing->id;
                    }
                    $updated++;
                    continue;
                }

                $password = $plain !== '' ? $plain : $defaultPassword;
                if ($password === '') {
                    $fail("password kosong dan password bawaan tidak diisi ($username).");
                    continue;
                }
                if (strlen($password) < 6) {
                    $fail("password minimal 6 karakter ($username).");
                    continue;
                }

                $new = User::create([
                    'fullname' => $fullname,
                    'username' => $username,
                    'email'    => $email !== '' ? $email : null,
                    'password' => $hashes[$password] ??= Hash::make($password), // hash sekali per password yang sama
                    'role_id'  => $roleId,
                    'class_id' => $classId,
                ]);
                $users[$ukey] = $new;
                if ($email !== '') {
                    $emails[$email] = (int) $new->id;
                }
                $created++;
            }

            return compact('created', 'updated', 'skipped', 'classesMade', 'errors');
        });

        $msg = "Import selesai: {$result['created']} pengguna baru";
        if ($result['updated']) {
            $msg .= ", {$result['updated']} diperbarui";
        }
        if ($result['classesMade']) {
            $msg .= ", {$result['classesMade']} kelas baru dibuat";
        }
        $msg .= ", {$result['skipped']} dilewati.";

        return $this->finish($result['created'] + $result['updated'] + $result['classesMade'] > 0, $msg, $result['errors']);
    }

    // ------------------------------------------------------------------ KELAS & RUANGAN

    public function importClasses(Request $request)
    {
        return $this->importNames($request, Classes::class, 'class_name', 'kelas');
    }

    public function importRooms(Request $request)
    {
        return $this->importNames($request, Room::class, 'room_name', 'ruangan');
    }

    private function importNames(Request $request, string $model, string $column, string $label)
    {
        $request->validate([
            'file' => 'required|file|extensions:csv,txt,xlsx|max:1024',
        ], [
            'file.extensions' => 'Berkas harus berformat Excel (.xlsx) atau CSV.',
        ]);

        try {
            $rows = CsvTable::read($request->file('file')->getRealPath());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        if (!$rows) {
            return back()->with('error', 'Berkas kosong.');
        }

        // Baris pertama dilewati bila berupa judul kolom
        $firstKey = preg_replace('/[^a-z0-9]/', '', strtolower($rows[0]['cells'][0] ?? ''));
        if (in_array($firstKey, self::SIMPLE_HEADERS, true)) {
            array_shift($rows);
        }

        $known = [];
        foreach ($model::pluck($column) as $name) {
            $known[CsvTable::key($name)] = true;
        }

        $created = $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $model, $column, &$known, &$created, &$skipped, &$errors, $label) {
            foreach ($rows as $row) {
                $name = CsvTable::clean($row['cells'][0] ?? '');
                if ($name === '') {
                    continue;
                }
                if (mb_strlen($name) > 50) {
                    $errors[] = "Baris {$row['line']}: nama $label terlalu panjang (maks 50).";
                    $skipped++;
                    continue;
                }
                $key = CsvTable::key($name);
                if (isset($known[$key])) {
                    $skipped++; // sudah ada
                    continue;
                }
                $model::create([$column => $name]);
                $known[$key] = true;
                $created++;
            }
        });

        return $this->finish($created > 0, "Import $label selesai: $created baru, $skipped dilewati (sudah ada/tidak valid).", $errors);
    }

    // ------------------------------------------------------------------ JADWAL

    private const SCHEDULE_HEADERS = [
        'cycle' => 'cycle', 'putaran' => 'cycle',
        'class' => 'class', 'classname' => 'class', 'kelas' => 'class',
        'day' => 'day', 'hari' => 'day',
        'start' => 'start', 'mulai' => 'start', 'jammulai' => 'start', 'starttime' => 'start',
        'end' => 'end', 'selesai' => 'end', 'jamselesai' => 'end', 'endtime' => 'end',
        'subject' => 'subject', 'mapel' => 'subject', 'matapelajaran' => 'subject',
        'teacher' => 'teacher', 'guru' => 'teacher', 'nip' => 'teacher', 'username' => 'teacher',
        'room' => 'room', 'ruang' => 'room', 'ruangan' => 'room', 'roomname' => 'room',
    ];

    public function importSchedules(Request $request)
    {
        $request->validate([
            'file'          => 'required|file|extensions:csv,txt,xlsx|max:8192',
            'default_cycle' => 'required|in:Putaran 1,Putaran 2',
            'academic_year' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'semester'      => 'required|in:1,2',
        ], [
            'file.extensions'     => 'Berkas harus berformat Excel (.xlsx) atau CSV.',
            'academic_year.regex' => 'Tahun ajaran harus berformat 2026/2027.',
        ]);

        try {
            $rows = CsvTable::read($request->file('file')->getRealPath());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        if (count($rows) < 2) {
            return back()->with('error', 'Berkas kosong atau hanya berisi judul kolom.');
        }

        $map = [];
        foreach ($rows[0]['cells'] as $i => $head) {
            $k = preg_replace('/[^a-z0-9]/', '', strtolower($head));
            if (isset(self::SCHEDULE_HEADERS[$k]) && !isset($map[self::SCHEDULE_HEADERS[$k]])) {
                $map[self::SCHEDULE_HEADERS[$k]] = $i;
            }
        }
        foreach (['class', 'day', 'start', 'end', 'subject', 'teacher'] as $required) {
            if (!isset($map[$required])) {
                return back()->with('error', "Kolom \"$required\" tidak ditemukan pada baris pertama. Unduh template agar judul kolom sesuai.");
            }
        }

        $defaultCycle = $request->default_cycle;
        $year         = $request->academic_year;
        $semester     = $request->semester;
        $createRooms  = $request->boolean('create_rooms');
        $setActive    = $request->boolean('set_active');

        @set_time_limit(600);

        $result = DB::transaction(function () use ($rows, $map, $defaultCycle, $year, $semester, $createRooms, $setActive) {
            $classes = [];
            foreach (Classes::all() as $c) {
                $classes[CsvTable::key($c->class_name)] = (int) $c->id;
            }
            $rooms = [];
            foreach (Room::all() as $r) {
                $rooms[CsvTable::key($r->room_name)] = (int) $r->id;
            }

            // Guru dicari lewat username (NIP) atau nama lengkap
            $byUsername = [];
            $byName = [];
            foreach (User::whereIn('role_id', [Role::WAKA, Role::GURU, Role::WALI_KELAS, Role::GURU_BK])->get(['id', 'username', 'fullname']) as $u) {
                $byUsername[strtolower($u->username)] = (int) $u->id;
                $byName[CsvTable::key($u->fullname)] = (int) $u->id;
            }

            // Jadwal yang sudah ada, dikelompokkan per putaran+hari untuk cek bentrok
            $existing = [];
            foreach (Schedule::all(['teacher_id', 'class_id', 'room_id', 'cycle_type', 'day_of_week', 'start_time', 'end_time', 'subject']) as $s) {
                $existing[$s->cycle_type . '|' . $s->day_of_week][] = [
                    (int) $s->teacher_id, (int) $s->class_id, (int) $s->room_id,
                    substr($s->start_time, 0, 5), substr($s->end_time, 0, 5), $s->subject,
                ];
            }

            $days = ['senin' => 'senin', 'selasa' => 'selasa', 'rabu' => 'rabu', 'kamis' => 'kamis',
                     'jumat' => 'jumat', 'sabtu' => 'sabtu'];

            $created = $skipped = $roomsMade = 0;
            $errors = [];

            $cell = function (array $cells, string $field) use ($map): string {
                return isset($map[$field], $cells[$map[$field]]) ? $cells[$map[$field]] : '';
            };

            foreach (array_slice($rows, 1) as $row) {
                $line  = $row['line'];
                $cells = $row['cells'];

                $fail = function (string $msg) use (&$errors, &$skipped, $line) {
                    $errors[] = "Baris $line: $msg";
                    $skipped++;
                };

                $className = CsvTable::clean($cell($cells, 'class'));
                $subject   = CsvTable::clean($cell($cells, 'subject'));
                $teacherTx = trim($cell($cells, 'teacher'));
                $roomName  = CsvTable::clean($cell($cells, 'room'));
                $dayKey    = strtolower(preg_replace('/[^a-z]/i', '', $cell($cells, 'day')));
                $day       = $days[$dayKey] ?? null;
                $start     = self::parseTime($cell($cells, 'start'));
                $end       = self::parseTime($cell($cells, 'end'));

                $cycleTx = $cell($cells, 'cycle');
                $cycle = $defaultCycle;
                if ($cycleTx !== '') {
                    if (!preg_match('/([12])\s*$/', $cycleTx, $m)) {
                        $fail("putaran \"$cycleTx\" tidak dikenal (isi Putaran 1 / Putaran 2).");
                        continue;
                    }
                    $cycle = 'Putaran ' . $m[1];
                }

                if ($subject === '' || mb_strlen($subject) > 150) {
                    $fail('mapel kosong atau lebih dari 150 karakter.');
                    continue;
                }
                if (!$day) {
                    $fail('hari tidak valid (Senin–Sabtu).');
                    continue;
                }
                if (!$start || !$end || $end <= $start) {
                    $fail("jam tidak valid ($subject, $className).");
                    continue;
                }

                $classId = $classes[CsvTable::key($className)] ?? null;
                if (!$classId) {
                    $fail("kelas \"$className\" belum ada. Import Kelas dulu.");
                    continue;
                }

                $teacherId = $byUsername[strtolower($teacherTx)] ?? $byName[CsvTable::key($teacherTx)] ?? null;
                if (!$teacherId) {
                    $fail("guru \"$teacherTx\" belum terdaftar ($subject, $className). Tambahkan akun gurunya dulu.");
                    continue;
                }

                // Tanpa ruang di berkas: pakai ruang kelas milik kelas itu sendiri
                if ($roomName === '') {
                    $roomName = 'Ruang ' . $className;
                }
                $rk = CsvTable::key($roomName);
                if (!isset($rooms[$rk])) {
                    if (!$createRooms) {
                        $fail("ruangan \"$roomName\" belum ada.");
                        continue;
                    }
                    $rooms[$rk] = (int) Room::create(['room_name' => $roomName])->id;
                    $roomsMade++;
                }
                $roomId = $rooms[$rk];

                // Anti-bentrok: irisan waktu pada ruang/kelas/guru yang sama (Start_A < End_B AND End_A > Start_B)
                $bucket = $cycle . '|' . $day;
                $duplicate = false;
                $reasons = null;
                foreach ($existing[$bucket] ?? [] as [$tId, $cId, $rId, $eStart, $eEnd, $eSubject]) {
                    if ($tId === $teacherId && $cId === $classId && $eStart === $start && $eEnd === $end) {
                        $duplicate = true;
                        break;
                    }
                    if ($start < $eEnd && $end > $eStart) {
                        $why = [];
                        if ($rId === $roomId) $why[] = 'ruangan';
                        if ($cId === $classId) $why[] = 'kelas';
                        if ($tId === $teacherId) $why[] = 'guru';
                        if ($why) {
                            $reasons = implode(' & ', $why) . " sama dengan \"$eSubject\" ($eStart-$eEnd)";
                            break;
                        }
                    }
                }
                if ($duplicate) {
                    $skipped++; // sudah ada persis sama (aman diimport ulang)
                    continue;
                }
                if ($reasons) {
                    $fail("bentrok: $reasons — $subject, $className, $day $start-$end.");
                    continue;
                }

                Schedule::create([
                    'teacher_id'    => $teacherId,
                    'class_id'      => $classId,
                    'room_id'       => $roomId,
                    'subject'       => $subject,
                    'day_of_week'   => $day,
                    'start_time'    => $start,
                    'end_time'      => $end,
                    'cycle_type'    => $cycle,
                    'academic_year' => $year,
                    'semester'      => $semester,
                ]);
                $existing[$bucket][] = [$teacherId, $classId, $roomId, $start, $end, $subject];
                $created++;
            }

            if ($setActive && $created > 0) {
                $config = SystemConfiguration::firstOrCreate(['id' => 1]);
                $config->current_academic_year = $year;
                $config->current_semester = $semester;
                $config->save();
            }

            return compact('created', 'skipped', 'roomsMade', 'errors');
        });

        $msg = "Import jadwal selesai: {$result['created']} jadwal baru";
        if ($result['roomsMade']) {
            $msg .= ", {$result['roomsMade']} ruangan baru dibuat";
        }
        $msg .= ", {$result['skipped']} dilewati (sudah ada/tidak valid).";

        return $this->finish($result['created'] > 0, $msg, $result['errors']);
    }

    // "07:15", "7.15", "07:15:00" atau angka pecahan hari dari Excel (0.3020833 = 07:15) -> "07:15"
    private static function parseTime(string $v): ?string
    {
        $v = trim($v);
        if (preg_match('/^(\d{1,2})[:.](\d{2})(?::\d{2})?$/', $v, $m) && (int) $m[1] < 24 && (int) $m[2] < 60) {
            return sprintf('%02d:%02d', $m[1], $m[2]);
        }
        if (is_numeric($v) && (float) $v > 0 && (float) $v < 1) {
            $minutes = (int) round(((float) $v) * 1440);
            return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
        }
        return null;
    }

    // ------------------------------------------------------------------ TEMPLATE CSV

    public function template(string $type)
    {
        $templates = [
            'users' => [
                'pengguna.csv',
                [
                    ['fullname', 'username', 'password', 'role', 'class', 'email'],
                    ['Budi Santoso, S.Pd', '197001012000011001', '', 'Guru', '', 'budi@contoh.sch.id'],
                    ['Ani Lestari', '18910', '', 'Siswa', 'X RPL 1', ''],
                ],
            ],
            'classes' => ['kelas.csv', [['class_name'], ['X RPL 1'], ['XI TKJ 2']]],
            'rooms'   => ['ruangan.csv', [['room_name'], ['LAB 1'], ['OR1']]],
            'schedules' => [
                'jadwal.csv',
                [
                    ['cycle', 'class', 'day', 'start', 'end', 'subject', 'teacher', 'room'],
                    ['Putaran 1', 'X RPL 1', 'senin', '07:15', '08:45', 'Bahasa Indonesia', '197001012000011001', ''],
                    ['Putaran 1', 'X RPL 2', 'senin', '07:15', '09:30', 'Dasar Web dan jaringan Komputer', '197001012000011001', 'LAB 16'],
                ],
            ],
        ];

        abort_unless(isset($templates[$type]), 404);
        [$filename, $lines] = $templates[$type];

        return response()->streamDownload(function () use ($lines) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8
            foreach ($lines as $line) {
                fputcsv($out, $line, ';', '"', '\\');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ------------------------------------------------------------------

    private function finish(bool $anyChange, string $message, array $errors)
    {
        $redirect = back()->with($anyChange ? 'success' : 'error', $message);

        if ($errors) {
            $shown = array_slice($errors, 0, self::MAX_ERRORS_SHOWN);
            if (count($errors) > count($shown)) {
                $shown[] = '... dan ' . (count($errors) - count($shown)) . ' galat lainnya.';
            }
            $redirect->with('import_errors', $shown);
        }

        return $redirect;
    }
}
