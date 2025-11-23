<?php

namespace Tests\Unit;

use App\Http\Requests\ObjectStoreValidationRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ObjectStoreValidationRequestTest extends TestCase
{
    protected ObjectStoreValidationRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new ObjectStoreValidationRequest;
    }

    public function test_rules_returns_correct_structure(): void
    {
        // Act
        $rules = $this->request->rules();

        // Assert
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('key', $rules);
        $this->assertArrayHasKey('value', $rules);
        $this->assertCount(2, $rules);
    }

    public function test_key_is_required(): void
    {
        // Arrange
        $data = [
            'value' => 'test_value',
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('key', $validator->errors()->toArray());
    }

    public function test_key_must_be_string(): void
    {
        // Arrange
        $data = [
            'key' => 12345,
            'value' => 'test_value',
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('key', $validator->errors()->toArray());
    }

    public function test_key_max_length_is_255(): void
    {
        // Arrange
        $data = [
            'key' => str_repeat('a', 256),
            'value' => 'test_value',
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('key', $validator->errors()->toArray());
    }

    public function test_value_is_required(): void
    {
        // Arrange
        $data = [
            'key' => 'test_key',
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('value', $validator->errors()->toArray());
    }

    public function test_value_accepts_string_type(): void
    {
        // Arrange
        $data = [
            'key' => 'test_key',
            'value' => 'string_value',
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function test_value_accepts_array_type(): void
    {
        // Arrange
        $data = [
            'key' => 'test_key',
            'value' => ['foo' => 'bar', 'baz' => 'qux'],
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function test_value_accepts_numeric_type(): void
    {
        // Arrange
        $data = [
            'key' => 'test_key',
            'value' => 42,
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function test_messages_returns_correct_structure(): void
    {
        // Act
        $messages = $this->request->messages();

        // Assert
        $this->assertIsArray($messages);
        $this->assertArrayHasKey('key.required', $messages);
        $this->assertArrayHasKey('key.string', $messages);
        $this->assertArrayHasKey('key.max', $messages);
        $this->assertArrayHasKey('value.required', $messages);
        $this->assertCount(4, $messages);
    }

    public function test_key_required_message(): void
    {
        // Arrange
        $data = [
            'value' => 'test_value',
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'The key field is required.',
            $validator->errors()->first('key')
        );
    }

    /**
     * Test custom message for key string validation.
     */
    public function test_key_string_message(): void
    {
        // Arrange
        $data = [
            'key' => 12345,
            'value' => 'test_value',
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'The key must be a string.',
            $validator->errors()->first('key')
        );
    }

    /**
     * Test custom message for key max length validation.
     */
    public function test_key_max_message(): void
    {
        // Arrange
        $data = [
            'key' => str_repeat('a', 256),
            'value' => 'test_value',
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

    /**
     * Test custom message for value required validation.
     */
    public function test_value_required_message(): void
    {
        // Arrange
        $data = [
            'key' => 'test_key',
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'The value field is required.',
            $validator->errors()->first('value')
        );
    }

    /**
     * Test key with exactly 255 characters passes validation.
     */
    public function test_key_with_exactly_255_characters(): void
    {
        // Arrange
        $data = [
            'key' => str_repeat('a', 255),
            'value' => 'test_value',
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /**
     * Test key with 256 characters fails validation.
     */
    public function test_key_with_256_characters_fails(): void
    {
        // Arrange
        $data = [
            'key' => str_repeat('a', 256),
            'value' => 'test_value',
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('key', $validator->errors()->toArray());
    }

    /**
     * Test empty string key fails required validation.
     */
    public function test_empty_string_key_fails(): void
    {
        // Arrange
        $data = [
            'key' => '',
            'value' => 'test_value',
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('key', $validator->errors()->toArray());
    }

    /**
     * Test null key fails validation.
     */
    public function test_null_key_fails(): void
    {
        // Arrange
        $data = [
            'key' => null,
            'value' => 'test_value',
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('key', $validator->errors()->toArray());
    }

    /**
     * Test null value fails validation.
     */
    public function test_null_value_fails(): void
    {
        // Arrange
        $data = [
            'key' => 'test_key',
            'value' => null,
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('value', $validator->errors()->toArray());
    }

    /**
     * Test validation passes with valid data.
     */
    public function test_validation_passes_with_valid_data(): void
    {
        // Arrange
        $data = [
            'key' => 'valid_key',
            'value' => ['nested' => ['data' => 'value']],
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->toArray());
    }

    /**
     * Test key accepts special characters.
     */
    public function test_key_accepts_special_characters(): void
    {
        // Arrange
        $data = [
            'key' => 'key_with-special.chars@123',
            'value' => 'test_value',
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /**
     * Test value accepts nested arrays.
     */
    public function test_value_accepts_nested_arrays(): void
    {
        // Arrange
        $data = [
            'key' => 'test_key',
            'value' => [
                'level1' => [
                    'level2' => [
                        'level3' => 'deep_value',
                    ],
                ],
            ],
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /**
     * Test value with empty array fails validation.
     */
    public function test_value_with_empty_array_fails(): void
    {
        // Arrange
        $data = [
            'key' => 'test_key',
            'value' => [],
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('value', $validator->errors()->toArray());
    }

    /**
     * Test value accepts zero as valid value.
     */
    public function test_value_accepts_zero(): void
    {
        // Arrange
        $data = [
            'key' => 'test_key',
            'value' => 0,
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /**
     * Test value accepts false as valid value.
     */
    public function test_value_accepts_false(): void
    {
        // Arrange
        $data = [
            'key' => 'test_key',
            'value' => false,
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /**
     * Test value with empty string fails validation.
     */
    public function test_value_with_empty_string_fails(): void
    {
        // Arrange
        $data = [
            'key' => 'test_key',
            'value' => '',
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('value', $validator->errors()->toArray());
    }
}
