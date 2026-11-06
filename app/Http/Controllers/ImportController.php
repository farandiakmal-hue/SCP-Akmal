<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\Role;
use App\Models\Room;
use App\Models\SystemConfiguration;
use App\Support\JamSesi;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/*
 | Import data sekolah dari file CSV (Kelas, Ruangan, Pengguna, Jadwal).
 |  - Pemisah kolom otomatis dikenali: koma (,) atau titik koma (;) -> aman untuk CSV dari Excel Indonesia.
 |  - Encoding UTF-8 (dengan/tanpa BOM) atau Windows-1252 dikenali otomatis.
 |  - Tahap 1 "Periksa" tidak menyimpan apa pun; tahap 2 "Impor" menyimpan baris yang valid.
 |  - Data yang sudah ada dilewati, jadi file yang sama aman diimpor ulang.
 */
class ImportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['role:' . Role::WAKA];
    }

    private const TYPES = [
        'kelas'    => 'Kelas',
        'ruangan'  => 'Ruangan',
        'pengguna' => 'Pengguna (siswa, guru, wali kelas, guru BK)',
        'jadwal'   => 'Jadwal pelajaran',
    ];

    private const COLUMNS = [
        'kelas'    => ['class_name'],
        'ruangan'  => ['room_name'],
        'pengguna' => ['username', 'fullname', 'role', 'class_name', 'email', 'password'],
        'jadwal'   => ['teacher_username', 'class_name', 'room_name', 'subject', 'day', 'start_time', 'end_time', 'cycle', 'academic_year', 'semester'],
    ];

    private const REQUIRED = [
        'kelas'    => ['class_name'],
        'ruangan'  => ['room_name'],
        'pengguna' => ['username', 'fullname', 'role'],
        'jadwal'   => ['teacher_username', 'class_name', 'room_name', 'subject', 'day', 'start_time', 'end_time'],
    ];

    private const EXAMPLES = [
        'kelas'    => [['X RPL 1'], ['XI TKJ 2']],
        'ruangan'  => [['LAB 1'], ['Ruang Kelas X RPL 1']],
        'pengguna' => [
            ['18910', 'NAMA SISWA CONTOH', 'siswa', 'X RPL 1', '', ''],
            ['198001012005011001', 'NAMA GURU CONTOH, S.Pd', 'guru', '', 'guru@contoh.sch.id', ''],
        ],
        'jadwal'   => [
            ['198001012005011001', 'X RPL 1', 'LAB 1', 'Basis Data', 'senin', '07:15', '09:30', 'Putaran 1', '2026/2027', '1'],
        ],
    ];

    // Nama kolom alternatif -> nama kolom baku
    private const ALIASES = [
        'nama_kelas' => 'class_name', 'kelas' => 'class_name', 'class' => 'class_name',
        'nama_ruangan' => 'room_name', 'ruangan' => 'room_name', 'ruang' => 'room_name', 'room' => 'room_name',
        'nama' => 'fullname', 'nama_lengkap' => 'fullname', 'name' => 'fullname',
        'nis' => 'username', 'nip' => 'username', 'user' => 'username',
        'peran' => 'role', 'kata_sandi' => 'password',
        'guru' => 'teacher_username', 'username_guru' => 'teacher_username', 'nip_guru' => 'teacher_username',
        'mapel' => 'subject', 'mata_pelajaran' => 'subject',
        'hari' => 'day', 'day_of_week' => 'day',
        'jam_mulai' => 'start_time', 'mulai' => 'start_time',
        'jam_selesai' => 'end_time', 'selesai' => 'end_time',
        'putaran' => 'cycle', 'cycle_type' => 'cycle',
        'tahun_ajaran' => 'academic_year',
    ];

    private const DAYS = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];

    public function index()
    {
        return view('master.import', [
            'types'   => self::TYPES,
            'columns' => self::COLUMNS,
        ]);
    }

    public function template(string $type)
    {
        abort_unless(isset(self::COLUMNS[$type]), 404);

        $rows = array_merge([self::COLUMNS[$type]], self::EXAMPLES[$type]);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar terbaca benar di Excel
            foreach ($rows as $row) {
                fputcsv($out, $row, ',', '"', '\\');
            }
            fclose($out);
        }, "template_{$type}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // Satu aksi untuk "Periksa" (mode=check) dan "Impor" (mode=import)
    public function run(Request $request)
    {
        $request->validate([
            'type'             => 'required|in:' . implode(',', array_keys(self::TYPES)),
            'mode'             => 'required|in:check,import',
            'token'            => ['nullable', 'regex:/^[a-f0-9\-]{36}\.csv$/'],
            'file'             => 'nullable|file|max:5120',
            'default_password' => 'nullable|string|max:100',
        ]);

        $type = $request->type;
        $dir = storage_path('app/imports');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // File berasal dari unggahan baru, atau dari tahap "Periksa" sebelumnya (token)
        if ($request->hasFile('file')) {
            $ext = strtolower($request->file('file')->getClientOriginalExtension());
            if (!in_array($ext, ['csv', 'txt'], true)) {
                return back()->with('error', 'File harus berformat CSV (.csv). Di Excel: Save As > CSV (Comma delimited) atau CSV UTF-8.');
            }
            $token = Str::uuid()->toString() . '.csv';
            $request->file('file')->move($dir, $token);
        } elseif ($request->filled('token') && is_file($dir . '/' . $request->token)) {
            $token = $request->token;
        } else {
            return back()->with('error', 'Pilih file CSV terlebih dahulu.');
        }

        $path = $dir . '/' . $token;
        $dry = $request->mode === 'check';
        $update = $request->boolean('update_existing');

        [$rows, $problem] = $this->readCsv($path, $type);

        if ($problem) {
            @unlink($path);
            return redirect()->route('import.index')->with('report', $this->abortReport($type, $problem));
        }

        @set_time_limit(0);
        $report = $this->process($type, $rows, $dry, $update, (string) $request->default_password);
        $report['type'] = $type;
        $report['label'] = self::TYPES[$type];
        $report['dry'] = $dry;
        $report['total'] = count($rows);
        $report['update_existing'] = $update;

        $redirect = redirect()->route('import.index')->with('report', $report);

        if ($dry && empty($report['aborted'])) {
            // simpan file agar bisa dilanjutkan tanpa memilih ulang
            return $redirect->with('import_token', $token);
        }

        @unlink($path);
        return $redirect;
    }

    // ---------------------------------------------------------------- membaca CSV

    /** @return array{0: array<int, array{line:int, data:array<string,string>}>, 1: ?string} */
    private function readCsv(string $path, string $type): array
    {
        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return [[], 'File kosong atau tidak bisa dibaca.'];
        }

        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        $firstLine = strtok($content, "\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter, '"', '\\');
        if (!$header) {
            fclose($handle);
            return [[], 'Baris judul kolom (header) tidak ditemukan.'];
        }

        $map = [];
        foreach ($header as $i => $h) {
            $name = Str::lower(trim((string) $h));
            $name = preg_replace('/[\s\-]+/', '_', $name);
            $map[$i] = self::ALIASES[$name] ?? $name;
        }

        $missing = array_values(array_diff(self::REQUIRED[$type], $map));
        if ($missing) {
            fclose($handle);
            return [[], 'Kolom wajib tidak ditemukan: ' . implode(', ', $missing)
                . '. Kolom yang terbaca: ' . implode(', ', array_filter($map)) . '. Unduh template untuk melihat format yang benar.'];
        }

        $rows = [];
        $line = 1;
        while (($cells = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $line++;
            $data = [];
            foreach ($map as $i => $col) {
                $data[$col] = trim((string) ($cells[$i] ?? ''));
            }
            if (implode('', $data) === '') {
                continue; // baris kosong
            }
            $rows[] = ['line' => $line, 'data' => $data];
        }
        fclose($handle);

        if (!$rows) {
            return [[], 'Tidak ada baris data di bawah judul kolom.'];
        }

        return [$rows, null];
    }

    // ---------------------------------------------------------------- pelaporan

    private function newReport(): array
    {
        return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => [], 'error_count' => 0, 'warnings' => [], 'preview' => [], 'aborted' => null];
    }

    private function addError(array &$report, int $line, string $message): void
    {
        $report['error_count']++;
        if (count($report['errors']) < 200) {
            $report['errors'][] = ['line' => $line, 'message' => $message];
        }
    }

    private function abortReport(string $type, string $message): array
    {
        $r = $this->newReport();
        $r['aborted'] = $message;
        $r['type'] = $type;
        $r['label'] = self::TYPES[$type];
        $r['dry'] = true;
        $r['total'] = 0;
        $r['update_existing'] = false;
        return $r;
    }

    private function key(string $value): string
    {
        return Str::lower(preg_replace('/\s+/', '', $value));
    }

    private function process(string $type, array $rows, bool $dry, bool $update, string $defaultPassword): array
    {
        return match ($type) {
            'kelas'    => $this->importNames($rows, $dry, 'class_name', 'Kelas'),
            'ruangan'  => $this->importNames($rows, $dry, 'room_name', 'Ruangan'),
            'pengguna' => $this->importUsers($rows, $dry, $update, $defaultPassword),
            default    => $this->importSchedules($rows, $dry),
        };
    }

    // ---------------------------------------------------------------- kelas & ruangan

    private function importNames(array $rows, bool $dry, string $column, string $label): array
    {
        $r = $this->newReport();
        $model = $column === 'class_name' ? Classes::class : Room::class;

        $known = [];
        foreach ($model::all([$column]) as $m) {
            $known[$this->key($m->{$column})] = true;
        }

        foreach ($rows as $row) {
            $name = preg_replace('/\s+/', ' ', $row['data'][$column] ?? '');

            if ($name === '') {
                $this->addError($r, $row['line'], "{$label} kosong.");
                continue;
            }
            if (mb_strlen($name) > 50) {
                $this->addError($r, $row['line'], "{$label} \"{$name}\" terlalu panjang (maks. 50 karakter).");
                continue;
            }
            if (isset($known[$this->key($name)])) {
                $r['skipped']++;
                continue;
            }

            $known[$this->key($name)] = true;
            $r['created']++;
            if (count($r['preview']) < 10) {
                $r['preview'][] = [$name];
            }
            if (!$dry) {
                $model::create([$column => $name]);
            }
        }

        return $r;
    }

    // ---------------------------------------------------------------- pengguna

    private function roleId(string $value): ?int
    {
        $k = Str::lower(preg_replace('/[\s_\-]+/', '', $value));
        return [
            'waka' => Role::WAKA, 'wakakurikulum' => Role::WAKA,
            'guru' => Role::GURU,
            'walikelas' => Role::WALI_KELAS, 'wali' => Role::WALI_KELAS,
            'gurubk' => Role::GURU_BK, 'bk' => Role::GURU_BK,
            'siswa' => Role::SISWA,
            'ortu' => Role::ORTU, 'orangtua' => Role::ORTU,
        ][$k] ?? null;
    }

    private function importUsers(array $rows, bool $dry, bool $update, string $defaultPassword): array
    {
        $r = $this->newReport();

        $classIds = [];
        foreach (Classes::all(['id', 'class_name']) as $c) {
            $classIds[$this->key($c->class_name)] = $c->id;
        }

        $existing = []; // username(lower) => id
        $emails = [];   // email(lower) => id pemilik
        foreach (DB::table('users')->select('id', 'username', 'email')->get() as $u) {
            $existing[Str::lower($u->username)] = (int) $u->id;
            if ($u->email) {
                $emails[Str::lower($u->email)] = (int) $u->id;
            }
        }

        $roleNames = Role::pluck('role_name', 'id')->all();
        $seen = [];
        $newRows = [];
        $updates = [];
        $myId = (int) auth()->id();

        foreach ($rows as $row) {
            $line = $row['line'];
            $d = $row['data'];

            $username = $d['username'] ?? '';
            $fullname = preg_replace('/\s+/', ' ', $d['fullname'] ?? '');
            $roleId = $this->roleId($d['role'] ?? '');
            $className = $d['class_name'] ?? '';
            $email = Str::lower($d['email'] ?? '');
            $password = $d['password'] ?? '';
            $ukey = Str::lower($username);

            if ($username === '' || mb_strlen($username) > 50) {
                $this->addError($r, $line, 'Username kosong atau lebih dari 50 karakter.');
                continue;
            }
            if (isset($seen[$ukey])) {
                $this->addError($r, $line, "Username \"{$username}\" muncul lebih dari sekali di file.");
                continue;
            }
            $seen[$ukey] = true;

            $isExisting = isset($existing[$ukey]);
            if ($isExisting && !$update) {
                $r['skipped']++;
                continue;
            }
            if ($isExisting && $existing[$ukey] === $myId) {
                $r['skipped']++;
                $r['warnings'][] = "Baris {$line}: akun Anda sendiri ({$username}) dilewati agar peran Anda tidak berubah.";
                continue;
            }

            if ($fullname === '' || mb_strlen($fullname) > 100) {
                $this->addError($r, $line, "Nama lengkap kosong atau lebih dari 100 karakter (username {$username}).");
                continue;
            }
            if ($roleId === null) {
                $this->addError($r, $line, "Role \"{$d['role']}\" tidak dikenal (pakai: siswa, guru, wali_kelas, guru_bk, waka, ortu).");
                continue;
            }

            $classId = null;
            if (in_array($roleId, [Role::SISWA, Role::WALI_KELAS], true)) {
                if ($className === '') {
                    $this->addError($r, $line, "Kelas wajib diisi untuk {$roleNames[$roleId]} ({$username}).");
                    continue;
                }
                $classId = $classIds[$this->key($className)] ?? null;
                if ($classId === null) {
                    $this->addError($r, $line, "Kelas \"{$className}\" belum ada. Impor data Kelas lebih dulu.");
                    continue;
                }
            }

            if ($email !== '') {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 191) {
                    $this->addError($r, $line, "Format email \"{$email}\" tidak valid.");
                    continue;
                }
                $owner = $emails[$email] ?? null;
                if ($owner !== null && $owner !== ($existing[$ukey] ?? null)) {
                    $this->addError($r, $line, "Email \"{$email}\" sudah dipakai pengguna lain.");
                    continue;
                }
                $emails[$email] = $existing[$ukey] ?? -$line;
            }

            if ($password !== '' && mb_strlen($password) < 6) {
                $this->addError($r, $line, "Password untuk {$username} kurang dari 6 karakter.");
                continue;
            }

            if ($isExisting) {
                $set = ['fullname' => $fullname, 'role_id' => $roleId, 'class_id' => $classId];
                if ($email !== '') {
                    $set['email'] = $email;
                }
                $updates[] = ['id' => $existing[$ukey], 'set' => $set, 'plain' => $password !== '' ? $password : null];
            } else {
                $newRows[] = [
                    'username' => $username, 'fullname' => $fullname, 'role_id' => $roleId, 'class_id' => $classId,
                    'email' => $email !== '' ? $email : null, 'plain' => $password !== '' ? $password : null,
                ];
            }

            if (count($r['preview']) < 10) {
                $r['preview'][] = [$username, $fullname, $roleNames[$roleId] ?? '-', $className !== '' ? $className : '-'];
            }
        }

        $needDefault = count(array_filter($newRows, fn ($n) => $n['plain'] === null));
        if ($needDefault > 0) {
            if ($dry) {
                $r['warnings'][] = "{$needDefault} pengguna baru tidak punya password di file. Isi \"Password awal\" (min. 6 karakter) saat mengimpor; semua akun tsb memakai password itu.";
            } elseif (mb_strlen($defaultPassword) < 6) {
                $r['aborted'] = 'Password awal wajib diisi (minimal 6 karakter) karena ada pengguna baru tanpa password di file. Tidak ada data yang disimpan.';
                return $r;
            }
        }

        $distinct = [];
        foreach ($newRows as $n) {
            $distinct[$n['plain'] ?? $defaultPassword] = true;
        }
        foreach ($updates as $u) {
            if ($u['plain'] !== null) {
                $distinct[$u['plain']] = true;
            }
        }
        if (!$dry && count($distinct) > 100) {
            $r['aborted'] = 'Terlalu banyak password berbeda di file (' . count($distinct) . '). Kosongkan kolom password dan pakai satu "Password awal" agar impor tidak terlalu lama. Tidak ada data yang disimpan.';
            return $r;
        }

        $r['created'] = count($newRows);
        $r['updated'] = count($updates);

        if ($dry) {
            return $r;
        }

        $hashes = [];
        $hash = function (string $plain) use (&$hashes): string {
            if (!isset($hashes[$plain])) {
                $hashes[$plain] = Hash::make($plain);
            }
            return $hashes[$plain];
        };

        $stamp = now();
        $batch = [];
        foreach ($newRows as $n) {
            $batch[] = [
                'username'   => $n['username'],
                'fullname'   => $n['fullname'],
                'role_id'    => $n['role_id'],
                'class_id'   => $n['class_id'],
                'email'      => $n['email'],
                'password'   => $hash($n['plain'] ?? $defaultPassword),
                'created_at' => $stamp,
                'updated_at' => $stamp,
            ];
        }

        DB::transaction(function () use ($batch, $updates, $hash, $stamp) {
            foreach (array_chunk($batch, 200) as $chunk) {
                DB::table('users')->insert($chunk);
            }
            foreach ($updates as $u) {
                $set = $u['set'] + ['updated_at' => $stamp];
                if ($u['plain'] !== null) {
                    $set['password'] = $hash($u['plain']);
                }
                DB::table('users')->where('id', $u['id'])->update($set);
            }
        });

        return $r;
    }

    // ---------------------------------------------------------------- jadwal

    private function normTime(string $value): ?string
    {
        if (!preg_match('/^(\d{1,2})[:.](\d{2})(?::\d{2})?$/', trim($value), $m)) {
            return null;
        }
        if ((int) $m[1] > 23 || (int) $m[2] > 59) {
            return null;
        }
        return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
    }

    private function importSchedules(array $rows, bool $dry): array
    {
        $r = $this->newReport();
        $config = SystemConfiguration::first();
        $defCycle = $config->active_cycle ?? 'Putaran 1';
        $defYear = $config->current_academic_year ?? '2026/2027';
        $defSemester = (string) ($config->current_semester ?? '1');

        $teachers = [];
        foreach (DB::table('users')->whereIn('role_id', [Role::WAKA, Role::GURU, Role::WALI_KELAS, Role::GURU_BK])->select('id', 'username')->get() as $u) {
            $teachers[Str::lower($u->username)] = (int) $u->id;
        }
        $classIds = [];
        foreach (Classes::all(['id', 'class_name']) as $c) {
            $classIds[$this->key($c->class_name)] = $c->id;
        }
        $roomIds = [];
        foreach (Room::all(['id', 'room_name']) as $m) {
            $roomIds[$this->key($m->room_name)] = $m->id;
        }

        $busy = [];  // "cycle|day" => daftar jadwal (untuk cek bentrok)
        $dupes = []; // kunci jadwal yang sudah ada
        foreach (DB::table('schedules')->get() as $s) {
            $st = substr($s->start_time, 0, 5);
            $en = substr($s->end_time, 0, 5);
            $busy[$s->cycle_type . '|' . $s->day_of_week][] = [$st, $en, $s->teacher_id, $s->class_id, $s->room_id, $s->subject];
            $dupes[$this->dupKey($s->cycle_type, $s->day_of_week, $st, $en, $s->teacher_id, $s->class_id, $s->room_id, $s->subject)] = true;
        }

        $breaks = JamSesi::breaks();
        $insert = [];

        foreach ($rows as $row) {
            $line = $row['line'];
            $d = $row['data'];

            $tKey = Str::lower($d['teacher_username'] ?? '');
            $teacherId = $teachers[$tKey] ?? null;
            $classId = $classIds[$this->key($d['class_name'] ?? '')] ?? null;
            $roomId = $roomIds[$this->key($d['room_name'] ?? '')] ?? null;
            $subject = preg_replace('/\s+/', ' ', $d['subject'] ?? '');
            $day = Str::lower($d['day'] ?? '');
            $start = $this->normTime($d['start_time'] ?? '');
            $end = $this->normTime($d['end_time'] ?? '');
            $cycle = ($d['cycle'] ?? '') !== '' ? $d['cycle'] : $defCycle;
            $year = ($d['academic_year'] ?? '') !== '' ? $d['academic_year'] : $defYear;
            $semester = Str::lower(($d['semester'] ?? '') !== '' ? $d['semester'] : $defSemester);
            $semester = ['ganjil' => '1', 'genap' => '2'][$semester] ?? $semester;

            if ($teacherId === null) {
                $this->addError($r, $line, "Guru dengan username \"{$d['teacher_username']}\" tidak ditemukan. Impor Pengguna lebih dulu.");
                continue;
            }
            if ($classId === null) {
                $this->addError($r, $line, "Kelas \"{$d['class_name']}\" tidak ditemukan.");
                continue;
            }
            if ($roomId === null) {
                $this->addError($r, $line, "Ruangan \"{$d['room_name']}\" tidak ditemukan. Impor Ruangan lebih dulu.");
                continue;
            }
            if ($subject === '' || mb_strlen($subject) > 150) {
                $this->addError($r, $line, 'Mata pelajaran kosong atau lebih dari 150 karakter.');
                continue;
            }
            if (!in_array($day, self::DAYS, true)) {
                $this->addError($r, $line, "Hari \"{$d['day']}\" tidak valid (senin s.d. sabtu).");
                continue;
            }
            if ($start === null || $end === null || $start >= $end) {
                $this->addError($r, $line, 'Jam mulai/selesai tidak valid (format HH:MM, mulai harus sebelum selesai).');
                continue;
            }
            if (!in_array($semester, ['1', '2'], true)) {
                $this->addError($r, $line, "Semester \"{$d['semester']}\" tidak valid (1 atau 2).");
                continue;
            }

            foreach ($breaks as [$label, $bs, $be]) {
                if ($start < $be && $end > $bs) {
                    $this->addError($r, $line, "Jadwal {$start}-{$end} melewati {$label} ({$bs}-{$be}). Pisahkan menjadi dua baris.");
                    continue 2;
                }
            }

            $dk = $this->dupKey($cycle, $day, $start, $end, $teacherId, $classId, $roomId, $subject);
            if (isset($dupes[$dk])) {
                $r['skipped']++;
                continue;
            }

            $conflict = null;
            foreach ($busy[$cycle . '|' . $day] ?? [] as $b) {
                if ($b[0] < $end && $b[1] > $start) {
                    $why = [];
                    if ($b[2] == $teacherId) $why[] = 'guru';
                    if ($b[3] == $classId) $why[] = 'kelas';
                    if ($b[4] == $roomId) $why[] = 'ruangan';
                    if ($why) {
                        $conflict = "Bentrok dengan \"{$b[5]}\" ({$b[0]}-{$b[1]}) pada " . implode(' & ', $why) . ' yang sama.';
                        break;
                    }
                }
            }
            if ($conflict) {
                $this->addError($r, $line, $conflict);
                continue;
            }

            $dupes[$dk] = true;
            $busy[$cycle . '|' . $day][] = [$start, $end, $teacherId, $classId, $roomId, $subject];
            $insert[] = [
                'teacher_id' => $teacherId, 'class_id' => $classId, 'room_id' => $roomId, 'subject' => $subject,
                'day_of_week' => $day, 'start_time' => $start . ':00', 'end_time' => $end . ':00',
                'cycle_type' => $cycle, 'academic_year' => $year, 'semester' => $semester,
            ];
            if (count($r['preview']) < 10) {
                $r['preview'][] = [$d['teacher_username'], $d['class_name'], $subject, ucfirst($day) . " {$start}-{$end}", $cycle];
            }
        }

        $r['created'] = count($insert);

        if (!$dry && $insert) {
            $stamp = now();
            foreach ($insert as &$i) {
                $i['created_at'] = $stamp;
                $i['updated_at'] = $stamp;
            }
            unset($i);
            DB::transaction(function () use ($insert) {
                foreach (array_chunk($insert, 200) as $chunk) {
                    DB::table('schedules')->insert($chunk);
                }
            });
        }

        return $r;
    }

    private function dupKey($cycle, $day, $start, $end, $teacherId, $classId, $roomId, $subject): string
    {
        return implode('|', [$cycle, $day, $start, $end, $teacherId, $classId, $roomId, Str::lower($subject)]);
    }
}
