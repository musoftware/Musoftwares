<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Freelance\Models\Job;
use Inertia\Inertia;

class JobController extends Controller
{
    public function index()
    {
        $jobs = Job::with('client')->paginate(15);
        return Inertia::render('Freelance/Jobs/Index', ['jobs' => $jobs]);
    }
}
