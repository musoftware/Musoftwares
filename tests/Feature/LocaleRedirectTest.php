<?php

namespace Tests\Feature;

use App\Models\BlogArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LocaleRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_localized_blog_url_redirects_to_canonical_url_and_sets_session(): void
    {
        $response = $this->get('/ar/blog/kyfy-adaf-aaadaaa-hkykyyn-mn-ay-mgmoaa-tylygram-al-mgmoaatk');

        $response->assertStatus(301);
        $response->assertRedirect('/blog/kyfy-adaf-aaadaaa-hkykyyn-mn-ay-mgmoaa-tylygram-al-mgmoaatk');
        $response->assertSessionHas('locale', 'ar');
    }

    public function test_localized_root_url_redirects_to_home_and_sets_session(): void
    {
        $response = $this->get('/ar');

        $response->assertStatus(301);
        $response->assertRedirect('/');
        $response->assertSessionHas('locale', 'ar');
    }

    public function test_localized_page_with_query_string_preserves_query_params(): void
    {
        $response = $this->get('/en/blog?search=telegram&page=2');

        $response->assertStatus(301);
        $this->assertStringContainsString('/blog?', $response->headers->get('Location'));
        $this->assertStringContainsString('search=telegram', $response->headers->get('Location'));
        $this->assertStringContainsString('page=2', $response->headers->get('Location'));
        $response->assertSessionHas('locale', 'en');
    }

    public function test_blog_show_redirects_to_group_translation_if_matching_group_found(): void
    {
        $groupId = (string) Str::uuid();

        $enArticle = BlogArticle::create([
            'title' => 'How to add Telegram members',
            'slug' => 'how-to-add-telegram-members',
            'language' => 'en',
            'group_id' => $groupId,
            'content' => 'Content en',
            'excerpt' => 'Excerpt en',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $arArticle = BlogArticle::create([
            'title' => 'كيفية اضافة اعضاء تيليجرام',
            'slug' => 'kyfy-adaf-aaadaaa-tylygram',
            'language' => 'ar',
            'group_id' => $groupId,
            'content' => 'Content ar',
            'excerpt' => 'Excerpt ar',
            'is_published' => true,
            'published_at' => now(),
        ]);

        // When in Arabic locale and requesting the English slug, it redirects to the Arabic article slug
        $response = $this->withSession(['locale' => 'ar'])
            ->get('/blog/how-to-add-telegram-members');

        $response->assertStatus(301);
        $response->assertRedirect(route('blog.show', 'kyfy-adaf-aaadaaa-tylygram'));
    }
}
