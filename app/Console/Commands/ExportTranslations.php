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
    protected $description = 'Export all Laravel language files to a single JSON file for frontend use';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $langPath = base_path('lang');
        $outputPath = resource_path('js/translations.json');

        if (! File::exists($langPath)) {
            $this->error("Lang directory not found at {$langPath}");

            return 1;
        }

        $translations = [];

        // 1. Process standard PHP translation files (e.g., lang/en/messages.php)
        foreach (File::directories($langPath) as $dir) {
            $locale = basename($dir);
            if (! isset($translations[$locale])) {
                $translations[$locale] = [];
            }

            foreach (File::allFiles($dir) as $file) {
                if ($file->getExtension() === 'php') {
                    $group = $file->getFilenameWithoutExtension();
                    $content = require $file->getPathname();
                    if (is_array($content)) {
                        $translations[$locale][$group] = $content;
                    }
                }
            }
        }

        // 2. Process root JSON translation files (e.g., lang/en.json)
        foreach (File::files($langPath) as $file) {
            if ($file->getExtension() === 'json') {
                $locale = $file->getFilenameWithoutExtension();
                $content = json_decode(File::get($file->getPathname()), true);

                if (is_array($content)) {
                    if (! isset($translations[$locale])) {
                        $translations[$locale] = [];
                    }
                    // JSON files are loaded at the root of the locale
                    $translations[$locale] = array_merge($translations[$locale], $content);
                }
            }
        }

        $encoded = json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $attempts = 0;
        $maxAttempts = 5;
        $saved = false;

        while ($attempts < $maxAttempts && ! $saved) {
            $attempts++;
            try {
                File::replace($outputPath, $encoded);
                $saved = true;
            } catch (\Throwable $e) {
                if ($attempts >= $maxAttempts) {
                    File::put($outputPath, $encoded);
                    $saved = true;
                } else {
                    usleep(100000); // 100ms retry delay
                }
            }
        }

        $this->info("Translations exported successfully to {$outputPath}");

        return 0;
    }
}
