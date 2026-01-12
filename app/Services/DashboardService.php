<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Semester;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        private GradingCalculationService $gradingService
    ) {}

    public function getAdminDashboard(): array
    {
        $activeSemester = Semester::getActive();
        
        return [
            'total_students' => Student::count(),
            'total_teachers' => Teacher::count(),
            'total_classrooms' => Classroom::count(),
            'active_semester' => $activeSemester?->type . ' ' . $activeSemester?->academicYear->name,
            'attendance_rate_graph' => $this->getAttendanceRateGraph(),
            'average_school_grades' => $this->getAverageSchoolGrades($activeSemester?->id),
            'recent_activities' => $this->getRecentActivities(),
        ];
    }

    public function getTeacherDashboard(int $teacherId): array
    {
        $activeSemester = Semester::getActive();
        $teacher = Teacher::find($teacherId);

        if (!$teacher) {
            return [];
        }

        return [
            'teacher_name' => $teacher->user->name,
            'active_semester' => $activeSemester?->type . ' ' . $activeSemester?->academicYear->name,
            'class_attendance_summary' => $this->getTeacherClassAttendanceSummary($teacherId, $activeSemester?->id),
            'incomplete_grades' => $this->gradingService->getMissingGradesForTeacher($teacherId, $activeSemester?->id),
            'teaching_schedule' => $this->getTeachingSchedule($teacherId),
        ];
    }

    public function getStudentDashboard(int $studentId): array
    {
        $activeSemester = Semester::getActive();
        $student = Student::with('user')->find($studentId);

        if (!$student) {
            return [];
        }

        return [
            'student_name' => $student->user->name,
            'nis' => $student->nis,
            'active_semester' => $activeSemester?->type . ' ' . $activeSemester?->academicYear->name,
            'attendance_summary' => $this->getStudentAttendanceSummary($studentId, $activeSemester?->id),
            'gpa_trend' => $this->getStudentGPATrend($studentId),
            'current_gpa' => $this->gradingService->calculateSemesterGPA($studentId, $activeSemester?->id),
            'recent_grades' => $this->getRecentGrades($studentId, $activeSemester?->id),
        ];
    }

    public function getParentDashboard(int $parentId): array
    {
        $parent = \App\Models\ParentModel::with(['student.user'])->find($parentId);
        
        if (!$parent) {
            return [];
        }

        $studentId = $parent->student_id;
        $activeSemester = Semester::getActive();

        return [
            'parent_name' => $parent->user->name,
            'student_name' => $parent->student->user->name,
            'relation_type' => $parent->relation_type,
            'active_semester' => $activeSemester?->type . ' ' . $activeSemester?->academicYear->name,
            'attendance_summary' => $this->getStudentAttendanceSummary($studentId, $activeSemester?->id),
            'gpa_trend' => $this->getStudentGPATrend($studentId),
            'current_gpa' => $this->gradingService->calculateSemesterGPA($studentId, $activeSemester?->id),
            'recent_attendance_alerts' => $this->getRecentAttendanceAlerts($studentId),
        ];
    }

    private function getAttendanceRateGraph(): array
    {
        $last7Days = collect();
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            
            $total = Attendance::whereDate('date', $date)->count();
            $present = Attendance::whereDate('date', $date)->where('status', 'H')->count();
            
            $rate = $total > 0 ? round(($present / $total) * 100, 2) : 0;
            
            $last7Days->push([
                'date' => $date->format('Y-m-d'),
                'rate' => $rate,
                'total' => $total,
                'present' => $present,
            ]);
        }

        return $last7Days->toArray();
    }

    private function getAverageSchoolGrades(?int $semesterId): ?float
    {
        if (!$semesterId) {
            return null;
        }

        $students = Student::all();
        $totalGPA = 0;
        $count = 0;

        foreach ($students as $student) {
            $gpa = $this->gradingService->calculateSemesterGPA($student->id, $semesterId);
            if ($gpa !== null) {
                $totalGPA += $gpa;
                $count++;
            }
        }

        return $count > 0 ? round($totalGPA / $count, 2) : null;
    }

    private function getRecentActivities(): array
    {
        $recentGrades = DB::table('grades')
            ->join('students', 'grades.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->join('subjects', 'grades.subject_id', '=', 'subjects.id')
            ->join('grade_components', 'grades.grade_component_id', '=', 'grade_components.id')
            ->select(
                'users.name as student_name',
                'subjects.name as subject_name',
                'grade_components.name as component_name',
                'grades.score',
                'grades.created_at'
            )
            ->orderBy('grades.created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        return array_map(function ($item) {
            return [
                'type' => 'grade',
                'description' => "{$item->student_name} - {$item->subject_name} ({$item->component_name}): {$item->score}",
                'timestamp' => $item->created_at,
            ];
        }, $recentGrades);
    }

    private function getTeacherClassAttendanceSummary(int $teacherId, ?int $semesterId): array
    {
        if (!$semesterId) {
            return [];
        }

        $teacher = Teacher::with('classrooms')->find($teacherId);
        $summary = [];

        foreach ($teacher->classrooms as $classroom) {
            $students = $classroom->students()
                ->wherePivot('semester_id', $semesterId)
                ->wherePivot('is_active', true)
                ->get();

            $totalAttendances = 0;
            $totalPresent = 0;

            foreach ($students as $student) {
                $attendances = Attendance::where('student_id', $student->id)
                    ->where('classroom_id', $classroom->id)
                    ->whereMonth('date', Carbon::now()->month)
                    ->get();

                $totalAttendances += $attendances->count();
                $totalPresent += $attendances->where('status', 'H')->count();
            }

            $rate = $totalAttendances > 0 ? round(($totalPresent / $totalAttendances) * 100, 2) : 0;

            $summary[] = [
                'classroom_id' => $classroom->id,
                'classroom_name' => $classroom->name,
                'total_students' => $students->count(),
                'attendance_rate' => $rate,
            ];
        }

        return $summary;
    }

    private function getTeachingSchedule(int $teacherId): array
    {
        $teacher = Teacher::with(['classrooms.subjects'])->find($teacherId);
        $schedule = [];

        foreach ($teacher->classrooms as $classroom) {
            $subjectId = $classroom->pivot->subject_id;
            $subject = \App\Models\Subject::find($subjectId);

            $schedule[] = [
                'classroom_id' => $classroom->id,
                'classroom_name' => $classroom->name,
                'subject_id' => $subject->id,
                'subject_name' => $subject->name,
            ];
        }

        return $schedule;
    }

    private function getStudentAttendanceSummary(int $studentId, ?int $semesterId): array
    {
        if (!$semesterId) {
            return [
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alpha' => 0,
                'total' => 0,
                'rate' => 0,
            ];
        }

        $semester = Semester::find($semesterId);
        
        $attendances = Attendance::where('student_id', $studentId)
            ->whereBetween('date', [$semester->start_date, $semester->end_date])
            ->get();

        $hadir = $attendances->where('status', 'H')->count();
        $total = $attendances->count();

        return [
            'hadir' => $hadir,
            'izin' => $attendances->where('status', 'I')->count(),
            'sakit' => $attendances->where('status', 'S')->count(),
            'alpha' => $attendances->where('status', 'A')->count(),
            'total' => $total,
            'rate' => $total > 0 ? round(($hadir / $total) * 100, 2) : 0,
        ];
    }

    private function getStudentGPATrend(int $studentId): array
    {
        $semesters = Semester::with('academicYear')
            ->orderBy('start_date')
            ->get();

        $trend = [];

        foreach ($semesters as $semester) {
            $gpa = $this->gradingService->calculateSemesterGPA($studentId, $semester->id);
            
            if ($gpa !== null) {
                $trend[] = [
                    'semester' => $semester->type,
                    'academic_year' => $semester->academicYear->name,
                    'gpa' => $gpa,
                ];
            }
        }

        return $trend;
    }

    private function getRecentGrades(int $studentId, ?int $semesterId): array
    {
        if (!$semesterId) {
            return [];
        }

        return DB::table('grades')
            ->join('subjects', 'grades.subject_id', '=', 'subjects.id')
            ->join('grade_components', 'grades.grade_component_id', '=', 'grade_components.id')
            ->where('grades.student_id', $studentId)
            ->where('grades.semester_id', $semesterId)
            ->select(
                'subjects.name as subject_name',
                'grade_components.name as component_name',
                'grades.score',
                'grades.created_at'
            )
            ->orderBy('grades.created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getRecentAttendanceAlerts(int $studentId): array
    {
        return Attendance::where('student_id', $studentId)
            ->whereIn('status', ['A', 'S'])
            ->with(['subject', 'classroom'])
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($attendance) {
                return [
                    'date' => $attendance->date->format('Y-m-d'),
                    'status' => Attendance::getStatusLabel($attendance->status),
                    'subject' => $attendance->subject?->name,
                    'classroom' => $attendance->classroom->name,
                    'notes' => $attendance->notes,
                ];
            })
            ->toArray();
    }
}