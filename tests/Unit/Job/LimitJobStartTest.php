<?php

namespace Tests\Unit\Jobs\Middleware;

use App\Jobs\Middleware\LimitJobStart;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LimitJobStartTest extends TestCase
{
    #[Test]
    public function test_middleware_allows_job_to_start_when_not_throttled()
    {
        // Arrange
        $job = Mockery::mock('stdClass');
        $job->shouldNotReceive('release'); // The job should not be released

        Redis::shouldReceive('throttle')
            ->once()
            ->with('test_key')
            ->andReturnSelf()
            ->shouldReceive('block')
            ->once()
            ->with(0)
            ->andReturnSelf()
            ->shouldReceive('allow')
            ->once()
            ->with(1)
            ->andReturnSelf()
            ->shouldReceive('every')
            ->once()
            ->with(5)
            ->andReturnSelf()
            ->shouldReceive('then')
            ->once()
            ->with(
                Mockery::on(function ($callback) use ($job) {
                    // Simulate the allowed behavior by calling the success callback
                    $callback($job);
                    return true;
                }),
                Mockery::on(function ($fallback) {
                    // The fallback should not be called in this case
                    return true;
                })
            );

        $middleware = new LimitJobStart(
            key: 'test_key',
            allowedJobs: 1,
            timeWindow: 5,
            releaseAfter: 5
        );

        // Act
        $middleware->handle($job, function ($job) {
            // Assert
            $this->assertTrue(true, 'The job was allowed to start.');
        });
    }

    #[Test]
    public function test_middleware_releases_job_when_throttled()
    {
        // Arrange
        $job = Mockery::mock('stdClass');
        $job->shouldReceive('release')->once()->with(5); // The job should be released

        Redis::shouldReceive('throttle')
            ->once()
            ->with('test_key')
            ->andReturnSelf()
            ->shouldReceive('block')
            ->once()
            ->with(0)
            ->andReturnSelf()
            ->shouldReceive('allow')
            ->once()
            ->with(1)
            ->andReturnSelf()
            ->shouldReceive('every')
            ->once()
            ->with(5)
            ->andReturnSelf()
            ->shouldReceive('then')
            ->once()
            ->with(
                Mockery::on(function ($callback) {
                    // The success callback should not be called in this case
                    return true;
                }),
                Mockery::on(function ($fallback) use ($job) {
                    // Simulate the throttled behavior by calling the fallback
                    $fallback($job);
                    return true;
                })
            );

        $middleware = new LimitJobStart(
            key: 'test_key',
            allowedJobs: 1,
            timeWindow: 5,
            releaseAfter: 5
        );

        // Act
        $middleware->handle($job, function () {
            // This should not be called because the job is throttled
            $this->fail('The job should have been throttled and released.');
        });
    }
}
