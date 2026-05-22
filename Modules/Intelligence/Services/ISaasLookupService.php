<?php

namespace Modules\Intelligence\Services;

use Modules\Core\Services\WalletService;
use Modules\Core\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;
use PDO;

class ISaasLookupService
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
        if (!$wallet) {
            throw new Exception("User does not have an active wallet.");
        }

        $dbPath = storage_path('app/db/All Arab.db');
        if (!file_exists($dbPath)) {
            throw new Exception("Intelligence Database not found.");
        }

        $pdo = new PDO("sqlite:{$dbPath}");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $results = [];
        $foundCount = 0;

        // Note: For a 6.7GB DB without an index, this might be slow for many IDs. 
        // We chunk the IDs to avoid massive IN clauses.
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
            // Deduct points based on found count (e.g., 1 credit per match)
            $costPerMatch = 1; // Assuming 1 unit
            $totalCost = $foundCount * $costPerMatch;
            
            $this->walletService->debitAvailable(
                $wallet,
                $totalCost,
                $wallet->currency,
                'isaas_lookup',
                null,
                "iSAAS Facebook ID lookup for {$foundCount} records."
            );
        }

        // Generate result CSV
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
                // assume one ID per line or comma separated if CSV
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
