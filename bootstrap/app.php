<?php

use App\Http\Middleware\EnsureActiveDeviceToken;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'active.device' => EnsureActiveDeviceToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'error_code' => 'validation_failed',
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], $e->status);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'error_code' => 'unauthenticated',
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });

        $exceptions->render(function (MissingAbilityException|AccessDeniedHttpException|AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                if ($e instanceof MissingAbilityException || $e->getPrevious() instanceof MissingAbilityException) {
                    return response()->json([
                        'status' => 'error',
                        'error_code' => 'insufficient_token_abilities',
                        'message' => 'The current API token does not have the required ability for this action.',
                    ], 403);
                }

                return response()->json([
                    'status' => 'error',
                    'error_code' => 'forbidden',
                    'message' => $e->getMessage() !== '' ? $e->getMessage() : 'This action is unauthorized.',
                ], 403);
            }
        });

        $exceptions->render(function (NotFoundHttpException|ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'error_code' => 'not_found',
                    'message' => 'The requested resource was not found.',
                ], 404);
            }
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'error_code' => 'rate_limit_exceeded',
                    'message' => 'Too many requests. Please slow down.',
                ], 429);
            }
        });
    })->create();
