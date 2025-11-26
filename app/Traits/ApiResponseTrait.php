<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Return a success JSON response.
     */
    public function successResponse(object $data, string $message = 'Resource retrieved successfully', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Return a resource collection JSON response.
     *
     * @param  mixed  $collection
     */
    public function resourceCollectionResponse(object $collection, string $message = 'Resource retrieved successfully', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            ...$collection->toArray(request()),
        ], $statusCode);
    }
}
