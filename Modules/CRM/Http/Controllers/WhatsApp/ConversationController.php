<?php

namespace Modules\CRM\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Modules\CRMWhatsAppInbox\Events\WhatsAppConversationResolved;
use App\Modules\CRMWhatsAppInbox\Services\ConversationAssignmentEngine;
use App\Modules\CRMWhatsAppInbox\Services\WhatsAppSlaEngine;
use Modules\CRM\Http\Requests\WhatsApp\AssignConversationRequest;
use Modules\CRM\Http\Resources\WhatsApp\ConversationResource;
use Modules\CRM\Http\Resources\WhatsApp\MessageResource;
use Modules\CRM\Models\WhatsAppConversation;
use App\Models\User;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(
        protected ConversationAssignmentEngine $assignmentEngine,
        protected WhatsAppSlaEngine $slaEngine,
    ) {}

    public function show(WhatsAppConversation $conversation)
    {
        if (!feature('crm.wa_inbox')) {
            return response()->json(['upgrade_required' => true], 403);
        }

        $conversation->load(['account:id,name,phone_number', 'assignedAgent:id,name,email,profile_photo_path', 'labels', 'lead.tags', 'slaPolicy', 'participants.user:id,name']);

        // Mark as read
        $conversation->markAsRead();

        return response()->json([
            'conversation' => new ConversationResource($conversation),
            'messages'     => MessageResource::collection(
                $conversation->messages()->with('sender:id,name,profile_photo_path')->paginate(50)
            ),
        ]);
    }

    public function assign(AssignConversationRequest $request, WhatsAppConversation $conversation)
    {
        $agent = User::findOrFail($request->validated('agent_id'));

        $this->assignmentEngine->manualAssign(
            $conversation,
            $agent,
            auth()->user(),
            $request->validated('reason')
        );

        return response()->json(['message' => 'Conversation assigned successfully.']);
    }

    public function transfer(Request $request, WhatsAppConversation $conversation)
    {
        $validated = $request->validate(['department' => 'required|string|max:100']);

        $this->assignmentEngine->transfer($conversation, $validated['department'], auth()->user());

        return response()->json(['message' => 'Conversation transferred.']);
    }

    public function resolve(WhatsAppConversation $conversation)
    {
        $this->slaEngine->recordResolution($conversation);

        event(new WhatsAppConversationResolved(
            $conversation->workspace_id,
            $conversation,
            auth()->id()
        ));

        return response()->json(['message' => 'Conversation resolved.']);
    }

    public function reopen(WhatsAppConversation $conversation)
    {
        $conversation->update([
            'status'      => 'open',
            'resolved_at' => null,
        ]);

        return response()->json(['message' => 'Conversation reopened.']);
    }

    public function togglePin(WhatsAppConversation $conversation)
    {
        $conversation->update(['is_pinned' => !$conversation->is_pinned]);

        return response()->json(['is_pinned' => $conversation->is_pinned]);
    }

    public function toggleStar(WhatsAppConversation $conversation)
    {
        $conversation->update(['is_starred' => !$conversation->is_starred]);

        return response()->json(['is_starred' => $conversation->is_starred]);
    }
}
