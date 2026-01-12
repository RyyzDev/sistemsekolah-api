<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * Kolom yang boleh diisi secara massal (Mass Assignment).
     */
    protected $fillable = [
        'google_id',
        'name',
        'email',
        'password',
        'role',
        'contact',
        'email_verified_at',
        'is_active'
    ];

    /**
     * Kolom yang harus disembunyikan saat output JSON (API).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting tipe data kolom.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    

      public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function parent(): HasOne
    {
        return $this->hasOne(ParentModel::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('Admin');
    }

    public function isTeacher(): bool
    {
        return $this->hasRole('Teacher');
    }

    public function isStudent(): bool
    {
        return $this->hasRole('Student');
    }

    public function isParent(): bool
    {
        return $this->hasRole('Parent');
    }
}