<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Find duplicate memberships by name
        $duplicates = DB::table('memberships')
            ->select('name', DB::raw('COUNT(*) as count'), DB::raw('GROUP_CONCAT(id) as ids'))
            ->groupBy('name')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            $ids = explode(',', $duplicate->ids);
            $primaryId = $ids[0]; // Keep the first one as primary
            $duplicateIds = array_slice($ids, 1); // Rest are duplicates

            $this->info("Merging duplicate memberships for '{$duplicate->name}':");
            $this->info("Primary ID: {$primaryId}");
            $this->info("Duplicate IDs: " . implode(', ', $duplicateIds));

            // Update membership_users table - move all users to primary membership
            DB::table('membership_users')
                ->whereIn('membership_id', $duplicateIds)
                ->update(['membership_id' => $primaryId]);

            $this->info("✓ Moved membership users to primary membership");

            // Update membership_premium_tools table - merge tool access
            foreach ($duplicateIds as $duplicateId) {
                // Get all tools linked to duplicate membership
                $duplicateTools = DB::table('membership_premium_tools')
                    ->where('membership_id', $duplicateId)
                    ->get();

                foreach ($duplicateTools as $tool) {
                    // Check if primary membership already has this tool
                    $existingTool = DB::table('membership_premium_tools')
                        ->where('membership_id', $primaryId)
                        ->where('premium_tool_id', $tool->premium_tool_id)
                        ->first();

                    if (!$existingTool) {
                        // Add tool to primary membership
                        DB::table('membership_premium_tools')->insert([
                            'membership_id' => $primaryId,
                            'premium_tool_id' => $tool->premium_tool_id,
                            'is_enabled' => $tool->is_enabled,
                            'created_at' => $tool->created_at,
                            'updated_at' => $tool->updated_at
                        ]);
                    }
                }
            }

            $this->info("✓ Merged premium tool access");

            // Update membership_programs table if it exists
            if (Schema::hasTable('membership_programs')) {
                DB::table('membership_programs')
                    ->whereIn('membership_id', $duplicateIds)
                    ->update(['membership_id' => $primaryId]);
                $this->info("✓ Merged membership programs");
            }

            // Delete duplicate memberships
            DB::table('memberships')->whereIn('id', $duplicateIds)->delete();
            $this->info("✓ Deleted duplicate memberships");
        }

        // Ensure we have the correct memberships
        $this->ensureCorrectMemberships();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // This migration cannot be reversed as it deletes data
        $this->warn('This migration cannot be reversed as it merges and deletes duplicate data.');
    }

    /**
     * Ensure we have the correct memberships after merging
     */
    private function ensureCorrectMemberships()
    {
        $expectedMemberships = [
            'Premium' => [
                'currency' => 1,
                'amount' => 29.99,
                'color_hue_degree' => 210
            ],
            'Enterprise' => [
                'currency' => 1,
                'amount' => 99.99,
                'color_hue_degree' => 120
            ]
        ];

        foreach ($expectedMemberships as $name => $data) {
            $membership = DB::table('memberships')->where('name', $name)->first();

            if (!$membership) {
                // Create if missing
                DB::table('memberships')->insert([
                    'name' => $name,
                    'currency' => $data['currency'],
                    'amount' => $data['amount'],
                    'color_hue_degree' => $data['color_hue_degree'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $this->info("✓ Created missing membership: {$name}");
            } else {
                // Update if exists but with wrong data
                DB::table('memberships')
                    ->where('id', $membership->id)
                    ->update([
                        'currency' => $data['currency'],
                        'amount' => $data['amount'],
                        'color_hue_degree' => $data['color_hue_degree'],
                        'updated_at' => now()
                    ]);
                $this->info("✓ Updated membership: {$name}");
            }
        }
    }

    /**
     * Output info message
     */
    private function info($message)
    {
        echo $message . PHP_EOL;
    }

    /**
     * Output warning message
     */
    private function warn($message)
    {
        echo "WARNING: " . $message . PHP_EOL;
    }
};
