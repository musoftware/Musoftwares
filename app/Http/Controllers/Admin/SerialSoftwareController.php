<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SerialSoftware;
use App\Services\SerialSoftwareService;
use App\Http\Requests\Admin\SerialSoftware\UpdateSerialSoftwareStatusRequest;
use App\Http\Requests\Admin\SerialSoftware\StoreSerialSoftwareRequest;
use App\Http\Resources\SerialSoftwareResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin: manage serial softwares (program registry).
 * Copied from old project: Admin/SerialSoftwareController.
 *
 * Softwares are auto-created by the API check-in endpoint.
 * Admin can: view license counts, change default_status, delete.
 *
 * Note: Fixed typo from old project: 'desteoy' → 'destroy'.
 */
class SerialSoftwareController extends Controller
{
    public function __construct(
        protected SerialSoftwareService $serialSoftwareService
    ) {}

    public function index(): Response
    {
        $softwares = SerialSoftware::query()
            ->withCount('devices')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn($sw) => (new SerialSoftwareResource($sw))->resolve());

        return Inertia::render('Admin/SerialSoftwares/Index', [
            'softwares' => $softwares,
            'filters'   => request()->only(['search']),
        ]);
    }

    /**
     * Update the default_status of a software.
     * This affects NEW devices on their first check-in only.
     * Existing device statuses are NOT changed.
     */
    public function updateStatus(UpdateSerialSoftwareStatusRequest $request, SerialSoftware $serialSoftware): RedirectResponse
    {
        $this->serialSoftwareService->updateStatus($serialSoftware, $request->validated('status'));

        return back()->with('success', 'Software default status updated.');
    }

    /**
     * Create a software manually (usually auto-created by API).
     */
    public function store(StoreSerialSoftwareRequest $request): RedirectResponse
    {
        $this->serialSoftwareService->createSoftware($request->validated());

        return back()->with('success', 'Software added.');
    }

    public function destroy(SerialSoftware $serialSoftware): RedirectResponse
    {
        $this->serialSoftwareService->deleteSoftware($serialSoftware);

        return back()->with('success', 'Software deleted.');
    }
}
