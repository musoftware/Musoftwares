<?php

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Modules\Tools\Models\WaAccount;
use Modules\Tools\Models\WaCampaign;
use Modules\Tools\Models\WaContact;
use Modules\Tools\Models\WaConversation;
use Modules\Tools\Models\WaMessage;
use Modules\Tools\Models\WaWorkflow;
use Modules\Tools\Jobs\ProcessWaCampaignJob;
use Modules\Tools\Jobs\ScheduleWarmupJob;
use Modules\Tools\Jobs\SyncWaInboxJob;

class WhatsAppController extends Controller
{
    protected int $runtimePort = 18400;

    // ── Accounts ─────────────────────────────────────────────────────────────

    public function listAccounts(Request $request)
    {
        $accounts = WaAccount::where('user_id', Auth::id())
            ->orderByDesc('health_score')
            ->get();

        // Enrich with live data from runtime
        $liveData = $this->getRuntimeSessions();

        return response()->json([
            'accounts' => $accounts->map(function ($acc) use ($liveData) {
                $live = collect($liveData)->firstWhere('accountId', $acc->session_id);
                return array_merge($acc->toArray(), [
                    'live_state'   => $live['state'] ?? 'unknown',
                    'live_health'  => $live['health'] ?? null,
                    'live_velocity' => null,
                ]);
            }),
        ]);
    }

    public function createAccount(Request $request)
    {
        $validated = $request->validate([
            'label'       => 'required|string|max:100',
            'proxy'       => 'nullable|url',
            'pool_numbers' => 'nullable|array',
        ]);

        $sessionId = 'wa-' . Auth::id() . '-' . uniqid();

        $account = WaAccount::create([
            'user_id'    => Auth::id(),
            'label'      => $validated['label'],
            'session_id' => $sessionId,
            'proxy'      => $validated['proxy'] ?? null,
            'pool_numbers' => $validated['pool_numbers'] ?? [],
        ]);

        return response()->json(['account' => $account, 'session_id' => $sessionId], 201);
    }

    public function connectAccount(Request $request, $accountId)
    {
        $account = WaAccount::where('user_id', Auth::id())->where('id', $accountId)->firstOrFail();

        // Trigger runtime to connect
        $response = $this->callRuntime("POST", "/whatsapp/sessions/{$account->session_id}/connect", [
            'proxy'    => $account->proxy,
            'headless' => false,
        ]);

        if ($response && $response->successful()) {
            $account->update(['status' => 'connecting', 'last_seen_at' => now()]);
            return response()->json(['queued' => true, 'session_id' => $account->session_id]);
        }

        return response()->json(['error' => 'Runtime unreachable'], 503);
    }

    public function disconnectAccount(Request $request, $accountId)
    {
        $account = WaAccount::where('user_id', Auth::id())->where('id', $accountId)->firstOrFail();
        $this->callRuntime("POST", "/whatsapp/sessions/{$account->session_id}/disconnect");
        $account->update(['status' => 'disconnected']);
        return response()->json(['ok' => true]);
    }

    public function accountHealth(Request $request, $accountId)
    {
        $account = WaAccount::where('user_id', Auth::id())->where('id', $accountId)->firstOrFail();
        $health  = $this->callRuntime("GET", "/whatsapp/sessions/{$account->session_id}/health");

        return response()->json([
            'account' => $account,
            'health'  => $health?->json('health') ?? $account->only(['health_score', 'trust_grade', 'warmup_day']),
        ]);
    }

    public function deleteAccount(Request $request, $accountId)
    {
        $account = WaAccount::where('user_id', Auth::id())->where('id', $accountId)->firstOrFail();
        $this->callRuntime("POST", "/whatsapp/sessions/{$account->session_id}/disconnect");
        $account->delete();
        return response()->json(['deleted' => true]);
    }

    // ── Campaigns ─────────────────────────────────────────────────────────────

