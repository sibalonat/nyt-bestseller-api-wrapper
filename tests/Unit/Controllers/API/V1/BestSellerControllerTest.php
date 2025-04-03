<?php

namespace Tests\Unit\Controllers\API\V1;

use Mockery;
use Tests\TestCase;
use App\Jobs\FetchBestSellers;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use App\Http\Requests\NTBooks\BestSellerRequest;
use App\Http\Controllers\API\V1\BestSellerController;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BestSellerControllerTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_returns_bestseller_collection_when_data_exists_in_redis()
    {
        // Arrange
        Queue::fake();

        $mockRequestData = ['list' => 'hardcover-fiction'];
        $cacheKey = 'bestsellers_' . md5(json_encode($mockRequestData));

        $mockBookData = [
            [
                'title' => 'Test Book',
                'author' => 'Test Author',
                'description' => 'Test Description',
                'publisher' => 'Test Publisher',
                'isbns' => [['isbn13' => '1234567890123']],
                'ranks_history' => [['rank' => 1, 'weeks_on_list' => 5]]
            ]
        ];

        // Mock Redis to return cached data
        Redis::shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andReturn(json_encode($mockBookData));

        // Mock the request
        $request = Mockery::mock(BestSellerRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($mockRequestData);

        // Act
        $controller = new BestSellerController();
        $response = $controller->index($request);

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $response);
        $responseData = $response->response()->getData(true)['data'];
        $this->assertCount(1, $responseData);
        $this->assertEquals('Test Book', $responseData[0]['title']);
        $this->assertEquals('Test Author', $responseData[0]['author']);
        $this->assertEquals('1234567890123', $responseData[0]['isbn']);
        $this->assertEquals('Test Description', $responseData[0]['description']);
        $this->assertEquals('Test Publisher', $responseData[0]['publisher']);
        $this->assertEquals(1, $responseData[0]['rank']);
        $this->assertEquals(5, $responseData[0]['weeks_on_list']);
        $this->assertArrayHasKey('created_at', $responseData[0]);

        // Verify job was dispatched
        Queue::assertPushed(FetchBestSellers::class);
    }

    #[Test]
    public function it_returns_processing_response_when_no_data_in_redis()
    {
        // Arrange
        Queue::fake();

        $mockRequestData = ['list' => 'hardcover-fiction'];
        $cacheKey = 'bestsellers_' . md5(json_encode($mockRequestData));

        // Mock Redis to return null (no cached data)
        Redis::shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andReturn(null);

        // Mock the request
        $request = Mockery::mock(BestSellerRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($mockRequestData);

        // Act
        $controller = new BestSellerController();
        $response = $controller->index($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(202, $response->getStatusCode());
        $this->assertEquals(['message' => 'Data is being processed'], $response->getData(true));

        // Verify job was dispatched
        Queue::assertPushed(FetchBestSellers::class);
    }

    #[Test]
    public function it_handles_exceptions_and_tries_to_get_cached_data()
    {
        // Arrange
        Queue::fake();

        $mockRequestData = ['list' => 'hardcover-fiction'];
        $cacheKey = 'bestsellers_' . md5(json_encode($mockRequestData));

        // Mock Redis to throw exception first, then return data on second call
        Redis::shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andThrow(new \Exception('Connection error'));

        $mockBookData = [
            [
                'title' => 'Test Book',
                'author' => 'Test Author',
                'description' => 'Test Description',
                'publisher' => 'Test Publisher',
                'isbns' => [['isbn13' => '1234567890123']],
                'ranks_history' => [['rank' => 1, 'weeks_on_list' => 5]]
            ]
        ];

        Redis::shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andReturn(json_encode($mockBookData));

        // Mock the request
        $request = Mockery::mock(BestSellerRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($mockRequestData);

        // Act
        $controller = new BestSellerController();
        $response = $controller->index($request);

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $response);

        // Verify job was dispatched
        Queue::assertPushed(FetchBestSellers::class);
    }

    #[Test]
    public function it_returns_error_response_when_exception_and_no_cached_data()
    {
        // Arrange
        Queue::fake();

        $mockRequestData = ['list' => 'hardcover-fiction'];
        $cacheKey = 'bestsellers_' . md5(json_encode($mockRequestData));

        // Mock Redis to throw exception first, then return null on second call
        Redis::shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andThrow(new \Exception('Connection error'));

        Redis::shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andReturn(null);

        // Mock the request
        $request = Mockery::mock(BestSellerRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($mockRequestData);

        // Act
        $controller = new BestSellerController();
        $response = $controller->index($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals(['error' => 'Unable to fetch data'], $response->getData(true));

        // Verify job was dispatched
        Queue::assertPushed(FetchBestSellers::class);
    }

    // #[Test]
    #[Test]
    public function it_fails_validation_for_invalid_author()
    {
        // Arrange
        $invalidData = ['author' => str_repeat('a', 256)];

        // Act
        $response = $this->getJson('/api/v1/bestsellers?' . http_build_query($invalidData));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['author']);
    }

    #[Test]
    public function it_fails_validation_for_invalid_isbn()
    {
        // Arrange
        $invalidData = ['isbn' => ''];

        // Act
        $response = $this->getJson('/api/v1/bestsellers?' . http_build_query($invalidData));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['isbn']);
    }

    #[Test]
    public function it_fails_validation_for_isbn_too_short()
    {
        // Arrange
        $invalidData = ['isbn' => '12345'];

        // Act
        $response = $this->getJson('/api/v1/bestsellers?' . http_build_query($invalidData));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['isbn']);
    }

    #[Test]
    public function it_fails_validation_for_title_too_long()
    {
        // Arrange
        $invalidData = ['title' => str_repeat('a', 256)];

        // Act
        $response = $this->getJson('/api/v1/bestsellers?' . http_build_query($invalidData));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    #[Test]
    public function it_fails_validation_for_negative_offset()
    {
        // Arrange
        $invalidData = ['offset' => -1];

        // Act
        $response = $this->getJson('/api/v1/bestsellers?' . http_build_query($invalidData));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['offset']);
    }

    #[Test]
    public function it_fails_validation_for_invalid_age_group_characters()
    {
        // Arrange
        $invalidData = ['age-group' => '123!@#'];

        // Act
        $response = $this->getJson('/api/v1/bestsellers?' . http_build_query($invalidData));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['age-group']);
    }

    #[Test]
    public function it_fails_validation_for_invalid_price_format()
    {
        // Arrange
        $invalidData = ['price' => 'abc123'];

        // Act
        $response = $this->getJson('/api/v1/bestsellers?' . http_build_query($invalidData));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['price']);
    }

    #[Test]
    public function it_fails_validation_for_publisher_too_long()
    {
        // Arrange
        $invalidData = ['publisher' => str_repeat('a', 256)];

        // Act
        $response = $this->getJson('/api/v1/bestsellers?' . http_build_query($invalidData));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['publisher']);
    }

    #[Test]
    public function it_fails_validation_for_contributor_too_long()
    {
        // Arrange
        $invalidData = ['contributor' => str_repeat('a', 256)];

        // Act
        $response = $this->getJson('/api/v1/bestsellers?' . http_build_query($invalidData));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['contributor']);
    }

    #[Test]
    public function it_passes_validation_for_valid_request_data()
    {
        // Arrange
        $validData = [
            'author' => 'John Doe',
            'isbn' => '1234567890',
            'title' => 'Test Title',
        ];

        $cacheKey = 'bestsellers_' . md5(json_encode($validData));

        // Mock Redis to return valid data
        $mockBookData = [
            [
                'title' => 'Test Book',
                'author' => 'John Doe',
                'description' => 'Test Description',
                'publisher' => 'Test Publisher',
                'isbns' => [['isbn13' => '1234567890123']],
                'ranks_history' => [['rank' => 1, 'weeks_on_list' => 5]],
            ]
        ];

        Redis::shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andReturn(json_encode($mockBookData));

        // Act
        $response = $this->getJson('/api/v1/bestsellers?' . http_build_query($validData));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonMissingValidationErrors();
        $response->assertJsonFragment(['title' => 'Test Book']);
    }
}
