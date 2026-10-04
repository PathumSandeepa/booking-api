<?php

use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: '',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [ForceJsonResponse::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Laravel repeats the first field error as the top-level message; keep it generic instead.
        $exceptions->render(fn (ValidationException $e, Request $request) => new JsonResponse([
            'message' => 'The given data was invalid.',
            'errors' => $e->errors(),
        ], $e->status));
    })
    ->create();
