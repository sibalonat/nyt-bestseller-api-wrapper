<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Jobs\FetchBestSellers;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;

class RateLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    #[Test]
    public function it_checks_endpoint_is_working()
    {
        // Mock Redis to return a valid response
        Redis::shouldReceive('get')
            ->once()
            ->andReturn(json_encode([
                [
                    'title' => 'Test Book',
                    'author' => 'Test Author',
                    'description' => 'Test Description',
                    'publisher' => 'Test Publisher',
                    'isbns' => [['isbn13' => '1234567890123']],
                    'ranks_history' => [['rank' => 1, 'weeks_on_list' => 5]]
                ]
            ]));

        // First make sure the endpoint works at all
        $response = $this->getJson('/api/v1/bestsellers');

        // Diagnose the error if there is one
        if ($response->getStatusCode() != 200) {
            echo "\nResponse body: " . $response->getContent() . "\n";
        }

        $response->assertStatus(200);
        Queue::assertPushed(FetchBestSellers::class);
    }

    #[Test]
    public function it_throttles_requests_when_ip_limit_exceeded()
    {

        // Clear existing rate limiters
        RateLimiter::clear('api');

        Redis::shouldReceive('get')
        ->times(10)
        ->andReturn(json_encode([
            [
                'title' => 'Test Book',
                'author' => 'Test Author',
                'isbns' => [['isbn13' => '1234567890123']],
                'ranks_history' => [['rank' => 1, 'weeks_on_list' => 5]]
            ]
        ]));
        // Mock Redis for each request in the loop
        for ($i = 0; $i < 10; $i++) {
            $response = $this->getJson('/api/v1/bestsellers');
            $response->assertStatus(200);
        }

        // The 11th request should be throttled - no need to mock Redis for this one
        // as it should be intercepted by the rate limiter
        $response = $this->getJson('/api/v1/bestsellers');
        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
        $response->assertJsonPath('message', 'Too Many Attempts.');
    }
}
