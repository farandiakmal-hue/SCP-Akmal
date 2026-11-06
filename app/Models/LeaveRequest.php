<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $guarded = [];

    // Kolom di database bernama status_approval (sesuai Blueprint).
    // Accessor/mutator ini menjaga agar view yang memakai $r->status tetap jalan.
    public function getStatusAttribute()
    {
        return $this->attributes['status_approval'] ?? null;
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['status_approval'] = $value;
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function applicant()
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeApprovedOnDate($query, string $date)
    {
        return $query->where('status_approval', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }

    // Batasi data sesuai hak akses: Wali Kelas hanya kelas perwaliannya,
    // Siswa/Ortu hanya anaknya sendiri, Waka & Guru BK semuanya.
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isRole(Role::WAKA, Role::GURU_BK)) {
            return $query;
        }

        if ($user->isRole(Role::WALI_KELAS)) {
            if (!$user->class_id) {
                return $query->whereRaw('1 = 0');
            }
            return $query->whereHas('student', fn ($q) => $q->where('class_id', $user->class_id));
        }

        if ($user->isPortalUser()) {
            return $query->whereIn('student_id', $user->portalStudentIds());
        }

        return $query->whereRaw('1 = 0');
    }
}
