<?php

namespace App\Mail;

use App\Models\BlogArticle;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

/** Tells the owner that this week's blog draft is waiting for a read. It is never published automatically. */
class BlogDraftReadyMail extends Mailable
{
    /** @param  Collection<int, BlogArticle>  $articles */
    public function __construct(public readonly Collection $articles) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'مسودة مقال جديدة للمراجعة: '.$this->articles->first()->title);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.blog_draft_ready',
            with: [
                'drafts' => $this->articles->map(fn (BlogArticle $article) => [
                    'title' => $article->title,
                    'language' => $article->language,
                    'editUrl' => route('admin.blog-articles.edit', $article->id),
                ])->all(),
            ],
        );
    }
}
