<?php

use Illuminate\Support\Facades\Route;
use Modules\Tools\Http\Controllers\DownloadController;
use Modules\Tools\Http\Controllers\MarketplaceController;
use Modules\Tools\Http\Controllers\SubscriptionController;
use Modules\Tools\Http\Controllers\RuntimeAuthController;
use Modules\Tools\Http\Controllers\WhatsAppController;

// ─── Runtime Device Login Handshake ────────────────────────────────────────────
// The local runtime opens the user's browser to this URL.
// After login, this page POSTs the Sanctum token back to the runtime.
Route::middleware('auth')->group(function () {
    Route::get('/runtime/connect', [RuntimeAuthController::class, 'connect'])->name('runtime.connect');
    Route::post('/runtime/connect', [RuntimeAuthController::class, 'authorize'])->name('runtime.authorize');
});


Route::prefix('tools')->name('tools.')->group(function () {

    // Browse — public
    Route::get('/', [MarketplaceController::class, 'index'])->name('explore');

    // Agent installer — public download (no auth needed, it's just an installer)
    Route::get('/agent/download/{type}', [DownloadController::class, 'downloadAgent'])->name('download.agent');

    // Auth-required routes — MUST be before /{slug} wildcard
    Route::middleware('auth')->group(function () {

        // Billing overview (static path — before wildcard)
        Route::get('/billing/overview', [SubscriptionController::class, 'billing'])->name('billing');

        // My Downloads (static path — before wildcard)
        Route::get('/my/downloads', [DownloadController::class, 'index'])->name('downloads');


        // Workspace settings (static path)
        Route::post('/workspace-settings', [MarketplaceController::class, 'saveWorkspaceSettings'])->name('workspace.settings.save');

        // Cancel subscription (static prefix — before wildcard)
        Route::post('/subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

        // Tool runner — web UI to run a subscribed tool
        Route::get('/{slug}/run', [MarketplaceController::class, 'run'])->name('run');

        // Tool tutorial — guide to setup/use the tool
        Route::get('/{slug}/tutorial', [MarketplaceController::class, 'tutorial'])->name('tutorial');

        // Slug-based routes — AFTER all static routes
        Route::get('/{slug}/subscribe/{planId}', [SubscriptionController::class, 'checkout'])->name('checkout');
        Route::post('/{slug}/subscribe/{planId}', [SubscriptionController::class, 'subscribe'])->name('subscribe');
        Route::get('/{slug}/download', [DownloadController::class, 'generate'])->name('download.generate');
        Route::get('/{slug}/download/{version_id}/serve', [DownloadController::class, 'serve'])->name('download.serve');
    });

    // Public: single tool detail — wildcard LAST
    Route::get('/{slug}', [MarketplaceController::class, 'show'])->name('show');
});

// ─── WhatsApp Operations Platform API ─────────────────────────────────────────
Route::prefix('api/whatsapp')->name('wa.')->middleware('auth')->group(function () {

    // Accounts
    Route::get('/accounts',                         [WhatsAppController::class, 'listAccounts'])->name('accounts.index');
    Route::post('/accounts',                        [WhatsAppController::class, 'createAccount'])->name('accounts.create');
    Route::post('/accounts/{accountId}/connect',    [WhatsAppController::class, 'connectAccount'])->name('accounts.connect');
    Route::post('/accounts/{accountId}/disconnect', [WhatsAppController::class, 'disconnectAccount'])->name('accounts.disconnect');
    Route::get('/accounts/{accountId}/health',      [WhatsAppController::class, 'accountHealth'])->name('accounts.health');
    Route::delete('/accounts/{accountId}',          [WhatsAppController::class, 'deleteAccount'])->name('accounts.delete');

    // Campaigns
    Route::get('/campaigns',                     [WhatsAppController::class, 'listCampaigns'])->name('campaigns.index');
    Route::post('/campaigns',                    [WhatsAppController::class, 'createCampaign'])->name('campaigns.create');
    Route::post('/campaigns/{id}/start',         [WhatsAppController::class, 'startCampaign'])->name('campaigns.start');
    Route::post('/campaigns/{id}/pause',         [WhatsAppController::class, 'pauseCampaign'])->name('campaigns.pause');
    Route::get('/campaigns/{id}/analytics',      [WhatsAppController::class, 'campaignAnalytics'])->name('campaigns.analytics');

    // Inbox
    Route::get('/inbox',                  [WhatsAppController::class, 'listConversations'])->name('inbox.index');
    Route::get('/inbox/{phone}',          [WhatsAppController::class, 'getConversation'])->name('inbox.show');
    Route::post('/inbox/{phone}/reply',   [WhatsAppController::class, 'sendReply'])->name('inbox.reply');
    Route::patch('/inbox/{phone}',        [WhatsAppController::class, 'updateConversation'])->name('inbox.update');

    // Contacts
    Route::get('/contacts',              [WhatsAppController::class, 'listContacts'])->name('contacts.index');
    Route::post('/contacts/import',      [WhatsAppController::class, 'importContacts'])->name('contacts.import');

    // Workflows
    Route::get('/workflows',             [WhatsAppController::class, 'listWorkflows'])->name('workflows.index');
    Route::post('/workflows',            [WhatsAppController::class, 'createWorkflow'])->name('workflows.create');

    // Quality
    Route::get('/quality',               [WhatsAppController::class, 'qualityDashboard'])->name('quality');
});

