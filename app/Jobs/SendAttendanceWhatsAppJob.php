<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAttendanceWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 120, 300];

    public function __construct(
        private int $attendanceId
    ) {}

    public function handle(WhatsAppService $whatsAppService): void
    {
        $attendance = Attendance::with(['student.user', 'student.parents'])->find($this->attendanceId);

        if (!$attendance || !$attendance->shouldNotifyParent()) {
            return;
        }

        $studentName = $attendance->student->user->name;
        $status = $attendance->status;
        $date = $attendance->date->format('d/m/Y');

        $sent = false;

        foreach ($attendance->student->parents as $parent) {
            if ($parent->phone) {
                $result = $whatsAppService->sendAttendanceAlert(
                    $parent->phone,
                    $studentName,
                    $status,
                    $date
                );

                if ($result) {
                    $sent = true;
                    Log::info('Attendance alert sent to parent', [
                        'attendance_id' => $this->attendanceId,
                        'parent_id' => $parent->id,
                        'phone' => $parent->phone,
                    ]);
                }
            }
        }

        if ($sent) {
            $attendance->update(['notification_sent' => true]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendAttendanceWhatsAppJob failed', [
            'attendance_id' => $this->attendanceId,
            'error' => $exception->getMessage(),
        ]);
    }
}