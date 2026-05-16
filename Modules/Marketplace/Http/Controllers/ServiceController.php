<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Marketplace\Models\Service;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with('seller')->paginate(15);
        return Inertia::render('Marketplace/Services/Index', ['services' => $services]);
    }
}
