<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\DashboardController;
use Modules\CRM\Http\Controllers\LeadController;
use Modules\CRM\Http\Controllers\SequenceController;
use Modules\CRM\Http\Controllers\CampaignController;
use Modules\CRM\Http\Controllers\LeadNoteController;
use Modules\CRM\Http\Controllers\LeadTagController;
use Modules\CRM\Http\Controllers\WorkspaceController;
use Modules\CRM\Http\Controllers\Api\KanbanController;

use Modules\CRM\Http\Controllers\SearchController;

// WhatsApp Inbox Controllers
use Modules\CRM\Http\Controllers\WhatsApp\InboxController;
use Modules\CRM\Http\Controllers\WhatsApp\ConversationController;
use Modules\CRM\Http\Controllers\WhatsApp\MessageController;
use Modules\CRM\Http\Controllers\WhatsApp\AccountController;
use Modules\CRM\Http\Controllers\WhatsApp\LabelController;
use Modules\CRM\Http\Controllers\WhatsApp\AutomationRuleController;
use Modules\CRM\Http\Controllers\WhatsApp\QuickReplyController;
use Modules\CRM\Http\Controllers\WhatsApp\SlaPolicyController;
use Modules\CRM\Http\Controllers\WhatsApp\AnalyticsController;
use Modules\CRM\Http\Controllers\WhatsApp\WebhookController;

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

        // ── CRM Settings
        Route::get('/settings', [\Modules\CRM\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [\Modules\CRM\Http\Controllers\SettingController::class, 'store'])->name('settings.store');

        // ══════════════════════════════════════════════════════════════
        // ── WhatsApp Inbox Routes ────────────────────────────────────
        // ══════════════════════════════════════════════════════════════
        Route::prefix('whatsapp')->name('whatsapp.')->group(function () {

            // ── Inbox ────────────────────────────────────────────────
            Route::get('/', [InboxController::class, 'index'])->name('inbox');
            Route::get('/search', [InboxController::class, 'search'])->name('search');

            // ── Accounts ─────────────────────────────────────────────
            Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
            Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
            Route::get('/accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');
            Route::put('/accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
            Route::delete('/accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');
            Route::post('/accounts/{account}/connect', [AccountController::class, 'connect'])->name('accounts.connect');
            Route::post('/accounts/{account}/disconnect', [AccountController::class, 'disconnect'])->name('accounts.disconnect');
            Route::get('/accounts/{account}/qr', [AccountController::class, 'getQrCode'])->name('accounts.qr');
            Route::get('/accounts/{account}/health', [AccountController::class, 'health'])->name('accounts.health');

            // ── Conversations ────────────────────────────────────────
            Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
            Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
            Route::post('/conversations/{conversation}/assign', [ConversationController::class, 'assign'])->name('conversations.assign');
            Route::post('/conversations/{conversation}/transfer', [ConversationController::class, 'transfer'])->name('conversations.transfer');
            Route::post('/conversations/{conversation}/resolve', [ConversationController::class, 'resolve'])->name('conversations.resolve');
            Route::post('/conversations/{conversation}/reopen', [ConversationController::class, 'reopen'])->name('conversations.reopen');
            Route::post('/conversations/{conversation}/pin', [ConversationController::class, 'togglePin'])->name('conversations.pin');
            Route::post('/conversations/{conversation}/star', [ConversationController::class, 'toggleStar'])->name('conversations.star');

            // ── Messages ─────────────────────────────────────────────
            Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index'])->name('messages.index');
            Route::post('/conversations/{conversation}/messages', [MessageController::class, 'send'])->name('messages.send');
            Route::post('/conversations/{conversation}/notes', [MessageController::class, 'addNote'])->name('messages.note');
            Route::post('/conversations/{conversation}/typing', [MessageController::class, 'typing'])->name('messages.typing');
            Route::post('/messages/{message}/star', [MessageController::class, 'toggleStar'])->name('messages.star');
            Route::post('/messages/{message}/react', [MessageController::class, 'react'])->name('messages.react');

            // ── Labels ───────────────────────────────────────────────
            Route::get('/labels', [LabelController::class, 'index'])->name('labels.index');
            Route::post('/labels', [LabelController::class, 'store'])->name('labels.store');
            Route::put('/labels/{label}', [LabelController::class, 'update'])->name('labels.update');
            Route::delete('/labels/{label}', [LabelController::class, 'destroy'])->name('labels.destroy');
            Route::post('/conversations/{conversation}/labels', [LabelController::class, 'attach'])->name('labels.attach');
            Route::delete('/conversations/{conversation}/labels/{label}', [LabelController::class, 'detach'])->name('labels.detach');

            // ── Automation Rules ─────────────────────────────────────
            Route::get('/automations', [AutomationRuleController::class, 'index'])->name('automations.index');
            Route::post('/automations', [AutomationRuleController::class, 'store'])->name('automations.store');
            Route::get('/automations/{automation}', [AutomationRuleController::class, 'show'])->name('automations.show');
            Route::put('/automations/{automation}', [AutomationRuleController::class, 'update'])->name('automations.update');
            Route::delete('/automations/{automation}', [AutomationRuleController::class, 'destroy'])->name('automations.destroy');
            Route::post('/automations/{automation}/toggle', [AutomationRuleController::class, 'toggle'])->name('automations.toggle');

            // ── Quick Replies ────────────────────────────────────────
            Route::get('/quick-replies', [QuickReplyController::class, 'index'])->name('quick-replies.index');
            Route::post('/quick-replies', [QuickReplyController::class, 'store'])->name('quick-replies.store');
            Route::put('/quick-replies/{quickReply}', [QuickReplyController::class, 'update'])->name('quick-replies.update');
            Route::delete('/quick-replies/{quickReply}', [QuickReplyController::class, 'destroy'])->name('quick-replies.destroy');

            // ── SLA Policies ─────────────────────────────────────────
            Route::get('/sla-policies', [SlaPolicyController::class, 'index'])->name('sla-policies.index');
            Route::post('/sla-policies', [SlaPolicyController::class, 'store'])->name('sla-policies.store');
            Route::get('/sla-policies/{slaPolicy}', [SlaPolicyController::class, 'show'])->name('sla-policies.show');
            Route::put('/sla-policies/{slaPolicy}', [SlaPolicyController::class, 'update'])->name('sla-policies.update');
            Route::delete('/sla-policies/{slaPolicy}', [SlaPolicyController::class, 'destroy'])->name('sla-policies.destroy');

            // ── Analytics ────────────────────────────────────────────
            Route::get('/analytics/overview', [AnalyticsController::class, 'overview'])->name('analytics.overview');
            Route::get('/analytics/agents', [AnalyticsController::class, 'agentPerformance'])->name('analytics.agents');
            Route::get('/analytics/sla', [AnalyticsController::class, 'slaCompliance'])->name('analytics.sla');
        });

        // ══════════════════════════════════════════════════════════════
        // ── WhatsApp Campaign Routes ─────────────────────────────────
        // ══════════════════════════════════════════════════════════════
        Route::prefix('whatsapp-campaigns')->name('whatsapp-campaigns.')->group(function () {

            // ── Campaigns CRUD + Lifecycle ────────────────────────────
            Route::get('/', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignController::class, 'index'])->name('index');
            Route::get('/create', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignController::class, 'create'])->name('create');
            Route::post('/', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignController::class, 'store'])->name('store');
            Route::get('/{campaign}', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignController::class, 'show'])->name('show');
            Route::get('/{campaign}/edit', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignController::class, 'edit'])->name('edit');
            Route::put('/{campaign}', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignController::class, 'update'])->name('update');
            Route::delete('/{campaign}', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignController::class, 'destroy'])->name('destroy');
            Route::post('/{campaign}/start', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignController::class, 'start'])->name('start');
            Route::post('/{campaign}/schedule', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignController::class, 'schedule'])->name('schedule');
            Route::post('/{campaign}/pause', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignController::class, 'pause'])->name('pause');
            Route::post('/{campaign}/resume', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignController::class, 'resume'])->name('resume');
            Route::post('/{campaign}/cancel', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignController::class, 'cancel'])->name('cancel');
            Route::post('/{campaign}/duplicate', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignController::class, 'duplicate'])->name('duplicate');

            // ── Audiences ────────────────────────────────────────────
            Route::get('/audiences', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\AudienceController::class, 'index'])->name('audiences.index');
            Route::post('/audiences', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\AudienceController::class, 'store'])->name('audiences.store');
            Route::get('/audiences/{audience}', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\AudienceController::class, 'show'])->name('audiences.show');
            Route::put('/audiences/{audience}', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\AudienceController::class, 'update'])->name('audiences.update');
            Route::delete('/audiences/{audience}', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\AudienceController::class, 'destroy'])->name('audiences.destroy');
            Route::post('/audiences/preview', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\AudienceController::class, 'preview'])->name('audiences.preview');
            Route::post('/audiences/{audience}/resolve', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\AudienceController::class, 'resolve'])->name('audiences.resolve');

            // ── Templates ────────────────────────────────────────────
            Route::get('/templates', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\TemplateController::class, 'index'])->name('templates.index');
            Route::post('/templates', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\TemplateController::class, 'store'])->name('templates.store');
            Route::put('/templates/{template}', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\TemplateController::class, 'update'])->name('templates.update');
            Route::delete('/templates/{template}', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\TemplateController::class, 'destroy'])->name('templates.destroy');
            Route::post('/templates/preview', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\TemplateController::class, 'preview'])->name('templates.preview');

            // ── Sequences ────────────────────────────────────────────
            Route::get('/{campaign}/sequences', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\SequenceController::class, 'index'])->name('sequences.index');
            Route::post('/{campaign}/sequences', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\SequenceController::class, 'store'])->name('sequences.store');
            Route::put('/sequences/{sequence}', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\SequenceController::class, 'update'])->name('sequences.update');
            Route::delete('/sequences/{sequence}', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\SequenceController::class, 'destroy'])->name('sequences.destroy');
            Route::post('/sequences/{sequence}/toggle', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\SequenceController::class, 'toggle'])->name('sequences.toggle');
            Route::post('/sequences/{sequence}/steps', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\SequenceController::class, 'storeStep'])->name('sequences.steps.store');
            Route::put('/sequences/steps/{step}', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\SequenceController::class, 'updateStep'])->name('sequences.steps.update');
            Route::delete('/sequences/steps/{step}', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\SequenceController::class, 'destroyStep'])->name('sequences.steps.destroy');
            Route::post('/sequences/{sequence}/reorder', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\SequenceController::class, 'reorderSteps'])->name('sequences.reorder');

            // ── Deliveries ───────────────────────────────────────────
            Route::get('/{campaign}/deliveries', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\DeliveryController::class, 'index'])->name('deliveries.index');
            Route::post('/deliveries/{delivery}/retry', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\DeliveryController::class, 'retry'])->name('deliveries.retry');
            Route::post('/{campaign}/deliveries/retry-all', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\DeliveryController::class, 'retryAll'])->name('deliveries.retry-all');
            Route::get('/{campaign}/deliveries/stats', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\DeliveryController::class, 'stats'])->name('deliveries.stats');

            // ── Analytics ────────────────────────────────────────────
            Route::get('/analytics/overview', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\AnalyticsController::class, 'overview'])->name('analytics.overview');
            Route::get('/analytics/{campaign}', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\AnalyticsController::class, 'campaign'])->name('analytics.campaign');
            Route::post('/analytics/compare', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\AnalyticsController::class, 'compare'])->name('analytics.compare');
            Route::get('/analytics/{campaign}/trends', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\AnalyticsController::class, 'trends'])->name('analytics.trends');
            Route::get('/analytics/{campaign}/export', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\AnalyticsController::class, 'export'])->name('analytics.export');

            // ── Automations ──────────────────────────────────────────
            Route::get('/automations', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignAutomationController::class, 'index'])->name('automations.index');
            Route::post('/automations', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignAutomationController::class, 'store'])->name('automations.store');
            Route::put('/automations/{campaign}', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignAutomationController::class, 'update'])->name('automations.update');
            Route::delete('/automations/{campaign}', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignAutomationController::class, 'destroy'])->name('automations.destroy');
            Route::post('/automations/{campaign}/toggle', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignAutomationController::class, 'toggle'])->name('automations.toggle');
        });
});

// ══════════════════════════════════════════════════════════════════════
// ── WhatsApp Webhook (External — NO auth middleware) ──────────────────
// ══════════════════════════════════════════════════════════════════════
Route::post('/crm/whatsapp/webhook/{account}', [WebhookController::class, 'handle'])
    ->name('crm.whatsapp.webhook')
    ->middleware('web')
    ->withoutMiddleware(['auth', 'verified', 'onboarding', 'subscription:crm', \Modules\CRM\Http\Middleware\WorkspaceMiddleware::class]);

// ── Campaign Webhook (External — NO auth middleware) ──────────────────
Route::post('/crm/whatsapp-campaigns/webhook', [\Modules\CRM\Http\Controllers\WhatsAppCampaign\CampaignWebhookController::class, 'handle'])
    ->name('crm.whatsapp-campaigns.webhook')
    ->middleware('web')
    ->withoutMiddleware(['auth', 'verified', 'onboarding', 'subscription:crm', \Modules\CRM\Http\Middleware\WorkspaceMiddleware::class]);

