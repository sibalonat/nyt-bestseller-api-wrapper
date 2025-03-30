<?php

namespace App\Jobs;

use App\Services\NYTService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Jobs\Middleware\LimitJobStart;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class FetchBestSellers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $params;
    protected $cacheKey;

    /**
     * Create a new job instance.
     */
    public function __construct(array $params, string $cacheKey)
    {
        $this->params = $params;
        $this->cacheKey = $cacheKey;
    }

    /**
     * Execute the job.
     */
    public function handle(NYTService $nytService): void
    {
        $data = $nytService->getBestSellers($this->params)->json();
        Redis::set($this->cacheKey, json_encode($data['results']), 3600);
    }

    public function middleware(): array
    {
        return [
            new LimitJobStart(
                key: 'nyt_'.md5(json_encode($this->params)),
                allowedJobs: 1,
                timeWindow: 5,
                releaseAfter: 5
            )
        ];
    }
}
