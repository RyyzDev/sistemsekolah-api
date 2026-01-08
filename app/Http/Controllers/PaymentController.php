<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Student;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    /**
     * Get all payments for current user
     */
    public function index(Request $request)
    {
        $student = Student::where('user_id', $request->user()->id)->first();
 
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan'
            ], 404);
        }

        $payments = Payment::with('items')
            ->where('student_id', $student->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data pembayaran berhasil diambil',
            'data' => $payments
        ]);
    }

    /**
     * Create new payment
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_type' => 'required|in:registration_fee,tuition_fee,uniform_fee,book_fee,other',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.item_description' => 'nullable|string|max:500',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'items.*.price' => 'required|numeric|min:1|max:100000000',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Sanitasi item_name agar bersih dari tag <script>
        $items = collect($request->items)->map(function ($item) {
            return [
                'item_name' => strip_tags($item['item_name']),
                'item_description' => strip_tags($item['item_description'] ?? ''),
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $item['quantity'] * $item['price'],
            ];
        });



        $student = Student::where('user_id', $request->user()->id)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan'
            ], 404);
        }

        // Check if student has submitted registration
        if ($student->status !== 'submitted') {
            return response()->json([
                'success' => false,
                'message' => 'Pendaftaran belum disubmit. Silakan submit pendaftaran terlebih dahulu.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Calculate total amount
            $amount = collect($request->items)->sum(function ($item) {
                return $item['quantity'] * $item['price'];
            });

            $adminFee = $this->calculateAdminFee($amount);
            $totalAmount = round($amount + $adminFee, 2);

            // Create payment
            $payment = new Payment();
            $orderId = $payment->generateOrderId();

            $payment->fill([
                'student_id' => $student->id,
                'order_id' => $orderId,
                'payment_type' => $request->payment_type,
                'amount' => $amount,
                'admin_fee' => $adminFee,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);
            $payment->save();

            // Create payment items
            foreach ($items as $item) {
                PaymentItem::create([
                    'payment_id' => $payment->id,
                    'item_name' => $item['item_name'],
                    'item_description' => $item['item_description'] ?? null,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            // Create Snap Token
            $snapData = $this->midtransService->createSnapToken($payment);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil dibuat',
                'data' => [
                    'payment' => $payment->load('items'),
                    'snap_token' => $snapData['snap_token'],
                    'snap_url' => $snapData['snap_url'],
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pembayaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }



      /**
     * Sync payment status with Midtrans (manual)
     */
    public function syncStatus(Request $request, $id)
    {
        $student = Student::where('user_id', $request->user()->id)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan'
            ], 404);
        }

        $payment = Payment::where('id', $id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        try {
            // Get status from Midtrans
            $status = $this->midtransService->syncPaymentWithMidtrans($payment->order_id);

            \Log::info('Midtrans Status Response:', (array) $status);

            // Create notification data from status check
            $notificationData = [
                'transaction_time' => $status->transaction_time ?? now()->format('Y-m-d H:i:s'),
                'transaction_status' => $status->transaction_status ?? 'pending',
                'transaction_id' => $status->transaction_id ?? null,
                'status_message' => $status->status_message ?? 'Status check',
                'status_code' => $status->status_code ?? '200',
                'signature_key' => $status->signature_key ?? 'manual-sync',
                'payment_type' => $status->payment_type ?? 'unknown',
                'order_id' => $status->order_id ?? $payment->order_id,
                'merchant_id' => $status->merchant_id ?? config('midtrans.merchant_id'),
                'gross_amount' => $status->gross_amount ?? $payment->total_amount,
                'fraud_status' => $status->fraud_status ?? 'accept',
                'currency' => $status->currency ?? 'IDR',
            ];

            // Update payment directly based on status
            $updateData = [
                'transaction_id' => $notificationData['transaction_id'],
                'payment_method' => $notificationData['payment_type'],
                'midtrans_response' => (array) $status,
            ];

            // Handle VA Number
            if (isset($status->va_numbers) && is_array($status->va_numbers) && count($status->va_numbers) > 0) {
                $updateData['va_number'] = $status->va_numbers[0]->va_number ?? null;
                $updateData['bank'] = $status->va_numbers[0]->bank ?? null;
            } elseif (isset($status->permata_va_number)) {
                $updateData['va_number'] = $status->permata_va_number;
                $updateData['bank'] = 'permata';
            }

            // Handle Biller Code & Bill Key (for Mandiri)
            if (isset($status->biller_code)) {
                $updateData['biller_code'] = $status->biller_code;
            }
            if (isset($status->bill_key)) {
                $updateData['bill_key'] = $status->bill_key;
            }

            // Update status based on transaction_status
            $transactionStatus = $notificationData['transaction_status'];
            
            if ($transactionStatus == 'capture') {
                $updateData['status'] = 'capture';
                $updateData['paid_at'] = now();
            } elseif ($transactionStatus == 'settlement') {
                $updateData['status'] = 'settlement';
                $updateData['paid_at'] = now();
            } elseif ($transactionStatus == 'pending') {
                $updateData['status'] = 'pending';
            } elseif ($transactionStatus == 'deny') {
                $updateData['status'] = 'deny';
            } elseif ($transactionStatus == 'expire') {
                $updateData['status'] = 'expire';
            } elseif ($transactionStatus == 'cancel') {
                $updateData['status'] = 'cancel';
            } elseif ($transactionStatus == 'refund') {
                $updateData['status'] = 'refund';
            }

            $payment->update($updateData);

            // Save notification to database
            $payment->notifications()->create([
                'order_id' => $notificationData['order_id'],
                'transaction_id' => $notificationData['transaction_id'],
                'transaction_status' => $notificationData['transaction_status'],
                'fraud_status' => $notificationData['fraud_status'],
                'notification_body' => $notificationData,
                'notification_at' => now(),
            ]);

            // Update student status if payment successful
            if (in_array($updateData['status'], ['settlement', 'capture'])) {
                $payment->student->update(['status' => 'paid']);
            }

            // Reload payment with relations
            $payment->refresh();
            $payment->load(['items', 'notifications', 'student']);

            return response()->json([
                'success' => true,
                'message' => 'Status pembayaran berhasil disinkronkan',
                'data' => [
                    'payment' => $payment,
                    'previous_status' => 'pending',
                    'current_status' => $payment->status,
                    'student_status' => $payment->student->status,
                    'midtrans_status' => $status
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Sync Payment Error: ' . $e->getMessage());
            \Log::error('Stack Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal sinkronisasi status pembayaran',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    /**
     * Get payment detail
     */
    public function show(Request $request, $id)
    {
        $student = Student::where('user_id', $request->user()->id)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan'
            ], 404);
        }

        $payment = Payment::with(['items', 'notifications'])
            ->where('id', $id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Data pembayaran berhasil diambil',
            'data' => $payment
        ]);
    }

    /**
     * Check payment status
     */
    public function checkStatus(Request $request, $id)
    {
        $student = Student::where('user_id', $request->user()->id)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan'
            ], 404);
        }

        $payment = Payment::where('id', $id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        try {
            $status = $this->midtransService->checkStatus($payment->order_id);

            return response()->json([
                'success' => true,
                'message' => 'Status pembayaran berhasil diambil',
                'data' => [
                    'payment' => $payment,
                    'midtrans_status' => $status
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengecek status pembayaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel payment
     */
    public function cancel(Request $request, $id)
    {
        $student = Student::where('user_id', $request->user()->id)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan'
            ], 404);
        }

        $payment = Payment::where('id', $id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        if (!$payment->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pembayaran dengan status pending yang dapat dibatalkan'
            ], 400);
        }

        try {
            $this->midtransService->cancel($payment->order_id);
            
            $payment->update(['status' => 'cancel']);

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil dibatalkan',
                'data' => $payment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan pembayaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Midtrans notification webhook
     */
    public function notification(Request $request)
    {
        try {
            $notification = $request->all();
            
            $result = $this->midtransService->handleNotification($notification);

            return response()->json([
                'success' => true,
                'message' => 'Notification processed successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Payment Notification Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment summary for admin
     */
    public function summary(Request $request)
    {
        $summary = [
            'total_payments' => Payment::count(),
            'pending' => Payment::pending()->count(),
            'success' => Payment::success()->count(),
            'failed' => Payment::failed()->count(),
            'total_revenue' => Payment::success()->sum('total_amount'),
            'by_payment_type' => Payment::select('payment_type', DB::raw('count(*) as total'), DB::raw('sum(total_amount) as revenue'))
                ->groupBy('payment_type')
                ->get(),
            'recent_payments' => Payment::with(['student'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan pembayaran berhasil diambil',
            'data' => $summary
        ]);
    }

    /**
     * Calculate admin fee
     */
    private function calculateAdminFee($amount)
    {
        // Example: 2% admin fee, max 5000
        $fee = $amount * 0.02;
        $limit = 5000;
        return round(min($fee, $limit), 2);
    }

    /**
     * Refund payment (admin only)
     */
    public function refund(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $payment = Payment::findOrFail($id);

        if (!$payment->isSuccess()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pembayaran yang berhasil yang dapat di-refund'
            ], 400);
        }

        try {
            $this->midtransService->refund(
                $payment->order_id,
                $request->amount,
                $request->reason
            );

            $payment->update(['status' => 'refund']);

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil di-refund',
                'data' => $payment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan refund',
                'error' => $e->getMessage()
            ], 500);
        }
    }





//     /**
//  * Debug payment status - untuk troubleshooting
//  */
// public function debugStatus(Request $request, $id)
// {
//     $student = Student::where('user_id', $request->user()->id)->first();

//     if (!$student) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Data siswa tidak ditemukan'
//         ], 404);
//     }

//     $payment = Payment::where('id', $id)
//         ->where('student_id', $student->id)
//         ->firstOrFail();

//     try {
//         // Get status from Midtrans
//         $status = $this->midtransService->checkStatus($payment->order_id);

//         // Convert object to array for easier debugging
//         $statusArray = json_decode(json_encode($status), true);

//         return response()->json([
//             'success' => true,
//             'message' => 'Debug info',
//             'data' => [
//                 'payment' => $payment,
//                 'midtrans_raw_response' => $statusArray,
//                 'midtrans_object_type' => gettype($status),
//                 'transaction_status' => $status->transaction_status ?? 'NOT_FOUND',
//                 'transaction_id' => $status->transaction_id ?? 'NOT_FOUND',
//                 'order_id' => $status->order_id ?? 'NOT_FOUND',
//                 'payment_type' => $status->payment_type ?? 'NOT_FOUND',
//                 'available_keys' => $statusArray ? array_keys($statusArray) : [],
//             ]
//         ]);

//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Error',
//             'error' => $e->getMessage(),
//             'line' => $e->getLine(),
//             'file' => basename($e->getFile()),
//             'trace' => collect($e->getTrace())->take(3)->toArray()
//         ], 500);
//     }
// }





}




