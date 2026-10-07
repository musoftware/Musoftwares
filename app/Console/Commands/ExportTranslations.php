<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export Laravel language files to one JSON file per locale for frontend use';

    private const MAX_WRITE_ATTEMPTS = 5;

    private const RETRY_DELAY_MICROSECONDS = 100000;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $langPath = base_path('lang');
        $outputDir = resource_path('js/lang');

        if (! File::exists($langPath)) {
            $this->error("Lang directory not found at {$langPath}");

            return self::FAILURE;
        }

        File::ensureDirectoryExists($outputDir);

        foreach ($this->collectTranslations($langPath) as $locale => $translations) {
            $outputPath = "{$outputDir}/{$locale}.json";
            $this->writeWithRetry($outputPath, json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $this->info("Translations for [{$locale}] exported to {$outputPath}");
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function collectTranslations(string $langPath): array
    {
        $translations = [];

        foreach (File::directories($langPath) as $dir) {
            $translations[basename($dir)] = $this->collectPhpGroups($dir);
        }

        foreach (File::files($langPath) as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }
            $locale = $file->getFilenameWithoutExtension();
            $content = json_decode(File::get($file->getPathname()), true);
            if (is_array($content)) {
                // JSON files are loaded at the root of the locale
                $translations[$locale] = array_merge($translations[$locale] ?? [], $content);
            }
        }

        return $translations;
    }

    /**
     * @return array<string, mixed>
     */
    private function collectPhpGroups(string $dir): array
    {
        $groups = [];

        foreach (File::allFiles($dir) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $content = require $file->getPathname();
            if (is_array($content)) {
                $groups[$file->getFilenameWithoutExtension()] = $content;
            }
        }

        return $groups;
    }

    private function writeWithRetry(string $outputPath, string $encoded): void
    {
        for ($attempt = 1; $attempt < self::MAX_WRITE_ATTEMPTS; $attempt++) {
            try {
                File::replace($outputPath, $encoded);

                return;
            } catch (\Throwable $e) {
                usleep(self::RETRY_DELAY_MICROSECONDS);
            }
        }

        File::put($outputPath, $encoded);
    }
}
