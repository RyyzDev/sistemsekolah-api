<?php

namespace App\Http\Controllers\Siakad;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function admin()
    {
        $data = $this->dashboardService->getAdminDashboard();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function teacher(Request $request)
    {
        $teacher = $request->user()->teacher;

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher profile not found',
            ], 404);
        }

        $data = $this->dashboardService->getTeacherDashboard($teacher->id);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function student(Request $request)
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student profile not found',
            ], 404);
        }

        $data = $this->dashboardService->getStudentDashboard($student->id);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function parent(Request $request)
    {
        $parent = $request->user()->parent;

        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Parent profile not found',
            ], 404);
        }

        $data = $this->dashboardService->getParentDashboard($parent->id);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}