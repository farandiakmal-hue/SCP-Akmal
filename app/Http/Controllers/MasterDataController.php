<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\LeaveRequest;

// Laravel 11+ menghapus $this->middleware() dari base Controller, jadi middleware
// per-controller sekarang didaftarkan lewat interface HasMiddleware ini.
class MasterDataController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        // Master data (user, kelas, ruangan) HANYA untuk Waka
        return [
            'role:' . Role::WAKA,
        ];
    }

    public function indexUsers(Request $request)
{
    $query = $this->applyUserFilters(User::with(['role', 'classRoom']), $request);

    $users = $query->orderBy('role_id')->orderBy('fullname')->paginate(25)->withQueryString();
    $roles = Role::all();
    $classes = Classes::all();
    $parents = User::where('role_id', Role::ORTU)->orderBy('fullname')->get();

    return view('master.users', compact('users', 'roles', 'classes', 'parents'));
}

private function applyUserFilters($query, Request $request)
{
    if ($request->filled('q')) {
        $q = $request->q;
        $query->where(fn ($w) => $w->where('fullname', 'like', "%{$q}%")
            ->orWhere('username', 'like', "%{$q}%"));
    }
    if ($request->filled('role_id')) {
        $query->where('role_id', $request->role_id);
    }
    if ($request->filled('class_id')) {
        $query->where('class_id', $request->class_id);
    }

    return $query;
}
    public function storeUser(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:6',
            'role_id'  => 'required|exists:roles,id',
            // Siswa wajib punya kelas; Wali Kelas wajib punya kelas perwalian
            'class_id' => 'nullable|exists:classes,id|required_if:role_id,' . Role::SISWA . ',' . Role::WALI_KELAS,
            'parent_id' => ['nullable', Rule::exists('users', 'id')->where('role_id', Role::ORTU)],
        ]);

        User::create([
            'fullname' => $request->fullname,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role_id'  => $request->role_id,
            'class_id' => $request->class_id,
            // Relasi Orang Tua hanya berlaku untuk siswa
            'parent_id' => $request->role_id == Role::SISWA ? $request->parent_id : null,
        ]);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function destroyUser($id)
    {
        if ((int) $id === (int) auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        User::findOrFail($id)->delete();
        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
    // --- IMPORT CSV ---
public function template($type)
{
    $templates = [
        'users' => [
            ['fullname', 'username', 'password', 'role', 'class', 'parent_username'],
            ['Budi Santoso', 'budi01', 'rahasia123', 'Siswa', 'X AK 1', 'ortu_budi'],
            ['Siti Aminah', 'siti.guru', 'rahasia123', 'Guru', '', ''],
        ],
        'classes' => [['class_name'], ['X AK 1'], ['X AK 2']],
        'rooms'   => [['room_name'], ['LAB 1'], ['Ruang Kelas X AK 1']],
    ];
    abort_unless(isset($templates[$type]), 404);

    return response()->streamDownload(function () use ($templates, $type) {
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca kolom dengan benar
        foreach ($templates[$type] as $row) {
            fputcsv($out, $row, ';');
        }
        fclose($out);
    }, "template_{$type}.csv", ['Content-Type' => 'text/csv']);
}
private const DEFAULT_PASSWORD = 'smkn1bantul';

public function importUsers(Request $request)
{
    set_time_limit(0);

    $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);
    [$header, $rows] = $this->readCsv($request->file('file'));

    foreach (['fullname', 'username', 'role'] as $col) {
        if (!in_array($col, $header)) {
            return back()->with('error', 'Kolom "' . $col . '" tidak ditemukan di baris pertama file.');
        }
    }

    // Role boleh slug (wali_kelas, guru_bk, ...), nama di tabel roles, atau ID 1-6
    $norm = fn ($s) => str_replace([' ', '-'], '_', strtolower(trim($s)));
    $roles = [
        'waka' => Role::WAKA, 'guru' => Role::GURU, 'wali_kelas' => Role::WALI_KELAS,
        'guru_bk' => Role::GURU_BK, 'siswa' => Role::SISWA,
        'ortu' => Role::ORTU, 'orang_tua' => Role::ORTU,
    ];
    foreach (Role::all() as $r) {
        $roles[$norm($r->role_name)] = $r->id;
        $roles[(string) $r->id] = $r->id;
    }

    $classes = Classes::all()
        ->mapWithKeys(fn ($c) => [strtolower(trim($c->class_name)) => $c->id])
        ->all();

    $existing = User::pluck('username')->flip()->all();
    $defaultHash = Hash::make(self::DEFAULT_PASSWORD); // di-hash sekali, dipakai ulang

    $ok = 0;
    $errors = [];

    foreach ($rows as ['line' => $line, 'data' => $d]) {
        $fullname = trim($d['fullname'] ?? '');
        $username = trim($d['username'] ?? '');
        $password = trim($d['password'] ?? '');
        $roleId   = $roles[$norm($d['role'] ?? '')] ?? null;
        $classRaw = trim($d['class_name'] ?? $d['class'] ?? '');
        $classId  = $classRaw === '' ? null : ($classes[strtolower($classRaw)] ?? false);

        if ($fullname === '' || $username === '') {
            $errors[] = "Baris $line: nama dan username wajib diisi.";
            continue;
        }
        if ($password !== '' && strlen($password) < 6) {
            $errors[] = "Baris $line: password minimal 6 karakter (kosongkan untuk memakai password awal).";
            continue;
        }
        if (!$roleId) {
            $errors[] = "Baris $line: role \"" . ($d['role'] ?? '') . '" tidak dikenali.';
            continue;
        }
        if ($classId === false) {
            $errors[] = "Baris $line: kelas \"$classRaw\" belum ada. Impor data Kelas lebih dulu.";
            continue;
        }
        if (in_array($roleId, [Role::SISWA, Role::WALI_KELAS]) && !$classId) {
            $errors[] = "Baris $line: siswa dan wali kelas wajib punya kelas.";
            continue;
        }
        if (isset($existing[$username])) {
            $errors[] = "Baris $line: username \"$username\" sudah dipakai.";
            continue;
        }

        $parentId = null;
        $parentUsername = trim($d['parent_username'] ?? '');
        if ($roleId == Role::SISWA && $parentUsername !== '') {
            $parent = User::where('username', $parentUsername)->where('role_id', Role::ORTU)->first();
            if (!$parent) {
                $errors[] = "Baris $line: orang tua dengan username \"$parentUsername\" tidak ditemukan.";
                continue;
            }
            $parentId = $parent->id;
        }

        User::create([
            'fullname'  => $fullname,
            'username'  => $username,
            'password'  => $password === '' ? $defaultHash : Hash::make($password),
            'role_id'   => $roleId,
            'class_id'  => $classId,
            'parent_id' => $parentId,
        ]);
        $existing[$username] = true;
        $ok++;
    }

    return $this->importResult($ok, $errors, 'pengguna');
}

public function importClasses(Request $request)
{
    return $this->importSimple($request, 'class_name', Classes::class, 'kelas');
}

public function importRooms(Request $request)
{
    return $this->importSimple($request, 'room_name', Room::class, 'ruangan');
}

private function importSimple(Request $request, string $column, string $model, string $label)
{
    $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);
    [$header, $rows] = $this->readCsv($request->file('file'));

    if (!in_array($column, $header)) {
        return back()->with('error', 'Kolom "' . $column . '" tidak ditemukan di baris pertama file. Gunakan template yang disediakan.');
    }

    $ok = 0;
    $errors = [];

    foreach ($rows as ['line' => $line, 'data' => $d]) {
        $name = trim($d[$column] ?? '');
        if ($name === '') {
            $errors[] = "Baris $line: nama $label kosong.";
            continue;
        }
        if ($model::where($column, $name)->exists()) {
            $errors[] = "Baris $line: $label \"$name\" sudah ada.";
            continue;
        }
        $model::create([$column => $name]);
        $ok++;
    }

    return $this->importResult($ok, $errors, $label);
}

