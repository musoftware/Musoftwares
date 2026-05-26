<?php

namespace Modules\CRM\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use Modules\CRM\Http\Requests\WhatsApp\StoreLabelRequest;
use Modules\CRM\Http\Resources\WhatsApp\LabelResource;
use Modules\CRM\Models\WhatsAppLabel;
use Modules\CRM\Models\WhatsAppConversation;

class LabelController extends Controller
{
    public function index()
    {
        if (!feature('crm.wa_inbox')) {
            return response()->json(['upgrade_required' => true], 403);
        }

        $labels = WhatsAppLabel::orderBy('sort_order')->get();
        return LabelResource::collection($labels);
    }

    public function store(StoreLabelRequest $request)
    {
        $label = WhatsAppLabel::create(array_merge($request->validated(), [
            'workspace_id' => session('crm_workspace_id'),
        ]));

        return response()->json(['label' => new LabelResource($label)], 201);
    }

    public function update(StoreLabelRequest $request, WhatsAppLabel $label)
    {
        $label->update($request->validated());
        return response()->json(['label' => new LabelResource($label)]);
    }

    public function destroy(WhatsAppLabel $label)
    {
        $label->delete();
        return response()->json(['message' => 'Label deleted.']);
    }

    public function attach(\Illuminate\Http\Request $request, WhatsAppConversation $conversation)
    {
        $validated = $request->validate(['label_id' => 'required|integer|exists:crm_whatsapp_labels,id']);
        $conversation->labels()->syncWithoutDetaching([$validated['label_id']]);

        activity()->log('whatsapp.conversation.label_added', $conversation, null, ['label_id' => $validated['label_id']]);

        return response()->json(['message' => 'Label attached.']);
    }

    public function detach(WhatsAppConversation $conversation, WhatsAppLabel $label)
    {
        $conversation->labels()->detach($label->id);

        activity()->log('whatsapp.conversation.label_removed', $conversation, null, ['label_id' => $label->id]);

        return response()->json(['message' => 'Label detached.']);
    }
}
