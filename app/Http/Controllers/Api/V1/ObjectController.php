<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\IObjectService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ObjectStoreValidationRequest;
use App\Http\Resources\ObjectStoreResource;

class ObjectController extends Controller
{
    public function __construct(private readonly IObjectService $objectService) {}

    public function store(ObjectStoreValidationRequest $request)
    {
        $object = $this->objectService->storeObject($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Resource created successfully',
            'data' => new ObjectStoreResource($object),
        ], 201);

    }
}
