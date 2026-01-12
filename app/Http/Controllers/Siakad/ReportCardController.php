<?php

namespace App\Http\Controllers\Siakad;

use App\Http\Controllers\Controller;
use App\Services\GradingCalculationService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportCardController extends Controller
{
    public function __construct(
        private GradingCalculationService $gradingService
    ) {}

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);

        $reportData = $this->gradingService->getStudentReportCard(
            $validated['student_id'],
            $validated['semester_id']
        );

        if (empty($reportData)) {
            return response()->json([
                'success' => false,
                'message' => 'Report card data not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $reportData,
        ]);
    }

    public function downloadPDF(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);

        $reportData = $this->gradingService->getStudentReportCard(
            $validated['student_id'],
            $validated['semester_id']
        );

        if (empty($reportData)) {
            return response()->json([
                'success' => false,
                'message' => 'Report card data not found',
            ], 404);
        }

        $pdf = Pdf::loadView('reports.raport', ['data' => $reportData])->setPaper('a4', 'portrait');

        $fileName = 'Raport_' . $reportData['student']['nis'] . '_' . $reportData['semester']['type'] . '_' . str_replace('/', '-', $reportData['semester']['academic_year']) . '.pdf';

        return $pdf->download($fileName);
    }
}