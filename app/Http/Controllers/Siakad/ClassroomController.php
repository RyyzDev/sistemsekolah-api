<?php

namespace App\Http\Controllers\Siakad;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index(Request $request)
    {
        $query = Classroom::with(['academicYear', 'homeroomTeacher']);

        if ($request->has('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        if ($request->has('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        $classrooms = $query->get();

        return response()->json([
            'success' => true,
            'data' => $classrooms,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'grade_level' => 'required|string|max:10',
            'academic_year_id' => 'required|exists:academic_years,id',
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'capacity' => 'integer|min:1',
        ]);

        $classroom = Classroom::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Classroom created successfully',
            'data' => $classroom->load(['academicYear', 'homeroomTeacher']),
        ], 201);
    }

    public function show(Classroom $classroom)
    {
        return response()->json([
            'success' => true,
            'data' => $classroom->load([
                'academicYear',
                'homeroomTeacher',
                'students.user',
                'subjects',
            ]),
        ]);
    }

    public function update(Request $request, Classroom $classroom)
    {
        $validated = $request->validate([
            'name' => 'string|max:50',
            'grade_level' => 'string|max:10',
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'capacity' => 'integer|min:1',
        ]);

        $classroom->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Classroom updated successfully',
            'data' => $classroom->fresh()->load(['academicYear', 'homeroomTeacher']),
        ]);
    }

    public function destroy(Classroom $classroom)
    {
        $classroom->delete();

        return response()->json([
            'success' => true,
            'message' => 'Classroom deleted successfully',
        ]);
    }

    public function assignTeacher(Request $request, Classroom $classroom)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        $exists = $classroom->teachers()
            ->wherePivot('subject_id', $validated['subject_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Subject already assigned to another teacher in this classroom',
            ], 422);
        }

        $classroom->teachers()->attach($validated['teacher_id'], [
            'subject_id' => $validated['subject_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Teacher assigned successfully',
        ]);
    }

    public function removeTeacher(Request $request, Classroom $classroom)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        $classroom->teachers()
            ->wherePivot('subject_id', $validated['subject_id'])
            ->detach($validated['teacher_id']);

        return response()->json([
            'success' => true,
            'message' => 'Teacher removed successfully',
        ]);
    }

    public function assignStudent(Request $request, Classroom $classroom)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);

        $exists = $classroom->students()
            ->wherePivot('student_id', $validated['student_id'])
            ->wherePivot('semester_id', $validated['semester_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Student already assigned to this classroom for this semester',
            ], 422);
        }

        $classroom->students()->attach($validated['student_id'], [
            'semester_id' => $validated['semester_id'],
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student assigned successfully',
        ]);
    }

    public function removeStudent(Request $request, Classroom $classroom)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);

        $classroom->students()
            ->wherePivot('semester_id', $validated['semester_id'])
            ->detach($validated['student_id']);

        return response()->json([
            'success' => true,
            'message' => 'Student removed successfully',
        ]);
    }
}