    public function listCampaigns(Request $request)
    {
        $campaigns = WaCampaign::where('user_id', Auth::id())
            ->withCount(['contacts as contact_count'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($campaigns);
    }

    public function createCampaign(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:200',
            'account_ids'      => 'required|array|min:1',
            'message_template' => 'required|string',
            'media_url'        => 'nullable|url',
            'humanize_preset'  => 'in:conservative,moderate,aggressive',
            'max_block_rate'   => 'nullable|numeric|min:0.01|max:0.2',
            'contacts'         => 'required|array|min:1',
            'contacts.*.phone' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $campaign = WaCampaign::create([
                'user_id'          => Auth::id(),
                'name'             => $validated['name'],
                'account_ids'      => $validated['account_ids'],
                'message_template' => $validated['message_template'],
                'media_url'        => $validated['media_url'] ?? null,
                'humanize_preset'  => $validated['humanize_preset'] ?? 'moderate',
                'max_block_rate'   => $validated['max_block_rate'] ?? 0.05,
                'total_contacts'   => count($validated['contacts']),
                'status'           => 'draft',
            ]);

            // Upsert contacts + attach to campaign
            foreach ($validated['contacts'] as $c) {
                $contact = WaContact::firstOrCreate(
                    ['user_id' => Auth::id(), 'phone' => $c['phone']],
                    ['name' => $c['name'] ?? null, 'company' => $c['company'] ?? null]
                );
                DB::table('wa_campaign_contacts')->insertOrIgnore([
                    'campaign_id' => $campaign->id,
                    'contact_id'  => $contact->id,
                    'status'      => 'pending',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['campaign' => $campaign], 201);
    }

    public function startCampaign(Request $request, $campaignId)
    {
        $campaign = WaCampaign::where('user_id', Auth::id())->where('id', $campaignId)->firstOrFail();

        if (!in_array($campaign->status, ['draft', 'paused'])) {
            return response()->json(['error' => 'Campaign already running or completed'], 422);
        }

        $campaign->update(['status' => 'running', 'started_at' => now()]);

        // Dispatch queue job to call runtime
        ProcessWaCampaignJob::dispatch($campaign->id, Auth::id());

        return response()->json(['started' => true, 'campaign_id' => $campaign->id]);
    }

    public function pauseCampaign(Request $request, $campaignId)
    {
        $campaign = WaCampaign::where('user_id', Auth::id())->where('id', $campaignId)->firstOrFail();

        if ($campaign->runtime_task_id) {
            $this->callRuntime("POST", "/tasks/{$campaign->runtime_task_id}/stop");
        }

        $campaign->update(['status' => 'paused']);
        return response()->json(['paused' => true]);
    }

    public function campaignAnalytics(Request $request, $campaignId)
    {
        $campaign = WaCampaign::where('user_id', Auth::id())->where('id', $campaignId)->firstOrFail();

        $byStatus = DB::table('wa_campaign_contacts')
            ->where('campaign_id', $campaignId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return response()->json([
            'campaign'      => $campaign,
            'stats'         => [
                'total'   => $campaign->total_contacts,
                'sent'    => $campaign->sent,
                'failed'  => $campaign->failed,
                'blocked' => $campaign->blocked,
                'skipped' => $campaign->skipped,
            ],
            'by_status'     => $byStatus,
            'block_rate'    => $campaign->sent > 0 ? round(($campaign->blocked / $campaign->sent) * 100, 2) : 0,
        ]);
    }

    // ── Inbox ─────────────────────────────────────────────────────────────────

    public function listConversations(Request $request)
    {
        $conversations = WaConversation::where('user_id', Auth::id())
            ->with(['contact:id,phone,name,lead_stage,engagement_score', 'assignedUser:id,name'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->account_id, fn($q) => $q->where('account_id', $request->account_id))
            ->when($request->assigned_to, fn($q) => $q->where('assigned_to', $request->assigned_to))
            ->orderByDesc('last_msg_at')
            ->paginate(30);

        return response()->json($conversations);
    }

    public function getConversation(Request $request, $phone)
    {
        $conversation = WaConversation::where('user_id', Auth::id())
            ->where('phone', $phone)
            ->with(['contact', 'assignedUser:id,name'])
            ->first();

        $messages = WaMessage::where('user_id', Auth::id())
            ->where('phone', $phone)
            ->orderBy('created_at')
            ->limit(100)
            ->get();

        // Mark as read
        if ($conversation) {
            $conversation->update(['unread_count' => 0]);
        }

        return response()->json(['conversation' => $conversation, 'messages' => $messages]);
    }

    public function sendReply(Request $request, $phone)
    {
        $request->validate(['message' => 'required|string', 'account_id' => 'required|string']);

        $account = WaAccount::where('user_id', Auth::id())
            ->where('session_id', $request->account_id)
            ->firstOrFail();

        // Store message in DB
        WaMessage::create([
            'user_id'    => Auth::id(),
            'phone'      => $phone,
            'account_id' => $account->session_id,
            'direction'  => 'outbound',
            'content'    => $request->message,
            'status'     => 'queued',
        ]);

        // Trigger via runtime API
        $response = $this->callRuntime("POST", "/whatsapp/inbox/{$phone}/reply", [
            'accountId' => $account->session_id,
            'message'   => $request->message,
        ]);

        return response()->json(['queued' => true, 'runtime_response' => $response?->json()]);
    }

    public function updateConversation(Request $request, $phone)
    {
        $conversation = WaConversation::where('user_id', Auth::id())
            ->where('phone', $phone)->first();

        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        $conversation->fill(array_filter([
            'label'       => $request->label,
            'assigned_to' => $request->assign_to,
            'status'      => $request->status,
        ]))->save();

        return response()->json(['ok' => true, 'conversation' => $conversation]);
    }

    // ── Contacts ─────────────────────────────────────────────────────────────

    public function listContacts(Request $request)
    {
        $contacts = WaContact::where('user_id', Auth::id())
            ->when($request->lead_stage, fn($q) => $q->where('lead_stage', $request->lead_stage))
            ->when($request->language,   fn($q) => $q->where('language', $request->language))
            ->when($request->search,     fn($q) => $q->where(function ($q2) use ($request) {
                $q2->where('phone', 'like', "%{$request->search}%")
                   ->orWhere('name', 'like', "%{$request->search}%");
            }))
            ->orderByDesc('engagement_score')
            ->paginate(50);

        return response()->json($contacts);
    }

    public function importContacts(Request $request)
    {
        $request->validate(['contacts' => 'required|array|min:1', 'contacts.*.phone' => 'required']);

        $created = 0;
        $updated = 0;

        foreach ($request->contacts as $c) {
            $existing = WaContact::where('user_id', Auth::id())->where('phone', $c['phone'])->first();
            if ($existing) {
                $existing->fill(array_filter($c))->save();
                $updated++;
            } else {
                WaContact::create(array_merge($c, ['user_id' => Auth::id()]));
                $created++;
            }
        }

        return response()->json(['created' => $created, 'updated' => $updated, 'total' => $created + $updated]);
    }

    // ── Quality Dashboard ─────────────────────────────────────────────────────

    public function qualityDashboard(Request $request)
    {
        $accounts = WaAccount::where('user_id', Auth::id())->get();

        // Get live velocity data from runtime
        $liveQuality = $this->callRuntime("GET", "/whatsapp/quality")?->json('accounts') ?? [];

        // Recent quality events from DB
        $recentEvents = DB::table('wa_quality_events')
            ->where('user_id', Auth::id())
            ->orderByDesc('occurred_at')
            ->limit(50)
            ->get();

        return response()->json([
            'accounts'      => $accounts->map(fn($a) => array_merge($a->toArray(), [
                'live' => collect($liveQuality)->firstWhere('accountId', $a->session_id),
            ])),
            'recent_events' => $recentEvents,
            'summary' => [
                'total_accounts'  => $accounts->count(),
                'connected'       => $accounts->where('status', 'connected')->count(),
                'warming'         => $accounts->where('status', 'warming')->count(),
                'banned'          => $accounts->where('status', 'banned')->count(),
                'avg_health'      => $accounts->avg('health_score') ?? 0,
            ],
        ]);
    }

    // ── Workflows ─────────────────────────────────────────────────────────────

    public function listWorkflows(Request $request)
    {
        return response()->json([
            'workflows' => WaWorkflow::where('user_id', Auth::id())
                ->orderByDesc('created_at')->get(),
        ]);
    }

    public function createWorkflow(Request $request)
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:200',
            'nodes'  => 'required|array',
            'edges'  => 'required|array',
            'trigger_type' => 'in:manual,keyword,new_contact,campaign_end',
        ]);

        $workflow = WaWorkflow::create(array_merge($validated, ['user_id' => Auth::id()]));
        return response()->json(['workflow' => $workflow], 201);
    }

    // ── Runtime Proxy Helpers ─────────────────────────────────────────────────

    private function callRuntime(string $method, string $path, array $data = [])
    {
        try {
            $url = "http://127.0.0.1:{$this->runtimePort}{$path}";
            return Http::timeout(10)->{strtolower($method)}($url, $data);
        } catch (\Throwable) {
            return null;
        }
    }

    private function getRuntimeSessions(): array
    {
        $resp = $this->callRuntime("GET", "/whatsapp/sessions");
        return $resp?->json('sessions') ?? [];
    }
}
