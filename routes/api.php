<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use PayIn\Infrastructure\Http\Api\V1\Controllers\PayInController;

Route::prefix('v1')
    ->middleware('payin.json')
    ->group(function (): void {
        Route::post('/payins', [PayInController::class, 'store'])
            ->name('payins.store')
            ->middleware('throttle:30,1');

        Route::get('/payins/{id}', [PayInController::class, 'show'])
            ->name('payins.show')
            ->whereUuid('id');

        Route::get('/payins', [PayInController::class, 'index'])
            ->name('payins.index');
    });
