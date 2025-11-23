<?php

namespace Tests\Unit;

use App\Contracts\IObjectService;
use App\Http\Controllers\Api\V1\ObjectController;
use App\Http\Requests\ObjectStoreValidationRequest;
use App\Models\ObjectStore;
use App\Services\ObjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;
use Throwable;

class ObjectStoreTest extends TestCase
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
        $this->service = new ObjectService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    /**
     * Test controller store method returns correct response structure.
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
        $this->assertEquals('Resource created successfully', $responseData['message']);
        $this->assertArrayHasKey('data', $responseData);
    }

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


    /**
     * Test service storeObject creates a new ObjectStore record.
     */
    public function test_service_store_object_creates_new_record(): void
    {
        // Arrange
        $data = [
            'key' => 'test_key',
            'value' => ['foo' => 'bar'],
        ];

        // Act
        $result = $this->service->storeObject($data);

        // Assert
        $this->assertInstanceOf(ObjectStore::class, $result);
        $this->assertDatabaseHas('object_stores', [
            'key' => 'test_key',
        ]);
    }

    public function test_service_store_object_returns_object_store_instance(): void
    {
        // Arrange
        $data = [
            'key' => 'instance_test',
            'value' => 'test_value',
        ];

        // Act
        $result = $this->service->storeObject($data);

        // Assert
        $this->assertInstanceOf(ObjectStore::class, $result);
        $this->assertNotNull($result->id);
        $this->assertEquals('instance_test', $result->key);
        $this->assertEquals('test_value', $result->value);
    }

    public function test_service_store_object_uses_transaction(): void
    {
        // Arrange
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $data = [
            'key' => 'transaction_test',
            'value' => 'test',
        ];

        // Act
        $result = $this->service->storeObject($data);

        // Assert
        $this->assertInstanceOf(ObjectStore::class, $result);
    }

    /**
     * Test service storeObject handles array values correctly.
     */
    public function test_service_store_object_handles_array_values(): void
    {
        // Arrange
        $data = [
            'key' => 'array_test',
            'value' => ['item1', 'item2', 'item3'],
        ];

        // Act
        $result = $this->service->storeObject($data);

        // Assert
        $this->assertEquals(['item1', 'item2', 'item3'], $result->value);
        $this->assertIsArray($result->value);
    }

    /**
     * Test service storeObject handles nested object values correctly.
     */
    public function test_service_store_object_handles_nested_objects(): void
    {
        // Arrange
        $data = [
            'key' => 'nested_test',
            'value' => [
                'level1' => [
                    'level2' => [
                        'level3' => 'deep_value',
                    ],
                ],
            ],
        ];

        // Act
        $result = $this->service->storeObject($data);

        // Assert
        $this->assertEquals('deep_value', $result->value['level1']['level2']['level3']);
    }

    /**
     * Test service storeObject handles string values correctly.
     */
    public function test_service_store_object_handles_string_values(): void
    {
        // Arrange
        $data = [
            'key' => 'string_test',
            'value' => 'simple_string',
        ];

        // Act
        $result = $this->service->storeObject($data);

        // Assert
        $this->assertEquals('simple_string', $result->value);
    }

    /**
     * Test service storeObject preserves data integrity.
     * @throws Throwable
     */
    public function test_service_store_object_preserves_data_integrity(): void
    {
        // Arrange
        $data = [
            'key' => 'integrity_test',
            'value' => [
                'string' => 'text',
                'number' => 123,
                'boolean' => false,
                'array' => [1, 2, 3],
                'object' => ['nested' => 'value'],
            ],
        ];

        // Act
        $result = $this->service->storeObject($data);

        // Assert
        $this->assertEquals('text', $result->value['string']);
        $this->assertEquals(123, $result->value['number']);
        $this->assertFalse($result->value['boolean']);
        $this->assertEquals([1, 2, 3], $result->value['array']);
        $this->assertEquals(['nested' => 'value'], $result->value['object']);
    }

    /**
     * Test service storeObject with maximum key length.
     */
    public function test_service_store_object_with_max_key_length(): void
    {
        // Arrange
        $maxKey = str_repeat('a', 255);
        $data = [
            'key' => $maxKey,
            'value' => 'test',
        ];

        // Act
        $result = $this->service->storeObject($data);

        // Assert
        $this->assertEquals($maxKey, $result->key);
        $this->assertEquals(255, strlen($result->key));
    }
}
