<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Nama hari (sesuai enum day_of_week) untuk sebuah tanggal
    public static function dayNameFor(\Carbon\Carbon $date): string
    {
        return ['minggu', 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'][$date->dayOfWeek];
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function journals()
    {
        return $this->hasMany(Journal::class);
    }
}