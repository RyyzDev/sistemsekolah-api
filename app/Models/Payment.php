<?php
// app/Models/Payment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'order_id',
        'transaction_id',
        'payment_type',
        'amount',
        'admin_fee',
        'total_amount',
        'payment_method',
        'status',
        'va_number',
        'bank',
        'biller_code',
        'bill_key',
        'snap_token',
        'snap_url',
        'paid_at',
        'expired_at',
        'midtrans_response',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'midtrans_response' => 'array',
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function items()
    {
        return $this->hasMany(PaymentItem::class);
    }

    public function notifications()
    {
        return $this->hasMany(PaymentNotification::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSettlement($query)
    {
        return $query->where('status', 'settlement');
    }

    public function scopeSuccess($query)
    {
        return $query->whereIn('status', ['settlement', 'capture']);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['deny', 'cancel', 'expire', 'failure']);
    }

    // Helper Methods
    public function generateOrderId()
    {
        $prefix = 'ORD';
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        
        return "{$prefix}-{$date}-{$random}";
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isSuccess()
    {
        return in_array($this->status, ['settlement', 'capture']);
    }

    public function isFailed()
    {
        return in_array($this->status, ['deny', 'cancel', 'expire', 'failure']);
    }

    public function isExpired()
    {
        return $this->expired_at && $this->expired_at->isPast() && $this->status === 'pending';
    }

    public function markAsPaid($transactionId = null)
    {
        $this->update([
            'status' => 'settlement',
            'paid_at' => now(),
            'transaction_id' => $transactionId ?? $this->transaction_id,
        ]);
    }

    public function markAsFailed($status = 'failure')
    {
        $this->update([
            'status' => $status,
        ]);
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Menunggu Pembayaran',
            'settlement' => 'Berhasil',
            'capture' => 'Berhasil',
            'deny' => 'Ditolak',
            'cancel' => 'Dibatalkan',
            'expire' => 'Kadaluarsa',
            'failure' => 'Gagal',
            'refund' => 'Dikembalikan',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'settlement' => 'success',
            'capture' => 'success',
            'deny' => 'danger',
            'cancel' => 'secondary',
            'expire' => 'danger',
            'failure' => 'danger',
            'refund' => 'info',
        ];

        return $colors[$this->status] ?? 'secondary';
    }
}