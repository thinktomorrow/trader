<?php

use Illuminate\Support\Facades\Route;
use Thinktomorrow\Trader\Infrastructure\Shop\Controllers\CustomerController;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers\CustomerAuthController;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers\CustomerForgotPasswordController;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers\CustomerRegisterController;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers\CustomerResetPasswordController;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers\VerificationController;

Route::group(['prefix' => 'you', 'middleware' => ['web']], function () {
    // Customer login routes
    Route::post('login', [CustomerAuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('customer.login.store');
    Route::get('login', [CustomerAuthController::class, 'showLoginForm'])->name('customer.login');

    // Customer Password Reset routes
    Route::get('password/reset', [CustomerForgotPasswordController::class, 'showLinkRequestForm'])->name('customer.password.request');
    Route::post('password/email', [CustomerForgotPasswordController::class, 'sendResetLinkEmail'])->name('customer.password.email');
    Route::get('password/reset/{token}', [CustomerResetPasswordController::class, 'showResetForm'])->name('customer.password.reset');
    Route::post('password/reset', [CustomerResetPasswordController::class, 'reset'])->name('customer.password.reset.store');

    // Customer registration routes
    Route::get('register', [CustomerRegisterController::class, 'showRegisterForm'])->name('customer.register');
    Route::post('register', [CustomerRegisterController::class, 'register'])->name('customer.register.store');

    // Email Verification Routes - for signed out customers
    Route::get('email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('customer.verification.verify');

    // Email Verification Routes - for logged in customers
    Route::get('email/verify', [VerificationController::class, 'show'])->name('customer.verification.show');
    Route::post('email/resend', [VerificationController::class, 'resend'])->name('customer.verification.resend');

    // Customer routes - authed
    Route::get('logout', [CustomerAuthController::class, 'logout'])->name('customer.logout')->middleware('customer-auth');
    Route::get('orders', [CustomerController::class, 'indexOrders'])->name('customer.orders')->middleware('customer-auth');
    Route::get('/', [CustomerController::class, 'index'])->name('customer.index')->middleware('customer-auth');
});
