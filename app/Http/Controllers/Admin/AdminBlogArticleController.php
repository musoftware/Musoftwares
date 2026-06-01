<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogArticle;
use App\Http\Requests\Admin\BlogArticle\StoreBlogArticleRequest;
use App\Http\Requests\Admin\BlogArticle\UpdateBlogArticleRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Marketplace\Models\Service;

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

    /**
     * Show the form for creating a new article.
     */
    public function create()
    {
        // For admin, we can load all active services, or standard services.
        // If there are many, standard is to use async combobox, but for now we'll pass a basic list.
        $services = Service::select('id', 'title')->get();

        return Inertia::render('Admin/BlogArticles/Create', [
            'services' => $services
        ]);
    }

    /**
     * Store a newly created article in storage.
     */
    public function store(StoreBlogArticleRequest $request)
    {
        BlogArticle::create($request->validated());

        return redirect()->route('admin.blog-articles.index')
                         ->with('success', __('admin.article_created_successfully', [], 'en'));
    }

    /**
     * Show the form for editing the specified article.
     */
    public function edit(BlogArticle $blog_article)
    {
        $services = Service::select('id', 'title')->get();

        return Inertia::render('Admin/BlogArticles/Edit', [
            'article' => $blog_article,
            'services' => $services
        ]);
    }

    /**
     * Update the specified article in storage.
     */
    public function update(UpdateBlogArticleRequest $request, BlogArticle $blog_article)
    {
        $blog_article->update($request->validated());

        return redirect()->route('admin.blog-articles.index')
                         ->with('success', __('admin.article_updated_successfully', [], 'en'));
    }

    /**
     * Remove the specified article from storage.
     */
    public function destroy(BlogArticle $blog_article)
    {
        $blog_article->delete();

        return redirect()->route('admin.blog-articles.index')
                         ->with('success', __('admin.article_deleted_successfully', [], 'en'));
    }
}
