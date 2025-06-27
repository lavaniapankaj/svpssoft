<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\FeeMiddleware;
use App\Http\Middleware\InventoryMiddleware;
use App\Http\Middleware\MarksMiddleware;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\RolesMiddleware;
use App\Http\Middleware\StudentMiddleware;
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
        //
        $middleware->alias([
            'role'=>RolesMiddleware::class,
            'is_validate'=>AdminMiddleware::class,
            // 'is_admin'=>AdminMiddleware::class,
            'is_st_admin'=>StudentMiddleware::class,
            'is_marks_admin'=>MarksMiddleware::class,
            'is_fee_admin'=>FeeMiddleware::class,
            'is_inventory_admin'=>InventoryMiddleware::class,
            
            'prevent-back-history' => PreventBackHistory::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
