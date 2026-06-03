<?php

namespace Modules\Fbmb\Services;

use App\Services\PointsService;
use App\Services\AmcAcademyApiService;
use App\Models\User;
use Exception;

class FbmbLookupService
{
    protected PointsService $pointsService;
    protected AmcAcademyApiService $amcApiService;

    public function __construct(PointsService $pointsService, AmcAcademyApiService $amcApiService)
    {
        $this->pointsService = $pointsService;
        $this->amcApiService = $amcApiService;
    }

    /**
     * Extract IDs from a file without processing.
     * Useful for pre-validation / estimating cost.
     */
    public function countIds(string $filePath): int
    {
        return count($this->extractIds($filePath));
    }

    public function processFile($user, string $filePath): array
    {
        $ids = $this->extractIds($filePath);
        $totalIds = count($ids);

        if (empty($ids)) {
            throw new Exception("No valid IDs found in the uploaded file.");
        }

        $results = [];
        $foundCount = 0;
        $chunks = array_chunk($ids, 500);
        foreach ($chunks as $chunk) {
            $chunkResults = $this->amcApiService->searchFbidsBulk($chunk);
            if (!empty($chunkResults)) {
                foreach ($chunkResults as $fbid => $phone) {
                    $results[] = [
                        'FBID' => $fbid,
                        'Phone' => $phone
                    ];
                    $foundCount++;
                }
            }
        }
        // if ($foundCount < 10) {
        //     throw new Exception("نتائج غير مقبولة وتم استرجاع النقاط");
        // }

        // Ensure temp directory exists
        $tempDir = storage_path('app/temp_isaas');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $resultCsvPath = $tempDir . '/result_' . uniqid() . '.csv';
        $fp = fopen($resultCsvPath, 'w');
        fputcsv($fp, ['Phone']);
        foreach ($results as $result) {
            fputcsv($fp, [$result['Phone']]);
        }
        fclose($fp);

        return [
            'total_ids' => $totalIds,
            'found_count' => $foundCount,
            'result_path' => $resultCsvPath,
        ];
    }

    protected function extractIds(string $filePath): array
    {
        $ids = [];
        $handle = fopen($filePath, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if (empty($line)) continue;

                // Extract only the first column as the ID
                $parts = explode(',', $line);
                $val = trim($parts[0]);
                if (is_numeric($val) && $val > 0) {
                    $ids[] = $val;
                }
            }
            fclose($handle);
        }
        return array_values(array_unique($ids));
    }
}
