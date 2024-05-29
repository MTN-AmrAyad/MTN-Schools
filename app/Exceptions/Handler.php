<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;



class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
    public function render($request, Throwable $exception)
    {
        // Handle Wrong Route
        if ($exception instanceof NotFoundHttpException) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Endpoint not found.'
                ], 404);
            }
        }
        // Handle RouteNotFoundException LOGIN
        if ($exception instanceof RouteNotFoundException) {
            return response()->json(['message' => 'You are not Login .'], 404);
        }
        // Handle RouteNotFoundException Method Not Found
        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json(['message' => 'Please check Your Method Request .'], 404);
        }
        // Handle RouteNotFoundException Relations Not Found
        if ($exception instanceof RelationNotFoundException) {
            return response()->json(['message' => $exception], 404);
        }

        return parent::render($request, $exception);
    }
}
