<?php

namespace App\Services;

use App\Contracts\IObjectService;
use App\Models\ObjectStore;
use Illuminate\Pagination\CursorPaginator;
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

    public function latestObjectList(): CursorPaginator
    {
        return ObjectStore::latestObjects()
            ->cursorPaginate(ObjectStore::PER_PAGE_COUNT);
    }

    public function getValueAt(string $key, int $timestamp): ObjectStore
    {
        return ObjectStore::byKeyAndTimestamp($key, $timestamp)->firstOrFail();
    }
}
