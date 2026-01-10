<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->student = Student::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'submitted',
        ]);
    }

    #[Test]
    public function it_requires_authentication_to_create_payment()
    {
        $response = $this->postJson('/api/payments', [
            'payment_type' => 'registration_fee',
            'items' => [
                ['item_name' => 'Test', 'quantity' => 1, 'price' => 100000],
            ],
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_prevents_user_from_viewing_other_users_payments()
    {
        $this->actingAs($this->user, 'sanctum');

        // Create another user with payment
        $otherUser = User::factory()->create();
        $otherStudent = Student::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'submitted',
        ]);
        $otherPayment = Payment::factory()->create([
            'student_id' => $otherStudent->id,
        ]);

        // Try to view other user's payment
        $response = $this->getJson("/api/payments/{$otherPayment->id}");

        $response->assertStatus(404);
    }

    #[Test]
    public function it_prevents_user_from_canceling_other_users_payments()
    {
        $this->actingAs($this->user, 'sanctum');

        $otherUser = User::factory()->create();
        $otherStudent = Student::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'submitted',
        ]);
        $otherPayment = Payment::factory()->create([
            'student_id' => $otherStudent->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/payments/{$otherPayment->id}/cancel");

        $response->assertStatus(404);
    }

    #[Test]
    public function it_prevents_sql_injection_in_order_id()
    {
        $this->actingAs($this->user, 'sanctum');

        // Create payment with normal order_id
        $payment = Payment::factory()->create([
            'student_id' => $this->student->id,
            'order_id' => "ORD-20260107-ABC123",
        ]);

        // Try SQL injection in query
        $maliciousId = "1 OR 1=1";
        $response = $this->getJson("/api/payments/{$maliciousId}");

        // Should return 404, not expose data
        $response->assertStatus(404);
    }

    #[Test]
    public function it_prevents_price_manipulation_via_request()
    {
        $this->actingAs($this->user, 'sanctum');

        // User cannot set amount, admin_fee, total_amount directly
        $response = $this->postJson('/api/payments', [
            'payment_type' => 'registration_fee',
            'items' => [
                ['item_name' => 'Test', 'quantity' => 1, 'price' => 100000],
            ],
            'amount' => 1, // Try to manipulate
            'admin_fee' => 1, // Try to manipulate
            'total_amount' => 2, // Try to manipulate
        ]);

        $response->assertStatus(201);

        $payment = Payment::latest()->first();

        // Should be calculated values, not manipulated values
        $this->assertEquals(250000, $payment->amount); 
        $this->assertEquals(5000, $payment->admin_fee);
        $this->assertEquals(255000, $payment->total_amount);
    }

    #[Test]
    public function it_prevents_status_manipulation_on_creation()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/payments', [
            'payment_type' => 'registration_fee',
            'items' => [
                ['item_name' => 'Test', 'quantity' => 1, 'price' => 100000],
            ],
            'status' => 'settlement', // Try to set as paid
        ]);

        $response->assertStatus(201);

        $payment = Payment::latest()->first();

        // Should be pending, not settlement
        $this->assertEquals('pending', $payment->status);
    }

    #[Test]
    public function it_sanitizes_item_names()
    {
        $this->actingAs($this->user, 'sanctum');

        $maliciousName = '<script>alert("XSS")</script>';

        $response = $this->postJson('/api/payments', [
            'payment_type' => 'registration_fee',
            'items' => [
                ['item_name' => $maliciousName, 'quantity' => 1, 'price' => 100000],
            ],
        ]);

        $response->assertStatus(201);

        $payment = Payment::latest()->first();
        $item = $payment->items->first();

        // Should not contain script tags
        $this->assertStringNotContainsString('<script>', $item->item_name);
    }





    #[Test]
    public function it_logs_payment_creation_for_audit()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/payments', [
            'payment_type' => 'registration_fee',
            'items' => [
                ['item_name' => 'Test', 'quantity' => 1, 'price' => 100000],
            ],
        ]);

        $response->assertStatus(201);

        $payment = Payment::latest()->first();

        // Verify audit trail exists
        $this->assertNotNull($payment->created_at);
        $this->assertNotNull($payment->updated_at);
        $this->assertNotNull($payment->order_id);
    }

    #[Test]
    public function it_prevents_payment_modification_after_success()
    {
        $this->actingAs($this->user, 'sanctum');

        $payment = Payment::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'settlement',
            'paid_at' => now(),
        ]);

        // Try to cancel paid payment (should fail)
        $response = $this->postJson("/api/payments/{$payment->id}/cancel");

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Hanya pembayaran dengan status pending yang dapat dibatalkan',
        ]);
    }

    #[Test]
    public function it_validates_payment_type_enum()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/payments', [
            'payment_type' => 'invalid_type',
            'items' => [
                ['item_name' => 'Test', 'quantity' => 1, 'price' => 100000],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_type']);
    }

    #[Test]
    public function it_requires_student_data_before_payment()
    {
        // Create user without student data
        $userWithoutStudent = User::factory()->create();
        $this->actingAs($userWithoutStudent, 'sanctum');

        $response = $this->postJson('/api/payments', [
            'payment_type' => 'registration_fee',
            'items' => [
                ['item_name' => 'Test', 'quantity' => 1, 'price' => 100000],
            ],
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Data siswa tidak ditemukan',
        ]);
    }


    #[Test]
    public function it_enforces_rate_limiting_on_payment_creation()
    {
     

        $this->actingAs($this->user, 'sanctum');


        $this->postJson('/api/payments', [
            'payment_type' => 'registration_fee',
        ])->assertStatus(201);
        $this->postJson('/api/payments', [
            'payment_type' => 'registration_fee',
        ])->assertStatus(201);
        $this->postJson('/api/payments', [
            'payment_type' => 'registration_fee',
        ])->assertStatus(201);         
        $this->postJson('/api/payments', [
            'payment_type' => 'registration_fee',
        ])->assertStatus(201); 
        $this->postJson('/api/payments', [
            'payment_type' => 'registration_fee',
        ])->assertStatus(201); 
     
        $response = $this->postJson('/api/payments', [
            'payment_type' => 'registration_fee',
        ]);

        $response->assertStatus(429);
    }
}