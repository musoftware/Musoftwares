<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogArticle\StoreBlogArticleRequest;
use App\Http\Requests\Admin\BlogArticle\UpdateBlogArticleRequest;
use App\Models\BlogArticle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Marketplace\Models\Service;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

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
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('content', 'like', "%{$searchTerm}%");
            });
        }

        $articles = $query->paginate(20)->withQueryString();

        // Retrieve active services and calculate post counts for each language
        $services = Service::where('status', 'active')->get();

        $articlesCount = BlogArticle::select('service_id', 'language', DB::raw('count(*) as count'))
            ->whereIn('service_id', $services->pluck('id'))
            ->groupBy('service_id', 'language')
            ->get()
            ->groupBy('service_id');

        $services->each(function ($service) use ($articlesCount) {
            $serviceCounts = $articlesCount->get($service->id) ?? collect();
            $service->articles_en_count = $serviceCounts->where('language', 'en')->first()?->count ?? 0;
            $service->articles_ar_count = $serviceCounts->where('language', 'ar')->first()?->count ?? 0;
        });

        return Inertia::render('Admin/BlogArticles/Index', [
            'articles' => $articles,
            'services' => $services,
            'filters' => [
                'q' => $request->q,
            ],
        ]);
    }

    /**
     * Manually trigger AI blog article generation for a specific service.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:marketplace_services,id',
            'lang' => 'required|in:en,ar,all',
        ]);

        $service = Service::findOrFail($request->service_id);

        try {
            Artisan::call('blog:generate-articles', [
                '--service_id' => $service->id,
                '--limit' => $request->lang === 'all' ? 2 : 1,
                '--lang' => $request->lang,
            ]);

            return back()->with('success', __('admin.blog_generated_successfully') ?: 'AI Blog Article(s) generated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate articles: ' . $e->getMessage());
        }
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
            'services' => $services,
        ]);
    }

    /**
     * Store a newly created article in storage.
     */
    public function store(StoreBlogArticleRequest $request)
    {
        BlogArticle::create($request->validated());

        return redirect()->route('admin.blog-articles.index')
            ->with('success', __('admin.article_created_successfully'));
    }

    /**
     * Show the form for editing the specified article.
     */
    public function edit(BlogArticle $blog_article)
    {
        $services = Service::select('id', 'title')->get();

        return Inertia::render('Admin/BlogArticles/Edit', [
            'article' => $blog_article,
            'services' => $services,
        ]);
    }

    /**
     * Update the specified article in storage.
     */
    public function update(UpdateBlogArticleRequest $request, BlogArticle $blog_article)
    {
        $blog_article->update($request->validated());

        return redirect()->route('admin.blog-articles.index')
            ->with('success', __('admin.article_updated_successfully'));
    }

    /**
     * Remove the specified article from storage.
     */
    public function destroy(BlogArticle $blog_article)
    {
        $blog_article->delete();

        return redirect()->route('admin.blog-articles.index')
            ->with('success', __('admin.article_deleted_successfully'));
    }

}
