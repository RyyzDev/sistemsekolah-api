<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'order_id' => $this->order_id,
            'transaction_id' => $this->transaction_id,
            'payment_type' => $this->payment_type,
            'amount' => $this->amount,
            'admin_fee' => $this->admin_fee,
            'total_amount' => $this->total_amount,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            
            // Virtual Account & Billing
            'va_number' => $this->va_number,
            'bank' => $this->bank,
            'biller_code' => $this->biller_code,
            'bill_key' => $this->bill_key,
            
            // Snap Info
            'snap_token' => $this->snap_token,
            // 'snap_url' => $this->snap_url,
            
            // Dates
            'paid_at' => $this->paid_at,
            // 'expired_at' => $this->expired_at,
            
            // JSON Data 
            // 'midtrans_response' => $this->midtrans_response,
            
            // 'notes' => $this->notes,
            'created_at' => $this->created_at->format('d-m-Y H:i'),
            'updated_at' => $this->updated_at->format('d-m-Y H:i'),

            // RELATIONSHIPS
            'items' => PaymentItemResource::collection($this->whenLoaded('items')),
            'notifications' => PaymentNotificationResource::collection($this->whenLoaded('notifications')),
        ];
    }
}