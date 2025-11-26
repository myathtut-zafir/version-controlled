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

/**
 * @group Objects
 *
 * APIs for managing key-value objects.
 */
class ObjectController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly IObjectService $objectService) {}

    /**
     * List Objects
     *
     * Display a listing of the latest version of all objects.
     *
     * @responseField success boolean The success status of the response.
     * @responseField message string The message of the response.
     * @responseField data object[] The list of objects.
     * @responseField links object The pagination links.
     * @responseField meta object The pagination meta data.
     */
    public function index()
    {
        $objects = $this->objectService->latestObjectList();
        $collection = new ObjectListCollection($objects);

        return $this->resourceCollectionResponse($collection);
    }

    /**
     * Store Object
     *
     * Store a newly created object in storage.
     *
     * @bodyParam key string required The key of the object. Example: my_key
     * @bodyParam value object required The value of the object (JSON). Example: {"foo": "bar"}
     *
     * @response 201 {
     *  "success": true,
     *  "message": "Resource created successfully",
     *  "data": {
     *      "id": 1,
     *      "key": "my_key",
     *      "value": {"foo": "bar"},
     *      "created_at_timestamp": 1700000000
     *  }
     * }
     */
    public function store(ObjectStoreValidationRequest $request)
    {
        $object = $this->objectService->storeObject($request->validated());

        return $this->successResponse(new ObjectStoreResource($object), 'Resource created successfully', 201);

    }

    /**
     * Show Object
     *
     * Display the specified object.
     *
     * @urlParam key string required The key of the object. Example: my_key
     *
     * @response {
     *  "success": true,
     *  "message": "Resource retrieved successfully",
     *  "data": {
     *      "id": 1,
     *      "key": "my_key",
     *      "value": {"foo": "bar"},
     *      "created_at_timestamp": 1700000000
     *  }
     * }
     */
    public function show(ObjectShowValidationRequest $request)
    {
        $object = $this->objectService->findLatestByKey($request->validated()['key']);

        return $this->successResponse(new ObjectStoreResource($object));
    }

    /**
     * Get Value at Timestamp
     *
     * Display the value of the object at a specific timestamp.
     *
     * @urlParam key string required The key of the object. Example: my_key
     *
     * @queryParam timestamp integer required The timestamp to check. Example: 1700000000
     *
     * @response {
     *  "success": true,
     *  "message": "Resource retrieved successfully",
     *  "data": {
     *      "id": 1,
     *      "key": "my_key",
     *      "value": {"foo": "bar"},
     *      "created_at_timestamp": 1700000000
     *  }
     * }
     */
    public function getValueAtTimestamp(GetValueAtTimestampRequest $request)
    {

        $object = $this->objectService->getValueAt($request->validated()['key'], $request->validated()['timestamp']);

        return $this->successResponse(new ObjectStoreResource($object));
    }
}
