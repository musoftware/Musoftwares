<?php

namespace Modules\Marketplace\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceLandingPage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminServiceLandingPageController extends Controller
{
    /**
     * Display a listing of all landing pages for admin.
     */
    public function index(Request $request)
    {
        $query = Service::whereHas('landingPage')
            ->with(['seller', 'landingPage.formSubmissions', 'landingPage.variants'])
            ->latest();

        if ($request->filled('q')) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%") // Service title
                  ->orWhereHas('seller', function($u) use ($searchTerm) { // User name/email
                      $u->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('landingPage', function($lp) use ($searchTerm) { // Landing page details
                      $lp->where('hero_title', 'like', "%{$searchTerm}%")
                         ->orWhere('slug', 'like', "%{$searchTerm}%");
                  });
            });
        }

        $servicesWithLandingPages = $query->paginate(15)->through(function ($service) {
            return [
                'id' => $service->id,
                'title' => $service->title,
                'user' => $service->seller ? [
                    'name' => $service->seller->name,
                    'email' => $service->seller->email,
                ] : null,
                'landing_page' => $service->landingPage ? [
                    'id' => $service->landingPage->id,
                    'slug' => $service->landingPage->slug,
                    'hero_title' => $service->landingPage->hero_title,
                    'is_active' => $service->landingPage->is_active,
                    'variants' => $service->landingPage->variants,
                    'formSubmissions' => $service->landingPage->formSubmissions,
                ] : null,
            ];
        });

        return Inertia::render('Admin/Marketplace/ServiceLandingPages/Index', [
            'servicesWithLandingPages' => $servicesWithLandingPages,
            'filters' => $request->only(['q']),
            'translations' => [
                'service_landing_pages' => __('admin.service_landing_pages'),
                'manage_landing_pages' => __('admin.manage_landing_pages'),
                'manage_all_landing_pages' => __('admin.manage_all_landing_pages'),
                'service' => __('admin.service'),
                'seller' => __('admin.seller'),
                'hero_title' => __('admin.hero_title'),
                'views_ab' => __('admin.views_ab'),
                'leads' => __('admin.leads'),
                'status' => __('admin.status'),
                'actions' => __('admin.actions'),
                'variants' => __('admin.variants'),
                'no_ab_test' => __('admin.no_ab_test'),
                'no_landing_pages_found' => __('admin.no_landing_pages_found'),
                'view' => __('admin.view'),
                'delete' => __('admin.delete'),
                'confirm_delete' => __('admin.confirm_delete_landing_page'),
            ],
        ]);
    }

    /**
     * Toggle the active status of a landing page.
     */
    public function toggleStatus(ServiceLandingPage $landingPage)
    {
        $landingPage->update([
            'is_active' => !$landingPage->is_active
        ]);

        return back()->with('success', 'Landing page status updated successfully.');
    }

    /**
     * Delete a landing page.
     */
    public function destroy(ServiceLandingPage $landingPage)
    {
        $landingPage->delete();

        return back()->with('success', 'Landing page deleted successfully.');
    }
}
