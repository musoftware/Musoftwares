<?php

namespace App\Http\Controllers;

use App\Models\BlogArticle;
use Inertia\Inertia;

class BlogController extends Controller
{
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
