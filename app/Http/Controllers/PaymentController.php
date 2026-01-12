<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\PaymentItemResource;
use App\Http\Resources\PaymentNotificationResource;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Student;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $student = $this->getStudent($request);

        $payments = Payment::with('items')
            ->where('student_id', $student->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10)); 

        return PaymentResource::collection($payments)->additional([
            'success' => true,
            'message' => 'Data pembayaran berhasil diambil',
        ]);
    }

    /** 
     * Get payment detail
     */
    public function show(Request $request, $id)
    {
        $student = $this->getStudent($request);

        $payment = Payment::with(['items', 'notifications'])
            ->where('id', $id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        return (new PaymentResource($payment))->additional([
            'success' => true,
            'message' => 'Detail pembayaran berhasil diambil',
        ]);
    }

    /** 
     *  Create new payment
     */
    public function store(StorePaymentRequest $request)
    {
        $student = $this->getStudent($request);

        if ($student->status !== 'submitted') {
            return response()->json([
                'success' => false,
                'message' => 'Pendaftaran belum disubmit.'
            ], 400);
        }

        // Siapkan Items
        $items = $this->preparePaymentItems($request);

        try {
            $result = DB::transaction(function () use ($request, $student, $items) {
                // 1. Hitung Total
                $amount = collect($items)->sum(fn($item) => $item['price'] * $item['quantity']);
                $adminFee = $this->calculateAdminFee($amount);
                
                // 2. Buat Payment
                $payment = Payment::create([
                    'student_id' => $student->id,
                    'order_id' => Payment::generateOrderId(),
                    'payment_type' => $request->payment_type,
                    'amount' => $amount,
                    'admin_fee' => $adminFee,
                    'total_amount' => round($amount + $adminFee, 2),
                    'status' => 'pending',
                ]);

                // 3. Buat Payment Items
                foreach ($items as $item) {
                    $payment->items()->create([
                        'item_name' => $item['item_name'],
                        'item_description' => $item['item_description'] ?? null,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $item['quantity'] * $item['price'],
                    ]);
                }

                // 4. Request Snap Token
                $snapData = $this->midtransService->createSnapToken($payment);
                // 5. Update Database Payment dengan Token
                $payment->update(['snap_token' => $snapData['snap_token']]);

                return [
                    'payment' => $payment->load('items'),
                    'snap' => $snapData
                ];
            });

            return (new PaymentResource($result['payment']))->additional([
                'success' => true,
                'message' => 'Pembayaran berhasil dibuat',
                'data' => [
                    'snap_token' => $result['snap']['snap_token'],
                    'snap_url'   => $result['snap']['snap_url'],
                ]
            ])->response()->setStatusCode(201);

        } catch (\Exception $e) {
            Log::error('Payment Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pembayaran',
            ], 500);
        }
    }

    /**
     * Sync payment status with Midtrans (manual)
     */
    public function syncStatus(Request $request, $id)
    {
        $student = $this->getStudent($request);
        $payment = Payment::where('id', $id)->where('student_id', $student->id)->firstOrFail();

        try {
            // 1. Get Status dari Service
            $status = $this->midtransService->syncPaymentWithMidtrans($payment);
            
            // 2. Parse data notifikasi agar lebih rapi
            $notificationData = $this->parseNotificationData($status, $payment);

            // 3. Update Logic
            DB::transaction(function () use ($payment, $notificationData, $status) {
                // Update Payment Utama
                $payment->update($this->mapPaymentUpdateData($status, $notificationData));

                // Simpan Log Notifikasi
                $payment->notifications()->create([
                    'order_id' => $notificationData['order_id'],
                    'transaction_id' => $notificationData['transaction_id'],
                    'transaction_status' => $notificationData['transaction_status'],
                    'fraud_status' => $notificationData['fraud_status'],
                    'notification_body' => (array) $status,
                    'notification_at' => now(),
                ]);

                // Update Status Siswa jika Lunas
                if (in_array($payment->status, ['settlement', 'capture'])) {
                    $payment->student()->update(['status' => 'paid']);
                }
            });

            return (new PaymentResource($payment->refresh()->load(['items'])))
                ->additional([
                    'success' => true,
                    'message' => 'Status pembayaran berhasil disinkronkan'
                ]);

        } catch (\Exception $e) {
            Log::error('Sync Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal sinkronisasi.'], 500);
        }
    }

    /**
     * Cancel payment
     */
    public function cancel(Request $request, $id)
    {
        $student = $this->getStudent($request);
        $payment = Payment::where('id', $id)->where('student_id', $student->id)->firstOrFail();

        if (!$payment->isPending()) {
            return response()->json(['success' => false, 'message' => 'Pembayaran tidak dapat dibatalkan.'], 400);
        }

        try {
            $this->midtransService->cancel($payment->order_id);
            $payment->update(['status' => 'cancel']);

            return (new PaymentResource($payment))->additional([
                'success' => true, 
                'message' => 'Pembayaran dibatalkan'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal membatalkan.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle Notification Webhook
     */
    public function notification(Request $request)
    {
        try {
            $this->midtransService->handleNotification($request->all());
            return response()->json(['success' => true, 'message' => 'OK']);
        } catch (\Exception $e) {
            Log::error('Webhook Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed'], 500);
        }
    }




    // =========================================================================
    // PRIVATE METHODS (Helpers)
    // =========================================================================

    private function getStudent(Request $request)
    {
        $student = Student::where('user_id', $request->user()->id)->first();
        if (!$student) {
            abort(response()->json(['success' => false, 'message' => 'Data siswa tidak ditemukan'], 404));
        }
        return $student;
    }

    private function preparePaymentItems(Request $request)
    {
        if ($request->payment_type === 'registration_fee') {
            return [
                ['item_name' => 'Biaya Formulir', 'quantity' => 1, 'price' => 100000],
                ['item_name' => 'Biaya Tes Masuk', 'quantity' => 1, 'price' => 150000]
            ];
        } elseif ($request->payment_type === 'uniform_fee') {
            return [
                ['item_name' => 'Seragam Wearpack', 'quantity' => 1, 'price' => 10000],
                ['item_name' => 'Seragam Batik', 'quantity' => 1, 'price' => 10000],
                ['item_name' => 'Seragam Olahraga', 'quantity' => 1, 'price' => 10000]
            ];
        } elseif ($request->payment_type === 'tuition_fee') {
            return [
                ['item_name' => 'SPP Bulanan', 'quantity' => 1, 'price' => 200000],
            ];
        } else {
            return response()->json([
                'status' => false,
                'message' => "Item pembayaran tidak tersedia!",
            ], 404);
        }


        return $request->items;
    }

    private function calculateAdminFee($amount)
    {
        return round(min($amount * 0.02, 5000), 2);
    }

    private function parseNotificationData($status, $payment)
    {
        return [
            'transaction_status' => $status->transaction_status ?? 'pending',
            'transaction_id' => $status->transaction_id ?? null,
            'payment_type' => $status->payment_type ?? 'unknown',
            'order_id' => $status->order_id ?? $payment->order_id,
            'gross_amount' => $status->gross_amount ?? $payment->total_amount,
            'fraud_status' => $status->fraud_status ?? 'accept',
        ];
    }

    private function mapPaymentUpdateData($status, $data)
    {
        $updateData = [
            'transaction_id' => $data['transaction_id'],
            'payment_method' => $data['payment_type'],
            'midtrans_response' => (array) $status,
            'status' => $data['transaction_status'], // Simplifikasi: status midtrans seringkali match dengan DB
        ];

        // Override status mapping jika perlu penyesuaian string
        $statusMap = [
            'capture' => 'paid', // atau settlement
            'settlement' => 'settlement',
            'pending' => 'pending',
            'deny' => 'deny',
            'expire' => 'expire',
            'cancel' => 'cancel',
        ];

        if (isset($statusMap[$data['transaction_status']])) {
            $updateData['status'] = $statusMap[$data['transaction_status']];
        }

        if (in_array($data['transaction_status'], ['capture', 'settlement'])) {
            $updateData['paid_at'] = now();
        }

        // Handle VA / Biller Code
        if (!empty($status->va_numbers)) {
            $updateData['va_number'] = $status->va_numbers[0]->va_number ?? null;
            $updateData['bank'] = $status->va_numbers[0]->bank ?? null;
        } elseif (isset($status->permata_va_number)) {
            $updateData['va_number'] = $status->permata_va_number;
            $updateData['bank'] = 'permata';
        }

        if (isset($status->biller_code)) $updateData['biller_code'] = $status->biller_code;
        if (isset($status->bill_key)) $updateData['bill_key'] = $status->bill_key;

        return $updateData;
    }
}