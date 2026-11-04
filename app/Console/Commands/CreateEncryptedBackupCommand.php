<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CreateEncryptedBackupCommand extends Command
{
    protected $signature = 'privacy:encrypted-backup {--disk=local : Target filesystem disk (e.g. local or s3)}';

    protected $description = 'Create an AES-256 encrypted backup archive before writing to local or external object storage.';

    private const CORE_TABLES = [
        'users',
        'user_consents_log',
        'data_subject_requests',
        'admin_audit_logs',
        'invoices',
        'transactions',
        'admin_settings',
    ];

    public function handle(): int
    {
        $diskName = (string) $this->option('disk');
        $payload = $this->collectBackupPayload();
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $compressed = gzencode($json, 9);

        if ($compressed === false) {
            $this->error('Failed to compress backup payload.');

            return self::FAILURE;
        }

        // Encrypt using Laravel's AES-256-CBC + HMAC-SHA256 encrypter BEFORE writing to any disk (including S3).
        $encryptedPayload = Crypt::encryptString(base64_encode($compressed));
        $timestamp = now()->timezone('Africa/Cairo')->format('Y-m-d_His');
        $path = "encrypted-backups/pdpl-backup-{$timestamp}.enc";

        Storage::disk($diskName)->put($path, $encryptedPayload);

        Log::info('[PDPL Encrypted Backup] Encrypted snapshot stored', [
            'disk' => $diskName,
            'path' => $path,
            'cipher' => config('app.cipher', 'AES-256-CBC'),
        ]);

        $this->info("Encrypted backup stored at [{$diskName}]: {$path}");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function collectBackupPayload(): array
    {
        $tables = [];
        foreach (self::CORE_TABLES as $table) {
            if (Schema::hasTable($table)) {
                $tables[$table] = DB::table($table)->get()->toArray();
            }
        }

        return [
            'generated_at_cairo' => now()->timezone('Africa/Cairo')->toIso8601String(),
            'cipher' => config('app.cipher', 'AES-256-CBC'),
            'tables' => $tables,
        ];
    }
}

