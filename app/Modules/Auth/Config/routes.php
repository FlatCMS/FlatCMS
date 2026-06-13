<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

use App\Core\Router;

/** @var Router $router */

// Auth routes (no locale prefix for admin)
$router->get('/login', [\App\Modules\Auth\Controllers\AuthController::class, 'showLogin'])->name('login');
$router->post('/login', [\App\Modules\Auth\Controllers\AuthController::class, 'login'])->name('login.post');
$router->post('/logout', [\App\Modules\Auth\Controllers\AuthController::class, 'logout'])->name('logout');

// Two-factor (email OTP)
$router->get('/two-factor', [\App\Modules\Auth\Controllers\AuthController::class, 'showTwoFactor'])->name('two-factor');
$router->post('/two-factor', [\App\Modules\Auth\Controllers\AuthController::class, 'verifyTwoFactor'])->name('two-factor.post');
$router->post('/two-factor/resend', [\App\Modules\Auth\Controllers\AuthController::class, 'resendTwoFactor'])->name('two-factor.resend');

// Registration
$router->get('/register/{type}', [\App\Modules\Auth\Controllers\AuthController::class, 'showRegister'])->name('register.type');
$router->get('/register', [\App\Modules\Auth\Controllers\AuthController::class, 'showRegister'])->name('register');
$router->post('/register', [\App\Modules\Auth\Controllers\AuthController::class, 'register'])->name('register.post');

// Password reset
$router->get('/forgot-password', [\App\Modules\Auth\Controllers\AuthController::class, 'showForgotPassword'])->name('forgot-password');
$router->post('/forgot-password', [\App\Modules\Auth\Controllers\AuthController::class, 'sendResetLink'])->name('forgot-password.post');
$router->get('/reset-password/{token}', [\App\Modules\Auth\Controllers\AuthController::class, 'showResetPassword'])->name('reset-password');
$router->post('/reset-password/{token}', [\App\Modules\Auth\Controllers\AuthController::class, 'resetPassword'])->name('reset-password.post');

// Protected avatar (owner-only)
$router->get('/avatar/{id}', [\App\Modules\Auth\Controllers\AuthController::class, 'serveAvatar'])
    ->middleware('auth')
    ->name('avatar');

// Admin routes (protected by auth middleware)
$router->group(['prefix' => 'admin', 'middleware' => 'auth'], function (Router $router) {
    // Profile & Change Password (via AuthController)
    $router->get('/profile', [\App\Modules\Auth\Controllers\AuthController::class, 'showProfile'])->name('admin.profile');
    $router->post('/profile', [\App\Modules\Auth\Controllers\AuthController::class, 'updateProfile'])->name('admin.profile.update');
    $router->post('/profile/licenses/{module}/request', [\App\Modules\Auth\Controllers\AuthController::class, 'requestLicenseReveal'])->name('admin.profile.licenses.request');
    $router->post('/profile/licenses/{module}/verify', [\App\Modules\Auth\Controllers\AuthController::class, 'verifyLicenseReveal'])->name('admin.profile.licenses.verify');
    $router->get('/change-password', [\App\Modules\Auth\Controllers\AuthController::class, 'showChangePassword'])->name('admin.change-password');
    $router->post('/change-password', [\App\Modules\Auth\Controllers\AuthController::class, 'changePassword'])->name('admin.change-password.post');
});
