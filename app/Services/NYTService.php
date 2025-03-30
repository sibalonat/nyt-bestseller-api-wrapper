<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class NYTService
{
    protected $apiKey;
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->apiKey = config('services.nyt.api_key');
    }


    /**
     * Get the best sellers from the NYT API.
     *
     * @param array $params
     * @return Response
     */
    public function getBestSellers(array $params): Response
    {
        return Http::retry(3, 100)
        ->get('https://api.nytimes.com/svc/books/v3/lists/best-sellers/history.json', array_merge($params, ['api-key' => $this->apiKey]));
    }
}
