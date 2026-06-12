<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\WebsiteService;
use Illuminate\Support\Facades\Storage;

class WebsiteServiceController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/WebsiteServices/Index', [
            'services' => WebsiteService::latest()->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/WebsiteServices/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('website_services', 'public');
        }
        unset($data['image']);

        WebsiteService::create($data);
        return redirect()->route('admin.website-services.index')->with('success', __('admin.service_created'));
    }

    public function edit(WebsiteService $website_service)
    {
        return Inertia::render('Admin/WebsiteServices/Edit', [
            'service' => $website_service
        ]);
    }

    public function update(Request $request, WebsiteService $website_service)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('image')) {
            if ($website_service->image_path) {
                Storage::disk('public')->delete($website_service->image_path);
            }
            $data['image_path'] = $request->file('image')->store('website_services', 'public');
        }
        unset($data['image']);

        $website_service->update($data);
        return redirect()->route('admin.website-services.index')->with('success', __('admin.service_updated'));
    }

    public function destroy(WebsiteService $website_service)
    {
        if ($website_service->image_path) {
            Storage::disk('public')->delete($website_service->image_path);
        }
        $website_service->delete();
        return redirect()->route('admin.website-services.index')->with('success', __('admin.service_deleted'));
    }
}
