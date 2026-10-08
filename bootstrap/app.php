<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $modelName = 'Data';
                if ($e->getPrevious() instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    $class = class_basename($e->getPrevious()->getModel());
                    $modelName = match ($class) {
                        'Category' => 'Kategori',
                        'Transaction' => 'Transaksi',
                        'FinancialPeriod' => 'Periode',
                        'RecurringTransaction' => 'Transaksi berulang',
                        'Goal' => 'Goal',
                        default => $class,
                    };
                }
                
                return response()->json([
                    'status' => 'error',
                    'data' => null,
                    'message' => "{$modelName} tidak ditemukan."
                ], 404);
            }
        });
    })->create();
