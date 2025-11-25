<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle Model Not Found Exception
        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found',
                    'error' => [
                        'type' => 'ModelNotFoundException',
                        'details' => 'The requested resource could not be found',
                    ],
                ], 404);
            }
        });
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'error' => [
                        'type' => 'ValidationException',
                        'details' => $e->errors(),
                    ],
                ], 422);
            }
        });

        // Handle Not Found HTTP Exception (404 routes).
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                // Check if this is a wrapped ModelNotFoundException.
                if ($e->getPrevious() instanceof ModelNotFoundException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Resource not found',
                        'error' => [
                            'type' => 'ModelNotFoundException',
                            'details' => 'The requested resource could not be found',
                        ],
                    ], 404);
                }

                //  it's a route not found.
                return response()->json([
                    'success' => false,
                    'message' => 'Endpoint not found',
                    'error' => [
                        'type' => 'NotFoundHttpException',
                        'details' => 'The requested endpoint does not exist',
                    ],
                ], 404);
            }
        });
    })->create();
