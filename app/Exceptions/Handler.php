<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;
use Spatie\Permission\Exceptions\UnauthorizedException as ExceptionsUnauthorizedException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ExceptionsUnauthorizedException) {
            return response()->json([
                'message' => 'User does not have the right roles.'
            ], 403);
        }


        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'message' => 'Resource not found'
            ], 404);
        }

        if ($exception instanceof RouteNotFoundException) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        if ($exception instanceof AuthenticationException) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($exception instanceof AuthorizationException) {
            return response()->json([
                'message' => 'This action is unauthorized'
            ], 403);
        }

        if ($exception instanceof ValidationException) {
            Log::info("message");
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors()
            ],$exception->getCode());
        }

        // if ($exception instanceof QueryException) {
        //     return response()->json([
        //         'message' => 'Database query error'
        //     ], 500);
        // }

        return parent::render($request, $exception);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

}
