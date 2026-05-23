<?php

namespace Modules\fbmb\Services;

use App\Services\PointsService;
use App\Models\User;
use Exception;
use PDO;

class FbmbLookupService
{
    protected PointsService $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
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

        $availablePoints = $this->pointsService->getBalance($user);
        if ($availablePoints <= 0) {
            throw new Exception("Insufficient points balance. Please get points first.");
        }

        $dbPath = storage_path('app/db/All Arab.db');
        if (! file_exists($dbPath)) {
            throw new Exception("Intelligence Database not found.");
        }

        $pdo = new PDO("sqlite:{$dbPath}");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $results = [];
        $foundCount = 0;
        $chunks = array_chunk($ids, 500);
        foreach ($chunks as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $sql = "SELECT FBID, Phone FROM data WHERE FBID IN ($placeholders) GROUP BY FBID";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($chunk);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[] = $row;
                $foundCount++;
            }
        }

        if ($foundCount > 0) {
            $costPerMatch = 1;
            $totalCost = $foundCount * $costPerMatch;

            // Verify points cover actual matches
            if ($availablePoints < $totalCost) {
                throw new Exception("Insufficient points. Found {$foundCount} matches requiring {$totalCost} points, but you only have {$availablePoints} available.");
            }

            $this->pointsService->debit(
                $user,
                $totalCost,
                'fbmb_lookup',
                null,
                "iSAAS Facebook ID lookup: {$foundCount} matches from {$totalIds} IDs."
            );
        }

        // Ensure temp directory exists
        $tempDir = storage_path('app/temp_isaas');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $resultCsvPath = $tempDir . '/result_' . uniqid() . '.csv';
        $fp = fopen($resultCsvPath, 'w');
        fputcsv($fp, ['FBID', 'Phone']);
        foreach ($results as $result) {
            fputcsv($fp, [$result['FBID'], $result['Phone']]);
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
