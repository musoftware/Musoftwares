<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        return $this->clientDashboard($user);
    }

    private function clientDashboard($user)
    {
        $dashboardService = app(DashboardService::class);
        $data = $dashboardService->getClientDashboardData($user);

        return Inertia::render('Client/Dashboard', $data);
    }
}
