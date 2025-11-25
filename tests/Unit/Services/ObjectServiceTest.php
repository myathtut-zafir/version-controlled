<?php

namespace Tests\Unit\Services;

use App\Contracts\IObjectService;
use App\Models\ObjectStore;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\CursorPaginator;
use Tests\TestCase;

class ObjectServiceTest extends TestCase
{
    use RefreshDatabase;

    protected IObjectService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(IObjectService::class);
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
     * Test storeObject returns ObjectStore instance with correct data.
     */
    public function test_store_object_returns_object_store_instance(): void
    {
        // Arrange
        $data = [
            'key' => 'instance_test',
            'value' => ['test' => 'value'],
        ];

        // Act
        $result = $this->service->storeObject($data);

        // Assert
        $this->assertInstanceOf(ObjectStore::class, $result);
        $this->assertNotNull($result->id);
        $this->assertEquals('instance_test', $result->key);
        $this->assertEquals(['test' => 'value'], $result->value);
        $this->assertIsInt($result->created_at_timestamp);
    }

    /**
     * Test storeObject sets created_at_timestamp automatically.
     */
    public function test_store_object_sets_timestamp_automatically(): void
    {
        // Arrange
        $data = [
            'key' => 'timestamp_test',
            'value' => ['data' => 'value'],
        ];

        $beforeTimestamp = now()->timestamp;

        // Act
        $result = $this->service->storeObject($data);

        $afterTimestamp = now()->timestamp;

        // Assert
        $this->assertGreaterThanOrEqual($beforeTimestamp, $result->created_at_timestamp);
        $this->assertLessThanOrEqual($afterTimestamp, $result->created_at_timestamp);
    }

    /**
     * Test findLatestByKey returns the latest record for a given key.
     */
    public function test_find_latest_by_key_returns_latest_record(): void
    {
        // Arrange
        $key = 'test_key';

        ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 1],
            'created_at_timestamp' => 1700000000,
        ]);

        $latest = ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 2],
            'created_at_timestamp' => 1700000100,
        ]);

        // Act
        $result = $this->service->findLatestByKey($key);

        // Assert
        $this->assertInstanceOf(ObjectStore::class, $result);
        $this->assertEquals($latest->id, $result->id);
        $this->assertEquals(['version' => 2], $result->value);
    }

    /**
     * Test latestObjectList returns CursorPaginator instance.
     */
    public function test_latest_object_list_returns_cursor_paginator(): void
    {
        // Arrange
        ObjectStore::create([
            'key' => 'key1',
            'value' => ['data' => 'value1'],
            'created_at_timestamp' => 1700000000,
        ]);

        // Act
        $result = $this->service->latestObjectList();

        // Assert
        $this->assertInstanceOf(CursorPaginator::class, $result);
    }

    /**
     * Test latestObjectList returns only latest records per key.
     */
    public function test_latest_object_list_returns_latest_per_key(): void
    {
        // Arrange
        ObjectStore::create([
            'key' => 'key1',
            'value' => ['version' => 1],
            'created_at_timestamp' => 1700000000,
        ]);

        $latestKey1 = ObjectStore::create([
            'key' => 'key1',
            'value' => ['version' => 2],
            'created_at_timestamp' => 1700000100,
        ]);

        ObjectStore::create([
            'key' => 'key2',
            'value' => ['version' => 1],
            'created_at_timestamp' => 1700000050,
        ]);

        $latestKey2 = ObjectStore::create([
            'key' => 'key2',
            'value' => ['version' => 2],
            'created_at_timestamp' => 1700000150,
        ]);

        // Act
        $result = $this->service->latestObjectList();

        // Assert
        $items = $result->items();
        $this->assertCount(2, $items);

        // Verify we got the latest versions
        $ids = collect($items)->pluck('id')->toArray();
        $this->assertContains($latestKey1->id, $ids);
        $this->assertContains($latestKey2->id, $ids);
    }

    /**
     * Test latestObjectList orders by timestamp descending.
     */
    public function test_latest_object_list_orders_by_timestamp_desc(): void
    {
        // Arrange
        $older = ObjectStore::create([
            'key' => 'key1',
            'value' => ['data' => 'older'],
            'created_at_timestamp' => 1700000000,
        ]);

        $newer = ObjectStore::create([
            'key' => 'key2',
            'value' => ['data' => 'newer'],
            'created_at_timestamp' => 1700000200,
        ]);

        // Act
        $result = $this->service->latestObjectList();

        // Assert
        $items = $result->items();
        $this->assertEquals($newer->id, $items[0]->id);
        $this->assertEquals($older->id, $items[1]->id);
    }

    /**
     * Test getValueAt returns correct record for given key and timestamp.
     */
    public function test_get_value_at_returns_correct_record(): void
    {
        // Arrange
        $key = 'test_key';

        ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 1],
            'created_at_timestamp' => 1700000000,
        ]);

        $targetRecord = ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 2],
            'created_at_timestamp' => 1700000100,
        ]);

        ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 3],
            'created_at_timestamp' => 1700000200,
        ]);

        $result = $this->service->getValueAt($key, 1700000150);

        // Assert
        $this->assertInstanceOf(ObjectStore::class, $result);
        $this->assertEquals($targetRecord->id, $result->id);
        $this->assertEquals(['version' => 2], $result->value);
    }

    /**
     * Test getValueAt returns latest record at exact timestamp.
     */
    public function test_get_value_at_returns_record_at_exact_timestamp(): void
    {
        // Arrange
        $key = 'test_key';

        ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 1],
            'created_at_timestamp' => 1700000000,
        ]);

        $exactRecord = ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 2],
            'created_at_timestamp' => 1700000100,
        ]);

        // Act
        $result = $this->service->getValueAt($key, 1700000100);

        // Assert
        $this->assertEquals($exactRecord->id, $result->id);
        $this->assertEquals(['version' => 2], $result->value);
    }

    /**
     * Test getValueAt throws exception when timestamp is before any records.
     */
    public function test_get_value_at_throws_exception_when_timestamp_too_early(): void
    {
        // Arrange
        ObjectStore::create([
            'key' => 'test_key',
            'value' => ['data' => 'value'],
            'created_at_timestamp' => 1700000100,
        ]);

        // Assert
        $this->expectException(ModelNotFoundException::class);

        // Act - Query before the record was created
        $this->service->getValueAt('test_key', 1700000000);
    }

    /**
     * Test getValueAt returns most recent record when multiple records exist before timestamp.
     */
    public function test_get_value_at_returns_most_recent_before_timestamp(): void
    {
        // Arrange
        $key = 'test_key';

        ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 1],
            'created_at_timestamp' => 1700000000,
        ]);

        ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 2],
            'created_at_timestamp' => 1700000050,
        ]);

        $mostRecent = ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 3],
            'created_at_timestamp' => 1700000100,
        ]);

        ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 4],
            'created_at_timestamp' => 1700000200,
        ]);

        // Act
        $result = $this->service->getValueAt($key, 1700000150);

        // Assert
        $this->assertEquals($mostRecent->id, $result->id);
        $this->assertEquals(['version' => 3], $result->value);
    }
}
