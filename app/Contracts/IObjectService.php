<?php

namespace App\Contracts;

use App\Models\ObjectStore;

interface IObjectService
{
    public function latestObjectList();
    public function storeObject(array $data): ObjectStore;

    public function findLatestByKey(string $key): ObjectStore;
}
