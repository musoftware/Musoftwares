<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $user = Auth::user();

        return $this->clientDashboard($user);
    }

    private function clientDashboard($user)
    {
        $dashboardService = app(\App\Services\DashboardService::class);
        $data = $dashboardService->getClientDashboardData($user);

        return Inertia::render('Client/Dashboard', $data);
    }

}

