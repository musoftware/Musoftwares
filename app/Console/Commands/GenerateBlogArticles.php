<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BlogArticle;
use Modules\Marketplace\Models\Service;
use App\Services\AI\BlogAiService;
use App\Services\AI\MarketplaceAiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateBlogArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blog:generate-articles 
                            {--service_id= : Specific Service ID to generate for} 
                            {--limit=5 : Maximum number of articles to generate in this run} 
                            {--lang=en : Language code for the generated articles (en or ar)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generate SEO-optimized blog articles for active services using AI';

    /**
     * Execute the console command.
     */
    public function handle(BlogAiService $blogAiService, MarketplaceAiService $marketplaceAiService): int
    {
        $this->info('Starting AI Blog Article Generator...');

        $serviceId = $this->option('service_id');
        $limit = (int) $this->option('limit');
        $lang = strtolower($this->option('lang'));

        if (!in_array($lang, ['en', 'ar'])) {
            $this->error("Invalid language: '{$lang}'. Only 'en' and 'ar' are supported.");
            return self::FAILURE;
        }

        // Fetch target services
        if ($serviceId) {
            $services = Service::where('status', 'active')->where('id', $serviceId)->get();
            if ($services->isEmpty()) {
                $this->error("No active service found with ID {$serviceId}.");
                return self::FAILURE;
            }
        } else {
            $services = Service::where('status', 'active')->get();
            if ($services->isEmpty()) {
                $this->info('No active services found in the database.');
                return self::SUCCESS;
            }
        }

        $count = 0;
        $generatedInThisRun = true;

        // Run sequential rounds of generation across services to distribute articles evenly
        while ($count < $limit && $generatedInThisRun) {
            $generatedInThisRun = false;

            foreach ($services as $service) {
                if ($count >= $limit) {
                    break;
                }

                $existingArticles = BlogArticle::where('service_id', $service->id)
                    ->where('language', $lang)
                    ->get();

                if ($existingArticles->count() >= 20) {
                    $this->info("Service #{$service->id} already has {$existingArticles->count()} articles in '{$lang}'. Skipping.");
                    continue;
                }

                $this->info("------------------------------------------------------------");
                $this->info("Generating article {$count} of {$limit} for Service #{$service->id}: '{$service->title}' ({$lang})");

                $existingTitles = $existingArticles->pluck('title')->toArray();

                try {
                    // Call LLM content engine
                    $articleData = $blogAiService->generateArticleForService($service, $existingTitles, $lang);

                    if (!$articleData || empty($articleData['title'])) {
                        $this->error("Failed to generate article content from AI.");
                        continue;
                    }

                    // Strict duplication check
                    if (in_array($articleData['title'], $existingTitles)) {
                        $this->warn("AI generated duplicate title: '{$articleData['title']}'. Skipping.");
                        continue;
                    }

                    $this->info("Generated Topic: '{$articleData['title']}'");

                    // Call LLM visual engine to generate cover image
                    $imagePath = null;
                    $imagePrompt = $articleData['image_prompt'] ?? $articleData['title'];

                    $this->info("Generating visual cover image...");
                    $imageResult = $marketplaceAiService->generateCoverImage($imagePrompt, $service->seller_id ?: 1);
                    if (!empty($imageResult['gallery']) && isset($imageResult['gallery'][0])) {
                        $imagePath = $imageResult['gallery'][0];
                    }

                    // Create & Publish
                    $article = BlogArticle::create([
                        'service_id' => $service->id,
                        'language' => $lang,
                        'group_id' => (string) Str::uuid(),
                        'title' => $articleData['title'],
                        'slug' => BlogArticle::generateUniqueSlug($articleData['title']),
                        'content' => $articleData['content'],
                        'excerpt' => $articleData['excerpt'] ?? Str::limit(strip_tags($articleData['content']), 150),
                        'featured_image' => $imagePath,
                        'meta_title' => $articleData['meta_title'] ?? $articleData['title'],
                        'meta_description' => $articleData['meta_description'] ?? $articleData['excerpt'],
                        'is_published' => true,
                        'published_at' => now(),
                    ]);

                    $this->info("Successfully published: [{$article->id}] '{$article->title}'");
                    $count++;
                    $generatedInThisRun = true;

                } catch (\Exception $e) {
                    $this->error("Error: " . $e->getMessage());
                    Log::error("Blog generation failed for service {$service->id}: " . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
            }
        }

        $this->info("============================================================");
        $this->info("AI Blog Generation Run completed. Generated {$count} articles.");
        return self::SUCCESS;
    }
}
