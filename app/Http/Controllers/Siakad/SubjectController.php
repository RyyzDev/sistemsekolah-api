<?php

namespace App\Http\Controllers\Siakad;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Subject::query();

        if ($request->has('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        $subjects = $query->get();

        return response()->json([
            'success' => true,
            'data' => $subjects,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:subjects,code',
            'name' => 'required|string|max:100',
            'grade_level' => 'required|string|max:10',
            'kkm' => 'numeric|min:0|max:100',
            'grade_weights' => 'nullable|array',
            'grade_weights.tugas' => 'nullable|integer|min:0|max:100',
            'grade_weights.uh' => 'nullable|integer|min:0|max:100',
            'grade_weights.uts' => 'nullable|integer|min:0|max:100',
            'grade_weights.uas' => 'nullable|integer|min:0|max:100',
        ]);

        if (isset($validated['grade_weights'])) {
            $totalWeight = array_sum($validated['grade_weights']);
            if ($totalWeight != 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Grade weights must sum to 100',
                ], 422);
            }
        }

        $subject = Subject::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Subject created successfully',
            'data' => $subject,
        ], 201);
    }

    public function show(Subject $subject)
    {
        return response()->json([
            'success' => true,
            'data' => $subject,
        ]);
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'code' => 'string|max:20|unique:subjects,code,' . $subject->id,
            'name' => 'string|max:100',
            'grade_level' => 'string|max:10',
            'kkm' => 'numeric|min:0|max:100',
            'grade_weights' => 'nullable|array',
            'grade_weights.tugas' => 'nullable|integer|min:0|max:100',
            'grade_weights.uh' => 'nullable|integer|min:0|max:100',
            'grade_weights.uts' => 'nullable|integer|min:0|max:100',
            'grade_weights.uas' => 'nullable|integer|min:0|max:100',
        ]);

        if (isset($validated['grade_weights'])) {
            $totalWeight = array_sum($validated['grade_weights']);
            if ($totalWeight != 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Grade weights must sum to 100',
                ], 422);
            }
        }

        $subject->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Subject updated successfully',
            'data' => $subject->fresh(),
        ]);
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subject deleted successfully',
        ]);
    }
}