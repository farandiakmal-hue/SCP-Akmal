<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeaveRequestController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Siswa/Ortu: pengajuan anaknya; Wali Kelas: kelas perwalian; Guru BK & Waka: semua
        $requests = LeaveRequest::visibleTo($user)->with('student')->latest()->get();

        $attendances = collect();
        $children = collect();

        if ($user->isPortalUser()) {
            $studentIds = $user->portalStudentIds();

            if ($user->isRole(Role::ORTU)) {
                $children = User::whereIn('id', $studentIds)->orderBy('fullname')->get();
            }

            // Rekap kehadiran untuk Siswa & Orang Tua
            $attendances = Attendance::with(['student', 'journal.schedule'])
                ->whereIn('student_id', $studentIds)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get();
        }

        return view('leave.index', compact('requests', 'attendances', 'children'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->isPortalUser()) {
            abort(403, 'Hanya siswa atau orang tua yang dapat mengajukan izin.');
        }

        $request->validate([
            'type'       => 'required|in:sakit,izin,izin_tengah_jam,dispensasi',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:500',
            'document'   => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'student_id' => 'nullable|integer',
        ]);

        // Siswa mengajukan untuk dirinya; Orang Tua mengajukan untuk anaknya
        $studentId = $user->id;
        if ($user->isRole(Role::ORTU)) {
            $childIds = $user->portalStudentIds();

            if (empty($childIds)) {
                return back()->withInput()->with('error', 'Akun Anda belum dihubungkan dengan data siswa. Hubungi Waka Kurikulum.');
            }

            $studentId = (int) $request->input('student_id', count($childIds) === 1 ? $childIds[0] : 0);
            if (!in_array($studentId, $childIds, true)) {
                return back()->withInput()->with('error', 'Pilih siswa (anak) yang valid.');
            }
        }

        $docPath = $request->file('document')->store('leaves', 'public');

        LeaveRequest::create([
            'student_id'    => $studentId,
            'applicant_id'  => $user->id,
            'type'          => $request->type,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'reason'        => $request->reason,
            'document_path' => $docPath,
            'status'        => 'pending',
        ]);

        return back()->with('success', 'Pengajuan izin berhasil dikirim, menunggu verifikasi.');
    }

    // Dokumen/surat izin dilayani lewat aplikasi, hanya untuk yang berhak melihat pengajuan tsb
    public function document($id)
    {
        $leave = LeaveRequest::visibleTo(auth()->user())->findOrFail($id);

        if (!$leave->document_path || !Storage::disk('public')->exists($leave->document_path)) {
            abort(404, 'File dokumen tidak ditemukan.');
        }

        return Storage::disk('public')->response($leave->document_path);
    }

    public function verify(Request $request, $id, $status)
    {
        $user = auth()->user();
        if (!$user->isRole(Role::WALI_KELAS, Role::GURU_BK)) {
            abort(403, 'Hanya Wali Kelas atau Guru BK yang dapat memverifikasi izin.');
        }

        if (!in_array($status, ['approved', 'rejected', 'revisi'])) {
            abort(400, 'Status tidak valid.');
        }

        // visibleTo: Wali Kelas hanya bisa memverifikasi siswa kelas perwaliannya
        $leave = LeaveRequest::visibleTo($user)->findOrFail($id);

        if (!in_array($leave->status_approval, ['pending', 'revisi'])) {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        // Alasan wajib diisi saat menolak atau meminta revisi; dilihat oleh siswa/orang tua
        $note = null;
        if (in_array($status, ['rejected', 'revisi'], true)) {
            $note = trim((string) $request->input('alasan'));
            if (mb_strlen($note) < 5) {
                return back()->with('error', 'Alasan ' . ($status === 'rejected' ? 'penolakan' : 'revisi') . ' wajib diisi (minimal 5 karakter).');
            }
            $note = mb_substr($note, 0, 500);
        }

        $leave->status_approval = $status;
        $leave->verified_by = $user->id;
        $leave->verification_note = $note;
        $leave->save();

        return back()->with('success', 'Status surat izin berhasil diperbarui.');
    }
}
