<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\IObjectService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ObjectShowValidationRequest;
use App\Http\Requests\ObjectStoreValidationRequest;
use App\Http\Resources\ObjectStoreResource;

class ObjectController extends Controller
{
    public function __construct(private readonly IObjectService $objectService) {}

    public function index()
    {
        $object = $this->objectService->latestObjectList();

        return response()->json([
            'success' => true,
            'message' => 'Resource retrieved successfully',
            'data' => ObjectStoreResource::collection($object),
        ]);
    }
    public function store(ObjectStoreValidationRequest $request)
    {
        $object = $this->objectService->storeObject($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Resource created successfully',
            'data' => new ObjectStoreResource($object),
        ], 201);

    }

    public function show(ObjectShowValidationRequest $request)
    {
        $object = $this->objectService->findLatestByKey($request->validated()['key']);

        return response()->json([
            'success' => true,
            'message' => 'Resource retrieved successfully',
            'data' => new ObjectStoreResource($object),
        ]);
    }
}
