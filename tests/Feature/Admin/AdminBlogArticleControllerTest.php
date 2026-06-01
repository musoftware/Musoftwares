<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\BlogArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBlogArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function createAdmin()
    {
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        return $admin;
    }

    public function test_admin_can_access_blog_articles_index()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.blog-articles.index'));

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_blog_articles_index()
    {
        $user = User::factory()->create(['onboarding_completed' => true]);

        $response = $this->actingAs($user)->get(route('admin.blog-articles.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_access_create_blog_article_page()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.blog-articles.create'));

        $response->assertStatus(200);
    }

    public function test_admin_can_store_blog_article()
    {
        $admin = $this->createAdmin();

        $payload = [
            'language' => 'en',
            'title' => 'Test Article',
            'content' => 'This is a test article content.',
            'slug' => 'test-article',
            'is_published' => true,
        ];

        $response = $this->actingAs($admin)->post(route('admin.blog-articles.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.blog-articles.index'));

        $this->assertDatabaseHas('blog_articles', [
            'title' => 'Test Article',
            'slug' => 'test-article',
        ]);
    }

    public function test_admin_can_access_edit_blog_article_page()
    {
        $admin = $this->createAdmin();
        $article = BlogArticle::create([
            'language' => 'en',
            'title' => 'Old Article',
            'content' => 'Old content',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.blog-articles.edit', $article->id));

        $response->assertStatus(200);
    }

    public function test_admin_can_update_blog_article()
    {
        $admin = $this->createAdmin();
        $article = BlogArticle::create([
            'language' => 'en',
            'title' => 'Old Article',
            'content' => 'Old content',
            'slug' => 'old-article'
        ]);

        $payload = [
            'language' => 'ar',
            'title' => 'Updated Article',
            'content' => 'Updated content',
            'slug' => 'updated-article'
        ];

        $response = $this->actingAs($admin)->put(route('admin.blog-articles.update', $article->id), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.blog-articles.index'));

        $this->assertDatabaseHas('blog_articles', [
            'id' => $article->id,
            'title' => 'Updated Article',
            'slug' => 'updated-article',
            'language' => 'ar'
        ]);
    }

    public function test_admin_can_delete_blog_article()
    {
        $admin = $this->createAdmin();
        $article = BlogArticle::create([
            'language' => 'en',
            'title' => 'To Delete Article',
            'content' => 'Content',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.blog-articles.destroy', $article->id));

        $response->assertRedirect(route('admin.blog-articles.index'));

        $this->assertSoftDeleted('blog_articles', [
            'id' => $article->id,
        ]);
    }
}
