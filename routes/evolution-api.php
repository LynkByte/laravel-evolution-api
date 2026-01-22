<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Lynkbyte\EvolutionApi\Http\Controllers\WebhookController;
use Lynkbyte\EvolutionApi\Http\Middleware\VerifyWebhookSignature;

/*
|--------------------------------------------------------------------------
| Evolution API Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle incoming webhooks from Evolution API.
| Configure the webhook URL in Evolution API to point to:
|
| POST {your-app-url}/api/evolution-api/webhook
|
| Or for instance-specific webhooks:
|
| POST {your-app-url}/api/evolution-api/webhook/{instance}
|
*/

Route::prefix(config('evolution-api.webhook.path', 'api/evolution-api'))
    ->middleware(config('evolution-api.webhook.middleware', ['api']))
    ->group(function () {

        // Apply webhook signature verification middleware
        Route::middleware([VerifyWebhookSignature::class])->group(function () {

            // Main webhook endpoint
            Route::post('/webhook', [WebhookController::class, 'handle'])
                ->name('evolution-api.webhook');

            // Instance-specific webhook endpoint
            Route::post('/webhook/{instance}', [WebhookController::class, 'handleInstance'])
                ->name('evolution-api.webhook.instance')
                ->where('instance', '[a-zA-Z0-9_-]+');
        });

        // Health check endpoint (no signature verification)
        Route::get('/health', [WebhookController::class, 'health'])
            ->name('evolution-api.health');
    });
