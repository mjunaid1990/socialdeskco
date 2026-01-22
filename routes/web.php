<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\SocialAccountController;
use App\Http\Controllers\Backend\PostController;
use App\Http\Controllers\Backend\LeadController;
use App\Http\Controllers\Backend\EngagementController;
use App\Http\Controllers\Backend\AutomationRuleController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\Backend\SettingsController;
use App\Http\Controllers\Backend\BusinessController;
use App\Http\Controllers\Backend\LeadAutomationController;
use App\Http\Controllers\Backend\MonitoringController;
use App\Http\Controllers\WebhookController;

// Frontend routes
Route::get('/', function () {
    return view('welcome');
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);

    Route::get('/register', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'create'])
                ->name('register');

    Route::post('/register', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'store']);

    Route::get('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'create'])
                ->name('password.request');

    Route::post('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store'])
                ->name('password.email');

    Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\NewPasswordController::class, 'create'])
                ->name('password.reset');

    Route::post('/reset-password', [\App\Http\Controllers\Auth\NewPasswordController::class, 'store'])
                ->name('password.store');
});

// Webhook routes (no auth middleware)
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/facebook', [WebhookController::class, 'facebookWebhook'])->name('facebook');
    Route::post('/instagram', [WebhookController::class, 'facebookWebhook'])->name('instagram'); // Instagram uses same handler
    Route::post('/whatsapp', [WebhookController::class, 'whatsappWebhook'])->name('whatsapp');
    Route::post('/twitter', [WebhookController::class, 'twitterWebhook'])->name('twitter');
});

Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::post('/logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
});

// OAuth routes (outside admin middleware)
Route::get('/oauth/callback/{platform}', [OAuthController::class, 'callback'])->name('oauth.callback');
Route::get('/oauth/redirect/{platform}', [OAuthController::class, 'redirect'])->name('oauth.redirect');

// Backend routes
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('social-accounts', SocialAccountController::class);
    Route::get('/posts/bulk-upload', [\App\Http\Controllers\Backend\PostController::class, 'bulkUpload'])->name('posts.bulk-upload');
    Route::post('/posts/process-bulk-upload', [\App\Http\Controllers\Backend\PostController::class, 'processBulkUpload'])->name('posts.process-bulk-upload');
    Route::resource('posts', PostController::class);
    Route::post('/posts/generate-with-ai', [\App\Http\Controllers\Backend\PostController::class, 'generateWithAi'])->name('posts.generateWithAi');
    Route::post('/posts/generate-variations-with-ai', [\App\Http\Controllers\Backend\PostController::class, 'generateVariationsWithAi'])->name('posts.generateVariationsWithAi');
    Route::post('/posts/generate-image-with-ai', [\App\Http\Controllers\Backend\PostController::class, 'generateImageWithAi'])->name('posts.generateImageWithAi');
    Route::post('/posts/generate-image-for-post-with-ai', [\App\Http\Controllers\Backend\PostController::class, 'generateImageForPostWithAi'])->name('posts.generateImageForPostWithAi');
    Route::post('/posts/{post}/apply-ai-image', [\App\Http\Controllers\Backend\PostController::class, 'applyAiImageToPost'])->name('posts.applyAiImage');
    Route::resource('leads', LeadController::class);
    Route::resource('engagements', EngagementController::class);
    Route::resource('automation-rules', AutomationRuleController::class);
    
    // Lead automation routes
    Route::get('/lead-automation', [LeadAutomationController::class, 'index'])->name('lead-automation.index');
    Route::get('/lead-automation/{lead}', [LeadAutomationController::class, 'show'])->name('lead-automation.show');
    Route::post('/lead-automation/{lead}/send-whatsapp', [LeadAutomationController::class, 'sendWhatsAppMessage'])->name('lead-automation.send-whatsapp');
    Route::post('/lead-automation/broadcast', [LeadAutomationController::class, 'sendBroadcast'])->name('lead-automation.broadcast');
    Route::get('/lead-automation/broadcast-form', [LeadAutomationController::class, 'broadcastForm'])->name('lead-automation.broadcast-form');
    Route::put('/lead-automation/{lead}/qualification', [LeadAutomationController::class, 'updateQualification'])->name('lead-automation.update-qualification');
    Route::post('/lead-automation/{lead}/rescore', [LeadAutomationController::class, 'rescoreLead'])->name('lead-automation.rescore');
    Route::get('/lead-automation/stats', [LeadAutomationController::class, 'getStats'])->name('lead-automation.stats');
    
    // Performance Monitoring routes (admin only)
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
    Route::get('/monitoring/api-metrics', [MonitoringController::class, 'apiMetrics'])->name('monitoring.api-metrics');
    
    // Settings routes
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/{id}', [SettingsController::class, 'update'])->name('settings.update');
    Route::delete('/settings/{id}', [SettingsController::class, 'destroy'])->name('settings.destroy');
    Route::put('/settings/key/{key}', [SettingsController::class, 'updateIndividualSetting'])->name('settings.update-key');
    Route::get('/settings/social-media', [SettingsController::class, 'socialMediaSettings'])->name('settings.social-media');
    Route::post('/settings/social-media', [SettingsController::class, 'saveSocialMediaSettings'])->name('settings.save-social-media');
    

});