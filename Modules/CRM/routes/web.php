<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\DashboardController;
use Modules\CRM\Http\Controllers\LeadController;
use Modules\CRM\Http\Controllers\LeadNoteController;
use Modules\CRM\Http\Controllers\LeadTagController;
use Modules\CRM\Http\Controllers\WorkspaceController;
use Modules\CRM\Http\Controllers\Api\KanbanController;
use Modules\CRM\Http\Controllers\SearchController;
use Modules\CRM\Http\Controllers\CrmWidgetController;
use Modules\CRM\Http\Controllers\CrmWidgetCaptureController;

// ── Public CRM Routes ──────────────────────────────────────────────
Route::middleware(['web'])->group(function () {
    Route::get('/crm/w/{token}', [CrmWidgetCaptureController::class, 'show'])->name('crm.widgets.embed');
    Route::post('/crm/w/{token}', [CrmWidgetCaptureController::class, 'store'])->name('crm.widgets.submit');

    Route::get('/crm-temp-migrate-run', function() {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return \Illuminate\Support\Facades\Artisan::output();
    });
});

// ── CRM Module Routes ──────────────────────────────────────────────
Route::middleware(['web', 'auth', 'verified', 'onboarding', 'subscription:crm', \Modules\CRM\Http\Middleware\WorkspaceMiddleware::class])
    ->prefix('crm')
    ->name('crm.')
    ->group(function () {
        // ── Workspaces ──────────────────────────────────────────────────
        Route::get('/', [WorkspaceController::class, 'index'])->name('workspaces.index');
        Route::get('/workspaces/collector', [WorkspaceController::class, 'collectorWorkspace'])->name('workspaces.collector');
        Route::get('/workspaces/telesales', [WorkspaceController::class, 'telesalesWorkspace'])->name('workspaces.telesales');
        Route::get('/workspaces/manager', [WorkspaceController::class, 'managerWorkspace'])->name('workspaces.manager');
        Route::get('/workspaces/marketing', [WorkspaceController::class, 'marketingWorkspace'])->name('workspaces.marketing');
        Route::get('/workspaces/support', [WorkspaceController::class, 'supportWorkspace'])->name('workspaces.support');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        // Universal Search
        Route::get('/search', [SearchController::class, 'index'])->name('search.page');
        Route::get('/api/search', [\Modules\CRM\Http\Controllers\Api\SearchController::class, 'index'])->name('search');
        
        // ── Leads
        Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
        Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
        Route::post('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.update-status');
        Route::post('/leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
        Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');

        // ── Pipeline / Kanban API
        Route::get('/api/kanban', [KanbanController::class, 'index'])->name('api.kanban.index');
        Route::put('/api/kanban/{lead}/stage', [KanbanController::class, 'updateStage'])->name('api.kanban.stage');

        // ── Lead Notes & Tags
        Route::post('/leads/{lead}/notes', [LeadNoteController::class, 'store'])->name('leads.notes.store');
        Route::put('/leads/{lead}/notes/{note}', [LeadNoteController::class, 'update'])->name('leads.notes.update');
        Route::delete('/leads/{lead}/notes/{note}', [LeadNoteController::class, 'destroy'])->name('leads.notes.destroy');

        Route::get('/tags', [LeadTagController::class, 'index'])->name('tags.index');
        Route::post('/tags', [LeadTagController::class, 'store'])->name('tags.store');
        Route::delete('/tags/{tag}', [LeadTagController::class, 'destroy'])->name('tags.destroy');
        Route::post('/leads/{lead}/tags/attach', [LeadTagController::class, 'attach'])->name('leads.tags.attach');
        Route::delete('/leads/{lead}/tags/{tag}/detach', [LeadTagController::class, 'detach'])->name('leads.tags.detach');

        // ── CRM Web Widgets
        Route::resource('widgets', CrmWidgetController::class);

        // ── CRM Settings
        Route::get('/settings', [\Modules\CRM\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [\Modules\CRM\Http\Controllers\SettingController::class, 'store'])->name('settings.store');

        // ── Campaigns 
        Route::resource('campaigns', \Modules\CRM\Http\Controllers\CampaignController::class);
        Route::post('campaigns/{campaign}/schedule', [\Modules\CRM\Http\Controllers\CampaignController::class, 'schedule'])->name('campaigns.schedule');
        Route::post('campaigns/{campaign}/pause', [\Modules\CRM\Http\Controllers\CampaignController::class, 'pause'])->name('campaigns.pause');
        Route::post('campaigns/{campaign}/resume', [\Modules\CRM\Http\Controllers\CampaignController::class, 'resume'])->name('campaigns.resume');
        
        // ── Sequences
        Route::resource('sequences', \Modules\CRM\Http\Controllers\SequenceController::class);

        // ── WhatsApp 
        Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
            Route::resource('accounts', \Modules\CRM\Http\Controllers\WhatsApp\AccountController::class);
            Route::post('accounts/{account}/connect', [\Modules\CRM\Http\Controllers\WhatsApp\AccountController::class, 'connect'])->name('accounts.connect');
            Route::post('accounts/{account}/disconnect', [\Modules\CRM\Http\Controllers\WhatsApp\AccountController::class, 'disconnect'])->name('accounts.disconnect');
            Route::get('accounts/{account}/qr', [\Modules\CRM\Http\Controllers\WhatsApp\AccountController::class, 'getQrCode'])->name('accounts.qr');
        });
});
