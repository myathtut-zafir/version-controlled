<?php

namespace Tests\Unit\Controllers;

use App\Contracts\IObjectService;
use App\Http\Controllers\Api\V1\ObjectController;
use App\Http\Requests\GetValueAtTimestampRequest;
use App\Http\Requests\ObjectShowValidationRequest;
use App\Http\Requests\ObjectStoreValidationRequest;
use App\Models\ObjectStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\CursorPaginator;
use Mockery;
use Tests\TestCase;

class ObjectControllerTest extends TestCase
{
    protected IObjectService $mockService;

    protected ObjectController $controller;

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
     * Test index method returns correct response structure.
     */
    public function test_index_returns_correct_response_structure(): void
    {
        // Arrange
        $items = collect([]);
        $mockPaginator = new CursorPaginator(
            $items,
            20,
            null,
            ['path' => '/api/v1/objects']
        );

        $this->mockService
            ->shouldReceive('latestObjectList')
            ->once()
            ->andReturn($mockPaginator);

        // Act
        $response = $this->controller->index();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = $response->getData(true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Resource retrieved successfully', $responseData['message']);
        $this->assertArrayHasKey('data', $responseData);
    }

    /**
     * Test store method calls service and returns correct response.
     */
    public function test_store_calls_service_and_returns_correct_response(): void
    {
        // Arrange
        $requestData = [
            'key' => 'test_key',
            'value' => ['foo' => 'bar'],
        ];

        $mockObjectStore = ObjectStore::factory()
            ->withId(1)
            ->withKey('test_key')
            ->withValue(['foo' => 'bar'])
            ->withTimestamp(1700000000)
            ->make();

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
        $this->assertEquals('Resource created successfully', $responseData['message']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals(1, $responseData['data']['id']);
        $this->assertEquals('test_key', $responseData['data']['key']);
    }

    public function test_store_wraps_result_in_resource(): void
    {
        // Arrange
        $requestData = [
            'key' => 'resource_test',
            'value' => ['nested' => 'data'],
        ];

        $mockObjectStore = ObjectStore::factory()
            ->withId(3)
            ->withKey('resource_test')
            ->withValue(['nested' => 'data'])
            ->withTimestamp(1700000000)
            ->make();

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

    public function test_show_calls_service_with_correct_key(): void
    {
        // Arrange
        $key = 'test_key';
        $requestData = ['key' => $key];

        $mockObjectStore = ObjectStore::factory()
            ->withId(5)
            ->withKey($key)
            ->withVersion(2)
            ->withTimestamp(1700000100)
            ->make();

        $this->mockService
            ->shouldReceive('findLatestByKey')
            ->once()
            ->with($key)
            ->andReturn($mockObjectStore);

        $mockRequest = Mockery::mock(ObjectShowValidationRequest::class);
        $mockRequest->shouldReceive('validated')
            ->once()
            ->andReturn($requestData);

        // Act
        $response = $this->controller->show($mockRequest);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = $response->getData(true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Resource retrieved successfully', $responseData['message']);
        $this->assertEquals(5, $responseData['data']['id']);
        $this->assertEquals($key, $responseData['data']['key']);
    }

    /**
     * Test show method returns correct response structure.
     */
    public function test_show_returns_correct_response_structure(): void
    {
        // Arrange
        $mockObjectStore = ObjectStore::factory()
            ->withId(1)
            ->withKey('test_key')
            ->withValue(['data' => 'value'])
            ->withTimestamp(1700000000)
            ->make();

        $this->mockService
            ->shouldReceive('findLatestByKey')
            ->once()
            ->andReturn($mockObjectStore);

        $mockRequest = Mockery::mock(ObjectShowValidationRequest::class);
        $mockRequest->shouldReceive('validated')
            ->once()
            ->andReturn(['key' => 'test_key']);

        // Act
        $response = $this->controller->show($mockRequest);

        // Assert
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('data', $responseData);
    }

    /**
     * Test getValueAtTimestamp calls service with correct parameters.
     */
    public function test_get_value_at_timestamp_calls_service_with_correct_parameters(): void
    {
        // Arrange
        $key = 'test_key';
        $timestamp = 1700000150;
        $requestData = [
            'key' => $key,
            'timestamp' => $timestamp,
        ];

        $mockObjectStore = ObjectStore::factory()
            ->withId(2)
            ->withKey($key)
            ->withVersion(2)
            ->withTimestamp(1700000100)
            ->make();

        $this->mockService
            ->shouldReceive('getValueAt')
            ->once()
            ->with($key, $timestamp)
            ->andReturn($mockObjectStore);

        $mockRequest = Mockery::mock(GetValueAtTimestampRequest::class);
        $mockRequest->shouldReceive('validated')
            ->andReturn($requestData);

        // Act
        $response = $this->controller->getValueAtTimestamp($mockRequest);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = $response->getData(true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Resource retrieved successfully', $responseData['message']);
        $this->assertEquals(2, $responseData['data']['id']);
        $this->assertEquals($key, $responseData['data']['key']);
    }

    /**
     * Test getValueAtTimestamp returns correct response structure.
     */
    public function test_get_value_at_timestamp_returns_correct_response_structure(): void
    {
        // Arrange
        $mockObjectStore = ObjectStore::factory()
            ->withId(10)
            ->withKey('test_key')
            ->withValue(['historical' => 'data'])
            ->withTimestamp(1700000000)
            ->make();

        $this->mockService
            ->shouldReceive('getValueAt')
            ->once()
            ->andReturn($mockObjectStore);

        $mockRequest = Mockery::mock(GetValueAtTimestampRequest::class);
        $mockRequest->shouldReceive('validated')
            ->andReturn([
                'key' => 'test_key',
                'timestamp' => 1700000100,
            ]);

        // Act
        $response = $this->controller->getValueAtTimestamp($mockRequest);

        // Assert
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals(['historical' => 'data'], $responseData['data']['value']);
    }

    /**
     * Test that controller uses IObjectService interface.
     */
    public function test_controller_uses_interface(): void
    {
        // This test verifies that the controller accepts IObjectService
        $mockService = Mockery::mock(IObjectService::class);
        $controller = new ObjectController($mockService);

        $this->assertInstanceOf(ObjectController::class, $controller);
    }
}
