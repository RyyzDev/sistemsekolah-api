<?php

namespace App\Http\Controllers\PPDB;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Student;
use App\Models\Document;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\PaymentNotification;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Resources\StudentResource;
use App\Http\Resources\StudentDetailResource;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\PaymentResource;



class AdminPPDBController extends Controller
{
     public function statistics()
    {
        $stats = [
            'total'     => Student::count(),
            'draft'     => Student::draft()->count(),
            'submitted' => Student::submitted()->count(),
            'verified'  => Student::verified()->count(),
            'accepted'  => Student::accepted()->count(),
            'by_path'   => Student::select('registration_path', DB::raw('count(*) as total'))
                ->groupBy('registration_path')->get(),
            'by_gender' => Student::select('gender', DB::raw('count(*) as total'))
                ->groupBy('gender')->get(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Statistik pendaftaran berhasil diambil',
            'data'    => $stats
        ]);
    }

     public function getStudentBy(Request $request)
    {
        $query = Student::with(['user', 'parents', 'achievements', 'documents']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('registration_path')) {
            $query->where('registration_path', $request->registration_path);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate($request->get('per_page', 15));

        return StudentResource::collection($students)
            ->additional([
                'success' => true,
                'message' => 'Data siswa berhasil diambil',
            ])
            ->response()
            ->setStatusCode(200);
    
    }

     public function verifyStudent(Request $request, $id)
    {
        $student = Student::with('user')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:verified,accepted,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
        $student->update(['status' => $request->status]);

        if ($request->status === 'accepted') {
            $student->user->update([
                'role' => 'student'
            ]);
        }

        DB::commit();
        return (new StudentDetailResource($student))
            ->additional([
                'success' => true,
                'message' => "Data siswa berhasil di {$request->status}",
            ])
            ->response()
            ->setStatusCode(200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => "Data Siswa gagal di {$request->status}",
                'error' => $e->getMessage()
            ], 500);
        }
    }


      public function verifyDocument(Request $request, $studentId, $documentId)
    {
        $document = Document::where('id', $documentId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        $document->verify($request->user()->name);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil diverifikasi',
            'data' => $document
        ]);
    }

      /**
     * Summary payment for admin
     */
    public function summary(Request $request)
    {
        try {
            // total pendapatan dari transaksi yang berhasil
            $totalRevenue = Payment::where('status', 'success')->sum('total_amount');

            // jumlah transaksi berdasarkan status
            $statusCounts = Payment::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get()
                ->pluck('total', 'status');

        $payments = Payment::where('status', 'success')
                    ->where('created_at', '>=', now()->subMonths(6))
                    ->get();

        $monthlyGraphic = $payments->groupBy(function($date) {
                return \Carbon\Carbon::parse($date->created_at)->format('F Y');
                })
                ->map(function ($month) {
                    return [
                        'month' => $month->first()->created_at->format('F Y'),
                        'amount' => $month->sum('total_amount'),
                    ];
                })->values();

            $stats = [
                'overview' => [
                    'total_revenue'    => (float) $totalRevenue,
                    'total_transactions' => Payment::count(),
                    'success_count'    => $statusCounts->get('success', 0),
                    'pending_count'    => $statusCounts->get('pending', 0),
                    'expired_or_cancel' => $statusCounts->get('expire', 0) + $statusCounts->get('cancel', 0),
                ],
                'monthly_revenue' => $monthlyGraphic,
                'recent_payments' => PaymentResource::collection(
                    Payment::with('student:id,full_name')
                        ->latest()
                        ->take(5)
                        ->get()
                )
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistik pembayaran global berhasil diambil',
                'data'    => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil ringkasan pembayaran',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


      /**
     * Refund payment (Admin Only)
     */
    public function refund(Request $request, $id)
    {
        
        // 1. Cari data pembayaran
        $payment = Payment::findOrFail($id);
        // return response()->json($payment);

        // 2. Cek apakah status TIDAK ADA di dalam daftar yang diizinkan
        if (!in_array($payment->status, ['settlement', 'capture'])) {
            return response()->json([
                'success' => false, 
                'message' => 'Hanya pembayaran dengan status sukses yang dapat di-refund.'
            ], 400);
        }
        try {
            // 3. Panggil service Midtrans untuk refund
            $reason = $request->reason;
            
            $midtransResponse = $this->midtransService->refund($payment->order_id, [
                'reason' => $reason
            ]);

            // 4. Update status di database lokal
            $payment->update([
                'status' => 'refund',
                'notes' => $reason
            ]);

            return (new PaymentResource($payment))->additional([
                'success' => true,
                'message' => 'Pembayaran berhasil di-refund',
                'midtrans_info' => $midtransResponse
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Gagal memproses refund ke Midtrans.', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
