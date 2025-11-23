<?php

namespace Tests\Unit;

use App\Contracts\IObjectService;
use App\Http\Controllers\Api\V1\ObjectController;
use App\Http\Requests\ObjectStoreValidationRequest;
use App\Models\ObjectStore;
use Illuminate\Http\JsonResponse;
use Mockery;
use Tests\TestCase;

class ObjectControllerTest extends TestCase
{
    protected ObjectController $controller;

    protected IObjectService $mockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockService = Mockery::mock(IObjectService::class);
        $this->controller = new ObjectController($this->mockService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test store method returns correct response structure.
     */
    public function test_store_returns_correct_response_structure(): void
    {
        // Arrange
        $requestData = [
            'key' => 'test_key',
            'value' => ['foo' => 'bar'],
        ];

        $mockObjectStore = new ObjectStore([
            'key' => 'test_key',
            'value' => ['foo' => 'bar'],
        ]);
        $mockObjectStore->id = 1;

        $this->mockService
            ->shouldReceive('storeObject')
            ->once()
            ->with($requestData)
            ->andReturn($mockObjectStore);

        $mockRequest = Mockery::mock(ObjectStoreValidationRequest::class);
        $mockRequest->shouldReceive('validated')
            ->once()
            ->andReturn($requestData);

        // Act
        $response = $this->controller->store($mockRequest);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());

        $responseData = $response->getData(true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);
    }

    /**
     * Test store method wraps result in ObjectStoreResource.
     */
    public function test_store_wraps_result_in_resource(): void
    {
        // Arrange
        $requestData = [
            'key' => 'resource_test',
            'value' => ['nested' => 'data'],
        ];

        $mockObjectStore = new ObjectStore([
            'key' => 'resource_test',
            'value' => ['nested' => 'data'],
            'created_at_timestamp' => 1700000000,
        ]);
        $mockObjectStore->id = 3;

        $this->mockService
            ->shouldReceive('storeObject')
            ->once()
            ->andReturn($mockObjectStore);

        $mockRequest = Mockery::mock(ObjectStoreValidationRequest::class);
        $mockRequest->shouldReceive('validated')
            ->once()
            ->andReturn($requestData);

        // Act
        $response = $this->controller->store($mockRequest);

        // Assert
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals(3, $responseData['data']['id']);
        $this->assertEquals('resource_test', $responseData['data']['key']);
        $this->assertEquals(['nested' => 'data'], $responseData['data']['value']);
    }
}
