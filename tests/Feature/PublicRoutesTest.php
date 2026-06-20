<?php

namespace Tests\Feature;

use App\Models\BlogArticle;
use App\Models\WebsiteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_returns_a_successful_response()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_platforms_pages_return_successful_response()
    {
        $this->get('/platforms')->assertStatus(200);
        $this->get('/platforms/crm')->assertStatus(200);
        $this->get('/platforms/erp')->assertStatus(200);
        $this->get('/platforms/cloud')->assertStatus(200);
    }

    public function test_portfolio_page_returns_successful_response()
    {
        $this->get('/portfolio')->assertStatus(200);
        $this->get('/portfolio/test-slug')->assertStatus(200);
    }

    public function test_solutions_pages_return_successful_response()
    {
        $this->get('/solutions')->assertStatus(200);
        $this->get('/solutions/healthcare')->assertStatus(200);
        $this->get('/solutions/education')->assertStatus(200);
        $this->get('/solutions/ecommerce')->assertStatus(200);
        $this->get('/solutions/real-estate')->assertStatus(200);
        $this->get('/solutions/finance')->assertStatus(200);
    }

    public function test_company_pages_return_successful_response()
    {
        $this->get('/company')->assertStatus(200);
        $this->get('/company/about')->assertStatus(200);
        $this->get('/company/careers')->assertStatus(200);
        $this->get('/company/contact')->assertStatus(200);
    }

    public function test_legal_pages_return_successful_response()
    {
        $this->get('/privacy-policy')->assertStatus(200);
        $this->get('/terms-of-service')->assertStatus(200);
        $this->get('/cookie-policy')->assertStatus(200);
    }

    public function test_pricing_page_returns_successful_response()
    {
        $this->get('/pricing')->assertStatus(200);
    }

    public function test_blog_index_returns_successful_response()
    {
        $this->get('/blog')->assertStatus(200);
    }

    public function test_blog_show_returns_successful_response()
    {
        $article = clone new BlogArticle;
        $article->title = 'Test Article';
        $article->slug = 'test-article';
        $article->content = 'This is a test article.';
        $article->is_published = true;
        $article->published_at = now();
        $article->save();

        $this->get('/blog/test-article')->assertStatus(200);
    }

    public function test_website_service_show_returns_successful_response()
    {
        $service = new WebsiteService;
        $service->title_en = 'Test Service';
        $service->title_ar = 'Test Service AR';
        $service->slug = 'test-service';
        $service->description_ar = 'desc';
        $service->description_en = 'desc';
        $service->subtitle_ar = 'sub';
        $service->subtitle_en = 'sub';
        $service->save();

        $this->get('/services/test-service')->assertStatus(200);
    }
}
