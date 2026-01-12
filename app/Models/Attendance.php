<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'classroom_id',
        'subject_id',
        'teacher_id',
        'date',
        'status',
        'notes',
        'notification_sent',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'notification_sent' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function shouldNotifyParent(): bool
    {
        return in_array($this->status, ['A', 'S']) && !$this->notification_sent;
    }

    public static function getStatusLabel(string $status): string
    {
        return match($status) {
            'H' => 'Hadir',
            'I' => 'Izin',
            'S' => 'Sakit',
            'A' => 'Alpha',
            default => 'Unknown',
        };
    }
}