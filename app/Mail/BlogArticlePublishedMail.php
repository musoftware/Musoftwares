<?php

namespace App\Mail;

use App\Models\BlogArticle;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells the owner a new article went live, with links to read it and to fix or hide it if something is wrong. */
class BlogArticlePublishedMail extends Mailable
{
    public function __construct(public readonly BlogArticle $article) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'مقال جديد اتنشر: '.$this->article->title);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.blog_article_published',
            with: [
                'title' => $this->article->title,
                'language' => $this->article->language,
                'url' => route('blog.show', $this->article->slug),
                'editUrl' => route('admin.blog-articles.edit', $this->article->id),
            ],
        );
    }
}
