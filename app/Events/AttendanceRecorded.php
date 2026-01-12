<?php

namespace App\Events;

use App\Models\Attendance;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceRecorded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Attendance $attendance
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('attendance.' . $this->attendance->student_id),
            new Channel('classroom.' . $this->attendance->classroom_id),
        ];
    }


    public function broadcastAs(): string
    {
        return 'attendance.recorded';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->attendance->id,
            'student_id' => $this->attendance->student_id,
            'student_name' => $this->attendance->student->user->name,
            'status' => $this->attendance->status,
            'status_label' => Attendance::getStatusLabel($this->attendance->status),
            'date' => $this->attendance->date->format('Y-m-d'),
            'classroom' => $this->attendance->classroom->name,
        ];
    }
}