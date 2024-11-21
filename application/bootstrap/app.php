<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->throttleWithRedis();
    })
    ->withExceptions(static function (Exceptions $exceptions) {

        // Handle API Not Found Http Exception
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                if ($e->getPrevious() instanceof ModelNotFoundException) {
                    $message = 'Record Not Found';
                } else {
                    $message = sprintf('Route %s Not Found', $request->url());
                }
                return response()->json(compact('message'), 404);
            }
        });

        // Handle API Authentication Exception
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([ 'message' => 'Unauthorized' ], 401);
            }
        });

        // Handle API Access Denied Http Exception
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([ 'message' => 'Access Denied' ], 401);
            }
        });

        // Handle API Method Not Allowed Http Exception
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([ 'message' => $e->getMessage() ], 400);
            }
        });

        // Handle API Throttle Requests Exception
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->noContent(429);
            }
        });

        // Handle API Validation Exception
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Validation Failed',
                    'fields' => $e->validator->errors()->toArray(),
                ], 422);
            }
        });

        // Handle API Unhandled Exception
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                if (app()->environment('local')) {
                    dd($e);
                }
                Log::error(sprintf('Unhandled API Exception: %s %s', strtoupper($request->method()), $request->url()), [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'previous' => $e->getPrevious() ? [
                        'message' => $e->getPrevious()->getMessage(),
                        'file' => $e->getPrevious()->getFile(),
                        'line' => $e->getPrevious()->getLine(),
                    ] : null,
                ]);
                return response()->json([ 'message' => 'Internal Server Error' ], 500);
            }
        });

    })->create();
