<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\IObjectService;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetValueAtTimestampRequest;
use App\Http\Requests\ObjectShowValidationRequest;
use App\Http\Requests\ObjectStoreValidationRequest;
use App\Http\Resources\ObjectListCollection;
use App\Http\Resources\ObjectStoreResource;
use App\Traits\ApiResponseTrait;

class ObjectController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly IObjectService $objectService) {}

    public function index()
    {
        $objects = $this->objectService->latestObjectList();
        $collection = new ObjectListCollection($objects);

        return $this->resourceCollectionResponse($collection);
    }

    public function store(ObjectStoreValidationRequest $request)
    {
        $object = $this->objectService->storeObject($request->validated());

        return $this->successResponse(new ObjectStoreResource($object), 'Resource created successfully', 201);

    }

    public function show(ObjectShowValidationRequest $request)
    {
        $object = $this->objectService->findLatestByKey($request->validated()['key']);

        return $this->successResponse(new ObjectStoreResource($object));
    }

    public function getValueAtTimestamp(GetValueAtTimestampRequest $request)
    {

        $object = $this->objectService->getValueAt($request->validated()['key'], $request->validated()['timestamp']);

        return $this->successResponse(new ObjectStoreResource($object));
    }
}
