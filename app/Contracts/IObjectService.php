<?php

namespace App\Contracts;

use App\Models\ObjectStore;
use Illuminate\Pagination\CursorPaginator;

interface IObjectService
{
    public function latestObjectList(): CursorPaginator;

    public function storeObject(array $data): ObjectStore;

    public function findLatestByKey(string $key): ObjectStore;

    public function getValueAt(string $key, int $timestamp): ObjectStore;
}
