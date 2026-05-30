<?php

namespace Modules\CRM\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Services\WhatsAppInboxService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\CRM\Http\Resources\WhatsApp\ConversationResource;

class InboxController extends Controller
{
    public function __construct(
        protected WhatsAppInboxService $inboxService,
    ) {}

    public function index(Request $request)
    {
        if (!feature('crm.wa_inbox')) {
            return Inertia::render('CRM/Upsell/WhatsAppInbox');
        }

        $workspaceId = session('crm_workspace_id');

        $conversations = $this->inboxService->getInbox($workspaceId, $request->only([
            'status', 'type', 'assigned_agent_id', 'unassigned', 'label_id',
            'search', 'account_id', 'priority', 'sla_breached', 'is_pinned', 'per_page',
        ]));

        return Inertia::render('CRM/WhatsApp/Inbox', [
            'conversations' => ConversationResource::collection($conversations),
            'filters'       => $request->only(['status', 'type', 'assigned_agent_id', 'search', 'priority']),
        ]);
    }

    public function search(Request $request)
    {
        if (!feature('crm.wa_inbox')) {
            return response()->json(['upgrade_required' => true], 403);
        }

        $workspaceId = session('crm_workspace_id');

        $results = $this->inboxService->searchMessages($workspaceId, $request->input('q', ''), $request->only([
            'conversation_id', 'type', 'sender_type', 'date_from', 'date_to', 'per_page',
        ]));

        return response()->json($results);
    }
}
