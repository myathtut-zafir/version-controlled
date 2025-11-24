<?php

namespace App\Services;

use App\Contracts\IObjectService;
use App\Models\ObjectStore;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class ObjectService implements IObjectService
{
    /**
     * @throws Throwable
     */
    public function storeObject(array $data): ObjectStore
    {
        return DB::transaction(function () use ($data) {
            return ObjectStore::create([
                'key' => $data['key'],
                'value' => $data['value'],
                'created_at_timestamp' => now()->timestamp,
            ]);
        });
    }

    public function findLatestByKey(string $key): ObjectStore
    {
        return ObjectStore::latestByKey($key)->firstOrFail();
    }

    public function latestObjectList(): LengthAwarePaginator
    {
        return ObjectStore::latestObjects()
            ->paginate(request('per_page', 15));
    }
}