private function importResult(int $ok, array $errors, string $label)
{
    $flash = $ok > 0
        ? ['success' => "$ok $label berhasil diimpor." . ($errors ? ' ' . count($errors) . ' baris dilewati.' : '')]
        : ['error' => "Tidak ada $label yang berhasil diimpor."];

    return back()->with($flash)->with('import_errors', array_slice($errors, 0, 15));
}

// Baca CSV (pemisah koma atau titik koma terdeteksi otomatis, BOM diabaikan)
private function readCsv($file): array
{
    $content = file_get_contents($file->getRealPath());
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

    $lines = preg_split('/\r\n|\r|\n/', trim($content));
    $first = $lines[0] ?? '';
    $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';

    $header = array_map(fn ($h) => strtolower(trim($h)), str_getcsv(array_shift($lines) ?? '', $delimiter));

    $rows = [];
    foreach ($lines as $i => $line) {
        if (trim($line) === '') {
            continue;
        }
        $values = array_pad(str_getcsv($line, $delimiter), count($header), '');
        $rows[] = [
            'line' => $i + 2, // nomor baris di file (baris 1 = header)
            'data' => array_combine($header, array_slice($values, 0, count($header))),
        ];
    }

    return [$header, $rows];
}
// --- HAPUS SEMUA ---
public function destroyAllUsers(Request $request)
{
    $this->validateDeleteConfirm($request);
    set_time_limit(0);

    // Akun Waka dan akun yang sedang login tidak pernah ikut terhapus
    $query = $this->applyUserFilters(User::query(), $request)
        ->where('role_id', '!=', Role::WAKA)
        ->where('id', '!=', auth()->id());

    $total = 0;
    $query->chunkById(500, function ($chunk) use (&$total) {
        $ids = $chunk->pluck('id');
        LeaveRequest::whereIn('verified_by', $ids)->update(['verified_by' => null]);
        $total += User::whereIn('id', $ids)->delete();
    });

    return $total > 0
        ? back()->with('success', "$total pengguna berhasil dihapus.")
        : back()->with('error', 'Tidak ada pengguna yang dihapus.');
}

