<?php

namespace Modules\Fbmb\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use Modules\Fbmb\Models\FbmbLookupResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use PDO;

class FbmbLookupTest extends TestCase
{
    use DatabaseTransactions;

    protected string $dbPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the storage app/db directory if not exists
        $dbDir = storage_path('app/db');
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $this->dbPath = $dbDir . '/All Arab.db';
        
        // Setup SQLite mock db
        $pdo = new PDO("sqlite:{$this->dbPath}");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE TABLE IF NOT EXISTS data (FBID TEXT, Phone TEXT)");
        $pdo->exec("DELETE FROM data");
        $pdo->exec("INSERT INTO data (FBID, Phone) VALUES ('12345', '123456789')");
        $pdo->exec("INSERT INTO data (FBID, Phone) VALUES ('67890', '987654321')");
    }

    public function test_user_can_upload_file_to_create_pending_lookup()
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'points_balance' => 10,
        ]);

        $fileContent = "12345\n67890\n";
        $file = UploadedFile::fake()->createWithContent('test.txt', $fileContent);

        $response = $this->actingAs($user)->post(route('fbmb.process'), [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('fbmb_lookup_results', [
            'user_id' => $user->id,
            'status' => 'pending',
            'total_ids' => 2,
        ]);

        $record = FbmbLookupResult::where('user_id', $user->id)->first();
        $this->assertNotNull($record->input_path);
        $this->assertTrue(file_exists($record->input_path));

        // Clean up
        if (file_exists($record->input_path)) {
            @unlink($record->input_path);
        }
    }

    public function test_console_command_processes_pending_lookups()
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'points_balance' => 10,
        ]);

        // Create temporary input file
        $tempDir = storage_path('app/fbmb_inputs');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $inputPath = $tempDir . '/test_' . uniqid() . '.txt';
        file_put_contents($inputPath, "12345\n67890\n99999\n"); // 2 matches, 1 miss

        $downloadToken = md5(uniqid(mt_rand(), true));

        $record = FbmbLookupResult::create([
            'user_id'           => $user->id,
            'download_token'    => $downloadToken,
            'total_ids'         => 3,
            'found_count'       => 0,
            'credits_used'      => 0,
            'remaining_balance' => 10,
            'input_path'        => $inputPath,
            'result_path'       => null,
            'status'            => 'pending',
            'expires_at'        => now()->addHours(24),
        ]);

        // Run Artisan command
        $exitCode = Artisan::call('fbmb:process-pending');
        $this->assertEquals(0, $exitCode);

        $record->refresh();
        $user->refresh();

        $this->assertEquals('completed', $record->status);
        $this->assertEquals(2, $record->found_count); // 12345 and 67890 matched
        $this->assertEquals(2, $record->credits_used);
        $this->assertEquals(8, $user->points_balance); // 10 - 2 matches
        $this->assertNotNull($record->result_path);
        $this->assertTrue(file_exists($record->result_path));
        $this->assertFalse(file_exists($inputPath)); // Input file deleted

        // Clean up output file
        if (file_exists($record->result_path)) {
            @unlink($record->result_path);
        }
    }

    public function test_console_command_fails_gracefully_on_insufficient_points()
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'points_balance' => 0,
        ]);

        $tempDir = storage_path('app/fbmb_inputs');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $inputPath = $tempDir . '/test_' . uniqid() . '.txt';
        file_put_contents($inputPath, "12345\n67890\n");

        $downloadToken = md5(uniqid(mt_rand(), true));

        $record = FbmbLookupResult::create([
            'user_id'           => $user->id,
            'download_token'    => $downloadToken,
            'total_ids'         => 2,
            'found_count'       => 0,
            'credits_used'      => 0,
            'remaining_balance' => 0,
            'input_path'        => $inputPath,
            'result_path'       => null,
            'status'            => 'pending',
            'expires_at'        => now()->addHours(24),
        ]);

        // Run Artisan command
        $exitCode = Artisan::call('fbmb:process-pending');
        $this->assertEquals(0, $exitCode);

        $record->refresh();
        $this->assertEquals('failed', $record->status);
        $this->assertNotNull($record->error_message);
        $this->assertStringContainsString('Insufficient points', $record->error_message);
        $this->assertFalse(file_exists($inputPath)); // Input file cleaned up
    }
}
