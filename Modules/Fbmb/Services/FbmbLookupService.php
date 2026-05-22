<?php

namespace Modules\fbmb\Services;

use Modules\Core\Services\WalletService;
use Modules\Core\Models\User;
use Exception;
use PDO;

class FbmbLookupService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function processFile($user, string $filePath): array
    {
        $ids = $this->extractIds($filePath);
        if (empty($ids)) {
            throw new Exception("No valid IDs found in the uploaded file.");
        }

        $wallet = $user->wallet;
        if (! $wallet) {
            throw new Exception("User does not have an active wallet.");
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
            $sql = "SELECT FBID, Phone FROM data WHERE FBID IN ($placeholders)";
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
            $this->walletService->debitAvailable(
                $wallet,
                $totalCost,
                $wallet->currency,
                'fbmb_lookup',
                null,
                "fbmb Facebook ID lookup for {$foundCount} records."
            );
        }

        $resultCsvPath = storage_path('app/temp_isaas/result_' . uniqid() . '.csv');
        $fp = fopen($resultCsvPath, 'w');
        fputcsv($fp, ['FBID', 'Phone']);
        foreach ($results as $result) {
            fputcsv($fp, [$result['FBID'], $result['Phone']]);
        }
        fclose($fp);

        return [
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
                $parts = explode(',', $line);
                foreach ($parts as $part) {
                    $val = trim($part);
                    if (is_numeric($val)) {
                        $ids[] = $val;
                    }
                }
            }
            fclose($handle);
        }
        return array_unique($ids);
    }
}
