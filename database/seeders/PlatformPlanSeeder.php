<?php

namespace Database\Seeders;

use App\Models\PlatformPlan;
use App\Models\PlatformServiceItem;
use Illuminate\Database\Seeder;

class PlatformPlanSeeder extends Seeder
{
    public function run(): void
    {
        // ── Fixed Plans ──────────────────────────────────────────────

        PlatformPlan::updateOrCreate(['slug' => 'go'], [
            'name' => 'Go',
            'description' => 'Perfect for getting started with one tool of your choice.',
            'monthly_price' => 20 / 3, // Roughly $6.66 per month
            'yearly_price' => 80,     // 20 * 4
            'included_modules' => [],
            'included_tools' => [],     // To be selected upon subscription
            'features' => [
                '1 Tool of your choice',
                'Basic support',
            ],
            'is_custom' => false,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        PlatformPlan::updateOrCreate(['slug' => 'plus'], [
            'name' => 'Plus',
            'description' => 'All automation tools included.',
            'monthly_price' => 50 / 3, // Roughly $16.66 per month
            'yearly_price' => 200,    // 50 * 4
            'included_modules' => [],
            'included_tools' => ['*'],
            'features' => [
                'All automation tools',
                'Priority support',
                'API access',
            ],
            'is_custom' => false,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        PlatformPlan::updateOrCreate(['slug' => 'pro'], [
            'name' => 'Pro',
            'description' => 'Complete business suite — everything included.',
            'monthly_price' => 90 / 3, // $30 per month
            'yearly_price' => 360,    // 90 * 4
            'included_modules' => ['*'],
            'included_tools' => ['*'],
            'features' => [
                'All automation tools',
                'All business modules',
                'CRM & ERP included',
                'Dedicated support',
            ],
            'is_custom' => false,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        PlatformPlan::updateOrCreate(['slug' => 'custom'], [
            'name' => 'Custom',
            'description' => 'Build your own plan — pick only what you need.',
            'monthly_price' => 0,
            'yearly_price' => 0,
            'included_modules' => [],
            'included_tools' => [],
            'features' => [],
            'is_custom' => true,
            'sort_order' => 4,
            'is_active' => true,
        ]);

        // ── Service Items (for Custom plan builder) ──────────────────

        // Modules
        PlatformServiceItem::updateOrCreate(['slug' => 'erp'], [
            'type' => 'module',
            'name' => 'Business OS (ERP)',
            'description' => 'Clients, invoices, projects, tasks, team members',
            'monthly_price' => 39.99,
            'yearly_price' => 399.99,
            'icon' => 'Building2',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        PlatformServiceItem::updateOrCreate(['slug' => 'crm'], [
            'type' => 'module',
            'name' => 'CRM & Lead Gen',
            'description' => 'Lead capture, campaigns, pipeline management',
            'monthly_price' => 14.99,
            'yearly_price' => 149.99,
            'icon' => 'Megaphone',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        PlatformServiceItem::updateOrCreate(['slug' => 'booking'], [
            'type' => 'module',
            'name' => 'Booking & Scheduling',
            'description' => 'Appointments, availability, calendar sync',
            'monthly_price' => 9.99,
            'yearly_price' => 99.99,
            'icon' => 'Calendar',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Tools
        PlatformServiceItem::updateOrCreate(['slug' => 'whatsapp'], [
            'type' => 'tool',
            'name' => 'WhatsApp Automation',
            'description' => 'Bulk messaging, auto-replies, contact sync',
            'monthly_price' => 9.99,
            'yearly_price' => 99.99,
            'icon' => 'MessageSquare',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        PlatformServiceItem::updateOrCreate(['slug' => 'sms'], [
            'type' => 'tool',
            'name' => 'SMS Gateway',
            'description' => 'Send SMS campaigns and notifications',
            'monthly_price' => 4.99,
            'yearly_price' => 49.99,
            'icon' => 'MessageSquare',
            'sort_order' => 11,
            'is_active' => true,
        ]);

        PlatformServiceItem::updateOrCreate(['slug' => 'competitor-tracker'], [
            'type' => 'tool',
            'name' => 'Competitor Tracker',
            'description' => 'Track competitor metrics and changes',
            'monthly_price' => 9.99,
            'yearly_price' => 99.99,
            'icon' => 'Radar',
            'sort_order' => 12,
            'is_active' => true,
        ]);

        PlatformServiceItem::updateOrCreate(['slug' => 'ad-library-monitor'], [
            'type' => 'tool',
            'name' => 'Ad Library Monitor',
            'description' => 'Monitor ads across multiple platforms',
            'monthly_price' => 14.99,
            'yearly_price' => 149.99,
            'icon' => 'Megaphone',
            'sort_order' => 13,
            'is_active' => true,
        ]);

        PlatformServiceItem::updateOrCreate(['slug' => 'ugc-creators-search'], [
            'type' => 'tool',
            'name' => 'UGC Creators Search',
            'description' => 'Find and connect with UGC creators',
            'monthly_price' => 9.99,
            'yearly_price' => 99.99,
            'icon' => 'Users',
            'sort_order' => 14,
            'is_active' => true,
        ]);

        PlatformServiceItem::updateOrCreate(['slug' => 'swipe-vault'], [
            'type' => 'tool',
            'name' => 'Swipe Vault',
            'description' => 'Save and organize creative inspiration',
            'monthly_price' => 4.99,
            'yearly_price' => 49.99,
            'icon' => 'Bookmark',
            'sort_order' => 15,
            'is_active' => true,
        ]);
    }
}
