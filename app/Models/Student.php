<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory, SoftDeletes;
 
    protected $fillable = [
        'user_id', 
        'registration_number', 
        'registration_type', 
        'registration_path',
        'registration_date', 
        'status', 
        'full_name', 
        'nickname', 
        'nisn', 
        'nik',
        'no_kk', 
        'no_akta_lahir', 
        'gender', 
        'birth_place', 
        'birth_date',
        'religion', 
        'citizenship', 
        'nationality', 
        'address', 
        'rt', 
        'rw',
        'dusun', 
        'kelurahan', 
        'kecamatan', 
        'kabupaten_kota', 
        'province',
        'postal_code', 
        'latitude', 
        'longitude', 
        'residence_type', 
        'transportation',
        'phone_number', 
        'mobile_number', 
        'email', 
        'height', 
        'weight', 
        'blood_type',
        'special_needs', 
        'special_needs_description', 
        'disease_history',
        'child_number', 
        'total_siblings', 
        'hobby', 
        'ambition',
        'kps_pkh_recipient', 
        'kps_pkh_number', 
        'kip_recipient', 
        'kip_number',
        'pip_eligible', 
        'kks_number', 
        'previous_school_name', 
        'previous_school_npsn',
        'previous_school_address', 
        'ijazah_number', 
        'ijazah_date', 
        'skhun_number', 
        'photo'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'registration_date' => 'date',
        'ijazah_date' => 'date',
        'kps_pkh_recipient' => 'boolean',
        'kip_recipient' => 'boolean',
        'pip_eligible' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parents()
    {
        return $this->hasMany(ParentModel::class);
    }

    public function father()
    {
        return $this->hasOne(ParentModel::class)->where('parent_type', 'ayah');
    }

    public function mother()
    {
        return $this->hasOne(ParentModel::class)->where('parent_type', 'ibu');
    }

    public function guardian()
    {
        return $this->hasOne(ParentModel::class)->where('parent_type', 'wali');
    }

    public function achievements()
    {
        return $this->hasMany(Achievement::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'student_classroom')
            ->withPivot('semester_id', 'is_active')
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function getCurrentClassroom(?int $semesterId = null): ?Classroom
    {
        $semesterId = $semesterId ?? Semester::getActive()?->id;
        
        return $this->classrooms()
            ->wherePivot('semester_id', $semesterId)
            ->wherePivot('is_active', true)
            ->first();
    }

    public function getAgeAttribute()
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }

    public function getFullAddressAttribute()
    {
        return "{$this->address}, RT {$this->rt}/RW {$this->rw}, {$this->kelurahan}, {$this->kecamatan}, {$this->kabupaten_kota}, {$this->province} {$this->postal_code}";
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function generateRegistrationNumber()
    {
        $year = date('Y');
        $lastNumber = self::whereYear('created_at', $year)->max('registration_number');
        
        if (!$lastNumber) {
            $number = 1;
        } else {
            $number = intval(substr($lastNumber, -4)) + 1;
        }
        
        return 'REG' . $year . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function isComplete()
    {
        $requiredFields = ['full_name', 'nik', 'no_kk', 'gender', 'birth_place', 'birth_date', 'address', 'kelurahan', 'kecamatan'];
        
        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }
        
        if (!$this->father && !$this->mother) {
            return false;
        }
        
        return true;
    }

    public function calculatePrestasiScore()
    {
        return $this->achievements()->sum('points');
    }


    public function hasPaidRegistrationFee()
    {
        return $this->payments()
            ->where('payment_type', 'registration_fee')
            ->whereIn('status', ['settlement', 'capture'])
            ->exists();
    }

    public function getRegistrationPayment()
    {
        return $this->payments()
            ->where('payment_type', 'registration_fee')
            ->latest()
            ->first();
    }

    public function getTotalPaid()
    {
        return $this->payments()
            ->whereIn('status', ['settlement', 'capture'])
            ->sum('total_amount');
    }
}