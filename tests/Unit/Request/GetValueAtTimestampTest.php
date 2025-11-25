<?php

namespace Tests\Unit\Request;

use App\Http\Requests\GetValueAtTimestampRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class GetValueAtTimestampTest extends TestCase
{
    protected GetValueAtTimestampRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new GetValueAtTimestampRequest;
    }

    public function test_rules_returns_correct_structure(): void
    {
        $rules = $this->request->rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('key', $rules);
        $this->assertArrayHasKey('timestamp', $rules);
        $this->assertCount(2, $rules);
    }

    public function test_key_is_required(): void
    {
        $data = [
            'timestamp' => 1700000000,
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('key', $validator->errors()->toArray());
    }

    public function test_key_must_be_string(): void
    {
        $data = [
            'key' => 12345,
            'timestamp' => 1700000000,
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('key', $validator->errors()->toArray());
    }

    public function test_key_max_length_is_255(): void
    {
        $data = [
            'key' => str_repeat('a', 256),
            'timestamp' => 1700000000,
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('key', $validator->errors()->toArray());
    }

    public function test_timestamp_is_required(): void
    {
        $data = [
            'key' => 'test_key',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('timestamp', $validator->errors()->toArray());
    }

    public function test_timestamp_must_be_integer(): void
    {
        $data = [
            'key' => 'test_key',
            'timestamp' => 'not_an_integer',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('timestamp', $validator->errors()->toArray());
    }

    public function test_timestamp_accepts_negative_values_above_min(): void
    {
        $data = [
            'key' => 'test_key',
            'timestamp' => -1,
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('timestamp', $validator->errors()->toArray());
    }

    public function test_valid_data_passes_validation(): void
    {
        $data = [
            'key' => 'test_key',
            'timestamp' => 1700000000,
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_messages_returns_correct_structure(): void
    {
        $messages = $this->request->messages();

        $this->assertIsArray($messages);
        $this->assertArrayHasKey('key.required', $messages);
        $this->assertArrayHasKey('key.string', $messages);
        $this->assertArrayHasKey('key.max', $messages);
        $this->assertArrayHasKey('timestamp.required', $messages);
        $this->assertArrayHasKey('timestamp.integer', $messages);
        $this->assertArrayHasKey('timestamp.min', $messages);
        $this->assertCount(6, $messages);
    }

    public function test_key_required_message(): void
    {
        $data = [
            'timestamp' => 1700000000,
        ];

        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'The key field is required.',
            $validator->errors()->first('key')
        );
    }

    public function test_key_string_message(): void
    {
        $data = [
            'key' => 12345,
            'timestamp' => 1700000000,
        ];

        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'The key must be a string.',
            $validator->errors()->first('key')
        );
    }

    public function test_key_max_message(): void
    {
        $data = [
            'key' => str_repeat('a', 256),
            'timestamp' => 1700000000,
        ];

        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'The key may not be greater than 255 characters.',
            $validator->errors()->first('key')
        );
    }

    public function test_timestamp_required_message(): void
    {
        $data = [
            'key' => 'test_key',
        ];

        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'The timestamp field is required.',
            $validator->errors()->first('timestamp')
        );
    }
}
