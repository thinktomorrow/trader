<?php

use Illuminate\Support\Facades\Route;
use Thinktomorrow\Trader\Infrastructure\Shop\Controllers\CustomerController;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers\CustomerAuthController;

// Customer Auth routes
\Illuminate\Support\Facades\Route::post('customer-login-store', [CustomerAuthController::class, 'login'])->name('customer.login.store');
\Illuminate\Support\Facades\Route::get('customer-login', [CustomerAuthController::class, 'showLoginForm'])->name('customer.login');
\Illuminate\Support\Facades\Route::get('customer-logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');

// Customer Password Reset routes
Route::get('password/reset', [\Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers\CustomerForgotPasswordController::class, 'showLinkRequestForm'])
    ->name('customer.password.request')
    ->middleware('web');

Route::post('password/email', [\Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers\CustomerForgotPasswordController::class, 'sendResetLinkEmail'])
    ->name('customer.password.email')
    ->middleware('web');

Route::get('password/reset/{token}', [\Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers\CustomerResetPasswordController::class, 'showResetForm'])
    ->name('customer.password.reset')
    ->middleware('web');

Route::post('password/reset', [\Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers\CustomerResetPasswordController::class, 'reset'])
    ->name('customer.password.reset.store')
    ->middleware('web');

// Customer routes - authed
Route::group(['prefix' => 'you', 'middleware' => ['customer-auth']], function () {
    \Illuminate\Support\Facades\Route::get('test', [CustomerController::class, 'dashboard'])->name('customer.home');
    \Illuminate\Support\Facades\Route::get('orders', [CustomerController::class, 'indexOrders'])->name('customer.orders');
});
