<?php

namespace App\Http\Controllers;

use App\Models\BlogArticle;
use Inertia\Inertia;

class BlogController extends Controller
{
    /**
     * Display a listing of the blog articles publicly.
     */
    public function index()
    {
        $articles = BlogArticle::with('service.seller')
            ->published()
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        return Inertia::render('Public/Blog/Index', [
            'articles' => $articles
        ])->withViewData([
            'meta' => [
                'title' => 'Blog | Musoftware',
                'description' => 'Read the latest insights, tutorials, and updates from the Musoftware team.',
                'url' => url()->current(),
            ]
        ]);
    }

    /**
     * Display the specified blog article publicly.
     */
    public function show(string $slug)
    {
        $article = BlogArticle::with('service.seller')
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return Inertia::render('Public/Blog/Show', [
            'article' => $article
        ])->withViewData([
            'meta' => [
                'title' => ($article->meta_title ?: $article->title) . ' | Musoftware',
                'description' => $article->meta_description ?: $article->excerpt,
                'image' => $article->featured_image ? asset('storage/' . $article->featured_image) : asset('images/default-meta.png'),
                'url' => url()->current(),
            ]
        ]);
    }
}
