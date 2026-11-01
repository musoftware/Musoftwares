<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\DataSubjectRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\DataSubjectRequest;
use App\Services\Privacy\DataSubjectRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class AdminDataSubjectRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $statusFilter = $request->query('status');

        $query = DataSubjectRequest::query()
            ->with([
                'user' => static fn ($q) => $q->withTrashed()->select(['id', 'name', 'email', 'deleted_at']),
                'processedByAdmin:id,name',
            ])
            ->latest('id');

        if (is_string($statusFilter) && $statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        $paginator = $query->paginate(25)->through(static function (DataSubjectRequest $item): array {
            return [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'user_name' => $item->user?->name ?? 'Anonymized User',
                'user_email' => $item->user?->email ?? '-',
                'user_deleted' => $item->user?->trashed() ?? false,
                'request_type' => $item->request_type->value,
                'status' => $item->status->value,
                'details' => $item->details,
                'admin_notes' => $item->admin_notes,
                'requested_at' => $item->requested_at?->timezone('Africa/Cairo')->format('Y-m-d H:i'),
                'due_at' => $item->due_at?->timezone('Africa/Cairo')->format('Y-m-d H:i'),
                'completed_at' => $item->completed_at?->timezone('Africa/Cairo')->format('Y-m-d H:i'),
                'processed_by_name' => $item->processedByAdmin?->name,
            ];
        });

        return Inertia::render('Admin/Privacy/Requests', [
            'requests' => $paginator,
            'currentStatus' => $statusFilter ?: '',
        ]);
    }

    public function update(
        Request $request,
        DataSubjectRequest $dataSubjectRequest,
        DataSubjectRequestService $dsrService
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                'in:pending,in_progress,completed,rejected_with_reason',
            ],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $dsrService->resolveRequest(
                $dataSubjectRequest,
                $request->user(),
                DataSubjectRequestStatus::from($validated['status']),
                $validated['admin_notes'] ?? null,
                $request
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['admin_notes' => $e->getMessage()]);
        }

        return back()->with('success', __('privacy_compliance.admin_dsr_updated_toast'));
    }
}
