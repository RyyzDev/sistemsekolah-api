<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'grade_level',
        'kkm',
        'grade_weights',
    ];

    protected function casts(): array
    {
        return [
            'kkm' => 'decimal:2',
            'grade_weights' => 'array',
        ];
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_teacher')
            ->withPivot('teacher_id')
            ->withTimestamps();
    }

    public function getGradeWeight(string $componentCode): int
    {
        if (empty($this->grade_weights)) {
            $component = GradeComponent::where('code', $componentCode)->first();
            return $component ? $component->default_weight : 25;
        }
        
        return $this->grade_weights[$componentCode] ?? 25;
    }
}