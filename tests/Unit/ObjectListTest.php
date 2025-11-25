<?php

namespace Tests\Unit;

use App\Contracts\IObjectService;
use App\Http\Controllers\Api\V1\ObjectController;
use App\Models\ObjectStore;
use App\Services\ObjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\CursorPaginator;
use Mockery;
use Tests\TestCase;

class ObjectListTest extends TestCase
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

    /**
     * Test controller index method returns correct response structure.
     */
    public function test_index_returns_correct_response_structure(): void
    {
        // Arrange
        $mockPaginator = Mockery::mock(CursorPaginator::class);
        $mockPaginator->shouldReceive('items')->andReturn([]);
        $mockPaginator->shouldReceive('first')->andReturn(null);
        $mockPaginator->shouldReceive('toBase')->andReturnSelf();
        $mockPaginator->shouldReceive('mapInto')->andReturn(collect([]));
        $mockPaginator->shouldReceive('setCollection')->andReturnSelf();
        $mockPaginator->shouldReceive('previousPageUrl')->andReturn(null);
        $mockPaginator->shouldReceive('nextPageUrl')->andReturn('http://test.com?cursor=abc');
        $mockPaginator->shouldReceive('path')->andReturn('http://test.com');
        $mockPaginator->shouldReceive('perPage')->andReturn(20);
        $mockPaginator->shouldReceive('nextCursor')->andReturn(null);
        $mockPaginator->shouldReceive('previousCursor')->andReturn(null);

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
        $this->assertArrayHasKey('links', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
    }

    /**
     * Test controller index method includes pagination links.
     */
    public function test_index_includes_pagination_links(): void
    {
        // Arrange
        $mockPaginator = Mockery::mock(\Illuminate\Pagination\CursorPaginator::class);
        $mockPaginator->shouldReceive('items')->andReturn([]);
        $mockPaginator->shouldReceive('first')->andReturn(null);
        $mockPaginator->shouldReceive('toBase')->andReturnSelf();
        $mockPaginator->shouldReceive('mapInto')->andReturn(collect([]));
        $mockPaginator->shouldReceive('setCollection')->andReturnSelf();
        $mockPaginator->shouldReceive('previousPageUrl')->andReturn('http://test.com?cursor=prev');
        $mockPaginator->shouldReceive('nextPageUrl')->andReturn('http://test.com?cursor=next');
        $mockPaginator->shouldReceive('path')->andReturn('http://test.com');
        $mockPaginator->shouldReceive('perPage')->andReturn(20);
        $mockPaginator->shouldReceive('nextCursor')->andReturn(null);
        $mockPaginator->shouldReceive('previousCursor')->andReturn(null);

        $this->mockService
            ->shouldReceive('latestObjectList')
            ->once()
            ->andReturn($mockPaginator);

        // Act
        $response = $this->controller->index();

        // Assert
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('links', $responseData);
        $this->assertEquals('http://test.com?cursor=prev', $responseData['links']['prev']);
        $this->assertEquals('http://test.com?cursor=next', $responseData['links']['next']);
    }

    /**
     * Test service latestObjectList returns CursorPaginator.
     */
    public function test_service_latest_object_list_returns_cursor_paginator(): void
    {
        // Arrange
        ObjectStore::create([
            'key' => 'test_key_1',
            'value' => ['data' => 'value1'],
            'created_at_timestamp' => now()->timestamp,
        ]);

        // Act
        $result = $this->service->latestObjectList();

        // Assert
        $this->assertInstanceOf(\Illuminate\Pagination\CursorPaginator::class, $result);
    }

    /**
     * Test service latestObjectList returns latest objects only.
     */
    public function test_service_latest_object_list_returns_latest_objects_only(): void
    {
        // Arrange - Create multiple versions of the same key
        ObjectStore::create([
            'key' => 'versioned_key',
            'value' => ['version' => 1],
            'created_at_timestamp' => now()->subHours(2)->timestamp,
        ]);

        $latestObject = ObjectStore::create([
            'key' => 'versioned_key',
            'value' => ['version' => 2],
            'created_at_timestamp' => now()->timestamp,
        ]);

        // Act
        $result = $this->service->latestObjectList();
        $items = $result->items();

        // Assert
        $this->assertCount(1, $items);
        $this->assertEquals($latestObject->id, $items[0]->id);
        $this->assertEquals(['version' => 2], $items[0]->value);
    }

    /**
     * Test service latestObjectList paginates results.
     */
    public function test_service_latest_object_list_paginates_results(): void
    {
        // Arrange - Create 25 objects (more than the default per_page of 20)
        for ($i = 1; $i <= 25; $i++) {
            ObjectStore::create([
                'key' => "key_{$i}",
                'value' => ['index' => $i],
                'created_at_timestamp' => now()->addSeconds($i)->timestamp,
            ]);
        }

        // Act
        $result = $this->service->latestObjectList();

        // Assert
        $this->assertEquals(20, $result->perPage());
        $this->assertCount(20, $result->items());
    }

    /**
     * Test service latestObjectList orders by created_at_timestamp descending.
     */
    public function test_service_latest_object_list_orders_by_timestamp_desc(): void
    {
        // Arrange
        ObjectStore::create([
            'key' => 'old_key',
            'value' => ['order' => 1],
            'created_at_timestamp' => now()->subDays(2)->timestamp,
        ]);

        $newestObject = ObjectStore::create([
            'key' => 'new_key',
            'value' => ['order' => 2],
            'created_at_timestamp' => now()->timestamp,
        ]);

        ObjectStore::create([
            'key' => 'middle_key',
            'value' => ['order' => 3],
            'created_at_timestamp' => now()->subDay()->timestamp,
        ]);

        // Act
        $result = $this->service->latestObjectList();
        $items = $result->items();

        // Assert
        $this->assertEquals($newestObject->id, $items[0]->id);
        $this->assertEquals(['order' => 2], $items[0]->value);
    }
}
