<?php

namespace Tests\Feature;

use App\Models\ObjectStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExceptionHandlingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that ModelNotFoundException returns consistent error format.
     */
    public function test_model_not_found_exception_returns_consistent_format(): void
    {
        // Act - Try to get a non-existent object
        $response = $this->getJson('/api/v1/object/non_existent_key');

        // Assert
        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Resource not found',
            'error' => [
                'type' => 'ModelNotFoundException',
                'details' => 'The requested resource could not be found',
            ],
        ]);

        // Verify structure
        $response->assertJsonStructure([
            'success',
            'message',
            'error' => [
                'type',
                'details',
            ],
        ]);
    }

    /**
     * Test that getValueAtTimestamp endpoint returns consistent error for non-existent resource.
     */
    public function test_get_value_at_timestamp_not_found_returns_consistent_format(): void
    {
        // Act - Try to get a non-existent object at a timestamp
        $response = $this->getJson('/api/v1/object/keys/non_existent?timestamp=1234567890');

        // Assert
        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Resource not found',
        ]);
        $response->assertJsonPath('error.type', 'ModelNotFoundException');
    }

    /**
     * Test that validation errors return consistent format.
     */
    public function test_validation_exception_returns_consistent_format(): void
    {
        // Act - Send invalid data (missing required fields)
        $response = $this->postJson('/api/v1/object', [
            'key' => '', // Empty key should fail validation
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Validation failed',
        ]);

        $response->assertJsonStructure([
            'success',
            'message',
            'error' => [
                'type',
                'details',
            ],
        ]);

        $response->assertJsonPath('error.type', 'ValidationException');
    }

    /**
     * Test that non-existent routes return consistent error format.
     */
    public function test_not_found_route_returns_consistent_format(): void
    {
        // Act - Try to access a non-existent route
        $response = $this->getJson('/api/v1/non-existent-endpoint');

        // Assert
        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Endpoint not found',
            'error' => [
                'type' => 'NotFoundHttpException',
                'details' => 'The requested endpoint does not exist',
            ],
        ]);
    }

    /**
     * Test that successful requests still return success: true format.
     */
    public function test_successful_request_returns_success_format(): void
    {
        // Arrange - Create a test object
        $object = ObjectStore::create([
            'key' => 'test_key',
            'value' => ['foo' => 'bar'],
            'created_at_timestamp' => now()->timestamp,
        ]);

        // Act - Get the object
        $response = $this->getJson('/api/v1/object/test_key');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Resource retrieved successfully',
        ]);

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'key',
                'value',
                'created_at_timestamp',
            ],
        ]);
    }

    /**
     * Test that error responses do not expose sensitive information.
     */
    public function test_error_responses_do_not_expose_sensitive_info(): void
    {
        // Act
        $response = $this->getJson('/api/v1/object/non_existent');

        // Assert - Should not contain file paths, line numbers, or stack traces
        $response->assertJsonMissing(['file']);
        $response->assertJsonMissing(['line']);
        $response->assertJsonMissing(['trace']);
        $response->assertJsonMissing(['exception']);
    }
}
