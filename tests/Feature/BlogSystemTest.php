<?php

namespace Tests\Feature;

use App\Models\BlogArticle;
use App\Models\Currency;
use App\Models\CurrenciesExchange;
use App\Models\User;
use App\Services\AI\BlogAiService;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServicePackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_search_filters_articles_correctly(): void
    {
        BlogArticle::create([
            'title' => 'Fixing Stripe Webhook Errors',
            'slug' => 'fixing-stripe-webhook-errors',
            'content' => 'Content about stripe webhooks.',
            'excerpt' => 'Excerpt stripe',
            'is_published' => true,
            'published_at' => now(),
            'language' => 'en',
        ]);

        BlogArticle::create([
            'title' => 'Building WhatsApp Bots with Laravel',
            'slug' => 'building-whatsapp-bots-with-laravel',
            'content' => 'Content about whatsapp bots.',
            'excerpt' => 'Excerpt whatsapp',
            'is_published' => true,
            'published_at' => now(),
            'language' => 'en',
        ]);

        // No search -> returns both
        $response = $this->get(route('blog.index'));
        $response->assertStatus(200);
        $response->assertSee('Fixing Stripe Webhook');
        $response->assertSee('Building WhatsApp Bots');

        // Search Stripe -> returns stripe article only
        $response = $this->get(route('blog.index', ['search' => 'stripe']));
        $response->assertStatus(200);
        $response->assertSee('Fixing Stripe Webhook');
        $response->assertDontSee('Building WhatsApp Bots');
    }

    public function test_blog_show_loads_related_service_and_converts_price(): void
    {
        // Setup currencies
        $usd = Currency::create(['currency' => 'USD', 'symbol' => '$', 'string_format' => '%s%v', 'is_default' => true]);
        $egp = Currency::create(['currency' => 'EGP', 'symbol' => 'ج.م', 'string_format' => '%s%v', 'is_default' => false]);

        CurrenciesExchange::create([
            'currency1' => $usd->id,
            'currency2' => $egp->id,
            'rate' => 50.00,
            'date_string' => now()->toDateString(),
        ]);

        // Create seller
        $seller = User::factory()->create();

        // Create service & package in USD
        $service = Service::create([
            'seller_id' => $seller->id,
            'title' => 'Laravel Integration Service',
            'slug' => 'laravel-integration-service',
            'status' => 'active',
            'description' => 'We integrate APIs.',
        ]);

        ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Basic Integration',
            'description' => 'Basic integration.',
            'price' => 100.00, // 100 USD
            'currency_id' => $usd->id,
            'delivery_days' => 2,
        ]);

        // Create article linked to service
        $article = BlogArticle::create([
            'service_id' => $service->id,
            'title' => 'How to do API Integrations',
            'slug' => 'how-to-do-api-integrations',
            'content' => 'Read this to learn API integrations.',
            'excerpt' => 'Excerpt API',
            'is_published' => true,
            'published_at' => now(),
            'language' => 'en',
        ]);

        // Create viewer user with EGP currency preference
        $viewer = User::factory()->create(['currency_id' => $egp->id]);

        $response = $this->actingAs($viewer)->get(route('blog.show', $article->slug));
        $response->assertStatus(200);
        
        // Assert that the page renders the service details and the converted price (100 * 50 = 5000 EGP)
        $inertiaData = $response->original->getData()['page']['props'];
        $packages = $inertiaData['article']['service']['packages'];
        
        $this->assertNotEmpty($packages);
        $this->assertEquals(5000.00, $packages[0]['price']);
        $this->assertEquals($egp->id, $packages[0]['currency_id']);
    }

    public function test_artisan_command_generates_unique_articles_using_ai(): void
    {
        // Create seller
        $seller = User::factory()->create();

        // Create service
        $service = Service::create([
            'seller_id' => $seller->id,
            'title' => 'WhatsApp Bot Builder',
            'slug' => 'whatsapp-bot-builder',
            'status' => 'active',
            'description' => 'A service to build WhatsApp bots.',
        ]);

        // Mock BlogAiService
        $mockAiService = $this->mock(BlogAiService::class);
        $mockAiService->shouldReceive('generateArticleForService')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($service) {
                return $arg->id === $service->id;
            }), \Mockery::any(), 'en')
            ->andReturn([
                'title' => 'New WhatsApp Bot Article',
                'excerpt' => 'AI Excerpt',
                'content' => 'AI content body.',
                'meta_title' => 'AI Meta Title',
                'meta_description' => 'AI Meta Description',
                'image_prompt' => 'Visual prompt',
            ]);

        // Run the Artisan command
        $exitCode = $this->artisan('blog:generate-articles', [
            '--service_id' => $service->id,
            '--limit' => 1,
            '--lang' => 'en'
        ]);
        $this->assertEquals(0, $exitCode);

        // Verify the database has the article
        $this->assertDatabaseHas('blog_articles', [
            'service_id' => $service->id,
            'title' => 'New WhatsApp Bot Article',
            'is_published' => true,
        ]);
    }

    public function test_blog_index_filters_articles_by_locale(): void
    {
        BlogArticle::create([
            'title' => 'English Post Title',
            'slug' => 'english-post-title',
            'content' => 'English content.',
            'excerpt' => 'English excerpt',
            'is_published' => true,
            'published_at' => now(),
            'language' => 'en',
        ]);

        BlogArticle::create([
            'title' => 'Arabic Post Title',
            'slug' => 'arabic-post-title',
            'content' => 'Arabic content.',
            'excerpt' => 'Arabic excerpt',
            'is_published' => true,
            'published_at' => now(),
            'language' => 'ar',
        ]);

        // Default locale / English locale index
        $response = $this->get(route('blog.index', ['lang' => 'en']));
        $response->assertStatus(200);
        $response->assertSee('English Post Title');
        $response->assertDontSee('Arabic Post Title');

        // Arabic locale index
        $response = $this->get(route('blog.index', ['lang' => 'ar']));
        $response->assertStatus(200);
        $response->assertSee('Arabic Post Title');
        $response->assertDontSee('English Post Title');
    }

    public function test_artisan_command_generates_articles_for_both_languages_when_lang_is_all(): void
    {
        $seller = User::factory()->create();

        $service = Service::create([
            'seller_id' => $seller->id,
            'title' => 'AI Service Test',
            'slug' => 'ai-service-test',
            'status' => 'active',
            'description' => 'Test AI generation.',
        ]);

        $mockAiService = $this->mock(BlogAiService::class);
        $mockAiService->shouldReceive('generateArticleForService')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($service) {
                return $arg->id === $service->id;
            }), \Mockery::any(), 'en')
            ->andReturn([
                'title' => 'English Service Blog Post',
                'excerpt' => 'AI Excerpt',
                'content' => 'AI content body.',
                'meta_title' => 'AI Meta Title',
                'meta_description' => 'AI Meta Description',
                'image_prompt' => 'Visual prompt',
            ]);

        $mockAiService->shouldReceive('generateArticleForService')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($service) {
                return $arg->id === $service->id;
            }), \Mockery::any(), 'ar')
            ->andReturn([
                'title' => 'Arabic Service Blog Post',
                'excerpt' => 'AI Excerpt Arabic',
                'content' => 'AI content body Arabic.',
                'meta_title' => 'AI Meta Title Arabic',
                'meta_description' => 'AI Meta Description Arabic',
                'image_prompt' => 'Visual prompt Arabic',
            ]);

        $exitCode = $this->artisan('blog:generate-articles', [
            '--service_id' => $service->id,
            '--limit' => 2,
            '--lang' => 'all'
        ]);
        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseHas('blog_articles', [
            'service_id' => $service->id,
            'title' => 'English Service Blog Post',
            'language' => 'en',
            'is_published' => true,
        ]);

        $this->assertDatabaseHas('blog_articles', [
            'service_id' => $service->id,
            'title' => 'Arabic Service Blog Post',
            'language' => 'ar',
            'is_published' => true,
        ]);
    }
}

