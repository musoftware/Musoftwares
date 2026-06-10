<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class PublicPageController extends Controller
{
    public function howItWorks()
    {
        return Inertia::render('Freelance/HowItWorks');
    }

    public function aboutUs()
    {
        return Inertia::render('Freelance/AboutUs');
    }
}
