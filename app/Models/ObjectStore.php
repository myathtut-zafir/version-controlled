<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 * @method static \Illuminate\Database\Eloquent\Builder latestByKey(string $key)
 */
class ObjectStore extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'object_stores';

    public $timestamps = false;

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
     * @param Builder $query
     * @param string $key
     * @return Builder
     */
    public function scopeLatestByKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key)->orderByDesc('id');
    }
}
