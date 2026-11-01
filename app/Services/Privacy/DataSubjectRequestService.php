<?php

declare(strict_types=1);

namespace App\Services\Privacy;

use App\Enums\DataSubjectRequestStatus;
use App\Enums\DataSubjectRequestType;
use App\Models\AdminAuditLog;
use App\Models\DataSubjectRequest;
use App\Models\User;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DataSubjectRequestService
{
    public function __construct(
        private readonly DataRetentionService $retentionService
    ) {}

    public function submitRequest(
        User $user,
        DataSubjectRequestType $type,
        ?string $details = null
    ): DataSubjectRequest {
        $existingOpen = DataSubjectRequest::query()
            ->where('user_id', $user->id)
            ->where('request_type', $type->value)
            ->whereIn('status', [
                DataSubjectRequestStatus::Pending->value,
                DataSubjectRequestStatus::InProgress->value,
            ])
            ->first();

        if ($existingOpen) {
            return $existingOpen;
        }

        $now = now();

        return DataSubjectRequest::create([
            'user_id' => $user->id,
            'request_type' => $type,
            'status' => $type === DataSubjectRequestType::AccessData
                ? DataSubjectRequestStatus::Completed
                : DataSubjectRequestStatus::Pending,
            'details' => $details,
            'requested_at' => $now,
            'due_at' => DataSubjectRequest::calculateDueAt($now),
            'completed_at' => $type === DataSubjectRequestType::AccessData ? $now : null,
        ]);
    }

    /**
     * Build a structured personal data package for portability (JSON or PDF export).
     *
     * @return array<string, mixed>
     */
    public function buildUserDataExport(User $user): array
    {
        return [
            'exported_at_cairo' => now()->timezone('Africa/Cairo')->toIso8601String(),
            'legal_framework' => 'Egyptian Personal Data Protection Law (Law No. 151 of 2020)',
            'account' => $this->extractAccountSection($user),
            'secondary_emails' => $user->emails()->get(['email', 'verified_at', 'source', 'created_at'])->toArray(),
            'consents_audit_log' => $this->extractConsentsSection($user),
            'data_subject_requests' => $this->extractRequestsSection($user),
            'invoices_summary' => $this->extractInvoicesSection($user),
        ];
    }

    public function resolveRequest(
        DataSubjectRequest $dsr,
        User $admin,
        DataSubjectRequestStatus $status,
        ?string $adminNotes = null,
        ?Request $httpRequest = null
    ): DataSubjectRequest {
        if ($status === DataSubjectRequestStatus::RejectedWithReason && trim((string) $adminNotes) === '') {
            throw new InvalidArgumentException('A clear legal or accounting reason is required when rejecting a request.');
        }

        $targetUser = $dsr->user;
        if (
            $status === DataSubjectRequestStatus::Completed
            && $dsr->request_type === DataSubjectRequestType::DeleteAccount
            && $targetUser
        ) {
            $this->assertAccountCanBeErased($targetUser);
            $this->retentionService->anonymizeAndSoftDeleteUser($targetUser);
        }

        $isTerminal = in_array($status, [
            DataSubjectRequestStatus::Completed,
            DataSubjectRequestStatus::RejectedWithReason,
        ], true);

        $dsr->update([
            'status' => $status,
            'processed_by_admin_id' => $admin->id,
            'admin_notes' => $adminNotes,
            'completed_at' => $isTerminal ? now() : null,
        ]);

        AdminAuditLog::create([
            'actor_user_id' => $admin->id,
            'actor_ip' => $httpRequest?->ip(),
            'actor_user_agent' => $httpRequest?->userAgent(),
            'action' => 'dsr.status.updated',
            'severity' => AdminAuditLog::SEVERITY_WARNING,
            'target_type' => DataSubjectRequest::class,
            'target_id' => $dsr->id,
            'meta' => ['status' => $status->value, 'request_type' => $dsr->request_type->value],
        ]);

        return $dsr->refresh();
    }

    private function assertAccountCanBeErased(User $user): void
    {
        if (method_exists($user, 'hasUnpaidInvoices') && $user->hasUnpaidInvoices()) {
            throw new InvalidArgumentException('Account has unpaid invoices. Reject with statutory tax/financial reason instead.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function extractAccountSection(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'mobile_1' => $user->mobile_1,
            'mobile_2' => $user->mobile_2,
            'whatsapp_number' => $user->whatsapp_number,
            'telegram_username' => $user->telegram_username,
            'country' => $user->country,
            'city' => $user->city,
            'email_verified_at' => $user->email_verified_at?->timezone('Africa/Cairo')->toIso8601String(),
            'created_at' => $user->created_at?->timezone('Africa/Cairo')->toIso8601String(),
            'last_activity_at' => $user->last_activity_at?->timezone('Africa/Cairo')->toIso8601String(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractConsentsSection(User $user): array
    {
        return $user->consentLogs()
            ->orderByDesc('id')
            ->get()
            ->map(static fn ($log): array => [
                'consent_type' => $log->consent_type->value ?? (string) $log->consent_type,
                'action' => $log->action->value ?? (string) $log->action,
                'policy_version' => $log->policy_version,
                'ip_address' => $log->ip_address,
                'created_at_cairo' => $log->created_at?->timezone('Africa/Cairo')->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractRequestsSection(User $user): array
    {
        return $user->dataSubjectRequests()
            ->orderByDesc('id')
            ->get()
            ->map(static fn (DataSubjectRequest $r): array => [
                'id' => $r->id,
                'request_type' => $r->request_type->value,
                'status' => $r->status->value,
                'requested_at_cairo' => $r->requested_at?->timezone('Africa/Cairo')->toIso8601String(),
                'completed_at_cairo' => $r->completed_at?->timezone('Africa/Cairo')->toIso8601String(),
                'admin_notes' => $r->admin_notes,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractInvoicesSection(User $user): array
    {
        if (! method_exists($user, 'invoices')) {
            return [];
        }

        return $user->invoices()
            ->latest('id')
            ->limit(100)
            ->get(['id', 'status', 'paid', 'unpaid', 'created_at'])
            ->map(static fn ($inv): array => [
                'invoice_number' => $inv->id,
                'status' => $inv->status,
                'paid' => $inv->paid,
                'unpaid' => $inv->unpaid,
                'created_at_cairo' => $inv->created_at?->timezone('Africa/Cairo')->toIso8601String(),
            ])
            ->all();
    }
}

