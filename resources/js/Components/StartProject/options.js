import { Briefcase, Database, Globe, HelpCircle, Layers, Monitor, Server, ShoppingBag, Smartphone, Terminal, Wrench, Zap } from 'lucide-react';
import { __ } from '@/lib/i18n';

/**
 * Option lists for the start-project form. They are functions so the labels follow the page language.
 * The ids and values are what the lead endpoint already receives; keep them stable.
 */

/** Plain-language choices that match what we really sell: websites, stores, apps, business programs, changes to an existing system. */
export const solutionOptions = () => [
    { id: 'website', icon: Globe, title: __('start_project.solution_website'), desc: __('start_project.solution_website_desc') },
    { id: 'store', icon: ShoppingBag, title: __('start_project.solution_store'), desc: __('start_project.solution_store_desc') },
    { id: 'app', icon: Smartphone, title: __('start_project.solution_app'), desc: __('start_project.solution_app_desc') },
    { id: 'system', icon: Briefcase, title: __('start_project.solution_system'), desc: __('start_project.solution_system_desc') },
    { id: 'existing', icon: Wrench, title: __('start_project.solution_existing'), desc: __('start_project.solution_existing_desc') },
    { id: 'not_sure', icon: HelpCircle, title: __('start_project.solution_not_sure'), desc: __('start_project.solution_not_sure_desc') },
];

export const industryOptions = () => [
    { value: 'Retail & Supermarket Chains', label: __('frontend.wizard_industry_retail') },
    { value: 'FinTech & Gold Trading', label: __('frontend.wizard_industry_fintech') },
    { value: 'Healthcare, Clinics & Pharma', label: __('frontend.wizard_industry_healthcare') },
    { value: 'Real Estate & Contracting', label: __('frontend.wizard_industry_realestate') },
    { value: 'Manufacturing & Factories', label: __('frontend.wizard_industry_manufacturing') },
    { value: 'Logistics & Courier Delivery', label: __('frontend.wizard_industry_logistics') },
    { value: 'E-Commerce & Online Brands', label: __('frontend.wizard_industry_ecommerce') },
    { value: 'Education & Training Academies', label: __('frontend.wizard_industry_education') },
    { value: 'Other Bespoke Services', label: __('frontend.wizard_industry_other') },
];

export const timelineOptions = () => [
    { id: 'urgent', title: __('frontend.wizard_timeline_urgent_title'), time: __('frontend.wizard_timeline_urgent_time') },
    { id: 'standard', title: __('frontend.wizard_timeline_standard_title'), time: __('frontend.wizard_timeline_standard_time') },
    { id: 'strategic', title: __('frontend.wizard_timeline_strategic_title'), time: __('frontend.wizard_timeline_strategic_time') },
];

/** Budget ranges the visitor picks from. These are the existing figures; do not change them without the owner. */
export const budgetOptions = () => [
    { id: 'tier_1', title: __('frontend.wizard_budget_tier1'), egp: '75,000 - 150,000 EGP', usd: '$1,500 - $3,000 USD' },
    { id: 'tier_2', title: __('frontend.wizard_budget_tier2'), egp: '150,000 - 350,000 EGP', usd: '$3,000 - $7,000 USD' },
    { id: 'tier_3', title: __('frontend.wizard_budget_tier3'), egp: '350,000+ EGP', usd: '$7,000+ USD' },
    { id: 'tier_custom', title: __('frontend.wizard_budget_custom'), egp: __('frontend.wizard_custom_review'), usd: __('frontend.wizard_custom_review') },
];

export const archetypeOptions = () => [
    { id: 'erp_ledger', icon: Database, title: __('frontend.wizard_arch_erp_title'), desc: __('frontend.wizard_arch_erp_desc') },
    { id: 'saas_platform', icon: Layers, title: __('frontend.wizard_arch_saas_title'), desc: __('frontend.wizard_arch_saas_desc') },
    { id: 'meta_whatsapp', icon: Zap, title: __('frontend.wizard_arch_meta_title'), desc: __('frontend.wizard_arch_meta_desc') },
    { id: 'desktop_rpa', icon: Terminal, title: __('frontend.wizard_arch_desktop_title'), desc: __('frontend.wizard_arch_desktop_desc') },
    { id: 'fintech_trading', icon: Server, title: __('frontend.wizard_arch_fintech_title'), desc: __('frontend.wizard_arch_fintech_desc') },
    { id: 'mobile_app', icon: Smartphone, title: __('frontend.wizard_arch_mobile_title'), desc: __('frontend.wizard_arch_mobile_desc') },
];

export const platformOptions = () => [
    { id: 'web', icon: Globe, title: __('frontend.wizard_platform_web') },
    { id: 'mobile', icon: Smartphone, title: __('frontend.wizard_platform_mobile') },
    { id: 'desktop', icon: Monitor, title: __('frontend.wizard_platform_desktop') },
    { id: 'edge', icon: Server, title: __('frontend.wizard_platform_edge') },
];

export const moduleGroups = () => [
    {
        category: __('frontend.wizard_cat_finance'),
        items: [
            { id: 'double_entry_ledger', title: __('frontend.wizard_mod_ledger_title'), desc: __('frontend.wizard_mod_ledger_desc') },
            { id: 'tax_einvoicing', title: __('frontend.wizard_mod_tax_title'), desc: __('frontend.wizard_mod_tax_desc') },
            { id: 'multi_currency', title: __('frontend.wizard_mod_currency_title'), desc: __('frontend.wizard_mod_currency_desc') },
            { id: 'payment_gateways', title: __('frontend.wizard_mod_gateways_title'), desc: 'Paymob, Fawry, Stripe, Apple Pay, Tabby, Tamara' },
            { id: 'inventory_pos', title: __('frontend.wizard_mod_inventory_title'), desc: __('frontend.wizard_mod_inventory_desc') },
        ],
    },
    {
        category: __('frontend.wizard_cat_comms'),
        items: [
            { id: 'whatsapp_alerts', title: __('frontend.wizard_mod_whatsapp_title'), desc: __('frontend.wizard_mod_whatsapp_desc') },
            { id: 'interactive_chatbot', title: __('frontend.wizard_mod_chatbot_title'), desc: __('frontend.wizard_mod_chatbot_desc') },
            { id: 'sms_otp', title: __('frontend.wizard_mod_sms_title'), desc: __('frontend.wizard_mod_sms_desc') },
            { id: 'webhooks_api', title: __('frontend.wizard_mod_api_title'), desc: __('frontend.wizard_mod_api_desc') },
        ],
    },
    {
        category: __('frontend.wizard_cat_security'),
        items: [
            { id: 'rbac_permissions', title: __('frontend.wizard_mod_rbac_title'), desc: __('frontend.wizard_mod_rbac_desc') },
            { id: 'biometric_2fa', title: __('frontend.wizard_mod_2fa_title'), desc: __('frontend.wizard_mod_2fa_desc') },
            { id: 'audit_trail', title: __('frontend.wizard_mod_audit_title'), desc: __('frontend.wizard_mod_audit_desc') },
            { id: 'tenant_isolation', title: __('frontend.wizard_mod_tenant_title'), desc: __('frontend.wizard_mod_tenant_desc') },
        ],
    },
];
