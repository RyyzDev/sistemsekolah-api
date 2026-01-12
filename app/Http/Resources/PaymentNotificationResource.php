<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'order_id' => $this->order_id,
            'transaction_id' => $this->transaction_id,
            'transaction_status' => $this->transaction_status,
            'fraud_status' => $this->fraud_status,
            
            // Casting model
            'notification_body' => $this->notification_body, 
            'notification_at' => $this->notification_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}