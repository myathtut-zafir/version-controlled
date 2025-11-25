<?php

namespace Tests\Unit\Request;

use App\Http\Requests\ObjectShowValidationRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ObjectShowValidationRequestTest extends TestCase
{
    protected ObjectShowValidationRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new ObjectShowValidationRequest;
    }

    public function test_authorize_returns_true(): void
    {
        // Act
        $result = $this->request->authorize();

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test rules method returns correct structure.
     */
    public function test_rules_returns_correct_structure(): void
    {
        // Act
        $rules = $this->request->rules();

        // Assert
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('key', $rules);
        $this->assertCount(1, $rules);
    }

    public function test_key_max_length_is_255(): void
    {
        // Arrange
        $data = [
            'key' => str_repeat('a', 256),
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('key', $validator->errors()->toArray());
    }

    public function test_messages_returns_correct_structure(): void
    {
        // Act
        $messages = $this->request->messages();

        // Assert
        $this->assertIsArray($messages);
        $this->assertArrayHasKey('key.string', $messages);
        $this->assertArrayHasKey('key.max', $messages);
        $this->assertCount(2, $messages);
    }

    public function test_key_max_message(): void
    {
        // Arrange
        $data = [
            'key' => str_repeat('a', 256),
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'The key may not be greater than 255 characters.',
            $validator->errors()->first('key')
        );
    }
}
