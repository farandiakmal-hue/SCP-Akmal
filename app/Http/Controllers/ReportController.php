<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classes;
use App\Models\LeaveRequest;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

    // Laporan izin siswa harian: siapa saja yang izin/sakit pada tanggal tertentu
    public function leaveDaily(Request $request)
    {
        $this->authorizeLeaveDailyReport();

        $date = $this->reportDate($request);
        $status = $request->input('status', 'approved');
        $leaves = $this->leaveDailyData($request, $date, $status);
        $classes = Classes::orderBy('class_name')->get();

        return view('reports.leave-daily', compact('leaves', 'date', 'status', 'classes'));
    }

    public function exportLeaveDailyCsv(Request $request)
    {
        $this->authorizeLeaveDailyReport();

        $date = $this->reportDate($request);
        $leaves = $this->leaveDailyData($request, $date, $request->input('status', 'approved'));

        $headers = [
            'Content-type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=laporan_izin_harian_{$date}.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($leaves, $date) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF"); // BOM agar terbaca benar di Excel
            fputcsv($file, ['Tanggal Laporan', 'Nama Siswa', 'Kelas', 'Jenis', 'Mulai', 'Selesai', 'Alasan', 'Status', 'Catatan Verifikasi', 'Diverifikasi Oleh']);

            foreach ($leaves as $l) {
                fputcsv($file, [
                    $date,
                    $l->student->fullname ?? '-',
                    $l->student->classRoom->class_name ?? '-',
                    ucfirst(str_replace('_', ' ', $l->type)),
                    $l->start_date,
                    $l->end_date,
                    $l->reason,
                    $l->status_approval,
                    $l->verification_note,
                    $l->verifier->fullname ?? '-',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function reportDate(Request $request): string
    {
        $d = (string) $request->input('date');
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : Carbon::now('Asia/Jakarta')->toDateString();
    }

    // Izin yang mencakup tanggal laporan (Wali Kelas: hanya kelas perwalian)
    private function leaveDailyData(Request $request, string $date, string $status)
    {
        $user = auth()->user();

        $query = LeaveRequest::visibleTo($user)
            ->with(['student.classRoom', 'verifier'])
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);

        if (in_array($status, ['pending', 'approved', 'rejected', 'revisi'], true)) {
            $query->where('status_approval', $status);
        }

        if ($request->filled('class_id') && !$user->isRole(Role::WALI_KELAS)) {
            $classId = (int) $request->class_id;
            $query->whereHas('student', fn ($s) => $s->where('class_id', $classId));
        }

        return $query->get()->sortBy(fn ($l) => $l->student->fullname ?? '')->values();
    }

    // Laporan Izin Harian hanya untuk Wali Kelas dan Guru BK (Waka tidak)
    private function authorizeLeaveDailyReport(): void
    {
        if (!auth()->user()->isRole(Role::WALI_KELAS, Role::GURU_BK)) {
            abort(403);
        }
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
}
