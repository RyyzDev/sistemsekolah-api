<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'semester', 'subject', 'score', 'grade_type', 'notes'
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function scopeRapor($query)
    {
        return $query->where('grade_type', 'rapor');
    }

    public function scopeUjianSekolah($query)
    {
        return $query->where('grade_type', 'us');
    }

    public function scopeUjianNasional($query)
    {
        return $query->where('grade_type', 'un');
    }
}