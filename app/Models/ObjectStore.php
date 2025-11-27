<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 * @method static \Illuminate\Database\Eloquent\Builder latestByKey(string $key)
 * @method static \Illuminate\Database\Eloquent\Builder latestObjects()
 * @method static \Illuminate\Database\Eloquent\Builder byKeyAndTimestamp(string $key, int $timestamp)
 */
class ObjectStore extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'object_stores';

    public $timestamps = false;

    const PER_PAGE_COUNT = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'created_at_timestamp',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
            'created_at_timestamp' => 'integer',
        ];
    }

    /**
     * Scope a query to get the latest record by key.
     */
    public function scopeLatestByKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key)
            ->orderByDesc('created_at_timestamp')
            ->orderByDesc('id');
    }

    public function scopeLatestObjects(Builder $query): Builder
    {
        $latestIds = static::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('key')
            ->pluck('id');

        return $query
            ->whereIn('id', $latestIds)
            ->orderByDesc('created_at_timestamp')
            ->orderByDesc('id');
    }

    /**
     * Scope a query to get a record by key and timestamp.
     */
    public function scopeByKeyAndTimestamp(Builder $query, string $key, int $timestamp): Builder
    {
        return $query->where('key', $key)
            ->where('created_at_timestamp', '<=', $timestamp)
            ->orderByDesc('created_at_timestamp')
            ->orderByDesc('id');
    }
}
