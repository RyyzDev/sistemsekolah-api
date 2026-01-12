<?php

namespace App\Http\Controllers\Siakad;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Jobs\SendAttendanceWhatsAppJob;
use App\Events\AttendanceRecorded;
use App\Events\NotificationCreated;
use App\Models\Notification;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::with(['student.user', 'classroom', 'subject', 'teacher.user']);

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $attendances = $query->orderBy('date', 'desc')->paginate($request->per_page ?? 15);
        return response()->json([
            'success' => true,
            'data' => $attendances,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'date' => 'required|date',
            'status' => 'required|in:H,I,S,A',
            'notes' => 'nullable|string',
        ]);

        $exists = Attendance::where('student_id', $validated['student_id'])
            ->where('classroom_id', $validated['classroom_id'])
            ->where('subject_id', $validated['subject_id'])
            ->whereDate('date', $validated['date'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance already recorded for this student, classroom, subject, and date',
            ], 422);
        }

        $attendance = Attendance::create($validated);

        event(new AttendanceRecorded($attendance));

        if ($attendance->shouldNotifyParent()) {
            SendAttendanceWhatsAppJob::dispatch($attendance->id);

            $student = $attendance->student;
            foreach ($student->parents as $parent) {
                $notification = Notification::create([
                    'user_id' => $parent->user_id,
                    'type' => 'attendance_alert',
                    'title' => 'Notifikasi Presensi',
                    'message' => "Siswa {$student->user->name} tercatat " . Attendance::getStatusLabel($attendance->status) . " pada tanggal " . $attendance->date->format('d/m/Y'),
                    'data' => [
                        'attendance_id' => $attendance->id,
                        'student_id' => $student->id,
                        'status' => $attendance->status,
                    ],
                ]);

                event(new NotificationCreated($notification));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance recorded successfully',
            'data' => $attendance->load(['student.user', 'classroom', 'subject']),
        ], 201);
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:H,I,S,A',
            'attendances.*.notes' => 'nullable|string',
        ]);

        $created = [];
        $errors = [];

        foreach ($validated['attendances'] as $attendanceData) {
            $exists = Attendance::where('student_id', $attendanceData['student_id'])
                ->where('classroom_id', $validated['classroom_id'])
                ->where('subject_id', $validated['subject_id'])
                ->whereDate('date', $validated['date'])
                ->exists();

            if ($exists) {
                $errors[] = [
                    'student_id' => $attendanceData['student_id'],
                    'message' => 'Attendance already exists',
                ];
                continue;
            }

            $attendance = Attendance::create([
                'student_id' => $attendanceData['student_id'],
                'classroom_id' => $validated['classroom_id'],
                'subject_id' => $validated['subject_id'],
                'teacher_id' => $validated['teacher_id'],
                'date' => $validated['date'],
                'status' => $attendanceData['status'],
                'notes' => $attendanceData['notes'] ?? null,
            ]);

            event(new AttendanceRecorded($attendance));

            if ($attendance->shouldNotifyParent()) {
                SendAttendanceWhatsAppJob::dispatch($attendance->id);

                $student = $attendance->student;
                foreach ($student->parents as $parent) {
                    $notification = Notification::create([
                        'user_id' => $parent->user_id,
                        'type' => 'attendance_alert',
                        'title' => 'Notifikasi Presensi',
                        'message' => "Siswa {$student->user->name} tercatat " . Attendance::getStatusLabel($attendance->status) . " pada tanggal " . $attendance->date->format('d/m/Y'),
                        'data' => [
                            'attendance_id' => $attendance->id,
                            'student_id' => $student->id,
                            'status' => $attendance->status,
                        ],
                    ]);

                    event(new NotificationCreated($notification));
                }
            }

            $created[] = $attendance;
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk attendance recorded',
            'data' => [
                'created' => count($created),
                'errors' => $errors,
            ],
        ], 201);
    }

    public function show(Attendance $attendance)
    {
        return response()->json([
            'success' => true,
            'data' => $attendance->load(['student.user', 'classroom', 'subject', 'teacher.user']),
        ]);
    }

    public function update(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'status' => 'in:H,I,S,A',
            'notes' => 'nullable|string',
        ]);

        $oldStatus = $attendance->status;
        $attendance->update($validated);

        if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
            if ($attendance->shouldNotifyParent()) {
                $attendance->update(['notification_sent' => false]);
                SendAttendanceWhatsAppJob::dispatch($attendance->id);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance updated successfully',
            'data' => $attendance->fresh()->load(['student.user', 'classroom', 'subject']),
        ]);
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance deleted successfully',
        ]);
    }

    public function getSummary(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $query = Attendance::whereBetween('date', [$validated['date_from'], $validated['date_to']]);

        if (isset($validated['student_id'])) {
            $query->where('student_id', $validated['student_id']);
        }

        if (isset($validated['classroom_id'])) {
            $query->where('classroom_id', $validated['classroom_id']);
        }

        $attendances = $query->get();

        $summary = [
            'hadir' => $attendances->where('status', 'H')->count(),
            'izin' => $attendances->where('status', 'I')->count(),
            'sakit' => $attendances->where('status', 'S')->count(),
            'alpha' => $attendances->where('status', 'A')->count(),
            'total' => $attendances->count(),
        ];

        $summary['rate'] = $summary['total'] > 0 
            ? round(($summary['hadir'] / $summary['total']) * 100, 2) 
            : 0;

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}