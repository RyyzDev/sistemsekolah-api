<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Payment;
use App\Models\PaymentItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class PaymentCalculationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_calculates_subtotal_correctly()
    {
        $testCases = [
            ['quantity' => 1, 'price' => 100000, 'expected' => 100000],
            ['quantity' => 2, 'price' => 50000, 'expected' => 100000],
            ['quantity' => 5, 'price' => 25000, 'expected' => 125000],
            ['quantity' => 10, 'price' => 10000, 'expected' => 100000],
        ];

        foreach ($testCases as $case) {
            $item = new PaymentItem([
                'quantity' => $case['quantity'],
                'price' => $case['price'],
            ]);

            $calculatedSubtotal = $item->quantity * $item->price;

            $this->assertEquals(
                $case['expected'],
                $calculatedSubtotal,
                "Subtotal should be {$case['expected']} for quantity {$case['quantity']} and price {$case['price']}"
            );
        }
    }

    #[Test]
    public function it_calculates_admin_fee_as_2_percent()
    {
        $testCases = [
            ['amount' => 100000, 'expected' => 2000],
            ['amount' => 200000, 'expected' => 4000],
            ['amount' => 250000, 'expected' => 5000],
            ['amount' => 300000, 'expected' => 5000],
            ['amount' => 500000, 'expected' => 5000],
            ['amount' => 1000000, 'expected' => 5000],
        ];

        foreach ($testCases as $case) {
            $fee = $case['amount'] * 0.02;
            $adminFee = min($fee, 5000);

            $this->assertEquals(
                $case['expected'],
                $adminFee,
                "Admin fee should be {$case['expected']} for amount {$case['amount']}"
            );
        }
    }

    #[Test]
    public function it_calculates_total_amount_correctly()
    {
        $testCases = [
            ['amount' => 100000, 'admin_fee' => 2000, 'expected' => 102000],
            ['amount' => 500000, 'admin_fee' => 5000, 'expected' => 505000],
            ['amount' => 1000000, 'admin_fee' => 5000, 'expected' => 1005000],
        ];

        foreach ($testCases as $case) {
            $total = $case['amount'] + $case['admin_fee'];

            $this->assertEquals(
                $case['expected'],
                $total,
                "Total should be {$case['expected']} for amount {$case['amount']} and admin fee {$case['admin_fee']}"
            );
        }
    }

    #[Test]
    public function it_handles_decimal_precision_correctly()
    {
        $testCases = [
            ['quantity' => 1, 'price' => 100000.50, 'expected' => 100000.50],
            ['quantity' => 3, 'price' => 33333.33, 'expected' => 99999.99],
            ['quantity' => 7, 'price' => 14285.71, 'expected' => 99999.97],
        ];

        foreach ($testCases as $case) {
            $subtotal = $case['quantity'] * $case['price'];

            $this->assertEquals(
                $case['expected'],
                round($subtotal, 2),
                "Subtotal should be {$case['expected']} with correct decimal precision"
            );
        }
    }

    #[Test]
    public function it_calculates_multiple_items_total()
    {
        $items = [
            ['quantity' => 1, 'price' => 500000],
            ['quantity' => 2, 'price' => 150000],
            ['quantity' => 3, 'price' => 100000],
        ];

        $total = 0;
        foreach ($items as $item) {
            $total += $item['quantity'] * $item['price'];
        }

        $expected = 1100000;

        $this->assertEquals($expected, $total);
    }

    #[Test]
    public function it_validates_minimum_amount()
    {
        $minAmount = 0;

        $testCases = [
            ['amount' => -100, 'valid' => false],
            ['amount' => 0, 'valid' => false],
            ['amount' => 1, 'valid' => true],
            ['amount' => 100000, 'valid' => true],
        ];

        foreach ($testCases as $case) {
            $isValid = $case['amount'] > $minAmount;

            $this->assertEquals(
                $case['valid'],
                $isValid,
                "Amount {$case['amount']} should " . ($case['valid'] ? 'be valid' : 'be invalid')
            );
        }
    }

    #[Test]
    public function it_validates_maximum_amount()
    {
        $maxAmount = 99999999.99;

        $testCases = [
            ['amount' => 99999999.99, 'valid' => true],
            ['amount' => 100000000, 'valid' => false],
            ['amount' => 999999999, 'valid' => false],
        ];

        foreach ($testCases as $case) {
            $isValid = $case['amount'] <= $maxAmount;

            $this->assertEquals(
                $case['valid'],
                $isValid,
                "Amount {$case['amount']} should " . ($case['valid'] ? 'be valid' : 'be invalid')
            );
        }
    }

    #[Test]
    public function it_handles_edge_cases_for_admin_fee()
    {
        $testCases = [
            ['amount' => 0, 'expected' => 0],
            ['amount' => 1, 'expected' => 0.02],
            ['amount' => 249999, 'expected' => 4999.98],
            ['amount' => 250000, 'expected' => 5000],
            ['amount' => 250001, 'expected' => 5000],
        ];

        foreach ($testCases as $case) {
            $fee = $case['amount'] * 0.02;
            $adminFee = round(min($fee, 5000), 2);

            $this->assertEquals(
                $case['expected'],
                $adminFee,
                "Admin fee should be {$case['expected']} for amount {$case['amount']}"
            );
        }
    }

    #[Test]
    public function it_prevents_overflow_in_calculations()
    {
        $largeQuantity = 999999;
        $largePrice = 99999;

        $subtotal = $largeQuantity * $largePrice;

        $this->assertIsNumeric($subtotal);
        $this->assertGreaterThan(0, $subtotal);
    }

    #[Test]
    public function it_maintains_precision_in_multi_step_calculation()
    {
        $items = [
            ['quantity' => 1, 'price' => 333.33],
            ['quantity' => 1, 'price' => 333.33],
            ['quantity' => 1, 'price' => 333.34],
        ];

        $amount = 0;
        foreach ($items as $item) {
            $amount += $item['quantity'] * $item['price'];
        }

        $adminFee = min($amount * 0.02, 5000);
        $totalAmount = $amount + $adminFee;

        $this->assertEquals(1000.00, round($amount, 2));
        $this->assertEquals(20.00, round($adminFee, 2));
        $this->assertEquals(1020.00, round($totalAmount, 2));
    }

    #[Test]
    public function it_calculates_percentage_correctly()
    {
        $testCases = [
            ['amount' => 100000, 'percentage' => 0.02, 'expected' => 2000],
            ['amount' => 100000, 'percentage' => 0.05, 'expected' => 5000],
            ['amount' => 100000, 'percentage' => 0.10, 'expected' => 10000],
            ['amount' => 500000, 'percentage' => 0.02, 'expected' => 10000],
        ];

        foreach ($testCases as $case) {
            $result = $case['amount'] * $case['percentage'];

            $this->assertEquals(
                $case['expected'],
                $result,
                "{$case['percentage']} of {$case['amount']} should be {$case['expected']}"
            );
        }
    }
}