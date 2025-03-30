<?php

namespace App\Http\Controllers\API\V1;

use App\Jobs\FetchBestSellers;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use App\Http\Resources\NTB\BestSellerResource;
use App\Http\Requests\NTBooks\BestSellerRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BestSellerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/bestsellers",
     *     tags={"BestSellers"},
     *     summary="Get bestsellers",
     *     description="Fetch a list of bestsellers based on the provided filters.",
     *     @OA\Parameter(
     *         name="author",
     *         in="query",
     *         description="Filter by author",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="isbn",
     *         in="query",
     *         description="Filter by ISBN",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="title",
     *         in="query",
     *         description="Filter by title",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Pagination offset",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="age-group",
     *         in="query",
     *         description="Filter by age group",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="price",
     *         in="query",
     *         description="Filter by price",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="publisher",
     *         in="query",
     *         description="Filter by publisher",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="contributor",
     *         in="query",
     *         description="Filter by contributor",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(type="array", @OA\Items(type="object"))
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     * Handle the incoming request to fetch best sellers.
     * @param BestSellerRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(BestSellerRequest $request) : AnonymousResourceCollection | JsonResponse
    {
        $params = $request->validated();
        $cacheKey = 'bestsellers_' . md5(json_encode($params));

        try {
            FetchBestSellers::dispatch($params, $cacheKey);

            $data = Redis::get($cacheKey);

            $DcData = json_decode($data, true);

            if ($data) {
                return BestSellerResource::collection(collect($DcData));
            } else {
                return response()->json(['message' => 'Data is being processed'], 202);
            }
        } catch (\Exception $e) {
            $data = Redis::get($cacheKey);
            $DcData = json_decode($data, true);

            if ($data) {
                return BestSellerResource::collection(collect($DcData));
            } else {
                return response()->json(['error' => 'Unable to fetch data'], 500);
            }
        }
    }
}
