<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GradeController extends Controller
{
    public function index($studentId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();

        $grades = $student->grades()->orderBy('semester')->orderBy('subject')->get();
        $averageScore = $student->grades()->avg('score');

        return response()->json([
            'success' => true,
            'message' => 'Data nilai berhasil diambil',
            'data' => $grades,
            'average_score' => round($averageScore, 2)
        ]);
    }

    public function store(Request $request, $studentId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (in_array($student->status, ['submitted', 'verified', 'accepted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data nilai tidak dapat ditambahkan setelah pendaftaran disubmit'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'semester' => 'required|in:1,2,3,4,5,6',
            'subject' => 'required|string|max:255',
            'score' => 'required|numeric|min:0|max:100',
            'grade_type' => 'required|in:pengetahuan,keterampilan,rapor,us,un',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $gradeData = $request->all();
        $gradeData['student_id'] = $studentId;

        $grade = Grade::create($gradeData);

        return response()->json([
            'success' => true,
            'message' => 'Data nilai berhasil ditambahkan',
            'data' => $grade
        ], 201);
    }

    public function storeBulk(Request $request, $studentId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (in_array($student->status, ['submitted', 'verified', 'accepted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data nilai tidak dapat ditambahkan setelah pendaftaran disubmit'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'grades' => 'required|array',
            'grades.*.semester' => 'required|in:1,2,3,4,5,6',
            'grades.*.subject' => 'required|string|max:255',
            'grades.*.score' => 'required|numeric|min:0|max:100',
            'grades.*.grade_type' => 'required|in:pengetahuan,keterampilan,rapor,us,un',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $grades = [];
        foreach ($request->grades as $gradeData) {
            $gradeData['student_id'] = $studentId;
            $grades[] = Grade::create($gradeData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data nilai berhasil ditambahkan',
            'data' => $grades
        ], 201);
    }

    public function show($studentId, $gradeId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();

        $grade = Grade::where('id', $gradeId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Data nilai berhasil diambil',
            'data' => $grade
        ]);
    }

    public function update(Request $request, $studentId, $gradeId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (in_array($student->status, ['submitted', 'verified', 'accepted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data nilai tidak dapat diubah setelah pendaftaran disubmit'
            ], 400);
        }

        $grade = Grade::where('id', $gradeId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'semester' => 'sometimes|in:1,2,3,4,5,6',
            'subject' => 'sometimes|string|max:255',
            'score' => 'sometimes|numeric|min:0|max:100',
            'grade_type' => 'sometimes|in:pengetahuan,keterampilan,rapor,us,un',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $grade->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Data nilai berhasil diupdate',
            'data' => $grade
        ]);
    }

    public function destroy(Request $request, $studentId, $gradeId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (in_array($student->status, ['submitted', 'verified', 'accepted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data nilai tidak dapat dihapus setelah pendaftaran disubmit'
            ], 400);
        }

        $grade = Grade::where('id', $gradeId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        $grade->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data nilai berhasil dihapus'
        ]);
    }
}