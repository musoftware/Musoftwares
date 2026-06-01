<?php

namespace Tests\Feature\Admin;

use App\Models\BlogArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminBlogArticleControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_blog_articles_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.blog-articles.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_blog_articles_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.blog-articles.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_create_blog_article_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.blog-articles.create'));
        $response->assertStatus(200);
    }

    public function test_admin_can_store_blog_article(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.blog-articles.store'), [
            'language' => 'en',
            'title' => 'Test Article',
            'content' => 'This is a test article.',
            'slug' => 'test-article',
            'is_published' => true,
        ]);

        $response->assertRedirect(route('admin.blog-articles.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('blog_articles', [
            'title' => 'Test Article',
            'slug' => 'test-article',
        ]);
    }

    public function test_admin_can_view_edit_blog_article_page(): void
    {
        $article = BlogArticle::factory()->create();
        $response = $this->actingAs($this->admin)->get(route('admin.blog-articles.edit', $article->id));
        $response->assertStatus(200);
    }

    public function test_admin_can_update_blog_article(): void
    {
        $article = BlogArticle::factory()->create();
        $response = $this->actingAs($this->admin)->put(route('admin.blog-articles.update', $article->id), [
            'language' => 'en',
            'title' => 'Updated Article',
            'content' => 'Updated content.',
        ]);

        $response->assertRedirect(route('admin.blog-articles.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('blog_articles', [
            'id' => $article->id,
            'title' => 'Updated Article',
        ]);
    }

    public function test_admin_can_destroy_blog_article(): void
    {
        $article = BlogArticle::factory()->create();
        $response = $this->actingAs($this->admin)->delete(route('admin.blog-articles.destroy', $article->id));

        $response->assertRedirect(route('admin.blog-articles.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('blog_articles', ['id' => $article->id]);
    }
}
