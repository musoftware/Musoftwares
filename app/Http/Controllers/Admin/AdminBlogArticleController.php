<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogArticle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminBlogArticleController extends Controller
{
    /**
     * Display a listing of all blog articles for admin.
     */
    public function index(Request $request)
    {
        $query = BlogArticle::with('service.seller')->latest();

        if ($request->filled('q')) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('content', 'like', "%{$searchTerm}%");
            });
        }

        $articles = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/BlogArticles/Index', [
            'articles' => $articles,
            'filters' => [
                'q' => $request->q
            ]
        ]);
    }
}
