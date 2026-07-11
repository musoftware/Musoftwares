<?php

namespace Database\Seeders;

use App\Models\ModulePlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ModulePlanSeeder extends Seeder
{
    public function run()
    {
        // Clear existing plans with foreign key checks disabled
        Schema::disableForeignKeyConstraints();
        ModulePlan::truncate();
        Schema::enableForeignKeyConstraints();

        // 1. Core ERP Plans (Based on Core Packages)
        ModulePlan::create([
            'module' => 'erp',
            'name' => 'ERP Starter',
            'price' => 19.00,
            'billing' => 'monthly',
            'features' => [
                'Core Foundation' => 'Authentication, Settings, Roles, Dashboards',
                'Core Business' => 'Customers, Suppliers, Products & Basic Taxes',
                'Core Sales' => 'Quotations, Sales Orders, Invoices & Payments',
                'Team Members' => 'Up to 2 team members',
                'Support' => 'Standard Email Support',
            ],
            'is_active' => true,
        ]);

        ModulePlan::create([
            'module' => 'erp',
            'name' => 'ERP Professional',
            'price' => 49.00,
            'billing' => 'monthly',
            'features' => [
                'Everything in Starter' => 'Foundation, Business, Sales',
                'Core Inventory' => 'Warehouses, Stock Tracking, Movement & Adjustments',
                'Core Purchasing' => 'Purchase Orders, Supplier Invoices & Receiving',
                'Core Reports' => 'Sales, Inventory, Purchase & Expense Reports',
                'Team Members' => 'Up to 10 team members',
                'Support' => 'Priority Support',
            ],
            'is_active' => true,
        ]);

        ModulePlan::create([
            'module' => 'erp',
            'name' => 'ERP Starter (Yearly)',
            'price' => 190.00,
            'billing' => 'yearly',
            'features' => [
                'Core Foundation' => 'Authentication, Settings, Roles, Dashboards',
                'Core Business' => 'Customers, Suppliers, Products & Basic Taxes',
                'Core Sales' => 'Quotations, Sales Orders, Invoices & Payments',
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
                'Everything in Starter' => 'Foundation, Business, Sales',
                'Core Inventory' => 'Warehouses, Stock Tracking, Movement & Adjustments',
                'Core Purchasing' => 'Purchase Orders, Supplier Invoices & Receiving',
                'Core Reports' => 'Sales, Inventory, Purchase & Expense Reports',
                'Team Members' => 'Up to 10 team members',
                'Saving' => 'Get 2 months free',
            ],
            'is_active' => true,
        ]);

        // 1.1 ERP Addons (Purchased on top of Core Plans)
        ModulePlan::create([
            'module' => 'erp_projects',
            'name' => 'Projects Management Addon',
            'price' => 10.00,
            'billing' => 'monthly',
            'features' => [
                'Projects' => 'Unlimited Projects',
                'Tasks' => 'Task management & tracking',
                'Time Tracking' => 'Timesheets and billable hours',
            ],
            'is_active' => true,
        ]);

        ModulePlan::create([
            'module' => 'erp_pos',
            'name' => 'Point of Sale (POS) Addon',
            'price' => 15.00,
            'billing' => 'monthly',
            'features' => [
                'Registers' => 'Unlimited Cash Registers',
                'Hardware' => 'Barcode scanners, receipt printers support',
                'Offline Mode' => 'Supported with auto-sync',
            ],
            'is_active' => true,
        ]);

        ModulePlan::create([
            'module' => 'erp_hr',
            'name' => 'HR & Payroll Addon',
            'price' => 20.00,
            'billing' => 'monthly',
            'features' => [
                'Employees' => 'Employee Profiles & Contracts',
                'Attendance' => 'Time clock and attendance tracking',
                'Payroll' => 'Automated salary slips and deductions',
                'Leaves' => 'Leave request management',
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
