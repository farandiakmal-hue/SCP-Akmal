<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    // Halaman Presensi untuk Siswa & Orang Tua (rekap kehadiran + filter bulan/status)
    public function mine(Request $request)
    {
        $user = auth()->user();
        if (!$user->isPortalUser()) {
            abort(403, 'Halaman ini hanya untuk siswa atau orang tua.');
        }

        $studentIds = $user->portalStudentIds();
        $children = $user->isRole(Role::ORTU)
            ? User::whereIn('id', $studentIds)->orderBy('fullname')->get()
            : collect();

        // Orang tua boleh memilih salah satu anaknya; selain itu tampilkan semua yang berhak
        $selectedIds = $studentIds;
        if ($user->isRole(Role::ORTU) && $request->filled('student_id')
            && in_array((int) $request->student_id, $studentIds, true)) {
            $selectedIds = [(int) $request->student_id];
        }

        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->month) ? $request->month : null;
        $status = in_array($request->status, ['hadir', 'sakit', 'izin', 'izin_tengah_jam', 'dispensasi', 'alpha'], true)
            ? $request->status : null;

        $base = Attendance::whereIn('student_id', $selectedIds);
        if ($month) {
            [$y, $m] = explode('-', $month);
            $base->whereYear('date', $y)->whereMonth('date', $m);
        }

        // Ringkasan dihitung sebelum filter status agar persentase tetap utuh
        $summary = (clone $base)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $total = (int) $summary->sum();
        $percentHadir = $total > 0 ? round((($summary['hadir'] ?? 0) / $total) * 100) : null;

        $students = User::with('classRoom')->whereIn('id', $selectedIds)->orderBy('fullname')->get();

        $attendances = (clone $base)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['student', 'journal.schedule'])
            ->orderByDesc('date')->orderByDesc('id')
            ->get();

        return view('attendance.mine', compact('students', 'attendances', 'summary', 'total', 'percentHadir', 'children', 'month', 'status'));
    }
}
