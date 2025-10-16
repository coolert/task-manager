<?php

use App\Http\Middleware\EnsureProjectMember;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'project.member' => EnsureProjectMember::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (Illuminate\Auth\AuthenticationException|Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
        });

        $exceptions->render(function (Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(
                    ['message' => $e->getMessage() ?: Symfony\Component\HttpFoundation\Response::$statusTexts[$e->getStatusCode()] ?? 'Error'],
                    $e->getStatusCode(),
                    $e->getHeaders()
                );
            }
        });
    })->create();
