<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
    'username',
    'password',
    'fullname',
    'role_id',
    'class_id',
    'parent_id',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    // Helper cek role, dipakai di controller & view supaya tidak menulis angka role_id berulang
    public function isRole(...$roleIds): bool
    {
        return in_array($this->role_id, $roleIds);
    }

    // Siswa -> Orang Tua, dan sebaliknya (users.parent_id pada Blueprint)
    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    // Siswa & Orang Tua memakai portal yang sama
    public function isPortalUser(): bool
    {
        return $this->isRole(Role::SISWA, Role::ORTU);
    }

    // ID siswa yang datanya boleh dilihat akun portal ini
    public function portalStudentIds(): array
    {
        if ($this->isRole(Role::SISWA)) {
            return [(int) $this->id];
        }
        if ($this->isRole(Role::ORTU)) {
            return $this->children()->pluck('id')->map(fn ($i) => (int) $i)->all();
        }
        return [];
    }

    public function roleLabel(): string
    {
        return $this->role->role_name ?? '-';
    }
}
