<?php
// database/migrations/xxxx_create_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            
            // Order Info
            $table->string('order_id')->unique();
            $table->string('transaction_id')->nullable();
            
            // Payment Details
            $table->enum('payment_type', ['registration_fee', 'tuition_fee', 'uniform_fee', 'book_fee', 'other'])->default('registration_fee');
            $table->decimal('amount', 12, 2);
            $table->decimal('admin_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            
            // Midtrans Data
            $table->string('payment_method')->nullable();
            $table->enum('status', [
                'pending',
                'settlement',
                'capture',
                'deny',
                'cancel',
                'expire',
                'failure',
                'refund'
            ])->default('pending');
            
            $table->string('va_number')->nullable();
            $table->string('bank')->nullable();
            $table->string('biller_code')->nullable();
            $table->string('bill_key')->nullable();
            
            // Snap Token for payment page
            $table->text('snap_token')->nullable();
            $table->text('snap_url')->nullable();
            
            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            
            // Midtrans Response
            $table->json('midtrans_response')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('order_id');
            $table->index('transaction_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};