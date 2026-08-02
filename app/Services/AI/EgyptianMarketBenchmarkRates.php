<?php

namespace App\Services\AI;

class EgyptianMarketBenchmarkRates
{
    /**
     * Egyptian Software Market Reference Rates (in USD).
     * Saved in USD, dynamically converted to EGP via CurrenciesExchange.
     */
    public static function getBenchmarks(): array
    {
        return [
            'landing_page' => [
                'name_ar'       => 'صفحة هبوط (Landing Page)',
                'name_en'       => 'Landing Page',
                'base_usd'      => 200,
                'min_usd'       => 150,
                'max_usd'       => 400,
                'avg_egp'       => 10000,
                'est_days'      => 3,
            ],
            'corporate_website' => [
                'name_ar'       => 'موقع تعريفي للشركة (Corporate Website)',
                'name_en'       => 'Corporate Website',
                'base_usd'      => 450,
                'min_usd'       => 300,
                'max_usd'       => 800,
                'avg_egp'       => 22500,
                'est_days'      => 7,
            ],
            'ecommerce_store' => [
                'name_ar'       => 'متجر إلكتروني (E-Commerce Store)',
                'name_en'       => 'E-Commerce Store',
                'base_usd'      => 1000,
                'min_usd'       => 500,
                'max_usd'       => 2500,
                'avg_egp'       => 50000,
                'est_days'      => 14,
            ],
            'crm_system' => [
                'name_ar'       => 'نظام إدارة علاقات العملاء (CRM)',
                'name_en'       => 'CRM System',
                'base_usd'      => 1200,
                'min_usd'       => 800,
                'max_usd'       => 3000,
                'avg_egp'       => 60000,
                'est_days'      => 21,
            ],
            'erp_system' => [
                'name_ar'       => 'نظام تخطيط موارد المؤسسات (ERP System)',
                'name_en'       => 'ERP System',
                'base_usd'      => 2500,
                'min_usd'       => 1500,
                'max_usd'       => 6000,
                'avg_egp'       => 125000,
                'est_days'      => 45,
            ],
            'mobile_application' => [
                'name_ar'       => 'تطبيق موبايل (iOS & Android App)',
                'name_en'       => 'Mobile Application',
                'base_usd'      => 1800,
                'min_usd'       => 1000,
                'max_usd'       => 4500,
                'avg_egp'       => 90000,
                'est_days'      => 30,
            ],
            'admin_dashboard' => [
                'name_ar'       => 'لوحة تحكم إدارية (Admin Dashboard)',
                'name_en'       => 'Admin Dashboard',
                'base_usd'      => 500,
                'min_usd'       => 300,
                'max_usd'       => 1200,
                'avg_egp'       => 25000,
                'est_days'      => 8,
            ],
            'custom_api' => [
                'name_ar'       => 'واجهة برمجة تطبيقات (Custom API / Integration)',
                'name_en'       => 'Custom API Integration',
                'base_usd'      => 350,
                'min_usd'       => 200,
                'max_usd'       => 800,
                'avg_egp'       => 17500,
                'est_days'      => 5,
            ],
            'ai_chatbot' => [
                'name_ar'       => 'شات بوت ذكي (AI Chatbot & Automation)',
                'name_en'       => 'AI Chatbot & Automation',
                'base_usd'      => 400,
                'min_usd'       => 250,
                'max_usd'       => 900,
                'avg_egp'       => 20000,
                'est_days'      => 6,
            ],
        ];
    }
}
