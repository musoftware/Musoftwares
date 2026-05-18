<?php

namespace Modules\Intelligence\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Intelligence\Models\IntelligenceSwipeCollection;

class SwipeVaultController extends Controller
{
    public function index()
    {
        $collections = IntelligenceSwipeCollection::withCount('items')->get();

        return Inertia::render('Intelligence/SwipeVault/Index', [
            'collections' => $collections
        ]);
    }
}
