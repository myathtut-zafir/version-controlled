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
        $objectStore = ObjectStore::factory()
            ->withKey('mass_assignment_test')
            ->withValue(['item1' => 'value1', 'item2' => 'value2'])
            ->withTimestamp(1700000000)
            ->create();

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
        $first = ObjectStore::factory()
            ->withKey($key)
            ->withVersion(1)
            ->withTimestamp(1700000000)
            ->create();

        $second = ObjectStore::factory()
            ->withKey($key)
            ->withVersion(2)
            ->withTimestamp(1700000100)
            ->create();

        $third = ObjectStore::factory()
            ->withKey($key)
            ->withVersion(3)
            ->withTimestamp(1700000200)
            ->create();

        // Create a record with a different key
        ObjectStore::factory()
            ->withKey('different_key')
            ->withValue(['version' => 'other'])
            ->withTimestamp(1700000300)
            ->create();

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
        ObjectStore::factory()
            ->withKey('key1')
            ->withValue(['data' => 'value1'])
            ->create();

        ObjectStore::factory()
            ->withKey('key2')
            ->withValue(['data' => 'value2'])
            ->create();

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
        ObjectStore::factory()
            ->withKey('key1')
            ->withVersion(1)
            ->withTimestamp(1700000000)
            ->create();

        $latestKey1 = ObjectStore::factory()
            ->withKey('key1')
            ->withVersion(2)
            ->withTimestamp(1700000100)
            ->create();

        // Create multiple versions for key2
        ObjectStore::factory()
            ->withKey('key2')
            ->withVersion(1)
            ->withTimestamp(1700000050)
            ->create();

        $latestKey2 = ObjectStore::factory()
            ->withKey('key2')
            ->withVersion(2)
            ->withTimestamp(1700000150)
            ->create();

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

        $record1 = ObjectStore::factory()
            ->withKey($key)
            ->withVersion(1)
            ->withTimestamp(1700000000)
            ->create();

        $record2 = ObjectStore::factory()
            ->withKey($key)
            ->withVersion(2)
            ->withTimestamp(1700000100)
            ->create();

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

        ObjectStore::factory()
            ->withKey($key)
            ->withVersion(1)
            ->withTimestamp(1700000000)
            ->create();

        ObjectStore::factory()
            ->withKey($key)
            ->withVersion(2)
            ->withTimestamp(1700000200)
            ->create();

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
        ObjectStore::factory()
            ->withKey('key1')
            ->withValue(['data' => 'value1'])
            ->withTimestamp(1700000000)
            ->create();

        ObjectStore::factory()
            ->withKey('key2')
            ->withValue(['data' => 'value2'])
            ->withTimestamp(1700000000)
            ->create();

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

        $older = ObjectStore::factory()
            ->withKey($key)
            ->withVersion(1)
            ->withTimestamp(1700000000)
            ->create();

        $newer = ObjectStore::factory()
            ->withKey($key)
            ->withVersion(2)
            ->withTimestamp(1700000100)
            ->create();

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

        $objectStore = ObjectStore::factory()
            ->withKey('complex_test')
            ->withValue($complexValue)
            ->create();

        $this->assertEquals($complexValue, $objectStore->value);
        $this->assertEquals('John Doe', $objectStore->value['user']['name']);
        $this->assertEquals(['en', 'es', 'fr'], $objectStore->value['user']['preferences']['languages']);
    }
}
