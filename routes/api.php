<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use PayIn\Infrastructure\Http\Api\V1\Controllers\AccountController;
use PayIn\Infrastructure\Http\Api\V1\Controllers\ClientController;
use PayIn\Infrastructure\Http\Api\V1\Controllers\PayInController;
use PayIn\Infrastructure\Http\Api\V1\Controllers\PaymentMethodController;

Route::prefix('v1')
    ->middleware('payin.json')
    ->group(function (): void {
        Route::post('/clients', [ClientController::class, 'store'])
            ->name('clients.store')
            ->middleware('throttle:30,1');

        Route::post('/accounts', [AccountController::class, 'store'])
            ->name('accounts.store')
            ->middleware('throttle:30,1');

        Route::get('/accounts/{id}', [AccountController::class, 'show'])
            ->name('accounts.show')
            ->whereUuid('id');

        Route::get('/accounts', [AccountController::class, 'index'])
            ->name('accounts.index');

        Route::post('/payment-methods', [PaymentMethodController::class, 'store'])
            ->name('payment-methods.store')
            ->middleware('throttle:30,1');

        Route::get('/payment-methods/{id}', [PaymentMethodController::class, 'show'])
            ->name('payment-methods.show')
            ->whereUuid('id');

        Route::get('/payment-methods', [PaymentMethodController::class, 'index'])
            ->name('payment-methods.index');

        Route::post('/payins', [PayInController::class, 'store'])
            ->name('payins.store')
            ->middleware('throttle:30,1');

        Route::get('/payins/{id}', [PayInController::class, 'show'])
            ->name('payins.show')
            ->whereUuid('id');

        Route::get('/payins', [PayInController::class, 'index'])
            ->name('payins.index');
    });
