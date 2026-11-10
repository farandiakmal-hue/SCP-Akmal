<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\Classes;
use App\Models\Journal;
use Carbon\Carbon;

class ReportController extends Controller
{
    // Rekap & Filter Presensi (Tim Kesiswaan/Wali Kelas/Guru BK memantau rekap absensi)
    public function attendanceReport(Request $request)
    {
        $this->authorizeReport();

        $attendances = $this->filteredQuery($request)->get();
        return view('reports.attendance', compact('attendances'));
    }

    public function exportAttendanceCsv(Request $request)
    {
        $this->authorizeReport();

        $fileName = 'rekap_presensi_' . date('Y-m-d') . '.csv';
        $attendances = $this->filteredQuery($request)->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        $columns = ['ID', 'Nama Siswa', 'Tanggal', 'Status', 'Mapel', 'Sumber'];

        $callback = function () use ($attendances, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($attendances as $row) {
                fputcsv($file, [
                    $row->id,
                    $row->student->fullname ?? 'N/A',
                    $row->date,
                    $row->status,
                    $row->journal->schedule->subject ?? '-',
                    $row->source,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function authorizeReport(): void
    {
        if (!auth()->user()->isRole(Role::WAKA, Role::WALI_KELAS, Role::GURU_BK)) {
            abort(403);
        }
    }

    // Query yang sama dipakai halaman rekap dan export CSV (filter ikut terbawa)
    private function filteredQuery(Request $request)
    {
        $user = auth()->user();
        $query = Attendance::with(['student', 'journal.schedule.class']);

        // Wali Kelas hanya melihat kelas perwaliannya
        if ($user->isRole(Role::WALI_KELAS)) {
            $classId = $user->class_id;
            $query->whereHas('student', fn ($q) => $classId
                ? $q->where('class_id', $classId)
                : $q->whereRaw('1 = 0'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        return $query->orderByDesc('date')->orderByDesc('id');
    }
    // Rekap presensi harian untuk Guru: satu baris per jurnal (pertemuan) pada tanggal terpilih
public function dailyRecap(Request $request)
{
    $user = auth()->user();
    if (!$user->isRole(Role::GURU)) {
        abort(403, 'Rekap harian hanya untuk Guru.');
    }

    $date = $request->input('date', Carbon::now('Asia/Jakarta')->toDateString());

    $journals = Journal::with(['schedule.class'])
        ->withCount([
            'attendances as total_count',
            'attendances as hadir_count'      => fn ($q) => $q->where('status', 'hadir'),
            'attendances as sakit_count'      => fn ($q) => $q->where('status', 'sakit'),
            'attendances as izin_count'       => fn ($q) => $q->whereIn('status', ['izin', 'izin_tengah_jam']),
            'attendances as dispensasi_count' => fn ($q) => $q->where('status', 'dispensasi'),
            'attendances as alpha_count'      => fn ($q) => $q->where('status', 'alpha'),
        ])
        ->whereDate('date', $date)
        ->whereHas('schedule', function ($q) use ($user, $request) {
            $q->where('teacher_id', $user->id);
            if ($request->filled('class_id')) {
                $q->where('class_id', $request->class_id);
            }
        })
        ->get()
        ->sortBy(fn ($j) => $j->schedule->start_time)
        ->values();

    $classes = Classes::orderBy('class_name')->get();

    return view('reports.daily', compact('journals', 'classes', 'date'));
}
}
