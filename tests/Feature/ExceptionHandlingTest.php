<?php

namespace Tests\Feature;

use App\Models\ObjectStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test the ModelNotFoundException and validation returns consistent error format.
 */
class ExceptionHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_not_found_exception_returns_consistent_format(): void
    {
        // Act
        $response = $this->getJson('/api/v1/objects/non_existent_key');

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

    public function test_get_value_at_timestamp_not_found_returns_consistent_format(): void
    {
        // Act
        $response = $this->getJson('/api/v1/objects/keys/non_existent?timestamp=1234567890');

        // Assert
        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Resource not found',
        ]);
        $response->assertJsonPath('error.type', 'ModelNotFoundException');
    }

    public function test_validation_exception_returns_consistent_format(): void
    {
        // Act
        $response = $this->postJson('/api/v1/objects', [
            'key' => '',
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

    public function test_not_found_route_returns_consistent_format(): void
    {
        // Act
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

    public function test_successful_request_returns_success_format(): void
    {
        // Arrange
        $object = ObjectStore::create([
            'key' => 'test_key',
            'value' => ['foo' => 'bar'],
            'created_at_timestamp' => now()->timestamp,
        ]);

        // Act
        $response = $this->getJson('/api/v1/objects/test_key');

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

    public function test_error_responses_do_not_expose_sensitive_info(): void
    {
        // Act
        $response = $this->getJson('/api/v1/objects/non_existent');

        // Assert
        $response->assertJsonMissing(['file']);
        $response->assertJsonMissing(['line']);
        $response->assertJsonMissing(['trace']);
        $response->assertJsonMissing(['exception']);
    }
}
