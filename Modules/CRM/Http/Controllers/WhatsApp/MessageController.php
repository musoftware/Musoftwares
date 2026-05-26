<?php

namespace Modules\CRM\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Modules\CRMWhatsAppInbox\Exceptions\UsageLimitExceededException;
use App\Modules\CRMWhatsAppInbox\Services\MessageDeliveryService;
use App\Modules\CRMWhatsAppInbox\Services\WhatsAppMediaService;
use App\Modules\CRMWhatsAppInbox\Services\WhatsAppRealtimeBroadcaster;
use Modules\CRM\Http\Requests\WhatsApp\SendMessageRequest;
use Modules\CRM\Http\Resources\WhatsApp\MessageResource;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppMessage;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        protected MessageDeliveryService $deliveryService,
        protected WhatsAppMediaService $mediaService,
        protected WhatsAppRealtimeBroadcaster $broadcaster,
    ) {}

    public function index(WhatsAppConversation $conversation, Request $request)
    {
        if (!feature('crm.wa_inbox')) {
            return response()->json(['upgrade_required' => true], 403);
        }

        $messages = $conversation->messages()
            ->with('sender:id,name,profile_photo_path')
            ->when($request->boolean('starred_only'), fn($q) => $q->starred())
            ->latest()
            ->paginate($request->input('per_page', 50));

        return MessageResource::collection($messages);
    }

    public function send(SendMessageRequest $request, WhatsAppConversation $conversation)
    {
        try {
            $validated = $request->validated();

            // Handle media upload
            if ($request->hasFile('media')) {
                $media = $this->mediaService->upload($request->file('media'), $conversation->workspace_id);
                $message = $this->deliveryService->sendMedia(
                    $conversation,
                    $media['url'],
                    $validated['type'] ?? 'image',
                    auth()->id(),
                    $validated['body'] ?? null,
                    $media['mime_type'],
                    $media['filename'],
                    $media['size']
                );
            } elseif (($validated['type'] ?? 'text') === 'template') {
                $message = $this->deliveryService->sendTemplate(
                    $conversation,
                    $validated['template_name'],
                    $validated['template_params'] ?? [],
                    auth()->id()
                );
            } else {
                $scheduledAt = !empty($validated['scheduled_at'])
                    ? \Carbon\Carbon::parse($validated['scheduled_at'])
                    : null;

                $message = $this->deliveryService->sendText(
                    $conversation,
                    $validated['body'],
                    auth()->id(),
                    $validated['quoted_message_id'] ?? null,
                    $scheduledAt
                );
            }

            return response()->json([
                'message' => new MessageResource($message),
            ], 201);
        } catch (UsageLimitExceededException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'upgrade_required' => true,
            ], 429);
        }
    }

    public function addNote(Request $request, WhatsAppConversation $conversation)
    {
        $validated = $request->validate([
            'body'     => 'required|string|max:5000',
            'mentions' => 'nullable|array',
        ]);

        $message = $this->deliveryService->addInternalNote(
            $conversation,
            $validated['body'],
            auth()->id(),
            $validated['mentions'] ?? null
        );

        return response()->json(['message' => new MessageResource($message)], 201);
    }

    public function typing(Request $request, WhatsAppConversation $conversation)
    {
        $this->broadcaster->broadcastTyping(
            $conversation->workspace_id,
            $conversation->id,
            auth()->id(),
            $request->boolean('is_typing', true)
        );

        return response()->json(['ok' => true]);
    }

    public function toggleStar(WhatsAppMessage $message)
    {
        $message->update(['is_starred' => !$message->is_starred]);

        return response()->json(['is_starred' => $message->is_starred]);
    }

    public function react(Request $request, WhatsAppMessage $message)
    {
        $validated = $request->validate(['emoji' => 'required|string|max:10']);

        $reaction = $this->deliveryService->react($message, $validated['emoji'], auth()->id());

        return response()->json(['message' => new MessageResource($reaction)], 201);
    }
}
