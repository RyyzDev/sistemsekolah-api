<?php

namespace App\Http\Controllers\Siakad;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $academicYears,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:20|unique:academic_years,name',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $academicYear = AcademicYear::create($validated);

        if ($validated['is_active'] ?? false) {
            $academicYear->activate();
        }

        return response()->json([
            'success' => true,
            'message' => 'Academic year created successfully',
            'data' => $academicYear,
        ], 201);
    }

    public function show(AcademicYear $academicYear)
    {
        return response()->json([
            'success' => true,
            'data' => $academicYear->load(['semesters', 'classrooms']),
        ]);
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $validated = $request->validate([
            'name' => 'string|max:20|unique:academic_years,name,' . $academicYear->id,
            'start_date' => 'date',
            'end_date' => 'date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $academicYear->update($validated);

        if (isset($validated['is_active']) && $validated['is_active']) {
            $academicYear->activate();
        }

        return response()->json([
            'success' => true,
            'message' => 'Academic year updated successfully',
            'data' => $academicYear->fresh(),
        ]);
    }

    public function destroy(AcademicYear $academicYear)
    {
        if ($academicYear->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete active academic year',
            ], 422);
        }

        $academicYear->delete();

        return response()->json([
            'success' => true,
            'message' => 'Academic year deleted successfully',
        ]);
    }

    public function activate(AcademicYear $academicYear)
    {
        $academicYear->activate();

        return response()->json([
            'success' => true,
            'message' => 'Academic year activated successfully',
            'data' => $academicYear->fresh(),
        ]);
    }

    public function getActive()
    {
        $active = AcademicYear::getActive();

        if (!$active) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $active->load('semesters'),
        ]);
    }
}