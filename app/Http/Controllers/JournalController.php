<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class JournalController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (!$user->isRole(Role::WAKA, Role::GURU, Role::WALI_KELAS, Role::GURU_BK)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $query = Journal::with(['schedule.class', 'schedule.teacher']);

        if ($user->isRole(Role::GURU)) {
            $query->whereHas('schedule', fn ($q) => $q->where('teacher_id', $user->id));
        }

        // Wali Kelas hanya melihat jurnal kelas perwaliannya
        if ($user->isRole(Role::WALI_KELAS)) {
            $classId = $user->class_id;
            $query->whereHas('schedule', fn ($q) => $classId ? $q->where('class_id', $classId) : $q->whereRaw('1 = 0'));
        }

                $journals = $query->orderByDesc('date')->orderByDesc('id')->paginate(15);
        return view('journal.index', compact('journals'));
    }

    // Detail jurnal beserta presensi seluruh siswa pada pertemuan tersebut
    public function show($id)
    {
        $journal = Journal::with(['schedule.class', 'schedule.teacher', 'attendances.student'])->findOrFail($id);
        $this->authorizeJournalAccess($journal);

        $attendances = $journal->attendances
            ->sortBy(fn ($a) => $a->student->fullname ?? '')
            ->values();

        return view('journal.show', compact('journal', 'attendances'));
    }

    // Menampilkan foto bukti langsung lewat aplikasi (tanpa perlu php artisan storage:link)
    public function photo($id)
    {
        $journal = Journal::with('schedule')->findOrFail($id);
        $this->authorizeJournalAccess($journal);

        if (!$journal->photo_path || !Storage::disk('public')->exists($journal->photo_path)) {
            abort(404, 'File foto tidak ditemukan.');
        }

        return Storage::disk('public')->response($journal->photo_path);
    }

    private function authorizeJournalAccess(Journal $journal): void
    {
        $user = auth()->user();

        if (!$user->isRole(Role::WAKA, Role::GURU, Role::WALI_KELAS, Role::GURU_BK)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
        if ($user->isRole(Role::GURU) && $journal->schedule->teacher_id != $user->id) {
            abort(403, 'Jurnal ini bukan milik Anda.');
        }
        if ($user->isRole(Role::WALI_KELAS) && $journal->schedule->class_id != $user->class_id) {
            abort(403, 'Jurnal ini bukan kelas perwalian Anda.');
        }
    }

    public function create($schedule_id)
    {
        $schedule = Schedule::with('class')->findOrFail($schedule_id);

        if ($schedule->teacher_id != auth()->id()) {
            abort(403, 'Jadwal ini bukan milik Anda.');
        }

        if ($error = $this->scheduleNotOpenError($schedule)) {
            return redirect()->route('schedule.index')->with('error', $error);
        }

        $students = User::where('role_id', Role::SISWA)
            ->where('class_id', $schedule->class_id)
            ->orderBy('fullname')
            ->get();

        $today = $this->today();

        $approvedLeaveStudentIds = LeaveRequest::approvedOnDate($today)
            ->whereIn('student_id', $students->pluck('id'))
            ->pluck('student_id', 'student_id')
            ->toArray();

        $alreadyFilled = Journal::where('schedule_id', $schedule->id)
            ->where('date', $today)
            ->exists();

        return view('journal.create', compact('schedule', 'students', 'approvedLeaveStudentIds', 'alreadyFilled'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'schedule_id'        => 'required|exists:schedules,id',
            'topic_description'  => 'required|string',
            'photo'              => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'attendance'         => 'nullable|array',
            'attendance.*'       => 'in:hadir,sakit,izin,izin_tengah_jam,dispensasi,alpha',
        ]);

        $schedule = Schedule::findOrFail($request->schedule_id);
        if ($schedule->teacher_id != auth()->id()) {
            abort(403, 'Jadwal ini bukan milik Anda.');
        }

        if ($error = $this->scheduleNotOpenError($schedule)) {
            return redirect()->route('schedule.index')->with('error', $error);
        }

        $today = $this->today();

        $alreadyFilled = Journal::where('schedule_id', $schedule->id)->where('date', $today)->exists();
        if ($alreadyFilled) {
            return back()->with('error', 'Jurnal untuk jadwal ini hari ini sudah pernah diisi.');
        }

        // Hanya siswa di kelas jadwal ini yang boleh dipresensi (ID lain dari form diabaikan)
        $students = User::where('role_id', Role::SISWA)
            ->where('class_id', $schedule->class_id)
            ->get();

        if ($students->isEmpty()) {
            return back()->withInput()->with('error', 'Jurnal tidak disimpan: belum ada siswa terdaftar di kelas ini, sehingga presensi tidak bisa dicatat. Tambahkan siswa di menu Master atau pilih kelas lain.');
        }

        // d. Sync Logic: izin yang sudah 'approved' dan berlaku hari ini otomatis jadi presensi
        $approvedLeaves = LeaveRequest::approvedOnDate($today)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $photoPath = $request->file('photo')->store('journals', 'public');

        DB::transaction(function () use ($schedule, $today, $request, $photoPath, $students, $approvedLeaves) {
            $journal = Journal::create([
                'schedule_id'       => $schedule->id,
                'date'              => $today,
                'topic_description' => $request->topic_description,
                'photo_path'        => $photoPath,
            ]);

            foreach ($students as $student) {
                if (isset($approvedLeaves[$student->id])) {
                    $status = $approvedLeaves[$student->id]->type;
                    $source = 'sync_izin';
                } else {
                    $status = $request->input('attendance.' . $student->id, 'hadir');
                    $source = 'manual';
                }

                Attendance::create([
                    'journal_id' => $journal->id,
                    'student_id' => $student->id,
                    'status'     => $status,
                    'date'       => $today,
                    'source'     => $source,
                ]);
            }
        });

        return redirect()->route('dashboard')->with('success', 'Jurnal & presensi berhasil disimpan!');
    }
        public function edit($id)
    {
        $journal = Journal::with(['schedule.class', 'attendances.student'])->findOrFail($id);

        if ($error = $this->journalNotEditableError($journal)) {
            return redirect()->route('journal.show', $journal->id)->with('error', $error);
        }

        $attendances = $journal->attendances
            ->sortBy(fn ($a) => $a->student->fullname ?? '')
            ->values();

        return view('journal.edit', compact('journal', 'attendances'));
    }

    public function update(Request $request, $id)
    {
        $journal = Journal::with('schedule')->findOrFail($id);

        if ($error = $this->journalNotEditableError($journal)) {
            return redirect()->route('journal.show', $journal->id)->with('error', $error);
        }

        $request->validate([
            'topic_description' => 'required|string',
            'photo'             => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'attendance'        => 'nullable|array',
            'attendance.*'      => 'in:hadir,sakit,izin,izin_tengah_jam,dispensasi,alpha',
        ]);

        $oldPhoto = $journal->photo_path;

        DB::transaction(function () use ($request, $journal) {
            $data = ['topic_description' => $request->topic_description];

            if ($request->hasFile('photo')) {
                $data['photo_path'] = $request->file('photo')->store('journals', 'public');
            }

            $journal->update($data);

            // Hanya presensi manual yang boleh diubah (hasil sinkronisasi izin tetap)
            foreach ($request->input('attendance', []) as $studentId => $status) {
                Attendance::where('journal_id', $journal->id)
                    ->where('student_id', $studentId)
                    ->where('source', 'manual')
                    ->update(['status' => $status]);
            }
        });

        if ($request->hasFile('photo') && $oldPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return redirect()->route('journal.show', $journal->id)->with('success', 'Jurnal berhasil diperbarui.');
    }

    // Jurnal hanya bisa diedit oleh guru pemiliknya dan hanya pada tanggal jurnal itu dibuat
    private function journalNotEditableError(Journal $journal): ?string
    {
        $user = auth()->user();

        if (!$user->isRole(Role::GURU) || $journal->schedule->teacher_id != $user->id) {
            return 'Hanya guru pemilik jurnal yang dapat mengedit jurnal ini.';
        }

        if (Carbon::parse($journal->date)->toDateString() !== $this->today()) {
            return 'Jurnal hanya dapat diedit pada hari yang sama dengan tanggal jurnal.';
        }

        return null;
    }

    private function today(): string
    {
        return Carbon::now('Asia/Jakarta')->toDateString();
    }

    // Jurnal hanya bisa dibuat untuk jadwal pada putaran aktif dan hari yang sesuai
    private function scheduleNotOpenError(Schedule $schedule): ?string
    {
        $config = SystemConfiguration::first();
        $cycle = $config->active_cycle ?? 'Putaran 1';

        if ($schedule->cycle_type !== $cycle) {
            return 'Jadwal ini milik ' . $schedule->cycle_type . ', sedangkan putaran aktif saat ini adalah ' . $cycle . '.';
        }

        $todayDay = Schedule::dayNameFor(Carbon::now('Asia/Jakarta'));
        if (strtolower($schedule->day_of_week) !== $todayDay) {
            return 'Jurnal hanya dapat diisi pada hari jadwalnya (' . ucfirst($schedule->day_of_week) . ').';
        }

        return null;
    }
}
