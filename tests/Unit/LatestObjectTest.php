<?php

namespace Tests\Unit;

use App\Contracts\IObjectService;
use App\Http\Controllers\Api\V1\ObjectController;
use App\Http\Requests\ObjectShowValidationRequest;
use App\Models\ObjectStore;
use App\Services\ObjectService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Mockery;
use Tests\TestCase;

class LatestObjectTest extends TestCase
{
    use RefreshDatabase;

    protected ObjectController $controller;

    protected IObjectService $mockService;

    protected ObjectService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockService = Mockery::mock(IObjectService::class);
        $this->controller = new ObjectController($this->mockService);
        $this->service = new ObjectService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_show_returns_correct_response_structure(): void
    {
        // Arrange
        $key = 'test_show_key';
        $mockObjectStore = new ObjectStore([
            'key' => $key,
            'value' => ['data' => 'test'],
        ]);
        $mockObjectStore->id = 1;

        $this->mockService
            ->shouldReceive('findLatestByKey')
            ->once()
            ->with($key)
            ->andReturn($mockObjectStore);

        $mockRequest = Mockery::mock(ObjectShowValidationRequest::class);
        $mockRequest->shouldReceive('validated')
            ->once()
            ->andReturn(['key' => $key]);

        // Act
        $response = $this->controller->show($mockRequest);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = $response->getData(true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Resource retrieved successfully', $responseData['message']);
        $this->assertArrayHasKey('data', $responseData);
    }

    /**
     * Test service findLatestByKey throws exception for non-existent key.
     */
    public function test_service_find_latest_by_key_throws_exception_for_non_existent_key(): void
    {
        // Arrange
        $nonExistentKey = 'non_existent_key_12345';

        // Assert & Act
        $this->expectException(ModelNotFoundException::class);
        $this->service->findLatestByKey($nonExistentKey);
    }

    /**
     * Test service findLatestByKey returns latest when multiple records exist.
     */
    public function test_service_find_latest_by_key_returns_latest_of_multiple(): void
    {
        // Arrange
        $key = 'multiple_records_key';

        // Create 3 records with the same key
        $first = ObjectStore::create([
            'key' => $key,
            'value' => 'first',
            'created_at_timestamp' => now()->timestamp - 200,
        ]);

        $second = ObjectStore::create([
            'key' => $key,
            'value' => 'second',
            'created_at_timestamp' => now()->timestamp - 100,
        ]);

        $third = ObjectStore::create([
            'key' => $key,
            'value' => 'third',
            'created_at_timestamp' => now()->timestamp,
        ]);

        // Act
        $result = $this->service->findLatestByKey($key);

        // Assert
        $this->assertEquals($third->id, $result->id);
        $this->assertEquals('third', $result->value);
        $this->assertNotEquals($first->id, $result->id);
        $this->assertNotEquals($second->id, $result->id);
    }
}
