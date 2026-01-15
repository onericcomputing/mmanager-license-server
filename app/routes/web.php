<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\Api\PdfController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

// API Routes
Route::prefix('api')->group(function () {
    Route::post('/license/verify', [LicenseController::class, 'verify']);
    Route::post('/pdf/generate', [PdfController::class, 'generate']);

    // Webhooks (no CSRF)
    Route::post('/webhooks/stripe', [WebhookController::class, 'stripe'])->name('webhooks.stripe');
    Route::post('/webhooks/paypal', [WebhookController::class, 'paypal'])->name('webhooks.paypal');
});

// Subscription Routes
Route::prefix('subscription')->group(function () {
    Route::get('/plans', [SubscriptionController::class, 'plans'])->name('subscription.plans');
    Route::get('/checkout/{plan}', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::post('/stripe/create', [SubscriptionController::class, 'createStripeSession'])->name('subscription.stripe.create');
    Route::post('/paypal/create', [SubscriptionController::class, 'createPayPalSubscription'])->name('subscription.paypal.create');
    Route::get('/success', [SubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::get('/manage', [SubscriptionController::class, 'manage'])->name('subscription.manage');
    Route::get('/portal', [SubscriptionController::class, 'portal'])->name('subscription.portal');
    Route::post('/cancel', [SubscriptionController::class, 'cancelSubscription'])->name('subscription.cancel.post');
    Route::post('/resume', [SubscriptionController::class, 'resumeSubscription'])->name('subscription.resume');
});

// Admin Routes
Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/login', fn() => view('admin.login'))->name('admin.login');
    Route::post('/login', fn() => redirect('/admin'))->name('admin.login.post');
    Route::post('/logout', function () {
        session()->forget('admin_authenticated');
        return redirect('/admin/login');
    })->name('admin.logout');

    Route::get('/licenses', [DashboardController::class, 'licenses'])->name('admin.licenses');
    Route::get('/licenses/{license}', [DashboardController::class, 'show'])->name('admin.licenses.show');
    Route::post('/licenses/{license}/suspend', [DashboardController::class, 'suspend'])->name('admin.licenses.suspend');
    Route::post('/licenses/{license}/revoke', [DashboardController::class, 'revoke'])->name('admin.licenses.revoke');
    Route::post('/licenses/{license}/reactivate', [DashboardController::class, 'reactivate'])->name('admin.licenses.reactivate');
    Route::post('/licenses/{license}/reset-domain', [DashboardController::class, 'resetDomain'])->name('admin.licenses.reset-domain');

    Route::get('/logs', [DashboardController::class, 'logs'])->name('admin.logs');
    Route::get('/subscriptions', [DashboardController::class, 'subscriptions'])->name('admin.subscriptions');
    Route::get('/payments', [DashboardController::class, 'payments'])->name('admin.payments');
});

// Health check
Route::get('/health', fn() => response()->json(['status' => 'ok']));