public function destroyAllClasses(Request $request)
{
    $this->validateDeleteConfirm($request);

    $total = Classes::count();
    Classes::query()->delete();

    return $total > 0
        ? back()->with('success', "$total kelas berhasil dihapus.")
        : back()->with('error', 'Tidak ada kelas yang dihapus.');
}

public function destroyAllRooms(Request $request)
{
    $this->validateDeleteConfirm($request);

    $total = Room::count();
    Room::query()->delete();

    return $total > 0
        ? back()->with('success', "$total ruangan berhasil dihapus.")
        : back()->with('error', 'Tidak ada ruangan yang dihapus.');
}

private function validateDeleteConfirm(Request $request): void
{
    $request->validate(
        ['confirm' => 'required|in:HAPUS'],
        ['confirm.in' => 'Ketik HAPUS (huruf besar) untuk mengonfirmasi.', 'confirm.required' => 'Ketik HAPUS untuk mengonfirmasi.']
    );
}

    public function indexClasses()
    {
        $classes = Classes::all();
        return view('master.classes', compact('classes'));
    }

    public function indexRooms()
    {
        $rooms = Room::all();
        return view('master.rooms', compact('rooms'));
    }

    // --- CRUD RUANGAN ---
    public function storeRoom(Request $request)
    {
        $request->validate([
            'room_name' => 'required|string|max:50|unique:rooms,room_name',
        ]);

        Room::create([
            'room_name' => $request->room_name,
        ]);

        return redirect()->back()->with('success', 'Ruangan berhasil ditambahkan.');
    }

    public function destroyRoom($id)
    {
        Room::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Ruangan berhasil dihapus.');
    }

    // --- CRUD KELAS ---
    public function storeClass(Request $request)
    {
        $request->validate([
            'class_name' => 'required|string|max:50|unique:classes,class_name',
        ]);

        Classes::create([
            'class_name' => $request->class_name,
        ]);

        return redirect()->back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function destroyClass($id)
    {
        Classes::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Kelas berhasil dihapus.');
    }
}
