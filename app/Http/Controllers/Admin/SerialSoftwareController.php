<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tools\SerialSoftware;
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
    public function index(): Response
    {
        $softwares = SerialSoftware::query()
            ->withCount('devices')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn($sw) => [
                'id'              => $sw->id,
                'name'            => $sw->name,
                'default_status'  => $sw->default_status,
                'total_licenses'  => $sw->devices_count,
                'active_count'    => $sw->devices()->where('status', 'active')->count(),
                'inactive_count'  => $sw->devices()->where('status', 'inactive')->count(),
                'created_at'      => $sw->created_at?->diffForHumans(),
            ]);

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
    public function updateStatus(Request $request, SerialSoftware $serialSoftware): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(SerialSoftware::statuses())],
        ]);

        $serialSoftware->update(['default_status' => $data['status']]);

        return back()->with('success', 'Software default status updated.');
    }

    /**
     * Create a software manually (usually auto-created by API).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255', 'unique:serial_softwares,name'],
            'default_status' => ['required', Rule::in(SerialSoftware::statuses())],
        ]);

        SerialSoftware::create($data);

        return back()->with('success', 'Software added.');
    }

    public function destroy(SerialSoftware $serialSoftware): RedirectResponse
    {
        $serialSoftware->delete();

        return back()->with('success', 'Software deleted.');
    }
}
