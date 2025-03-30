<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     title="NYT API Wrapper",
 *     version="1.0.0",
 *     description="API documentation for the NYT API Wrapper"
 * )
 */
class HealthCheckController extends Controller
{
        /**
     * @OA\Get(
     *     path="/api/health",
     *     tags={"Health"},
     *     summary="Check application health",
     *     description="Returns the health status of the application, including database and cache connectivity.",
     *     @OA\Response(
     *         response=200,
     *         description="Healthy",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="database", type="boolean", example=true),
     *             @OA\Property(property="cache", type="boolean", example=true),
     *             @OA\Property(property="status", type="string", example="healthy")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unhealthy",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="database", type="boolean", example=false),
     *             @OA\Property(property="cache", type="boolean", example=true),
     *             @OA\Property(property="status", type="string", example="unhealthy")
     *         )
     *     )
     * )
     */
    public function check(): JsonResponse
    {
        $status = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'status' => 'healthy',
        ];

        if (in_array(false, $status, true)) {
            $status['status'] = 'unhealthy';
            return response()->json($status, 500);
        }

        return response()->json($status, 200);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
