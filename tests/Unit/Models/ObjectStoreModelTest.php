<?php

namespace Tests\Unit\Models;

use App\Models\ObjectStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObjectStoreModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that model can be created with mass assignment.
     */
    public function test_model_can_be_created_with_mass_assignment(): void
    {
        $data = [
            'key' => 'mass_assignment_test',
            'value' => ['item1' => 'value1', 'item2' => 'value2'],
            'created_at_timestamp' => 1700000000,
        ];

        $objectStore = ObjectStore::create($data);

        $this->assertInstanceOf(ObjectStore::class, $objectStore);
        $this->assertEquals('mass_assignment_test', $objectStore->key);
        $this->assertEquals(['item1' => 'value1', 'item2' => 'value2'], $objectStore->value);
        $this->assertEquals(1700000000, $objectStore->created_at_timestamp);
    }

    /**
     * Test scopeLatestByKey returns records filtered by key and ordered by id descending.
     */
    public function test_scope_latest_by_key_filters_and_orders_correctly(): void
    {
        // Create multiple records with the same key
        $key = 'test_key';
        $first = ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 1],
            'created_at_timestamp' => 1700000000,
        ]);

        $second = ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 2],
            'created_at_timestamp' => 1700000100,
        ]);

        $third = ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 3],
            'created_at_timestamp' => 1700000200,
        ]);

        // Create a record with a different key
        ObjectStore::create([
            'key' => 'different_key',
            'value' => ['version' => 'other'],
            'created_at_timestamp' => 1700000300,
        ]);

        // Act
        $results = ObjectStore::latestByKey($key)->get();

        // Assert
        $this->assertCount(3, $results);
        $this->assertEquals($third->id, $results[0]->id);
        $this->assertEquals($second->id, $results[1]->id);
        $this->assertEquals($first->id, $results[2]->id);
    }

    /**
     * Test scopeLatestByKey returns only records with the specified key.
     */
    public function test_scope_latest_by_key_filters_by_key(): void
    {
        ObjectStore::create([
            'key' => 'key1',
            'value' => ['data' => 'value1'],
            'created_at_timestamp' => time(),
        ]);

        ObjectStore::create([
            'key' => 'key2',
            'value' => ['data' => 'value2'],
            'created_at_timestamp' => time(),
        ]);

        $results = ObjectStore::latestByKey('key1')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('key1', $results[0]->key);
    }

    /**
     * Test scopeLatestObjects returns only the latest record for each unique key.
     */
    public function test_scope_latest_objects_returns_latest_per_key(): void
    {
        // Create multiple versions for key1
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

        // Create multiple versions for key2
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
        $results = ObjectStore::latestObjects()->get();

        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals($latestKey2->id, $results[0]->id);
        $this->assertEquals($latestKey1->id, $results[1]->id);
    }

    /**
     * Test scopeByKeyAndTimestamp filters by key and timestamp.
     */
    public function test_scope_by_key_and_timestamp_filters_correctly(): void
    {
        $key = 'test_key';

        $record1 = ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 1],
            'created_at_timestamp' => 1700000000,
        ]);

        $record2 = ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 2],
            'created_at_timestamp' => 1700000100,
        ]);

        $results = ObjectStore::byKeyAndTimestamp($key, 1700000100)->get();

        $this->assertCount(2, $results);
        $this->assertEquals($record2->id, $results[0]->id);
        $this->assertEquals($record1->id, $results[1]->id);
    }

    /**
     * Test scopeByKeyAndTimestamp excludes records after the timestamp.
     */
    public function test_scope_by_key_and_timestamp_excludes_future_records(): void
    {
        $key = 'test_key';

        ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 1],
            'created_at_timestamp' => 1700000000,
        ]);

        ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 2],
            'created_at_timestamp' => 1700000200,
        ]);

        // Query with timestamp that should only return the first record
        $results = ObjectStore::byKeyAndTimestamp($key, 1700000100)->get();

        $this->assertCount(1, $results);
        $this->assertEquals(['version' => 1], $results[0]->value);
    }

    /**
     * Test scopeByKeyAndTimestamp filters by key.
     */
    public function test_scope_by_key_and_timestamp_filters_by_key(): void
    {
        ObjectStore::create([
            'key' => 'key1',
            'value' => ['data' => 'value1'],
            'created_at_timestamp' => 1700000000,
        ]);

        ObjectStore::create([
            'key' => 'key2',
            'value' => ['data' => 'value2'],
            'created_at_timestamp' => 1700000000,
        ]);

        $results = ObjectStore::byKeyAndTimestamp('key1', 1700000100)->get();

        $this->assertCount(1, $results);
        $this->assertEquals('key1', $results[0]->key);
    }

    /**
     * Test scopeByKeyAndTimestamp orders by timestamp and id descending.
     */
    public function test_scope_by_key_and_timestamp_orders_correctly(): void
    {
        $key = 'test_key';

        $older = ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 1],
            'created_at_timestamp' => 1700000000,
        ]);

        $newer = ObjectStore::create([
            'key' => $key,
            'value' => ['version' => 2],
            'created_at_timestamp' => 1700000100,
        ]);

        $results = ObjectStore::byKeyAndTimestamp($key, 1700000200)->get();

        $this->assertEquals($newer->id, $results[0]->id);
        $this->assertEquals($older->id, $results[1]->id);
    }

    /**
     * Test that value can store complex nested arrays.
     */
    public function test_value_can_store_complex_nested_arrays(): void
    {
        $complexValue = [
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'preferences' => [
                    'theme' => 'dark',
                    'notifications' => true,
                    'languages' => ['en', 'es', 'fr'],
                ],
            ],
            'metadata' => [
                'created_by' => 'system',
                'tags' => ['important', 'urgent'],
            ],
        ];

        $objectStore = ObjectStore::create([
            'key' => 'complex_test',
            'value' => $complexValue,
            'created_at_timestamp' => time(),
        ]);

        $this->assertEquals($complexValue, $objectStore->value);
        $this->assertEquals('John Doe', $objectStore->value['user']['name']);
        $this->assertEquals(['en', 'es', 'fr'], $objectStore->value['user']['preferences']['languages']);
    }
}
