<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $config = SystemConfiguration::first() ?? SystemConfiguration::create(['id' => 1]);
        $user = auth()->user();
        $alerts = collect();
        $endingSoon = collect(); // default kosong supaya view tidak error
        $pendingLeaveCount = 0;

        $now = Carbon::now('Asia/Jakarta');
        $todayDate = $now->format('Y-m-d');
        $todayDay = Schedule::dayNameFor($now);
        $cycle = $config->active_cycle ?? 'Putaran 1';

        if ($user->isRole(Role::GURU)) {
            $schedules = Schedule::with('class')
                ->where('teacher_id', $user->id)
                ->where('cycle_type', $cycle)
                ->whereRaw('LOWER(day_of_week) = ?', [$todayDay])
                ->get();

            // e. Dashboard Alert Reminder: jadwal guru ini (putaran aktif) yang sudah lewat >30 menit tapi belum ada jurnal
            $alerts = $this->getLateJournalAlerts($schedules, $now, $todayDate);

            // Jadwal yang akan berakhir dalam 15 menit ke depan
            $endingSoon = $this->getEndingSoonSchedules($schedules, $now, $todayDate);
        } elseif ($user->isRole(Role::WAKA)) {
            $schedules = Schedule::with(['class', 'teacher'])
                ->where('cycle_type', $cycle)
                ->whereRaw('LOWER(day_of_week) = ?', [$todayDay])
                ->get();

            // a. Waka menerima rekap SEMUA guru yang belum mengisi jurnal hari ini (putaran aktif)
            $alerts = $this->getLateJournalAlerts($schedules, $now, $todayDate);

            // Jadwal (semua guru) yang akan berakhir dalam 15 menit ke depan
            $endingSoon = $this->getEndingSoonSchedules($schedules, $now, $todayDate);
        } elseif ($user->isRole(Role::WALI_KELAS, Role::GURU_BK)) {
            // Tim Kesiswaan: hanya izin yang menjadi kewenangannya (Wali Kelas = kelas perwalian)
            $pendingLeaveCount = LeaveRequest::visibleTo($user)
                ->where('status_approval', 'pending')
                ->count();
        }

        return view('dashboard', compact('config', 'alerts', 'endingSoon', 'pendingLeaveCount'));
    }

    private function getLateJournalAlerts($schedules, Carbon $now, string $todayDate)
    {
        return $schedules->filter(function ($s) use ($now, $todayDate) {
            $endTime = Carbon::parse($todayDate . ' ' . $s->end_time, 'Asia/Jakarta');
            $deadline = (clone $endTime)->addMinutes(30);

            $hasJournal = Journal::where('schedule_id', $s->id)
                ->where('date', $todayDate)
                ->exists();

            return $now->greaterThan($deadline) && !$hasJournal;
        })->values();
    }

    /**
     * Jadwal hari ini yang akan berakhir dalam $minutes menit ke depan.
     */
    private function getEndingSoonSchedules($schedules, Carbon $now, string $todayDate, int $minutes = 15)
    {
        return $schedules->filter(function ($s) use ($now, $todayDate, $minutes) {
            $endTime = Carbon::parse($todayDate . ' ' . $s->end_time, 'Asia/Jakarta');

            return $now->lessThanOrEqualTo($endTime)
                && $now->diffInMinutes($endTime) <= $minutes;
        })->values();
    }

    public function updateConfig(Request $request)
    {
        $user = auth()->user();
        if (!$user->isRole(Role::WAKA)) {
            abort(403, 'Hanya Waka Kurikulum yang dapat mengubah pengaturan siklus/kunci jadwal.');
        }

        $request->validate([
            'active_cycle' => 'nullable|in:Putaran 1,Putaran 2',
        ]);

        $config = SystemConfiguration::firstOrCreate(['id' => 1]);

        // b. Sistem Blok & Pergantian Putaran (Cycle Toggle)
        if ($request->filled('active_cycle')) {
            $config->active_cycle = $request->active_cycle;
        }

        // c. Mekanisme Kunci Jadwal (Lock Schedule)
        if ($request->has('toggle_lock')) {
            $config->is_schedule_locked = !$config->is_schedule_locked;
        }

        $config->updated_by = $user->id;
        $config->save();

        return back()->with('success', 'Pengaturan sistem berhasil diperbarui!');
    }
}