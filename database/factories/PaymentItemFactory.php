<?php

namespace Database\Factories;

use App\Models\PaymentItem;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentItemFactory extends Factory
{
    protected $model = PaymentItem::class;

    public function definition()
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $price = $this->faker->numberBetween(50000, 1000000);
        $subtotal = $quantity * $price;

        return [
            'payment_id' => Payment::factory(),
            'item_name' => $this->faker->randomElement([
                'Biaya Pendaftaran',
                'SPP Bulan Juli',
                'Seragam Putih Abu-Abu',
                'Seragam Olahraga',
                'Buku Paket Semester 1',
                'Biaya Lab Komputer',
                'Biaya Kegiatan Ekstrakurikuler',
                'Biaya Ujian',
                'Biaya LDKS',
                'Biaya Study Tour',
            ]),
            'item_description' => $this->faker->optional()->sentence(),
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $subtotal,
        ];
    }
}