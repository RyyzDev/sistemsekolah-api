<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'achievement_name', 'achievement_type', 'level',
        'rank', 'organizer', 'achievement_date', 'year', 'description',
        'certificate_file', 'points'
    ];

    protected $casts = [
        'achievement_date' => 'date',
        'year' => 'integer',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($achievement) {
            if (!$achievement->points) {
                $achievement->points = $achievement->calculatePoints();
            }
        });
    }

    public function calculatePoints()
    {
        $levelPoints = [
            'internasional' => 100,
            'nasional' => 80,
            'provinsi' => 60,
            'kabupaten' => 40,
            'kecamatan' => 20,
            'sekolah' => 10,
        ];

        $rankPoints = [
            'juara_1' => 1.0,
            'juara_2' => 0.8,
            'juara_3' => 0.6,
            'finalis' => 0.4,
            'peserta' => 0.2,
        ];

        $basePoints = $levelPoints[$this->level] ?? 0;
        $multiplier = $rankPoints[$this->rank] ?? 0;

        return $basePoints * $multiplier;
    }
}