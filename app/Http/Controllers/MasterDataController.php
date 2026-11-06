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

    // --- HALAMAN INDEX MASTER DATA ---
    public function indexUsers(Request $request)
    {
        $query = User::with(['role', 'classRoom'])->orderBy('role_id')->orderBy('fullname');

        $query->when($request->filled('q'), function ($q) use ($request) {
            $term = '%' . $request->q . '%';
            $q->where(fn ($w) => $w->where('fullname', 'like', $term)
                ->orWhere('username', 'like', $term)
                ->orWhere('email', 'like', $term));
        });
        $query->when($request->filled('role_id'), fn ($q) => $q->where('role_id', (int) $request->role_id));
        $query->when($request->filled('class_id'), fn ($q) => $q->where('class_id', (int) $request->class_id));

        $users = $query->paginate(25)->withQueryString();
        $roles = Role::all();
        $classes = Classes::orderBy('class_name')->get();
        $parents = User::where('role_id', Role::ORTU)->orderBy('fullname')->get();
        return view('master.users', compact('users', 'roles', 'classes', 'parents'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'email'    => 'nullable|email|max:191|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id'  => 'required|exists:roles,id',
            // Siswa wajib punya kelas; Wali Kelas wajib punya kelas perwalian
            'class_id' => 'nullable|exists:classes,id|required_if:role_id,' . Role::SISWA . ',' . Role::WALI_KELAS,
            'parent_id' => ['nullable', Rule::exists('users', 'id')->where('role_id', Role::ORTU)],
        ]);

        User::create([
            'fullname' => $request->fullname,
            'username' => $request->username,
            'email'    => $request->filled('email') ? strtolower(trim($request->email)) : null,
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
