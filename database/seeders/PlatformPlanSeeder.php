<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlatformPlan;
use App\Models\PlatformServiceItem;

class PlatformPlanSeeder extends Seeder
{
    public function run(): void
    {
        // ── Fixed Plans ──────────────────────────────────────────────

        PlatformPlan::updateOrCreate(['slug' => 'starter'], [
            'name'             => 'Starter',
            'description'      => 'Perfect for getting started with WhatsApp automation.',
            'monthly_price'    => 2,
            'yearly_price'     => 20,
            'included_modules' => [],
            'included_tools'   => ['whatsapp'],
            'features'         => [
                'WhatsApp Business Tool',
                'Automated messaging',
                'Contact management',
                'Basic analytics',
                'Email support',
            ],
            'is_custom'  => false,
            'sort_order'  => 1,
            'is_active'   => true,
        ]);

        PlatformPlan::updateOrCreate(['slug' => 'professional'], [
            'name'             => 'Professional',
            'description'      => 'All automation tools for growing businesses.',
            'monthly_price'    => 5,
            'yearly_price'     => 50,
            'included_modules' => ['crm'],
            'included_tools'   => ['*'],
            'features'         => [
                'All automation tools',
                'WhatsApp, SMS & more',
                'CRM lead management',
                'Campaign automation',
                'Priority support',
                'API access',
            ],
            'is_custom'  => false,
            'sort_order'  => 2,
            'is_active'   => true,
        ]);

        PlatformPlan::updateOrCreate(['slug' => 'business_suite'], [
            'name'             => 'Business Suite',
            'description'      => 'Complete business operating system — everything included.',
            'monthly_price'    => 9,
            'yearly_price'     => 90,
            'included_modules' => ['erp', 'crm', 'booking', 'intelligence'],
            'included_tools'   => ['*'],
            'features'         => [
                'Full ERP system',
                'CRM & lead gen',
                'Booking & scheduling',
                'Intelligence & analytics',
                'All tools & plugins',
                'Team members (up to 10)',
                'Dedicated support',
                'Custom integrations',
            ],
            'is_custom'  => false,
            'sort_order'  => 3,
            'is_active'   => true,
        ]);

        PlatformPlan::updateOrCreate(['slug' => 'custom'], [
            'name'             => 'Custom',
            'description'      => 'Build your own plan — pick only what you need.',
            'monthly_price'    => 0,
            'yearly_price'     => 0,
            'included_modules' => [],
            'included_tools'   => [],
            'features'         => [],
            'is_custom'  => true,
            'sort_order'  => 4,
            'is_active'   => true,
        ]);

        // ── Service Items (for Custom plan builder) ──────────────────

        // Modules
        PlatformServiceItem::updateOrCreate(['slug' => 'erp'], [
            'type'          => 'module',
            'name'          => 'Business OS (ERP)',
            'description'   => 'Clients, invoices, projects, tasks, team members',
            'monthly_price' => 39.99,
            'yearly_price'  => 399.99,
            'icon'          => 'Building2',
            'sort_order'    => 1,
            'is_active'     => true,
        ]);

        PlatformServiceItem::updateOrCreate(['slug' => 'crm'], [
            'type'          => 'module',
            'name'          => 'CRM & Lead Gen',
            'description'   => 'Lead capture, campaigns, pipeline management',
            'monthly_price' => 14.99,
            'yearly_price'  => 149.99,
            'icon'          => 'Megaphone',
            'sort_order'    => 2,
            'is_active'     => true,
        ]);

        PlatformServiceItem::updateOrCreate(['slug' => 'booking'], [
            'type'          => 'module',
            'name'          => 'Booking & Scheduling',
            'description'   => 'Appointments, availability, calendar sync',
            'monthly_price' => 9.99,
            'yearly_price'  => 99.99,
            'icon'          => 'Calendar',
            'sort_order'    => 3,
            'is_active'     => true,
        ]);

        PlatformServiceItem::updateOrCreate(['slug' => 'intelligence'], [
            'type'          => 'module',
            'name'          => 'Intelligence & Analytics',
            'description'   => 'Market tracking, ad monitoring, insights',
            'monthly_price' => 19.99,
            'yearly_price'  => 199.99,
            'icon'          => 'Radar',
            'sort_order'    => 4,
            'is_active'     => true,
        ]);

        // Tools
        PlatformServiceItem::updateOrCreate(['slug' => 'whatsapp'], [
            'type'          => 'tool',
            'name'          => 'WhatsApp Automation',
            'description'   => 'Bulk messaging, auto-replies, contact sync',
            'monthly_price' => 9.99,
            'yearly_price'  => 99.99,
            'icon'          => 'MessageSquare',
            'sort_order'    => 10,
            'is_active'     => true,
        ]);

        PlatformServiceItem::updateOrCreate(['slug' => 'sms'], [
            'type'          => 'tool',
            'name'          => 'SMS Gateway',
            'description'   => 'Send SMS campaigns and notifications',
            'monthly_price' => 4.99,
            'yearly_price'  => 49.99,
            'icon'          => 'MessageSquare',
            'sort_order'    => 11,
            'is_active'     => true,
        ]);
    }
}
