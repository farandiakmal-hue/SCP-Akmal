<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\Room;
use App\Models\Role;
use App\Models\Schedule;
use App\Support\JamSesi;
use App\Models\SystemConfiguration;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function index(Request $request)
{
    $userId = auth()->id();
    $userRole = auth()->user()->role_id ?? null;

    $config = SystemConfiguration::first();
    $cycle = $config->active_cycle ?? 'Putaran 1';

    $classes = Classes::all();
    $rooms = Room::all();

    // Selain Guru: default hari ini (Minggu -> semua hari), bisa difilter lewat ?hari=
    $hariIni = Schedule::dayNameFor(Carbon::now('Asia/Jakarta'));
    $hari = $request->query('hari', $hariIni === 'minggu' ? 'semua' : $hariIni);

    $query = Schedule::where('cycle_type', $cycle);

    if ($userRole == Role::GURU) {
        $query->with(['class', 'room'])->where('teacher_id', $userId);
    } else {
        $query->with(['class', 'room', 'teacher']);
        if ($hari !== 'semua') {
            $query->where('day_of_week', $hari);
        }
    }

    $schedules = $query
        ->orderByRaw("FIELD(day_of_week, 'senin','selasa','rabu','kamis','jumat','sabtu')")
        ->orderBy('start_time')
        ->get();

    return view('schedule.index', compact('schedules', 'config', 'classes', 'rooms', 'hari'));
}
    public function store(Request $request)
    {
        // Input jadwal hanya hak Guru (sesuai matriks fitur)
        if (!auth()->user()->isRole(Role::GURU)) {
            abort(403, 'Hanya Guru yang dapat menginput jadwal mengajar.');
        }

        $config = SystemConfiguration::first() ?? SystemConfiguration::create(['id' => 1]);

        if ($config->is_schedule_locked) {
            return back()->with('error', 'Jadwal sedang dikunci oleh Waka Kurikulum. Anda tidak bisa menambah jadwal baru.');
        }

        $sesiKeys = implode(',', array_keys(JamSesi::all()));

        $request->validate([
            'class_id'      => 'required|exists:classes,id',
            'room_id'       => 'required|exists:rooms,id',
            'subject'       => 'required|string|max:150',
            'day_of_week'   => 'required|in:senin,selasa,rabu,kamis,jumat,sabtu',
            'sesi_mulai'    => 'required|integer|in:' . $sesiKeys,
            'sesi_selesai'  => 'required|integer|in:' . $sesiKeys . '|gte:sesi_mulai',
        ], [
            'sesi_selesai.gte' => 'Sesi selesai tidak boleh sebelum sesi mulai.',
        ]);

        $teacherId = auth()->id();
        $day = strtolower($request->day_of_week);
        $cycle = $config->active_cycle ?? 'Putaran 1';

        // Jam jadwal ditentukan dari sesi: mulai = awal sesi mulai, selesai = akhir sesi selesai
        $start = JamSesi::find((int) $request->sesi_mulai)['start'];
        $end = JamSesi::find((int) $request->sesi_selesai)['end'];

        // Waktu istirahat: jadwal tidak boleh melewati istirahat (Start_A < End_B AND End_A > Start_B)
        foreach (JamSesi::breaks() as [$label, $breakStart, $breakEnd]) {
            if ($start < $breakEnd && $end > $breakStart) {
                return back()->withInput()->with('error', 'Jadwal (' . $start . ' - ' . $end . ') melewati ' . $label . ' (' . $breakStart . ' - ' . $breakEnd . '). Pilih sesi sebelum atau sesudah istirahat.');
            }
        }

        $conflict = Schedule::where('cycle_type', $cycle)
            ->where('day_of_week', $day)
            ->where(function ($q) use ($request, $teacherId) {
                $q->where('room_id', $request->room_id)
                    ->orWhere('class_id', $request->class_id)
                    ->orWhere('teacher_id', $teacherId);
            })
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->first();

       if ($conflict) {
    $sebab = [];
    if ($conflict->room_id == $request->room_id) $sebab[] = 'ruangan';
    if ($conflict->class_id == $request->class_id) $sebab[] = 'kelas';
    if ($conflict->teacher_id == $teacherId) $sebab[] = 'guru (Anda)';

    $namaGuru = $conflict->teacher_id == $teacherId
        ? 'Anda'
        : ($conflict->teacher->fullname ?? 'guru lain');

    return back()->withInput()->with('error',
        'Jadwal bentrok dengan "' . $conflict->subject . '" milik ' . $namaGuru . ' (' .
        substr($conflict->start_time, 0, 5) . '-' . substr($conflict->end_time, 0, 5) .
        ') pada ' . implode(' & ', $sebab) . ' yang sama.'
    );
}

        Schedule::create([
            'teacher_id'    => $teacherId,
            'class_id'      => $request->class_id,
            'room_id'       => $request->room_id,
            'subject'       => $request->subject,
            'day_of_week'   => $day,
            'start_time'    => $start,
            'end_time'      => $end,
            'cycle_type'    => $cycle,
            'academic_year' => $config->current_academic_year ?? '2025/2026',
            'semester'      => $config->current_semester ?? '1',
        ]);

        return redirect('/schedule')->with('success', 'Jadwal berhasil ditambahkan!');
    }

    public function destroy($id)
{
    $config = SystemConfiguration::first() ?? SystemConfiguration::create(['id' => 1]);

    if ($config->is_schedule_locked) {
        return back()->with('error', 'Jadwal sedang dikunci, tidak bisa menghapus jadwal.');
    }

    $schedule = Schedule::findOrFail($id);

   if ($schedule->teacher_id != auth()->id() && !auth()->user()->isRole(Role::WAKA)) {
        abort(403, 'Anda tidak berhak menghapus jadwal ini.');
    }

    $schedule->delete();
    return back()->with('success', 'Jadwal berhasil dihapus.');
}
}
