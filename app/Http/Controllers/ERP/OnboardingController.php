<?php

namespace App\Http\Controllers\ERP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('ERP/Onboarding');
    }
}
