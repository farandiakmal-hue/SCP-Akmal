<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Support\JamSesi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    private const DAYS = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];

    public function index(Request $request)
    {
        $userId = auth()->id();
        $userRole = auth()->user()->role_id ?? null;

        $config = SystemConfiguration::first();
        $cycle = $config->active_cycle ?? 'Putaran 1';

        $classes = Classes::orderBy('class_name')->get();
        $rooms = Room::orderBy('room_name')->get();

        $query = Schedule::with(['class', 'room', 'teacher'])->where('cycle_type', $cycle);

        if ($userRole == Role::GURU) {
            $query->where('teacher_id', $userId);
        }

        // Pencarian jadwal (mengikuti putaran aktif): mapel/nama guru, hari, kelas
        $query->when($request->filled('day') && in_array($request->day, self::DAYS, true),
            fn ($q) => $q->where('day_of_week', $request->day));
        $query->when($request->filled('class_id'),
            fn ($q) => $q->where('class_id', (int) $request->class_id));
        $query->when($request->filled('q'), function ($q) use ($request) {
            $term = '%' . $request->q . '%';
            $q->where(function ($w) use ($term) {
                $w->where('subject', 'like', $term)
                    ->orWhereHas('teacher', fn ($t) => $t->where('fullname', 'like', $term));
            });
        });

        // Urut rapi: hari (Senin..Sabtu) lalu jam mulai
        $dayOrder = array_flip(self::DAYS);
        $schedules = $query->get()
            ->sortBy(fn ($s) => sprintf('%d-%s', $dayOrder[strtolower($s->day_of_week)] ?? 9, $s->start_time))
            ->values();

        return view('schedule.index', compact('schedules', 'config', 'classes', 'rooms'));
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

        $cycle = $config->active_cycle ?? 'Putaran 1';
        $result = $this->buildData($request, $cycle, auth()->id(), null);
        if ($result instanceof RedirectResponse) {
            return $result;
        }

        Schedule::create($result + [
            'teacher_id'    => auth()->id(),
            'cycle_type'    => $cycle,
            'academic_year' => $config->current_academic_year ?? '2025/2026',
            'semester'      => $config->current_semester ?? '1',
        ]);

        return redirect()->route('schedule.index')->with('success', 'Jadwal berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $schedule = $this->ownedEditable($id);
        if ($schedule instanceof RedirectResponse) {
            return $schedule;
        }

        // Cocokkan jam jadwal dengan nomor sesi (bila sesuai batas sesi)
        $sesiMulai = $sesiSelesai = null;
        foreach (JamSesi::all() as $s) {
            if ($s['start'] === substr($schedule->start_time, 0, 5)) $sesiMulai = $s['no'];
            if ($s['end'] === substr($schedule->end_time, 0, 5)) $sesiSelesai = $s['no'];
        }

        $classes = Classes::orderBy('class_name')->get();
        $rooms = Room::orderBy('room_name')->get();

        return view('schedule.edit', compact('schedule', 'classes', 'rooms', 'sesiMulai', 'sesiSelesai'));
    }

    public function update(Request $request, $id)
    {
        $schedule = $this->ownedEditable($id);
        if ($schedule instanceof RedirectResponse) {
            return $schedule;
        }

        $result = $this->buildData($request, $schedule->cycle_type, $schedule->teacher_id, $schedule->id);
        if ($result instanceof RedirectResponse) {
            return $result;
        }

        $schedule->update($result);

        return redirect()->route('schedule.index')->with('success', 'Jadwal berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $config = SystemConfiguration::first();
        if ($config && $config->is_schedule_locked) {
            return back()->with('error', 'Jadwal sedang dikunci, tidak bisa menghapus jadwal.');
        }

        $schedule = Schedule::findOrFail($id);

        if ($schedule->teacher_id != auth()->id() && !auth()->user()->isRole(Role::WAKA)) {
            abort(403, 'Anda tidak berhak menghapus jadwal ini.');
        }

        $schedule->delete();
        return back()->with('success', 'Jadwal berhasil dihapus.');
    }

    // Jadwal milik guru yang login dan masih boleh diubah (belum dikunci)
    private function ownedEditable($id)
    {
        if (!auth()->user()->isRole(Role::GURU)) {
            abort(403, 'Hanya Guru yang dapat mengubah jadwal mengajar.');
        }

        $schedule = Schedule::findOrFail($id);
        if ($schedule->teacher_id != auth()->id()) {
            abort(403, 'Anda tidak berhak mengubah jadwal ini.');
        }

        $config = SystemConfiguration::first();
        if ($config && $config->is_schedule_locked) {
            return redirect()->route('schedule.index')
                ->with('error', 'Jadwal sedang dikunci oleh Waka Kurikulum. Anda tidak bisa mengubah jadwal.');
        }

        return $schedule;
    }

    /*
     | Validasi input + jam dari sesi + istirahat + anti-bentrok (Conflict Prevention).
     | Mengembalikan array data jadwal, atau RedirectResponse berisi pesan error.
     */
    private function buildData(Request $request, string $cycle, int $teacherId, ?int $ignoreId)
    {
        $sesiKeys = implode(',', array_keys(JamSesi::all()));

        $request->validate([
            'class_id'     => 'required|exists:classes,id',
            'room_id'      => 'required|exists:rooms,id',
            'subject'      => 'required|string|max:150',
            'day_of_week'  => 'required|in:' . implode(',', self::DAYS),
            'sesi_mulai'   => 'required|integer|in:' . $sesiKeys,
            'sesi_selesai' => 'required|integer|in:' . $sesiKeys . '|gte:sesi_mulai',
        ], [
            'sesi_selesai.gte' => 'Sesi selesai tidak boleh sebelum sesi mulai.',
        ]);

        $day = strtolower($request->day_of_week);

        // Jam jadwal ditentukan dari sesi: mulai = awal sesi mulai, selesai = akhir sesi selesai
        $start = JamSesi::find((int) $request->sesi_mulai)['start'];
        $end = JamSesi::find((int) $request->sesi_selesai)['end'];

        // Waktu istirahat: jadwal tidak boleh melewati istirahat (Start_A < End_B AND End_A > Start_B)
        foreach (JamSesi::breaks() as [$label, $breakStart, $breakEnd]) {
            if ($start < $breakEnd && $end > $breakStart) {
                return back()->withInput()->with('error', 'Jadwal (' . $start . ' - ' . $end . ') melewati ' . $label . ' (' . $breakStart . ' - ' . $breakEnd . '). Pilih sesi sebelum atau sesudah istirahat.');
            }
        }

        // Conflict Prevention: irisan waktu pada ruang, kelas, atau guru yang sama (putaran & hari sama)
        $conflict = Schedule::where('cycle_type', $cycle)
            ->where('day_of_week', $day)
            ->where(function ($q) use ($request, $teacherId) {
                $q->where('room_id', $request->room_id)
                    ->orWhere('class_id', $request->class_id)
                    ->orWhere('teacher_id', $teacherId);
            })
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->first();

        if ($conflict) {
            $sebab = [];
            if ($conflict->room_id == $request->room_id) $sebab[] = 'ruangan';
            if ($conflict->class_id == $request->class_id) $sebab[] = 'kelas';
            if ($conflict->teacher_id == $teacherId) $sebab[] = 'guru (Anda)';

            return back()->withInput()->with('error',
                'Jadwal bentrok dengan "' . $conflict->subject . '" (' .
                substr($conflict->start_time, 0, 5) . '-' . substr($conflict->end_time, 0, 5) .
                ') pada ' . implode(' & ', $sebab) . ' yang sama.'
            );
        }

        return [
            'class_id'    => $request->class_id,
            'room_id'     => $request->room_id,
            'subject'     => $request->subject,
            'day_of_week' => $day,
            'start_time'  => $start,
            'end_time'    => $end,
        ];
    }
}
