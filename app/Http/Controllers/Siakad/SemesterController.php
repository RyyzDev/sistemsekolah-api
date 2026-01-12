<?php

namespace App\Http\Controllers\Siakad;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use Illuminate\Http\Request;

class SemesterController extends Controller
{
    public function index()
    {
        $semesters = Semester::with('academicYear')
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $semesters,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'type' => 'required|in:Ganjil,Genap',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $exists = Semester::where('academic_year_id', $validated['academic_year_id'])
            ->where('type', $validated['type'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Semester already exists for this academic year',
            ], 422);
        }

        $semester = Semester::create($validated);

        if ($validated['is_active'] ?? false) {
            $semester->activate();
        }

        return response()->json([
            'success' => true,
            'message' => 'Semester created successfully',
            'data' => $semester->load('academicYear'),
        ], 201);
    }

    public function show(Semester $semester)
    {
        return response()->json([
            'success' => true,
            'data' => $semester->load('academicYear'),
        ]);
    }

    public function update(Request $request, Semester $semester)
    {
        $validated = $request->validate([
            'start_date' => 'date',
            'end_date' => 'date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $semester->update($validated);

        if (isset($validated['is_active']) && $validated['is_active']) {
            $semester->activate();
        }

        return response()->json([
            'success' => true,
            'message' => 'Semester updated successfully',
            'data' => $semester->fresh()->load('academicYear'),
        ]);
    }

    public function destroy(Semester $semester)
    {
        if ($semester->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete active semester',
            ], 422);
        }

        $semester->delete();

        return response()->json([
            'success' => true,
            'message' => 'Semester deleted successfully',
        ]);
    }

    public function activate(Semester $semester)
    {
        $semester->activate();

        return response()->json([
            'success' => true,
            'message' => 'Semester activated successfully',
            'data' => $semester->fresh()->load('academicYear'),
        ]);
    }

    public function getActive()
    {
        $active = Semester::getActive();

        if (!$active) {
            return response()->json([
                'success' => false,
                'message' => 'No active semester found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $active->load('academicYear'),
        ]);
    }
}