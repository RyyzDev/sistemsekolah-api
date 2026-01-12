<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Subject;
use App\Models\Semester;
use App\Models\Grade;
use App\Models\GradeComponent;
use Illuminate\Support\Collection;

class GradingCalculationService
{
    public function calculateFinalScore(int $studentId, int $subjectId, int $semesterId): ?float
    {
        $subject = Subject::find($subjectId);
        if (!$subject) {
            return null;
        }

        $grades = Grade::where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->with('gradeComponent')
            ->get();

        if ($grades->isEmpty()) {
            return null;
        }

        $totalScore = 0;
        $totalWeight = 0;

        foreach ($grades as $grade) {
            $weight = $subject->getGradeWeight($grade->gradeComponent->code);
            $totalScore += ($grade->score * $weight);
            $totalWeight += $weight;
        }

        if ($totalWeight === 0) {
            return null;
        }

        return round($totalScore / $totalWeight, 2);
    }

    public function calculateSemesterGPA(int $studentId, int $semesterId): ?float
    {
        $student = Student::find($studentId);
        if (!$student) {
            return null;
        }

        $classroom = $student->getCurrentClassroom($semesterId);
        if (!$classroom) {
            return null;
        }

        $subjects = $classroom->subjects;
        $totalScore = 0;
        $subjectCount = 0;

        foreach ($subjects as $subject) {
            $finalScore = $this->calculateFinalScore($studentId, $subject->id, $semesterId);
            if ($finalScore !== null) {
                $totalScore += $finalScore;
                $subjectCount++;
            }
        }

        if ($subjectCount === 0) {
            return null;
        }

        return round($totalScore / $subjectCount, 2);
    }

    public function getStudentReportCard(int $studentId, int $semesterId): array
    {
        $student = Student::with(['user', 'classrooms' => function ($query) use ($semesterId) {
            $query->wherePivot('semester_id', $semesterId);
        }])->find($studentId);

        if (!$student) {
            return [];
        }

        $classroom = $student->getCurrentClassroom($semesterId);
        if (!$classroom) {
            return [];
        }

        $semester = Semester::with('academicYear')->find($semesterId);
        $subjects = $classroom->subjects;
        
        $reportData = [];

        foreach ($subjects as $subject) {
            $grades = Grade::where('student_id', $studentId)
                ->where('subject_id', $subject->id)
                ->where('semester_id', $semesterId)
                ->with('gradeComponent')
                ->get()
                ->keyBy('gradeComponent.code');

            $componentScores = [];
            $components = GradeComponent::orderBy('sort_order')->get();

            foreach ($components as $component) {
                $componentScores[$component->code] = [
                    'name' => $component->name,
                    'score' => $grades->get($component->code)?->score ?? null,
                    'weight' => $subject->getGradeWeight($component->code),
                ];
            }

            $finalScore = $this->calculateFinalScore($studentId, $subject->id, $semesterId);
            $isPassing = $finalScore !== null && $finalScore >= $subject->kkm;

            $reportData[] = [
                'subject_code' => $subject->code,
                'subject_name' => $subject->name,
                'kkm' => $subject->kkm,
                'components' => $componentScores,
                'final_score' => $finalScore,
                'is_passing' => $isPassing,
                'grade_letter' => $this->getGradeLetter($finalScore),
                'predicate' => $this->getPredicate($finalScore),
            ];
        }

        $gpa = $this->calculateSemesterGPA($studentId, $semesterId);

        $attendanceSummary = $this->getAttendanceSummary($studentId, $semesterId);

        return [
            'student' => [
                'id' => $student->id,
                'name' => $student->user->name,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
            ],
            'semester' => [
                'id' => $semester->id,
                'type' => $semester->type,
                'academic_year' => $semester->academicYear->name,
            ],
            'classroom' => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'homeroom_teacher' => $classroom->homeroomTeacher?->name,
            ],
            'subjects' => $reportData,
            'gpa' => $gpa,
            'attendance_summary' => $attendanceSummary,
        ];
    }

    public function getClassroomGradesSummary(int $classroomId, int $subjectId, int $semesterId): array
    {
        $classroom = \App\Models\Classroom::with(['students' => function ($query) use ($semesterId) {
            $query->wherePivot('semester_id', $semesterId)
                  ->wherePivot('is_active', true);
        }])->find($classroomId);

        if (!$classroom) {
            return [];
        }

        $summary = [];
        $allScores = [];

        foreach ($classroom->students as $student) {
            $finalScore = $this->calculateFinalScore($student->id, $subjectId, $semesterId);
            
            if ($finalScore !== null) {
                $allScores[] = $finalScore;
            }

            $summary[] = [
                'student_id' => $student->id,
                'student_name' => $student->user->name,
                'nis' => $student->nis,
                'final_score' => $finalScore,
                'grade_letter' => $this->getGradeLetter($finalScore),
            ];
        }

        $statistics = [
            'average' => !empty($allScores) ? round(array_sum($allScores) / count($allScores), 2) : null,
            'highest' => !empty($allScores) ? max($allScores) : null,
            'lowest' => !empty($allScores) ? min($allScores) : null,
            'total_students' => count($summary),
            'graded_students' => count($allScores),
        ];

        return [
            'classroom' => [
                'id' => $classroom->id,
                'name' => $classroom->name,
            ],
            'students' => $summary,
            'statistics' => $statistics,
        ];
    }

    private function getGradeLetter(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        return match(true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'E',
        };
    }

    private function getPredicate(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        return match(true) {
            $score >= 90 => 'Sangat Baik',
            $score >= 80 => 'Baik',
            $score >= 70 => 'Cukup',
            $score >= 60 => 'Kurang',
            default => 'Sangat Kurang',
        };
    }

    private function getAttendanceSummary(int $studentId, int $semesterId): array
    {
        $semester = Semester::find($semesterId);
        
        $attendances = \App\Models\Attendance::where('student_id', $studentId)
            ->whereBetween('date', [$semester->start_date, $semester->end_date])
            ->get();

        return [
            'hadir' => $attendances->where('status', 'H')->count(),
            'izin' => $attendances->where('status', 'I')->count(),
            'sakit' => $attendances->where('status', 'S')->count(),
            'alpha' => $attendances->where('status', 'A')->count(),
            'total' => $attendances->count(),
        ];
    }

    public function getMissingGradesForTeacher(int $teacherId, int $semesterId): Collection
    {
        $teacher = \App\Models\Teacher::with(['classrooms.students'])->find($teacherId);
        
        if (!$teacher) {
            return collect();
        }

        $missing = collect();
        $components = GradeComponent::all();

        foreach ($teacher->classrooms as $classroom) {
            $subjectId = $classroom->pivot->subject_id;
            $subject = Subject::find($subjectId);

            foreach ($classroom->students as $student) {
                if ($student->pivot->semester_id != $semesterId) {
                    continue;
                }

                foreach ($components as $component) {
                    $gradeExists = Grade::where('student_id', $student->id)
                        ->where('subject_id', $subjectId)
                        ->where('semester_id', $semesterId)
                        ->where('grade_component_id', $component->id)
                        ->exists();

                    if (!$gradeExists) {
                        $missing->push([
                            'student_id' => $student->id,
                            'student_name' => $student->user->name,
                            'classroom_id' => $classroom->id,
                            'classroom_name' => $classroom->name,
                            'subject_id' => $subjectId,
                            'subject_name' => $subject->name,
                            'component_id' => $component->id,
                            'component_name' => $component->name,
                        ]);
                    }
                }
            }
        }

        return $missing;
    }
}