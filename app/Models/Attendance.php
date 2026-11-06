<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_id',
        'student_id',
        'status',
        'date',
        'note',
        'source', 
    ];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
