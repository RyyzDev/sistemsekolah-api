<?php
// app/Models/PaymentNotification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'order_id',
        'transaction_id',
        'transaction_status',
        'fraud_status',
        'notification_body',
        'notification_at',
    ];

    protected $casts = [
        'notification_body' => 'array',
        'notification_at' => 'datetime',
    ];

    // Relationships
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}