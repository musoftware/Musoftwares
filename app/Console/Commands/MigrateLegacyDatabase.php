<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:legacy {--step=all : The step to run (users, financials, coworkers, marketplace, subscriptions, crm)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates data from the legacy database to the new structure.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting legacy database migration...');
        
        // Test old DB connection
        try {
            DB::connection('old_mysql')->getPdo();
        } catch (\Exception $e) {
            $this->error('Could not connect to the legacy database. Please ensure DB_OLD_DATABASE is configured in .env and the database exists.');
            $this->error($e->getMessage());
            return 1;
        }

        $step = $this->option('step');

        if ($step === 'all' || $step === 'users') {
            $this->migrateUsers();
        }
        
        if ($step === 'all' || $step === 'financials') {
            $this->migrateFinancials();
        }
        
        if ($step === 'all' || $step === 'coworkers') {
            $this->migrateCoworkers();
        }
        
        if ($step === 'all' || $step === 'marketplace') {
            $this->migrateMarketplace();
        }
        
        if ($step === 'all' || $step === 'subscriptions') {
            $this->migrateSubscriptions();
        }
        
        if ($step === 'all' || $step === 'crm') {
            $this->migrateCrm();
        }

        $this->info('Migration completed successfully.');
        return 0;
    }

    protected function migrateUsers()
    {
        $this->info('Migrating Users...');
        DB::connection('old_mysql')->table('users')->orderBy('id')->chunk(500, function ($users) {
            $newUsers = [];
            foreach ($users as $user) {
                $newUsers[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'password' => $user->password,
                    'role' => 'client', // Default role
                    'phone' => $user->phone_number ?? $user->whatsapp_number,
                    'remember_token' => $user->remember_token,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            }
            // Use insertOrIgnore to prevent duplicates if run multiple times
            DB::table('users')->insertOrIgnore($newUsers);
            $this->info("Migrated " . count($users) . " users.");
        });
    }

    protected function migrateFinancials()
    {
        $this->info('Migrating Financials...');
        // TODO: Implement financials migration
    }

    protected function migrateCoworkers()
    {
        $this->info('Migrating Coworkers to Freelancers...');
        DB::connection('old_mysql')->table('co_workers')->orderBy('id')->chunk(100, function ($coworkers) {
            foreach ($coworkers as $coworker) {
                // Try to find a user with this email
                $userId = null;
                if ($coworker->email) {
                    $user = DB::table('users')->where('email', $coworker->email)->first();
                    if ($user) {
                        $userId = $user->id;
                    } else {
                        // Create a user for this freelancer
                        $userId = DB::table('users')->insertGetId([
                            'name' => $coworker->person_name,
                            'email' => $coworker->email,
                            'password' => bcrypt('password123'), // Default password
                            'role' => 'client', // Or a freelancer role if available
                            'phone' => $coworker->mobile,
                            'created_at' => $coworker->created_at,
                            'updated_at' => $coworker->updated_at,
                        ]);
                    }
                }

                // Create the freelancer profile
                DB::table('platform_freelancers')->updateOrInsert(
                    ['id' => $coworker->id], // Maintain original ID
                    [
                        'user_id' => $userId,
                        'name' => $coworker->person_name,
                        'email' => $coworker->email,
                        'mobile' => $coworker->mobile,
                        'facebook' => $coworker->facebook,
                        'linked_in' => $coworker->linked_in,
                        'whatsapp' => $coworker->whatsapp,
                        'time_from' => $coworker->time_from,
                        'time_to' => $coworker->time_to,
                        'created_at' => $coworker->created_at,
                        'updated_at' => $coworker->updated_at,
                    ]
                );

                // Migrate their skills (co_tech_tags)
                $tags = DB::connection('old_mysql')->table('co_tech_tags_workers')
                    ->join('co_tech_tags', 'co_tech_tags.id', '=', 'co_tech_tags_workers.co_tech_tag_id')
                    ->where('co_tech_tags_workers.co_worker_id', $coworker->id)
                    ->select('co_tech_tags.id', 'co_tech_tags.tag_name as name')
                    ->get();

                foreach ($tags as $tag) {
                    // Ensure the skill exists
                    DB::table('platform_skills')->updateOrInsert(
                        ['id' => $tag->id],
                        ['name' => $tag->name]
                    );

                    // Attach skill to freelancer
                    DB::table('platform_freelancer_skill')->updateOrInsert([
                        'freelancer_id' => $coworker->id,
                        'skill_id' => $tag->id
                    ]);
                }
            }
            $this->info("Migrated " . count($coworkers) . " coworkers to freelancers.");
        });
    }

    protected function migrateMarketplace()
    {
        $this->info('Migrating Legacy Services to Marketplace...');
        
        // 1. Migrate Categories
        $this->info('Migrating Service Categories...');
        DB::connection('old_mysql')->table('service_categories')->orderBy('id')->chunk(100, function ($categories) {
            $newCategories = [];
            foreach ($categories as $cat) {
                $newCategories[] = [
                    'id' => $cat->id,
                    'name' => $cat->title ?? 'Category ' . $cat->id,
                    'slug' => $cat->slug ?? 'category-' . $cat->id,
                    'description' => '',
                    'created_at' => $cat->created_at,
                    'updated_at' => $cat->updated_at,
                ];
            }
            DB::table('marketplace_service_categories')->insertOrIgnore($newCategories);
        });

        // 2. Migrate Services and Default Packages
        $this->info('Migrating Services & Packages...');
        DB::connection('old_mysql')->table('services')->orderBy('id')->chunk(100, function ($services) {
            foreach ($services as $service) {
                // Ensure seller exists
                $sellerExists = DB::table('users')->where('id', $service->user_id)->exists();
                if (!$sellerExists) continue;

                // Insert Service
                DB::table('marketplace_services')->insertOrIgnore([
                    'id' => $service->id,
                    'seller_id' => $service->user_id,
                    'category_id' => $service->service_category_id,
                    'title' => $service->title,
                    'description' => current(explode("\n", $service->description ?: 'Legacy Service')),
                    'status' => in_array($service->status, ['active', 'approved']) ? 'active' : 'draft',
                    'is_featured' => $service->featured,
                    'created_at' => $service->created_at,
                    'updated_at' => $service->updated_at,
                    'deleted_at' => $service->deleted_at,
                ]);

                // Create a default package for the service
                DB::table('marketplace_packages')->insertOrIgnore([
                    'service_id' => $service->id,
                    'name' => 'Basic',
                    'description' => 'Default package migrated from legacy system',
                    'price' => $service->price,
                    'currency_code' => $service->currency == 2 ? 'EGP' : 'USD',
                    'delivery_days' => 3, // Defaulting to 3 days
                    'created_at' => $service->created_at,
                    'updated_at' => $service->updated_at,
                ]);
            }
            $this->info("Migrated a chunk of services.");
        });
    }

    protected function migrateSubscriptions()
    {
        $this->info('Migrating Subscriptions...');
        // TODO: Implement subscriptions migration (Waiting on plan confirmation)
    }

    protected function migrateCrm()
    {
        $this->info('Migrating CRM...');
        // TODO: Implement CRM migration (Waiting on plan confirmation)
    }
}
