<?php

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (ThrottleRequestsException $e) {
            return response()->json([
                'message' => 'Slow down! Too many requests.',
                'retry_after' => $e->getHeaders()['Retry-After']
            ], 429);
        });
    }
}
