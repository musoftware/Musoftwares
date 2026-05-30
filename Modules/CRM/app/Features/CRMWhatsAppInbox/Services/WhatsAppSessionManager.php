<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Services;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Contracts\WhatsAppProviderInterface;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppAccountConnected;
use Modules\CRM\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Crypt;

class WhatsAppSessionManager
{
    public function __construct(
        protected WhatsAppProviderInterface $provider,
    ) {}

    /**
     * Initiate a new WhatsApp connection (QR code flow).
     */
    public function initiateConnection(WhatsAppAccount $account): array
    {
        $account->update(['status' => 'connecting']);

        $result = $this->provider->connect($account);

        if (!empty($result['qr_code'])) {
            $account->update([
                'qr_code'       => $result['qr_code'],
                'qr_expires_at' => now()->addMinutes(2),
            ]);
        }

        return $result;
    }

    /**
     * Handle successful connection callback.
     */
    public function handleConnectionSuccess(WhatsAppAccount $account, array $sessionData): void
    {
        $account->update([
            'status'        => 'connected',
            'session_data'  => Crypt::encryptString(json_encode($sessionData)),
            'qr_code'       => null,
            'qr_expires_at' => null,
            'last_seen_at'  => now(),
        ]);

        event(new WhatsAppAccountConnected($account->workspace_id, $account));
    }

    /**
     * Disconnect an account.
     */
    public function disconnect(WhatsAppAccount $account): bool
    {
        $result = $this->provider->disconnect($account);

        $account->update([
            'status'       => 'disconnected',
            'session_data' => null,
            'qr_code'      => null,
            'last_seen_at' => now(),
        ]);

        return $result;
    }

    /**
     * Attempt to reconnect using stored session data.
     */
    public function reconnect(WhatsAppAccount $account): array
    {
        if (!$account->session_data) {
            return $this->initiateConnection($account);
        }

        $account->update(['status' => 'connecting']);

        try {
            $result = $this->provider->connect($account);

            if (($result['status'] ?? '') === 'connected') {
                $account->update([
                    'status'       => 'connected',
                    'last_seen_at' => now(),
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            // Session expired, need fresh QR
            $account->update(['session_data' => null]);
            return $this->initiateConnection($account);
        }
    }

    /**
     * Get the current QR code for pairing.
     */
    public function getQrCode(WhatsAppAccount $account): ?string
    {
        if ($account->isConnected()) {
            return null;
        }

        // If QR expired, regenerate
        if ($account->isQrExpired() || !$account->qr_code) {
            $qr = $this->provider->getQrCode($account);
            if ($qr) {
                $account->update([
                    'qr_code'       => $qr,
                    'qr_expires_at' => now()->addMinutes(2),
                ]);
            }
            return $qr;
        }

        return $account->qr_code;
    }

    /**
     * Check and update device health.
     */
    public function checkHealth(WhatsAppAccount $account): array
    {
        $status = $this->provider->getStatus($account);
        $deviceInfo = [];

        if ($status === 'connected') {
            $deviceInfo = $this->provider->getDeviceInfo($account);
        }

        $account->update([
            'status'        => $status,
            'health_status' => $deviceInfo ?: null,
            'last_seen_at'  => $status === 'connected' ? now() : $account->last_seen_at,
        ]);

        return [
            'status'      => $status,
            'device_info' => $deviceInfo,
            'last_seen'   => $account->last_seen_at?->diffForHumans(),
        ];
    }

    /**
     * Get all accounts for a workspace with their health status.
     */
    public function getAccountsWithHealth(int $workspaceId): \Illuminate\Database\Eloquent\Collection
    {
        return WhatsAppAccount::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->with('assignedUser:id,name,email')
            ->get();
    }
}
