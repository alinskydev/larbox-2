<?php

use App\Base\Application;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: '',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {
            if (config('app.debug')) return null;

            $isProduction = app()->environment('production');

            if ($e instanceof NotFoundHttpException) {
                if ($e->getPrevious() instanceof ModelNotFoundException) {
                    return response()->json(['message' => __('Запись не найдена')], 400);
                } else {
                    return response()->json(['message' => 'Not found'], 404);
                }
            }

            if ($e instanceof AuthenticationException) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            if ($e instanceof QueryException && $isProduction) {
                return response()->json(['message' => 'Invalid input data type'], 400);
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], $e->status);
            }

            if ($e instanceof ThrottleRequestsException) {
                return response()->json(
                    [
                        'message' => $e->getMessage(),
                        'seconds_left' => Arr::get($e->getHeaders(), 'Retry-After', 0),
                    ],
                    429,
                );
            }

            $message = $e->getMessage();

            if (str_starts_with($message, 'SQLSTATE[22003]')) {
                $message = 'Value is out of limit';
            }

            if (str_starts_with($message, 'SQLSTATE[22P02]')) {
                $message = 'Invalid input syntax for this type';
            }

            if (str_starts_with($message, 'SQLSTATE[23503]')) {
                $message = __('Данная запись не подлежит удалению, так как она задействована в других разделах');
            }

            return response()->json(
                ['message' => $message ?: 'An error occurred'],
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 400,
            );
        });
    })
    ->booting(function () {
        Route::pattern('id', '[0-9]+');
        Route::pattern('index', '[0-9]+');
        Route::pattern('model', '[0-9]+');

        RateLimiter::for('api', function (Request $request) {
            $limit = App::environment('production') ? 300 : 1000;
            return Limit::perMinute($limit)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            $limitByIp = Limit::perMinute($request->is('common/auth/login') ? 5 : 3);
            $limitByUsername = $request->filled('username') ? Limit::perHour(10) : Limit::none();

            return [
                $limitByIp->by($request->path() . '|' . $request->ip()),
                $limitByUsername->by($request->path() . '|' . $request->input('username')),
            ];
        });
    })
    ->create();
