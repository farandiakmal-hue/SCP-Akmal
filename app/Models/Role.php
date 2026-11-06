<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $guarded = [];

    const WAKA = 1;
    const GURU = 2;
    const WALI_KELAS = 3;
    const GURU_BK = 4;
    const SISWA = 5;
    const ORTU = 6;

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
