<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class HealthCheckTest extends TestCase
{
    #[Test]
    public function test_health_check_returns_healthy_status()
    {
        // Mock database and cache checks
        DB::shouldReceive('connection')->once()->andReturnSelf();
        DB::shouldReceive('getPdo')->once()->andReturn(true);

        Redis::shouldReceive('ping')->once()->andReturn('PONG');

        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertJson([
            'database' => true,
            'cache' => true,
            'status' => 'healthy',
        ]);
    }

    #[Test]
    public function test_health_check_returns_unhealthy_status()
    {
        // Mock database failure
        DB::shouldReceive('connection')->once()->andThrow(new \Exception('Database error'));

        // Mock cache success
        Redis::shouldReceive('ping')->once()->andReturn('PONG');

        $response = $this->getJson('/api/health');

        $response->assertStatus(500);
        $response->assertJson([
            'database' => false,
            'cache' => true,
            'status' => 'unhealthy',
        ]);
    }
}
