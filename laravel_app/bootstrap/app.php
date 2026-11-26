<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [\App\Http\Middleware\SelectAuthenticationContext::class]);
        $middleware->prependToPriorityList(
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \App\Http\Middleware\SelectAuthenticationContext::class,
        );
        $middleware->alias([
            'active' => \App\Http\Middleware\EnsureActiveUser::class,
            'admin.auth' => \App\Http\Middleware\AuthenticateAdmin::class,
        ]);
        $middleware->redirectUsersTo(fn (\Illuminate\Http\Request $request) => route(
            $request->is('admin', 'admin/*') ? 'admin.dashboard' : 'account'
        ));
        $middleware->redirectGuestsTo(fn (\Illuminate\Http\Request $request) => route(
            $request->is('admin', 'admin/*') ? 'admin.login' : 'login'
        ));
        $middleware->validateCsrfTokens(except: ['payments/webhook']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash(['keys', 'secret', 'file', 'current_password', 'password', 'password_confirmation']);
    })->create()->usePublicPath(dirname(__DIR__, 2).'/public_html');
