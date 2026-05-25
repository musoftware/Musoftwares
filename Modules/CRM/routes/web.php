<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\DashboardController;
use Modules\CRM\Http\Controllers\LeadController;
use Modules\CRM\Http\Controllers\SequenceController;
use Modules\CRM\Http\Controllers\CampaignController;
use Modules\CRM\Http\Controllers\LeadNoteController;
use Modules\CRM\Http\Controllers\LeadTagController;

use Modules\CRM\Http\Controllers\SearchController;

// ── CRM Module Routes ──────────────────────────────────────────────
Route::middleware(['web', 'auth', 'verified', 'onboarding', 'subscription:crm', \Modules\CRM\Http\Middleware\WorkspaceMiddleware::class])
    ->prefix('crm')
    ->name('crm.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        // Universal Search
        Route::get('/search', [SearchController::class, 'index'])->name('search');
        
        // ── Leads
        Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
        Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
        Route::post('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.update-status');
        Route::post('/leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
        Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');

        // ── Lead Notes & Tags
        Route::post('/leads/{lead}/notes', [LeadNoteController::class, 'store'])->name('leads.notes.store');
        Route::put('/leads/{lead}/notes/{note}', [LeadNoteController::class, 'update'])->name('leads.notes.update');
        Route::delete('/leads/{lead}/notes/{note}', [LeadNoteController::class, 'destroy'])->name('leads.notes.destroy');

        Route::get('/tags', [LeadTagController::class, 'index'])->name('tags.index');
        Route::post('/tags', [LeadTagController::class, 'store'])->name('tags.store');
        Route::delete('/tags/{tag}', [LeadTagController::class, 'destroy'])->name('tags.destroy');
        Route::post('/leads/{lead}/tags/attach', [LeadTagController::class, 'attach'])->name('leads.tags.attach');
        Route::delete('/leads/{lead}/tags/{tag}/detach', [LeadTagController::class, 'detach'])->name('leads.tags.detach');

        // ── CRM Sequences
        Route::get('/sequences', [SequenceController::class, 'index'])->name('sequences.index');
        Route::post('/sequences', [SequenceController::class, 'store'])->name('sequences.store');
        Route::get('/sequences/{sequence}', [SequenceController::class, 'show'])->name('sequences.show');
        Route::delete('/sequences/{sequence}', [SequenceController::class, 'destroy'])->name('sequences.destroy');
        Route::post('/sequences/{sequence}/steps', [SequenceController::class, 'storeStep'])->name('sequences.steps.store');
        Route::put('/sequences/steps/{step}', [SequenceController::class, 'updateStep'])->name('sequences.steps.update');
        Route::delete('/sequences/steps/{step}', [SequenceController::class, 'deleteStep'])->name('sequences.steps.destroy');
        Route::post('/sequences/{sequence}/generate-ai', [SequenceController::class, 'generateStepsWithAI'])->name('sequences.generate-ai');
        Route::post('/sequences/{sequence}/apply-ai', [SequenceController::class, 'applyGeneratedSteps'])->name('sequences.apply-ai');

        // ── CRM Campaigns
        Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
        Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
        Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
        Route::put('/campaigns/{campaign}', [CampaignController::class, 'update'])->name('campaigns.update');
        Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');
        Route::post('/campaigns/generate-ai-content', [CampaignController::class, 'generateAIContent'])->name('campaigns.generate-ai');
        Route::post('/campaigns/{campaign}/schedule', [CampaignController::class, 'schedule'])->name('campaigns.schedule');
});
