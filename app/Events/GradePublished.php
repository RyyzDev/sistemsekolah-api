<?php

namespace App\Events;

use App\Models\Grade;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GradePublished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Grade $grade
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('student.' . $this->grade->student_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'grade.published';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->grade->id,
            'student_id' => $this->grade->student_id,
            'subject_name' => $this->grade->subject->name,
            'component_name' => $this->grade->gradeComponent->name,
            'score' => $this->grade->score,
            'created_at' => $this->grade->created_at->toIso8601String(),
        ];
    }
}