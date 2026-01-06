<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentModel extends Model
{
    use HasFactory;

    protected $table = 'parents';

    protected $fillable = [
        'student_id', 'parent_type', 'full_name', 'nik', 'birth_place',
        'birth_date', 'religion', 'citizenship', 'education', 'occupation',
        'occupation_category', 'monthly_income', 'phone_number', 'mobile_number',
        'email', 'address', 'rt', 'rw', 'kelurahan', 'kecamatan',
        'kabupaten_kota', 'province', 'postal_code', 'living_status', 'is_guardian'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'monthly_income' => 'decimal:2',
        'is_guardian' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function getAgeAttribute()
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }

    public function getFullAddressAttribute()
    {
        if (!$this->address) {
            return $this->student->full_address;
        }
        
        return "{$this->address}, RT {$this->rt}/RW {$this->rw}, {$this->kelurahan}, {$this->kecamatan}, {$this->kabupaten_kota}, {$this->province} {$this->postal_code}";
    }
}