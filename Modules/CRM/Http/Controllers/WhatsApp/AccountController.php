<?php

namespace Modules\CRM\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Modules\CRMWhatsAppInbox\Services\WhatsAppSessionManager;
use App\Modules\CRMWhatsAppInbox\Services\CRMWhatsAppLimitsService;
use Modules\CRM\Http\Requests\WhatsApp\StoreAccountRequest;
use Modules\CRM\Http\Resources\WhatsApp\AccountResource;
use Modules\CRM\Models\WhatsAppAccount;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(
        protected WhatsAppSessionManager $sessionManager,
        protected CRMWhatsAppLimitsService $limitsService,
    ) {}

    public function index()
    {
        if (!feature('crm.wa_inbox')) {
            return response()->json(['upgrade_required' => true], 403);
        }

        $workspaceId = session('crm_workspace_id');
        $accounts = $this->sessionManager->getAccountsWithHealth($workspaceId);

        return AccountResource::collection($accounts);
    }

    public function store(StoreAccountRequest $request)
    {
        $workspaceId = session('crm_workspace_id');

        if (!$this->limitsService->canUse($workspaceId, 'max_connected_whatsapp_accounts')) {
            return response()->json([
                'error'            => 'Maximum connected WhatsApp accounts reached.',
                'upgrade_required' => true,
            ], 429);
        }

        $account = WhatsAppAccount::create(array_merge($request->validated(), [
            'workspace_id' => $workspaceId,
        ]));

        return response()->json(['account' => new AccountResource($account)], 201);
    }

    public function show(WhatsAppAccount $account)
    {
        return response()->json(['account' => new AccountResource($account)]);
    }

    public function update(Request $request, WhatsAppAccount $account)
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'is_default'  => 'sometimes|boolean',
            'assigned_to' => 'sometimes|nullable|integer|exists:users,id',
        ]);

        $account->update($validated);

        return response()->json(['account' => new AccountResource($account)]);
    }

    public function destroy(WhatsAppAccount $account)
    {
        if ($account->isConnected()) {
            $this->sessionManager->disconnect($account);
        }

        $account->delete();

        return response()->json(['message' => 'Account deleted.']);
    }

    public function connect(WhatsAppAccount $account)
    {
        $result = $this->sessionManager->initiateConnection($account);

        return response()->json($result);
    }

    public function disconnect(WhatsAppAccount $account)
    {
        $this->sessionManager->disconnect($account);

        return response()->json(['message' => 'Account disconnected.']);
    }

    public function getQrCode(WhatsAppAccount $account)
    {
        $qr = $this->sessionManager->getQrCode($account);

        return response()->json([
            'qr_code'    => $qr,
            'expires_at' => $account->qr_expires_at?->toIso8601String(),
        ]);
    }

    public function health(WhatsAppAccount $account)
    {
        $health = $this->sessionManager->checkHealth($account);

        return response()->json($health);
    }
}
