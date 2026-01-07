<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        $amount = $this->faker->numberBetween(100000, 5000000);
        $adminFee = min($amount * 0.02, 5000);
        $totalAmount = $amount + $adminFee;

        return [
            'student_id' => Student::factory(),
            'order_id' => $this->generateOrderId(),
            'transaction_id' => null,
            'payment_type' => $this->faker->randomElement([
                'registration_fee',
                'tuition_fee',
                'uniform_fee',
                'book_fee',
                'other'
            ]),
            'amount' => $amount,
            'admin_fee' => $adminFee,
            'total_amount' => $totalAmount,
            'payment_method' => null,
            'status' => 'pending',
            'va_number' => null,
            'bank' => null,
            'biller_code' => null,
            'bill_key' => null,
            'snap_token' => $this->faker->uuid(),
            'snap_url' => $this->faker->url(),
            'paid_at' => null,
            'expired_at' => now()->addHours(24),
            'midtrans_response' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'paid_at' => null,
            ];
        });
    }

    public function settlement()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'settlement',
                'transaction_id' => $this->faker->uuid(),
                'payment_method' => $this->faker->randomElement([
                    'bank_transfer',
                    'credit_card',
                    'gopay',
                    'qris',
                    'shopeepay'
                ]),
                'paid_at' => now(),
            ];
        });
    }

    public function capture()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'capture',
                'transaction_id' => $this->faker->uuid(),
                'payment_method' => 'credit_card',
                'paid_at' => now(),
            ];
        });
    }

    public function expired()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'expire',
                'expired_at' => now()->subHours(1),
            ];
        });
    }

    public function cancelled()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancel',
            ];
        });
    }

    public function withBankTransfer()
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_method' => 'bank_transfer',
                'va_number' => $this->faker->numerify('################'),
                'bank' => $this->faker->randomElement(['bca', 'bni', 'bri', 'mandiri', 'permata']),
            ];
        });
    }

    private function generateOrderId()
    {
        $prefix = 'ORD';
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        
        return "{$prefix}-{$date}-{$random}";
    }
}