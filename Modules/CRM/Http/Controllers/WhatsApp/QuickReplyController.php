<?php

namespace Modules\CRM\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use Modules\CRM\Http\Requests\WhatsApp\StoreQuickReplyRequest;
use Modules\CRM\Models\WhatsAppQuickReply;

class QuickReplyController extends Controller
{
    public function index()
    {
        if (!feature('crm.wa_inbox')) {
            return response()->json(['upgrade_required' => true], 403);
        }

        $replies = WhatsAppQuickReply::orderBy('title')->get();
        return response()->json(['quick_replies' => $replies]);
    }

    public function store(StoreQuickReplyRequest $request)
    {
        $reply = WhatsAppQuickReply::create(array_merge($request->validated(), [
            'workspace_id' => session('crm_workspace_id'),
            'created_by'   => auth()->id(),
        ]));

        return response()->json(['quick_reply' => $reply], 201);
    }

    public function update(StoreQuickReplyRequest $request, WhatsAppQuickReply $quickReply)
    {
        $quickReply->update($request->validated());
        return response()->json(['quick_reply' => $quickReply]);
    }

    public function destroy(WhatsAppQuickReply $quickReply)
    {
        $quickReply->delete();
        return response()->json(['message' => 'Quick reply deleted.']);
    }
}
