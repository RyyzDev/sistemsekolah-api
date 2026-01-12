<?php

namespace App\Http\Controllers\Siakad;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Services\GradingCalculationService;
use App\Events\GradePublished;
use Illuminate\Http\Request;

class GradeSiakadController extends Controller
{
    public function __construct(
        private GradingCalculationService $gradingService
    ) {}

    public function index(Request $request)
    {
        $query = Grade::with([
            'student.user',
            'subject',
            'semester.academicYear',
            'teacher.user',
            'gradeComponent',
        ]);

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }

        $grades = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $grades,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'semester_id' => 'required|exists:semesters,id',
            'teacher_id' => 'required|exists:teachers,id',
            'grade_component_id' => 'required|exists:grade_components,id',
            'score' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $exists = Grade::where('student_id', $validated['student_id'])
            ->where('subject_id', $validated['subject_id'])
            ->where('semester_id', $validated['semester_id'])
            ->where('grade_component_id', $validated['grade_component_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Grade already exists for this combination',
            ], 422);
        }

        $grade = Grade::create($validated);

        event(new GradePublished($grade));

        return response()->json([
            'success' => true,
            'message' => 'Grade created successfully',
            'data' => $grade->load(['student.user', 'subject', 'gradeComponent']),
        ], 201);
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'semester_id' => 'required|exists:semesters,id',
            'teacher_id' => 'required|exists:teachers,id',
            'grade_component_id' => 'required|exists:grade_components,id',
            'grades' => 'required|array',
            'grades.*.student_id' => 'required|exists:students,id',
            'grades.*.score' => 'required|numeric|min:0|max:100',
            'grades.*.notes' => 'nullable|string',
        ]);

        $created = [];
        $errors = [];

        foreach ($validated['grades'] as $gradeData) {
            $exists = Grade::where('student_id', $gradeData['student_id'])
                ->where('subject_id', $validated['subject_id'])
                ->where('semester_id', $validated['semester_id'])
                ->where('grade_component_id', $validated['grade_component_id'])
                ->exists();

            if ($exists) {
                $errors[] = [
                    'student_id' => $gradeData['student_id'],
                    'message' => 'Grade already exists',
                ];
                continue;
            }

            $grade = Grade::create([
                'student_id' => $gradeData['student_id'],
                'subject_id' => $validated['subject_id'],
                'semester_id' => $validated['semester_id'],
                'teacher_id' => $validated['teacher_id'],
                'grade_component_id' => $validated['grade_component_id'],
                'score' => $gradeData['score'],
                'notes' => $gradeData['notes'] ?? null,
            ]);

            event(new GradePublished($grade));
            $created[] = $grade;
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk grades created',
            'data' => [
                'created' => count($created),
                'errors' => $errors,
            ],
        ], 201);
    }

    public function show(Grade $grade)
    {
        return response()->json([
            'success' => true,
            'data' => $grade->load([
                'student.user',
                'subject',
                'semester.academicYear',
                'teacher.user',
                'gradeComponent',
            ]),
        ]);
    }

    public function update(Request $request, Grade $grade)
    {
        $validated = $request->validate([
            'score' => 'numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $grade->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Grade updated successfully',
            'data' => $grade->fresh()->load(['student.user', 'subject', 'gradeComponent']),
        ]);
    }

    public function destroy(Grade $grade)
    {
        $grade->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grade deleted successfully',
        ]);
    }

    public function calculateFinalScore(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);

        $finalScore = $this->gradingService->calculateFinalScore(
            $validated['student_id'],
            $validated['subject_id'],
            $validated['semester_id']
        );

        return response()->json([
            'success' => true,
            'data' => [
                'final_score' => $finalScore,
            ],
        ]);
    }

    public function getClassroomSummary(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);

        $summary = $this->gradingService->getClassroomGradesSummary(
            $validated['classroom_id'],
            $validated['subject_id'],
            $validated['semester_id']
        );

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}