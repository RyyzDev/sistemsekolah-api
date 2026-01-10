<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Services\MidtransService;
use Mockery\MockInterface;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

    }

    protected function mockMidtrans()
    {
        $this->mock(MidtransService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createSnapToken')
                ->andReturn([
                    'snap_token' => 'mock-snap-token-123',
                    'snap_url' => 'https://example.com'
                ]);
        });
    }
}
