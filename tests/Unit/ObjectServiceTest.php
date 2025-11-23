<?php

namespace Tests\Unit;

use App\Models\ObjectStore;
use App\Services\ObjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ObjectServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ObjectService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ObjectService;
    }

    /**
     * Test storeObject creates a new ObjectStore record.
     */
    public function test_store_object_creates_new_record(): void
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

    /**
     * Test storeObject returns ObjectStore instance.
     */
    public function test_store_object_returns_object_store_instance(): void
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

    /**
     * Test storeObject uses database transaction.
     */
    public function test_store_object_uses_transaction(): void
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
     * Test storeObject handles array values correctly.
     */
    public function test_store_object_handles_array_values(): void
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
     * Test storeObject handles nested object values correctly.
     */
    public function test_store_object_handles_nested_objects(): void
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
     * Test storeObject handles string values correctly.
     */
    public function test_store_object_handles_string_values(): void
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
     * Test storeObject handles numeric values correctly.
     */
    public function test_store_object_handles_numeric_values(): void
    {
        // Arrange
        $data = [
            'key' => 'numeric_test',
            'value' => 42,
        ];

        // Act
        $result = $this->service->storeObject($data);

        // Assert
        $this->assertEquals(42, $result->value);
        $this->assertIsInt($result->value);
    }

    /**
     * Test storeObject handles boolean values correctly.
     */
    public function test_store_object_handles_boolean_values(): void
    {
        // Arrange
        $data = [
            'key' => 'boolean_test',
            'value' => true,
        ];

        // Act
        $result = $this->service->storeObject($data);

        // Assert
        $this->assertTrue($result->value);
        $this->assertIsBool($result->value);
    }

    /**
     * Test storeObject preserves data integrity.
     */
    public function test_store_object_preserves_data_integrity(): void
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
     * Test storeObject with maximum key length.
     */
    public function test_store_object_with_max_key_length(): void
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
