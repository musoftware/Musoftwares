<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ERP\Models\ModulePlan;

class ModulePlanSeeder extends Seeder
{
    public function run()
    {
        // Clear existing plans with foreign key checks disabled
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        ModulePlan::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // 1. ERP Plans
        ModulePlan::create([
            'module' => 'erp',
            'name' => 'ERP Starter',
            'price' => 19.00,
            'billing' => 'monthly',
            'features' => [
                'Projects' => 'Up to 5 active projects',
                'Invoices' => 'Up to 10 invoices per month',
                'Tasks' => 'Up to 50 active tasks',
                'Wallet Integration' => 'Fully supported',
                'Team Members' => 'Up to 2 team members',
            ],
            'is_active' => true,
        ]);

        ModulePlan::create([
            'module' => 'erp',
            'name' => 'ERP Professional',
            'price' => 49.00,
            'billing' => 'monthly',
            'features' => [
                'Projects' => 'Unlimited projects',
                'Invoices' => 'Unlimited invoices',
                'Tasks' => 'Unlimited tasks',
                'Wallet Integration' => 'Fully supported',
                'Team Members' => 'Up to 10 team members',
                'Advanced Reporting' => 'PnL reports and detailed analytics',
                'Time Tracking' => 'Included with automated invoicing',
            ],
            'is_active' => true,
        ]);

        ModulePlan::create([
            'module' => 'erp',
            'name' => 'ERP Starter (Yearly)',
            'price' => 190.00,
            'billing' => 'yearly',
            'features' => [
                'Projects' => 'Up to 5 active projects',
                'Invoices' => 'Up to 10 invoices per month',
                'Tasks' => 'Up to 50 active tasks',
                'Wallet Integration' => 'Fully supported',
                'Team Members' => 'Up to 2 team members',
                'Saving' => 'Get 2 months free',
            ],
            'is_active' => true,
        ]);

        ModulePlan::create([
            'module' => 'erp',
            'name' => 'ERP Professional (Yearly)',
            'price' => 490.00,
            'billing' => 'yearly',
            'features' => [
                'Projects' => 'Unlimited projects',
                'Invoices' => 'Unlimited invoices',
                'Tasks' => 'Unlimited tasks',
                'Wallet Integration' => 'Fully supported',
                'Team Members' => 'Up to 10 team members',
                'Advanced Reporting' => 'PnL reports and detailed analytics',
                'Time Tracking' => 'Included with automated invoicing',
                'Saving' => 'Get 2 months free',
            ],
            'is_active' => true,
        ]);

        // 2. Freelancer Premium Plans (for extra perks, basic tier is free)
        ModulePlan::create([
            'module' => 'freelance',
            'name' => 'Freelancer Premium',
            'price' => 9.99,
            'billing' => 'monthly',
            'features' => [
                'Commission Fee' => 'Reduced from 10% to 5%',
                'Premium Badge' => 'Stripe-like calm verification badge',
                'Monthly Connects' => '100 bidding connects included (valued at $10)',
                'Priority Support' => 'First-in-line ticket resolution',
            ],
            'is_active' => true,
        ]);

        // 3. Marketing Module Plans
        ModulePlan::create([
            'module' => 'marketing',
            'name' => 'Marketing Starter',
            'price' => 29.00,
            'billing' => 'monthly',
            'features' => [
                'Email Campaigns' => 'Up to 5 campaigns per month',
                'Subscribers' => 'Up to 1,000 active leads',
                'Lead Generation' => 'Interactive landing pages and forms',
                'Basic Analytics' => 'Open rates, click-through rates tracking',
            ],
            'is_active' => true,
        ]);
        
        ModulePlan::create([
            'module' => 'marketing',
            'name' => 'Marketing Pro',
            'price' => 79.00,
            'billing' => 'monthly',
            'features' => [
                'Email Campaigns' => 'Unlimited campaigns',
                'Subscribers' => 'Up to 10,000 active leads',
                'Lead Generation' => 'A/B testing, custom branding',
                'Advanced Automation' => 'Custom triggered workflows & sequence rules',
                'Premium Support' => '24/7 priority SLA support',
            ],
            'is_active' => true,
        ]);
    }
}
