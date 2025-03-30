<?php

namespace App\Jobs\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

class LimitJobStart
{
    protected string $key;
    protected int $allowedJobs;
    protected int $timeWindow;
    protected int $releaseAfter;

    public function __construct(string $key, int $allowedJobs = 1, int $timeWindow = 5, int $releaseAfter = 5)
    {
        $this->key = $key;
        $this->allowedJobs = $allowedJobs;
        $this->timeWindow = $timeWindow;
        $this->releaseAfter = $releaseAfter;
    }

    public function handle(object $job, Closure $next): void
    {
        Redis::throttle($this->key)
            ->block(0)
            ->allow($this->allowedJobs)
            ->every($this->timeWindow)
            ->then(function () use ($job, $next) {
                $next($job);
            }, function () use ($job) {
                $job->release($this->releaseAfter);
            });
    }
}
