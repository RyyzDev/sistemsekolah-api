<?php
// app/Services/MidtransService.php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentItem;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;
use Midtrans\Notification;

class MidtransService
{
    public function __construct()
    {
        // Set Midtrans Configuration
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * Create Snap Token for payment
     */
    public function createSnapToken(Payment $payment)
    {
        $items = $payment->items->map(function ($item) {
            return [
                'id' => $item->id,
                'price' => (int) $item->price,
                'quantity' => $item->quantity,
                'name' => $item->item_name,
            ];
        })->toArray();

        $transactionDetails = [
            'order_id' => $payment->order_id,
            'gross_amount' => (int) $payment->total_amount,
        ];

        $customerDetails = [
            'first_name' => $payment->student->full_name,
            'email' => $payment->student->email ?? $payment->student->user->email,
            'phone' => $payment->student->mobile_number ?? $payment->student->phone_number,
        ];

        $params = [
            'transaction_details' => $transactionDetails,
            'customer_details' => $customerDetails,
            'item_details' => $items,
            'enabled_payments' => [
                'credit_card',
                'bca_va',
                'bni_va',
                'bri_va',
                'permata_va',
                'other_va',
                'gopay',
                'shopeepay',
                'qris',
                'indomaret',
                'alfamart',
            ],
            'expiry' => [
                'duration' => 24,
                'unit' => 'hours'
            ],
            'callbacks' => [
                'finish' => config('midtrans.finish_url'),
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            $snapUrl = Snap::createTransaction($params)->redirect_url;

            $payment->update([
                'snap_token' => $snapToken,
                'snap_url' => $snapUrl,
                'expired_at' => now()->addHours(24),
            ]);

            return [
                'snap_token' => $snapToken,
                'snap_url' => $snapUrl,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to create Snap Token: ' . $e->getMessage());
        }
    }

    /**
     * Handle Midtrans notification callback
     */
    public function handleNotification($notificationData)
    {
        try {
            $notification = new Notification();

            $orderId = $notification->order_id;
            $transactionStatus = $notification->transaction_status;
            $fraudStatus = $notification->fraud_status ?? null;
            $transactionId = $notification->transaction_id;
            $paymentType = $notification->payment_type ?? null;

            // Find payment
            $payment = Payment::where('order_id', $orderId)->first();

            if (!$payment) {
                throw new \Exception("Payment with order_id {$orderId} not found");
            }

            // Save notification to database
            $payment->notifications()->create([
                'order_id' => $orderId,
                'transaction_id' => $transactionId,
                'transaction_status' => $transactionStatus,
                'fraud_status' => $fraudStatus,
                'notification_body' => $notificationData,
                'notification_at' => now(),
            ]);

            // Update payment status based on transaction status
            $this->updatePaymentStatus($payment, $transactionStatus, $fraudStatus, $notification);

            return [
                'success' => true,
                'message' => 'Notification handled successfully',
                'payment' => $payment,
            ];

        } catch (\Exception $e) {
            \Log::error('Midtrans Notification Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update payment status based on Midtrans notification
     */
    private function updatePaymentStatus(Payment $payment, $transactionStatus, $fraudStatus, $notification)
    {
        $updateData = [
            'transaction_id' => $notification->transaction_id,
            'payment_method' => $notification->payment_type ?? $payment->payment_method,
            'midtrans_response' => (array) $notification,
        ];

        // Handle VA Number
        if (isset($notification->va_numbers) && count($notification->va_numbers) > 0) {
            $updateData['va_number'] = $notification->va_numbers[0]->va_number;
            $updateData['bank'] = $notification->va_numbers[0]->bank;
        } elseif (isset($notification->permata_va_number)) {
            $updateData['va_number'] = $notification->permata_va_number;
            $updateData['bank'] = 'permata';
        }

        // Handle Biller Code & Bill Key (for Mandiri)
        if (isset($notification->biller_code)) {
            $updateData['biller_code'] = $notification->biller_code;
        }
        if (isset($notification->bill_key)) {
            $updateData['bill_key'] = $notification->bill_key;
        }

        // Update status based on transaction_status
        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'accept') {
                $updateData['status'] = 'capture';
                $updateData['paid_at'] = now();
            }
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

        // Update student status if payment successful
        if (in_array($updateData['status'], ['settlement', 'capture'])) {
            $payment->student->update(['status' => 'accepted']);
        }
    }

    /**
     * Check payment status from Midtrans
     */
    public function checkStatus($orderId)
    {
        try {
            $status = Transaction::status($orderId);
            return $status;
        } catch (\Exception $e) {
            throw new \Exception('Failed to check payment status: ' . $e->getMessage());
        }
    }

    /**
     * Cancel transaction
     */
    public function cancel($orderId)
    {
        try {
            $result = Transaction::cancel($orderId);
            return $result;
        } catch (\Exception $e) {
            throw new \Exception('Failed to cancel transaction: ' . $e->getMessage());
        }
    }

    /**
     * Expire transaction
     */
    public function expire($orderId)
    {
        try {
            $result = Transaction::expire($orderId);
            return $result;
        } catch (\Exception $e) {
            throw new \Exception('Failed to expire transaction: ' . $e->getMessage());
        }
    }

    /**
     * Refund transaction
     */
    public function refund($orderId, $amount = null, $reason = null)
    {
        try {
            $params = [];
            if ($amount) {
                $params['amount'] = (int) $amount;
            }
            if ($reason) {
                $params['reason'] = $reason;
            }

            $result = Transaction::refund($orderId, $params);
            return $result;
        } catch (\Exception $e) {
            throw new \Exception('Failed to refund transaction: ' . $e->getMessage());
        }
    }
}