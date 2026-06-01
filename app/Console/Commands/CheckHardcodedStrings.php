<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckHardcodedStrings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:check-hardcoded {--report : Export a JSON report of the hardcoded strings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan project files for hardcoded English strings (e.g. in views, TSX components, controllers).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Scanning application for hardcoded English strings...");

        $targetDirectories = [
            resource_path('js'),
            resource_path('views'),
            app_path(),
            base_path('Modules'),
        ];

        $hardcodedMatches = [];
        $totalMatches = 0;

        foreach ($directories as $dir => $extensions) {
            if (!File::isDirectory($dir)) {
                continue;
            }

            $files = File::allFiles($dir);

            foreach ($files as $file) {
                if (!in_array($file->getExtension(), $extensions)) {
                    continue;
                }

                $content = file_get_contents($file->getPathname());
                $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $matchesInFile = [];

                if (in_array($file->getExtension(), ['php'])) {
                    // Blade vs standard PHP
                    if (str_ends_with($file->getFilename(), '.blade.php')) {
                        // Blade text nodes between tags
                        preg_match_all('/>\s*([A-Za-z][^<>{}\n]*[a-zA-Z\.!?])\s*</', $content, $matches);
                        if (!empty($matches[1])) {
                            $matchesInFile = array_merge($matchesInFile, $matches[1]);
                        }
                        
                        // placeholder="..."
                        preg_match_all('/(?:placeholder|title|label)=["\']([A-Za-z][^"\']+)["\']/', $content, $matches);
                        if (!empty($matches[1])) {
                            $matchesInFile = array_merge($matchesInFile, $matches[1]);
                        }
                    } else {
                        // Standard PHP Controllers / Services
                        
                        // ->with('success', __('general.hardcoded_text'))
                        preg_match_all('/->with\(\s*[\'"][^\'"]+[\'"]\s*,\s*[\'"]([A-Za-z][^\'"]+)[\'"]\s*\)/', $content, $matches);
                        if (!empty($matches[1])) {
                            $matchesInFile = array_merge($matchesInFile, $matches[1]);
                        }
                        
                        // abort(403, __('general.hardcoded_text')) or throw new Exception('Hardcoded Text')
                        preg_match_all('/(?:abort|throw new [a-zA-Z0-9_\\\\]+Exception)\([^,]+,\s*[\'"]([A-Za-z][^\'"]+)[\'"]\)/', $content, $matches);
                        if (!empty($matches[1])) {
                            $matchesInFile = array_merge($matchesInFile, $matches[1]);
                        }

                        // ActivityLogger::log('...', __('general.hardcoded_text'))
                        preg_match_all('/ActivityLogger::log\([^,]+,\s*[\'"]([A-Za-z][^\'"]+)[\'"]\)/', $content, $matches);
                        if (!empty($matches[1])) {
                            $matchesInFile = array_merge($matchesInFile, $matches[1]);
                        }

                        // ->line(__('general.hardcoded_text'))
                        preg_match_all('/->line\(\s*[\'"]([A-Za-z][^\'"]+)[\'"]\s*\)/', $content, $matches);
                        if (!empty($matches[1])) {
                            $matchesInFile = array_merge($matchesInFile, $matches[1]);
                        }
                    }
                } elseif (in_array($file->getExtension(), ['js', 'jsx', 'ts', 'tsx', 'vue'])) {
                    // JSX / TSX
                    
                    // Text nodes: >Some Text<
                    // Excluding those that start/end with { or < to avoid matching expressions
                    preg_match_all('/>\s*([A-Za-z][^<>{}\n]*[a-zA-Z\.!?])\s*</', $content, $matches);
                    if (!empty($matches[1])) {
                        $matchesInFile = array_merge($matchesInFile, $matches[1]);
                    }

                    // String attributes: placeholder="Search..."
                    preg_match_all('/(?:placeholder|title|label|aria-label|description)=["\']([A-Za-z][^"\']+)["\']/', $content, $matches);
                    if (!empty($matches[1])) {
                        $matchesInFile = array_merge($matchesInFile, $matches[1]);
                    }
                }

                // Clean and filter matches
                $cleanedMatches = [];
                foreach ($matchesInFile as $m) {
                    $m = trim($m);
                    // Filter out likely non-text matches (e.g. classes, short strings, camelCase, file paths)
                    if (strlen($m) < 3) continue;
                    if (preg_match('/^[a-z0-9_A-Z]+$/', $m) && !str_contains($m, ' ')) continue; // one word code-like
                    if (str_contains($m, '::')) continue;
                    if (str_contains($m, '->')) continue;
                    if (str_contains($m, '/>')) continue;
                    
                    $cleanedMatches[] = $m;
                }

                $cleanedMatches = array_unique($cleanedMatches);

                if (!empty($cleanedMatches)) {
                    $hardcodedMatches[$relativePath] = $cleanedMatches;
                    $totalMatches += count($cleanedMatches);
                }
            }
        }

        if ($totalMatches === 0) {
            $this->info("\nGreat! No hardcoded English strings found.");
            return 0;
        }

        $this->warn("\nFound potentially {$totalMatches} hardcoded strings across " . count($hardcodedMatches) . " files.");

        // Show a preview
        $previewCount = 0;
        foreach ($hardcodedMatches as $file => $strings) {
            if ($previewCount >= 20) {
                break;
            }
            $this->line("\n<fg=yellow>{$file}</>");
            foreach ($strings as $string) {
                $this->line("- \"{$string}\"");
                $previewCount++;
                if ($previewCount >= 20) {
                    $this->line("... and more.");
                    break;
                }
            }
        }

        if ($this->option('report')) {
            $reportPath = storage_path('logs/hardcoded_strings_report.json');
            
            $reportData = [
                'total_files_with_hardcoded_strings' => count($hardcodedMatches),
                'total_hardcoded_strings' => $totalMatches,
                'files' => $hardcodedMatches,
            ];
            
            File::put($reportPath, json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("\nDetailed report exported to: {$reportPath}");
        } else {
            $this->info("\nTip: Run with --report to generate a JSON report with all file locations.");
        }

        return 1;
    }
}
