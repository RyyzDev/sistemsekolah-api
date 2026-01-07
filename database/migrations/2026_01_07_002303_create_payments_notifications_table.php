<?php
// database/migrations/xxxx_create_payment_notifications_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('order_id');
            $table->string('transaction_id')->nullable();
            $table->string('transaction_status');
            $table->string('fraud_status')->nullable();
            
            $table->json('notification_body');
            
            $table->timestamp('notification_at');
            $table->timestamps();
            
            $table->index('order_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_notifications');
    }
};