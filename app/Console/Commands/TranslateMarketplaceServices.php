<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Marketplace\Models\Service;
use App\Services\TranslationService;
use Illuminate\Support\Facades\Log;

class TranslateMarketplaceServices extends Command
{
    protected $signature = 'marketplace:translate-services';
    protected $description = 'Background translation of untranslated marketplace services';

    public function handle(): int
    {
        $this->info('Starting background translation of marketplace services...');
        $translator = app(TranslationService::class);

        $services = Service::take(20)->get();
        $count = 0;

        foreach ($services as $service) {
            $updated = false;

            foreach (['en', 'ar'] as $targetLocale) {
                // Title
                $titleTrans = $service->title_translations ?? [];
                if (empty($titleTrans[$targetLocale]) && ! empty($service->title)) {
                    try {
                        $sourceLang = $translator->detectLanguage($service->title);
                        if ($sourceLang !== $targetLocale) {
                            $translated = $translator->translate($service->title, $targetLocale, $sourceLang);
                            if (! empty($translated)) {
                                $titleTrans[$targetLocale] = $translated;
                                $service->title_translations = $titleTrans;
                                $updated = true;
                            }
                        } else {
                            $titleTrans[$targetLocale] = $service->title;
                            $service->title_translations = $titleTrans;
                            $updated = true;
                        }
                    } catch (\Throwable $e) {
                        Log::warning("Background title translation failed for service {$service->id}: {$e->getMessage()}");
                    }
                }

                // Tagline
                $taglineTrans = $service->tagline_translations ?? [];
                if (empty($taglineTrans[$targetLocale]) && ! empty($service->tagline)) {
                    try {
                        $sourceLang = $translator->detectLanguage($service->tagline);
                        if ($sourceLang !== $targetLocale) {
                            $translated = $translator->translate($service->tagline, $targetLocale, $sourceLang);
                            if (! empty($translated)) {
                                $taglineTrans[$targetLocale] = $translated;
                                $service->tagline_translations = $taglineTrans;
                                $updated = true;
                            }
                        } else {
                            $taglineTrans[$targetLocale] = $service->tagline;
                            $service->tagline_translations = $taglineTrans;
                            $updated = true;
                        }
                    } catch (\Throwable $e) {
                        Log::warning("Background tagline translation failed for service {$service->id}: {$e->getMessage()}");
                    }
                }

                // Description
                $descTrans = $service->description_translations ?? [];
                if (empty($descTrans[$targetLocale]) && ! empty($service->description)) {
                    try {
                        $sourceLang = $translator->detectLanguage($service->description);
                        if ($sourceLang !== $targetLocale) {
                            $translated = $translator->translate($service->description, $targetLocale, $sourceLang);
                            if (! empty($translated)) {
                                $descTrans[$targetLocale] = $translated;
                                $service->description_translations = $descTrans;
                                $updated = true;
                            }
                        } else {
                            $descTrans[$targetLocale] = $service->description;
                            $service->description_translations = $descTrans;
                            $updated = true;
                        }
                    } catch (\Throwable $e) {
                        Log::warning("Background description translation failed for service {$service->id}: {$e->getMessage()}");
                    }
                }
            }

            if ($updated) {
                $service->save();
                $count++;
            }
        }

        $this->info("Completed background translation for {$count} services.");
        return self::SUCCESS;
    }
}
