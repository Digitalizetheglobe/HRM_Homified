<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            $token = csrf_token();

            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()
                    ->json([
                        'success' => false,
                        'message' => 'Your session token was refreshed. Please try again.',
                        'token' => $token,
                    ], 419)
                    ->header('X-CSRF-TOKEN', $token)
                    ->header('Cache-Control', 'no-store');
            }

            if ($request->is('login') || $request->routeIs('login')) {
                return redirect()->route('login');
            }

            $fallback = $request->headers->get('referer') ?: url('/');

            return redirect()->to($fallback);
        });
    }
}
