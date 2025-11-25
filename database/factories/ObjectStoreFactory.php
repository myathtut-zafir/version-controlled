<?php

namespace Database\Factories;

use App\Models\ObjectStore;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ObjectStore>
 */
class ObjectStoreFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Model>
     */
    protected $model = ObjectStore::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word(),
            'value' => ['data' => fake()->word()],
            'created_at_timestamp' => fake()->unixTime(),
        ];
    }

    public function withKey(string $key): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
        ]);
    }

    public function withVersion(int $version): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => ['version' => $version],
        ]);
    }

    public function withTimestamp(int $timestamp): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at_timestamp' => $timestamp,
        ]);
    }

    public function withId(int $id): static
    {
        return $this->state(fn (array $attributes) => [
            'id' => $id,
        ]);
    }

    public function withValue(array $value): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => $value,
        ]);
    }
}
