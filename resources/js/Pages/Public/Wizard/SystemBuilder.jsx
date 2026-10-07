import React, { useState, useMemo } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { 
    Layers, 
    Server, 
    Zap, 
    Database, 
    ShieldCheck, 
    Smartphone, 
    Monitor, 
    Globe, 
    Check, 
    CheckCircle2, 
    ArrowRight, 
    ArrowLeft, 
    Sparkles, 
    MessageSquare, 
    Building2, 
    Clock, 
    DollarSign, 
    Cpu, 
    Terminal, 
    Lock, 
    Receipt, 
    Send, 
    FileText, 
    HelpCircle, 
    Copy, 
    CheckCheck,
    CreditCard
} from 'lucide-react';
import { __ } from '@/lib/i18n';
import { openWhatsAppChat } from '@/lib/whatsapp';
import StudioHeader from '@/Components/Studio/StudioHeader';
import axios from 'axios';
import { useToast } from '@/Components/ui/use-toast';

export default function SystemBuilder() {
    const { toast } = useToast();
    const [currentStep, setCurrentStep] = useState(1);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isSubmitted, setIsSubmitted] = useState(false);
    const [briefId, setBriefId] = useState('');
    const [copied, setCopied] = useState(false);

    // Form State
    const [formData, setFormData] = useState({
        // Step 1: Archetype & Platforms
        archetype: 'erp_ledger',
        targetAudience: 'internal',
        deploymentPlatforms: ['web'],
        scaleExpected: 'medium',

        // Step 2: Modules & Features
        modules: [
            'double_entry_ledger',
            'tax_einvoicing',
            'rbac_permissions',
            'whatsapp_alerts'
        ],
        customFeaturesText: '',

        // Step 3: Timeline & Budget
        timeline: 'standard',
        budgetTier: 'tier_2',
        currency: 'EGP',

        // Step 4: Organization & Contact
        companyName: '',
        industry: 'retail_pos',
        contactName: '',
        contactPhone: '',
        contactEmail: '',
        projectNotes: '',
        existingSystem: '',
        figmaOrDocUrl: '',
    });

    const archetypes = [
        {
            id: 'erp_ledger',
            title: __('frontend.wizard_arch_erp_title'),
            badge: __('frontend.wizard_arch_erp_badge'),
            desc: __('frontend.wizard_arch_erp_desc'),
            icon: Database,
            recommendedFor: __('frontend.wizard_arch_erp_for'),
        },
        {
            id: 'saas_platform',
            title: __('frontend.wizard_arch_saas_title'),
            badge: __('frontend.wizard_arch_saas_badge'),
            desc: __('frontend.wizard_arch_saas_desc'),
            icon: Layers,
            recommendedFor: __('frontend.wizard_arch_saas_for'),
        },
        {
            id: 'meta_whatsapp',
            title: __('frontend.wizard_arch_meta_title'),
            badge: __('frontend.wizard_arch_meta_badge'),
            desc: __('frontend.wizard_arch_meta_desc'),
            icon: Zap,
            recommendedFor: __('frontend.wizard_arch_meta_for'),
        },
        {
            id: 'desktop_rpa',
            title: __('frontend.wizard_arch_desktop_title'),
            badge: __('frontend.wizard_arch_desktop_badge'),
            desc: __('frontend.wizard_arch_desktop_desc'),
            icon: Terminal,
            recommendedFor: __('frontend.wizard_arch_desktop_for'),
        },
        {
            id: 'fintech_trading',
            title: __('frontend.wizard_arch_fintech_title'),
            badge: __('frontend.wizard_arch_fintech_badge'),
            desc: __('frontend.wizard_arch_fintech_desc'),
            icon: Server,
            recommendedFor: __('frontend.wizard_arch_fintech_for'),
        },
        {
            id: 'mobile_app',
            title: __('frontend.wizard_arch_mobile_title'),
            badge: __('frontend.wizard_arch_mobile_badge'),
            desc: __('frontend.wizard_arch_mobile_desc'),
            icon: Smartphone,
            recommendedFor: __('frontend.wizard_arch_mobile_for'),
        },
    ];

    const deploymentPlatformOptions = [
        { id: 'web', title: __('frontend.wizard_platform_web'), icon: Globe },
        { id: 'mobile', title: __('frontend.wizard_platform_mobile'), icon: Smartphone },
        { id: 'desktop', title: __('frontend.wizard_platform_desktop'), icon: Monitor },
        { id: 'edge', title: __('frontend.wizard_platform_edge'), icon: Server },
    ];

    const moduleCategories = [
        {
            category: __('frontend.wizard_cat_finance'),
            items: [
                { id: 'double_entry_ledger', title: __('frontend.wizard_mod_ledger_title'), desc: __('frontend.wizard_mod_ledger_desc') },
                { id: 'tax_einvoicing', title: __('frontend.wizard_mod_tax_title'), desc: __('frontend.wizard_mod_tax_desc') },
                { id: 'multi_currency', title: __('frontend.wizard_mod_currency_title'), desc: __('frontend.wizard_mod_currency_desc') },
                { id: 'payment_gateways', title: __('frontend.wizard_mod_gateways_title'), desc: 'Paymob, Fawry, Stripe, Apple Pay, Tabby, Tamara' },
                { id: 'inventory_pos', title: __('frontend.wizard_mod_inventory_title'), desc: __('frontend.wizard_mod_inventory_desc') },
            ]
        },
        {
            category: __('frontend.wizard_cat_comms'),
            items: [
                { id: 'whatsapp_alerts', title: __('frontend.wizard_mod_whatsapp_title'), desc: __('frontend.wizard_mod_whatsapp_desc') },
                { id: 'interactive_chatbot', title: __('frontend.wizard_mod_chatbot_title'), desc: __('frontend.wizard_mod_chatbot_desc') },
                { id: 'sms_otp', title: __('frontend.wizard_mod_sms_title'), desc: __('frontend.wizard_mod_sms_desc') },
                { id: 'webhooks_api', title: __('frontend.wizard_mod_api_title'), desc: __('frontend.wizard_mod_api_desc') },
            ]
        },
        {
            category: __('frontend.wizard_cat_security'),
            items: [
                { id: 'rbac_permissions', title: __('frontend.wizard_mod_rbac_title'), desc: __('frontend.wizard_mod_rbac_desc') },
                { id: 'biometric_2fa', title: __('frontend.wizard_mod_2fa_title'), desc: __('frontend.wizard_mod_2fa_desc') },
                { id: 'audit_trail', title: __('frontend.wizard_mod_audit_title'), desc: __('frontend.wizard_mod_audit_desc') },
                { id: 'tenant_isolation', title: __('frontend.wizard_mod_tenant_title'), desc: __('frontend.wizard_mod_tenant_desc') },
            ]
        },
    ];

    const timelineOptions = [
        { id: 'urgent', title: __('frontend.wizard_timeline_urgent_title'), time: __('frontend.wizard_timeline_urgent_time'), desc: __('frontend.wizard_timeline_urgent_desc') },
        { id: 'standard', title: __('frontend.wizard_timeline_standard_title'), time: __('frontend.wizard_timeline_standard_time'), desc: __('frontend.wizard_timeline_standard_desc') },
        { id: 'strategic', title: __('frontend.wizard_timeline_strategic_title'), time: __('frontend.wizard_timeline_strategic_time'), desc: __('frontend.wizard_timeline_strategic_desc') },
    ];

    const budgetOptions = [
        { id: 'tier_1', title: __('frontend.wizard_budget_tier1'), egp: '75,000 - 150,000 EGP', usd: '$1,500 - $3,000 USD' },
        { id: 'tier_2', title: __('frontend.wizard_budget_tier2'), egp: '150,000 - 350,000 EGP', usd: '$3,000 - $7,000 USD' },
        { id: 'tier_3', title: __('frontend.wizard_budget_tier3'), egp: '350,000+ EGP', usd: '$7,000+ USD' },
        { id: 'tier_custom', title: __('frontend.wizard_budget_custom'), egp: __('frontend.wizard_custom_review'), usd: __('frontend.wizard_custom_review') },
    ];

    const industryOptions = [
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

    const industryLabel = (value) => industryOptions.find(i => i.value === value)?.label || value;
    const moduleTitle = (id) => moduleCategories.flatMap(g => g.items).find(i => i.id === id)?.title || id.replace(/_/g, ' ');
    const platformTitle = (id) => deploymentPlatformOptions.find(p => p.id === id)?.title || id;

    const togglePlatform = (platId) => {
        setFormData(prev => {
            const list = prev.deploymentPlatforms.includes(platId)
                ? prev.deploymentPlatforms.filter(p => p !== platId)
                : [...prev.deploymentPlatforms, platId];
            return { ...prev, deploymentPlatforms: list.length ? list : ['web'] };
        });
    };

    const toggleModule = (modId) => {
        setFormData(prev => {
            const list = prev.modules.includes(modId)
                ? prev.modules.filter(m => m !== modId)
                : [...prev.modules, modId];
            return { ...prev, modules: list };
        });
    };

    // Auto-generate WhatsApp Message
    const generatedWhatsAppUrl = useMemo(() => {
        const arch = archetypes.find(a => a.id === formData.archetype)?.title || formData.archetype;
        const platforms = formData.deploymentPlatforms.join(', ');
        const budget = budgetOptions.find(b => b.id === formData.budgetTier)?.[formData.currency === 'USD' ? 'usd' : 'egp'] || 'Flexible';
        
        const message = `Hello Mahmoud! I have configured a new system brief on Musoftwares Wizard:

*Project / Company:* ${formData.companyName || 'New Venture'}
*System Archetype:* ${arch}
*Platforms:* ${platforms}
*Industry:* ${formData.industry}
*Selected Modules (${formData.modules.length}):*
${formData.modules.map(m => `- ${m.replace(/_/g, ' ')}`).join('\n')}

*Timeline:* ${formData.timeline}
*Budget Range:* ${budget}
*Contact:* ${formData.contactName} (${formData.contactPhone})

*Notes / Scope Summary:*
${formData.projectNotes || 'Standard Architectural Implementation'}

Looking forward to reviewing the technical proposal!`;

        return `https://wa.me/201015218548?text=${encodeURIComponent(message)}`;
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [formData]);

    const handleNext = () => {
        if (currentStep === 4) {
            if (!formData.companyName || !formData.contactName || !formData.contactPhone) {
                toast({
                    title: __('frontend.wizard_required_title'),
                    description: __('frontend.wizard_required_desc'),
                    variant: 'destructive',
                });
                return;
            }
        }
        setCurrentStep(prev => Math.min(5, prev + 1));
        window.scrollTo({ top: 300, behavior: 'smooth' });
    };

    const handleBack = () => {
        setCurrentStep(prev => Math.max(1, prev - 1));
        window.scrollTo({ top: 300, behavior: 'smooth' });
    };

    const handleSubmitBrief = async (e) => {
        e.preventDefault();
        setIsSubmitting(true);

        const generatedCode = `MS-SYS-${Math.floor(1000 + Math.random() * 9000)}`;

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            await axios.post('/tools/lead-capture', {
                name: formData.contactName,
                email: formData.contactEmail,
                mobile: formData.contactPhone,
                business: formData.companyName,
                scope: {
                    brief_code: generatedCode,
                    archetype: formData.archetype,
                    platforms: formData.deploymentPlatforms,
                    industry: formData.industry,
                    modules: formData.modules,
                    timeline: formData.timeline,
                    budget: formData.budgetTier,
                    currency: formData.currency,
                    notes: formData.projectNotes,
                    figma_url: formData.figmaOrDocUrl,
                }
            }, {
                headers: {
                    'X-CSRF-TOKEN': token,
                }
            });

            setBriefId(generatedCode);
            setIsSubmitted(true);
            toast({
                title: __('frontend.wizard_submitted_title'),
                description: __('frontend.wizard_submitted_desc', { code: generatedCode }),
            });
        } catch (error) {
            // Still generate local ID and let user WhatsApp directly
            setBriefId(generatedCode);
            setIsSubmitted(true);
        } finally {
            setIsSubmitting(false);
        }
    };

    const copyBriefSummary = () => {
        const text = `Brief ID: ${briefId || 'MS-SYS-READY'}\nCompany: ${formData.companyName}\nSystem: ${formData.archetype}\nModules: ${formData.modules.length}\nContact: ${formData.contactName} (${formData.contactPhone})`;
        navigator.clipboard.writeText(text);
        setCopied(true);
        setTimeout(() => setCopied(false), 2500);
        toast({ title: __('frontend.wizard_brief_copied') });
    };

    return (
        <PublicLayout>
            <Head>
                <title>{`${__('frontend.wizard_meta_title')} | Musoftwares`}</title>
                <meta name="description" content={__('frontend.wizard_meta_desc')} />
            </Head>

            <div className="w-full bg-[#ffffff] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-8 sm:pt-14 pb-24 sm:pb-36">
                
                {/* Header */}
                <StudioHeader
                    badge={__('frontend.wizard_badge')}
                    title={
                        <>
                            {__('frontend.wizard_hero_title')} <br className="hidden sm:inline" />
                            <span className="text-[#0071e3]">{__('frontend.wizard_hero_highlight')}</span>
                        </>
                    }
                    subtitle={__('frontend.wizard_subtitle')}
                />

                <div className="max-w-[1200px] mx-auto px-6 sm:px-10">
                    
                    {/* Multi-Step Progress Tracker */}
                    <div className="mb-12">
                        <div className="flex items-center justify-between relative text-xs max-w-3xl mx-auto">
                            {/* Track Line */}
                            <div className="absolute top-1/2 left-0 right-0 h-[2px] bg-[#e5e5ea] -translate-y-1/2 z-0" />
                            <div 
                                className="absolute top-1/2 left-0 h-[2px] bg-[#0071e3] -translate-y-1/2 z-0 transition-all duration-500" 
                                style={{ width: `${((currentStep - 1) / 4) * 100}%` }}
                            />

                            {[
                                { step: 1, label: __('frontend.wizard_step_archetype') },
                                { step: 2, label: __('frontend.wizard_step_modules') },
                                { step: 3, label: __('frontend.wizard_step_timeline') },
                                { step: 4, label: __('frontend.wizard_step_organization') },
                                { step: 5, label: __('frontend.wizard_step_review') },
                            ].map((s) => (
                                <div key={s.step} className="flex flex-col items-center relative z-10">
                                    <button
                                        type="button"
                                        onClick={() => s.step < currentStep && setCurrentStep(s.step)}
                                        className={`w-9 h-9 rounded-full flex items-center justify-center font-semibold text-xs transition-all ${
                                            currentStep === s.step
                                                ? 'bg-[#0071e3] text-white scale-110 shadow-md shadow-[#0071e3]/30'
                                                : currentStep > s.step
                                                ? 'bg-white border-2 border-[#0071e3] text-[#0071e3]'
                                                : 'bg-[#f5f5f7] border border-black/10 text-[#1d1d1f]/40'
                                        }`}
                                    >
                                        {currentStep > s.step ? <Check className="w-4 h-4" strokeWidth={3} /> : s.step}
                                    </button>
                                    <span className={`text-[11px] mt-2 hidden sm:block font-medium ${
                                        currentStep === s.step ? 'text-[#1d1d1f] font-semibold' : 'text-[#1d1d1f]/40'
                                    }`}>
                                        {s.label}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Step Card Container */}
                    <div className="bg-white border border-black/5 rounded-[28px] p-6 sm:p-12 relative overflow-hidden shadow-[0_20px_60px_rgba(0,0,0,0.04)]">
                        
                        <AnimatePresence mode="wait">
                            
                            {/* STEP 1: ARCHETYPE & PLATFORMS */}
                            {currentStep === 1 && (
                                <motion.div
                                    key="step1"
                                    initial={{ opacity: 0, x: 20 }}
                                    animate={{ opacity: 1, x: 0 }}
                                    exit={{ opacity: 0, x: -20 }}
                                    transition={{ duration: 0.3 }}
                                    className="space-y-8"
                                >
                                    <div className="space-y-1">
                                        <span className="text-[11px] font-semibold uppercase tracking-wider text-[#0071e3]">{__('frontend.wizard_step_counter', { current: '01', total: '05' })}</span>
                                        <h3 className="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] font-sans tracking-tight">
                                            {__('frontend.wizard_s1_title')}
                                        </h3>
                                        <p className="text-sm text-[#1d1d1f]/60 font-sans">
                                            {__('frontend.wizard_s1_desc')}
                                        </p>
                                    </div>

                                    {/* Archetypes Grid */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                        {archetypes.map((arch) => {
                                            const IconComp = arch.icon;
                                            const isSelected = formData.archetype === arch.id;
                                            return (
                                                <div
                                                    key={arch.id}
                                                    onClick={() => setFormData(prev => ({ ...prev, archetype: arch.id }))}
                                                    className={`p-6 rounded-[20px] border transition-all cursor-pointer flex flex-col justify-between space-y-4 ${
                                                        isSelected
                                                            ? 'border-2 border-[#0071e3] bg-[#0071e3]/5 shadow-[0_8px_24px_rgba(0,113,227,0.12)] ring-4 ring-[#0071e3]/10'
                                                            : 'border-black/10 bg-white hover:border-black/30 hover:shadow-md'
                                                    }`}
                                                >
                                                    <div className="space-y-3">
                                                        <div className="flex items-center justify-between">
                                                            <div className={`p-2.5 rounded-xl ${isSelected ? 'bg-[#0071e3] text-white shadow-sm' : 'bg-[#f5f5f7] text-[#1d1d1f]'}`}>
                                                                <IconComp className="h-5 w-5" />
                                                            </div>
                                                            <span className="text-[10px] font-medium px-2.5 py-0.5 rounded-full bg-[#f5f5f7] border border-black/5 text-[#1d1d1f]/70">
                                                                {arch.badge}
                                                            </span>
                                                        </div>

                                                        <div>
                                                            <h4 className="font-semibold text-base text-[#1d1d1f] font-sans">{arch.title}</h4>
                                                            <p className="text-xs text-[#1d1d1f]/60 mt-1 font-sans leading-relaxed">
                                                                {arch.desc}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <div className="pt-3 border-t border-black/5 text-[11px] text-[#1d1d1f]/60 flex items-center justify-between">
                                                        <span className="truncate max-w-[180px]">{arch.recommendedFor}</span>
                                                        <div className={`w-5 h-5 rounded-full border flex items-center justify-center transition-all ${isSelected ? 'border-[#0071e3] bg-[#0071e3] text-white' : 'border-black/20 bg-white'}`}>
                                                            {isSelected && <Check className="w-3 h-3" strokeWidth={3} />}
                                                        </div>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>

                                    {/* Deployment Platforms */}
                                    <div className="pt-6 border-t border-black/5 space-y-4">
                                        <div className="space-y-0.5">
                                            <h4 className="font-semibold text-sm text-[#1d1d1f] font-sans">
                                                {__('frontend.wizard_platforms_title')}
                                            </h4>
                                            <p className="text-xs text-[#1d1d1f]/60 font-sans">{__('frontend.wizard_platforms_desc')}</p>
                                        </div>

                                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                                            {deploymentPlatformOptions.map((plat) => {
                                                const IconC = plat.icon;
                                                const active = formData.deploymentPlatforms.includes(plat.id);
                                                return (
                                                    <button
                                                        key={plat.id}
                                                        type="button"
                                                        onClick={() => togglePlatform(plat.id)}
                                                        className={`p-4 rounded-[16px] border transition-all cursor-pointer flex items-center gap-3 text-start ${
                                                            active
                                                                ? 'border-2 border-[#0071e3] bg-[#0071e3]/5 text-[#1d1d1f] shadow-sm font-semibold'
                                                                : 'border-black/10 bg-white text-[#1d1d1f]/70 hover:border-black/30 hover:text-[#1d1d1f]'
                                                        }`}
                                                    >
                                                        <IconC className={`w-4 h-4 ${active ? 'text-[#0071e3]' : 'text-[#1d1d1f]/50'}`} />
                                                        <span>{plat.title}</span>
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                </motion.div>
                            )}

                            {/* STEP 2: MODULES & FEATURES */}
                            {currentStep === 2 && (
                                <motion.div
                                    key="step2"
                                    initial={{ opacity: 0, x: 20 }}
                                    animate={{ opacity: 1, x: 0 }}
                                    exit={{ opacity: 0, x: -20 }}
                                    transition={{ duration: 0.3 }}
                                    className="space-y-8"
                                >
                                    <div className="space-y-1">
                                        <span className="text-[11px] font-semibold uppercase tracking-wider text-[#0071e3]">{__('frontend.wizard_step_counter', { current: '02', total: '05' })}</span>
                                        <h3 className="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] font-sans tracking-tight">
                                            {__('frontend.wizard_s2_title')}
                                        </h3>
                                        <p className="text-sm text-[#1d1d1f]/60 font-sans">
                                            {__('frontend.wizard_s2_desc')}
                                        </p>
                                    </div>

                                    <div className="space-y-6">
                                        {moduleCategories.map((group, gIdx) => (
                                            <div key={gIdx} className="space-y-3">
                                                <h4 className="text-xs uppercase tracking-wider text-[#0071e3] font-semibold">
                                                    {group.category}
                                                </h4>
                                                <div className="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                                    {group.items.map((item) => {
                                                        const isChecked = formData.modules.includes(item.id);
                                                        return (
                                                            <div
                                                                key={item.id}
                                                                onClick={() => toggleModule(item.id)}
                                                                className={`p-4 rounded-[18px] border transition-all cursor-pointer flex items-start gap-3.5 ${
                                                                    isChecked
                                                                        ? 'border-[#0071e3] bg-[#0071e3]/5 shadow-sm ring-1 ring-[#0071e3]'
                                                                        : 'border-black/5 bg-white hover:border-black/20 hover:shadow-sm'
                                                                }`}
                                                            >
                                                                <div className={`mt-0.5 w-4 h-4 rounded-md shrink-0 border flex items-center justify-center transition-all ${
                                                                    isChecked ? 'bg-[#0071e3] border-[#0071e3] text-white' : 'border-black/20 bg-white'
                                                                }`}>
                                                                    {isChecked && <Check className="w-3 h-3" strokeWidth={3} />}
                                                                </div>
                                                                <div className="space-y-0.5">
                                                                    <div className="font-semibold text-xs text-[#1d1d1f]">{item.title}</div>
                                                                    <p className="text-[11px] text-[#1d1d1f]/60 font-sans leading-relaxed">{item.desc}</p>
                                                                </div>
                                                            </div>
                                                        );
                                                    })}
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {/* Custom Modules Text */}
                                    <div className="pt-4 border-t border-black/5 space-y-2">
                                        <label htmlFor="wizard-custom-features" className="text-xs font-semibold text-[#1d1d1f] block">
                                            {__('frontend.wizard_custom_features_label')}
                                        </label>
                                        <textarea
                                            id="wizard-custom-features"
                                            rows={3}
                                            value={formData.customFeaturesText}
                                            onChange={(e) => setFormData(prev => ({ ...prev, customFeaturesText: e.target.value }))}
                                            placeholder={__('frontend.wizard_custom_features_placeholder')}
                                            className="w-full p-3.5 bg-[#f5f5f7] border border-black/10 rounded-xl text-[#1d1d1f] text-xs focus:bg-white focus:ring-2 focus:ring-[#0071e3] focus:outline-none transition-colors"
                                        />
                                    </div>
                                </motion.div>
                            )}

                            {/* STEP 3: TIMELINE & BUDGET */}
                            {currentStep === 3 && (
                                <motion.div
                                    key="step3"
                                    initial={{ opacity: 0, x: 20 }}
                                    animate={{ opacity: 1, x: 0 }}
                                    exit={{ opacity: 0, x: -20 }}
                                    transition={{ duration: 0.3 }}
                                    className="space-y-8"
                                >
                                    <div className="space-y-1">
                                        <span className="text-[11px] font-semibold uppercase tracking-wider text-[#0071e3]">{__('frontend.wizard_step_counter', { current: '03', total: '05' })}</span>
                                        <h3 className="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] font-sans tracking-tight">
                                            {__('frontend.wizard_s3_title')}
                                        </h3>
                                        <p className="text-sm text-[#1d1d1f]/60 font-sans">
                                            {__('frontend.wizard_s3_desc')}
                                        </p>
                                    </div>

                                    {/* Timeline */}
                                    <div className="space-y-3">
                                        <h4 className="text-xs uppercase tracking-wider text-[#1d1d1f]/70 font-semibold">
                                            {__('frontend.wizard_launch_window')}
                                        </h4>
                                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                                            {timelineOptions.map((t) => {
                                                const isSel = formData.timeline === t.id;
                                                return (
                                                    <div
                                                        key={t.id}
                                                        onClick={() => setFormData(prev => ({ ...prev, timeline: t.id }))}
                                                        className={`p-5 rounded-[18px] border transition-all cursor-pointer flex flex-col justify-between space-y-3 ${
                                                            isSel ? 'border-2 border-[#0071e3] bg-[#0071e3]/5 shadow-sm' : 'border-black/10 bg-white hover:border-black/30 hover:shadow-md'
                                                        }`}
                                                    >
                                                        <div className="flex items-center justify-between">
                                                            <span className="text-sm font-semibold text-[#1d1d1f]">{t.title}</span>
                                                            <span className="text-xs text-[#0071e3] font-semibold">{t.time}</span>
                                                        </div>
                                                        <p className="text-[11px] text-[#1d1d1f]/60 font-sans">{t.desc}</p>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>

                                    {/* Budget Tier */}
                                    <div className="pt-6 border-t border-black/5 space-y-4">
                                        <div className="flex items-center justify-between">
                                            <h4 className="text-xs uppercase tracking-wider text-[#1d1d1f]/70 font-semibold">
                                                {__('frontend.wizard_budget_range')}
                                            </h4>
                                            
                                            {/* Currency Switcher */}
                                            <div className="flex items-center rounded-full bg-[#f5f5f7] p-1 border border-black/5 text-xs">
                                                <button
                                                    type="button"
                                                    onClick={() => setFormData(prev => ({ ...prev, currency: 'EGP' }))}
                                                    className={`px-3 py-0.5 rounded-full transition-all cursor-pointer font-semibold ${formData.currency === 'EGP' ? 'bg-white text-[#1d1d1f] shadow-sm' : 'text-[#1d1d1f]/60 hover:text-[#1d1d1f]'}`}
                                                >
                                                    EGP
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => setFormData(prev => ({ ...prev, currency: 'USD' }))}
                                                    className={`px-3 py-0.5 rounded-full transition-all cursor-pointer font-semibold ${formData.currency === 'USD' ? 'bg-white text-[#1d1d1f] shadow-sm' : 'text-[#1d1d1f]/60 hover:text-[#1d1d1f]'}`}
                                                >
                                                    USD
                                                </button>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                                            {budgetOptions.map((b) => {
                                                const isSel = formData.budgetTier === b.id;
                                                return (
                                                    <div
                                                        key={b.id}
                                                        onClick={() => setFormData(prev => ({ ...prev, budgetTier: b.id }))}
                                                        className={`p-5 rounded-[18px] border transition-all cursor-pointer flex items-center justify-between ${
                                                            isSel ? 'border-2 border-[#0071e3] bg-[#0071e3]/5 shadow-sm' : 'border-black/10 bg-white hover:border-black/30 hover:shadow-md'
                                                        }`}
                                                    >
                                                        <div>
                                                            <div className="font-semibold text-[#1d1d1f]">{b.title}</div>
                                                            <div className="text-xs text-[#0071e3] font-semibold mt-1">
                                                                {formData.currency === 'USD' ? b.usd : b.egp}
                                                            </div>
                                                        </div>
                                                        <div className={`w-5 h-5 rounded-full border flex items-center justify-center transition-all ${isSel ? 'border-[#0071e3] bg-[#0071e3] text-white' : 'border-black/20 bg-white'}`}>
                                                            {isSel && <Check className="w-3 h-3" strokeWidth={3} />}
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                </motion.div>
                            )}

                            {/* STEP 4: ORGANIZATION & CONTACT DETAILS */}
                            {currentStep === 4 && (
                                <motion.div
                                    key="step4"
                                    initial={{ opacity: 0, x: 20 }}
                                    animate={{ opacity: 1, x: 0 }}
                                    exit={{ opacity: 0, x: -20 }}
                                    transition={{ duration: 0.3 }}
                                    className="space-y-8"
                                >
                                    <div className="space-y-1">
                                        <span className="text-[11px] font-semibold uppercase tracking-wider text-[#0071e3]">{__('frontend.wizard_step_counter', { current: '04', total: '05' })}</span>
                                        <h3 className="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] font-sans tracking-tight">
                                            {__('frontend.wizard_s4_title')}
                                        </h3>
                                        <p className="text-sm text-[#1d1d1f]/60 font-sans">
                                            {__('frontend.wizard_s4_desc')}
                                        </p>
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs">
                                        <div className="space-y-1.5">
                                            <label htmlFor="wizard-company" className="text-[#1d1d1f] font-semibold block">{__('frontend.wizard_company_label')}</label>
                                            <input
                                                id="wizard-company"
                                                type="text"
                                                required
                                                placeholder={__('frontend.wizard_company_placeholder')}
                                                value={formData.companyName}
                                                onChange={(e) => setFormData(prev => ({ ...prev, companyName: e.target.value }))}
                                                className="w-full p-3 bg-[#f5f5f7] border border-black/10 rounded-xl text-[#1d1d1f] focus:bg-white focus:ring-2 focus:ring-[#0071e3] focus:outline-none transition-colors"
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <label htmlFor="wizard-industry" className="text-[#1d1d1f] font-semibold block">{__('frontend.wizard_industry_label')}</label>
                                            <select
                                                id="wizard-industry"
                                                value={formData.industry}
                                                onChange={(e) => setFormData(prev => ({ ...prev, industry: e.target.value }))}
                                                className="w-full p-3 bg-[#f5f5f7] border border-black/10 rounded-xl text-[#1d1d1f] focus:bg-white focus:ring-2 focus:ring-[#0071e3] focus:outline-none transition-colors"
                                            >
                                                {industryOptions.map((ind) => (
                                                    <option key={ind.value} value={ind.value}>{ind.label}</option>
                                                ))}
                                            </select>
                                        </div>

                                        <div className="space-y-1.5">
                                            <label htmlFor="wizard-contact-name" className="text-[#1d1d1f] font-semibold block">{__('frontend.wizard_contact_name_label')}</label>
                                            <input
                                                id="wizard-contact-name"
                                                type="text"
                                                required
                                                placeholder={__('frontend.wizard_contact_name_placeholder')}
                                                value={formData.contactName}
                                                onChange={(e) => setFormData(prev => ({ ...prev, contactName: e.target.value }))}
                                                className="w-full p-3 bg-[#f5f5f7] border border-black/10 rounded-xl text-[#1d1d1f] focus:bg-white focus:ring-2 focus:ring-[#0071e3] focus:outline-none transition-colors"
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <label htmlFor="wizard-phone" className="text-[#1d1d1f] font-semibold block">{__('frontend.wizard_phone_label')}</label>
                                            <input
                                                id="wizard-phone"
                                                type="tel"
                                                required
                                                placeholder="+20 10X XXX XXXX"
                                                value={formData.contactPhone}
                                                onChange={(e) => setFormData(prev => ({ ...prev, contactPhone: e.target.value }))}
                                                className="w-full p-3 bg-[#f5f5f7] border border-black/10 rounded-xl text-[#1d1d1f] focus:bg-white focus:ring-2 focus:ring-[#0071e3] focus:outline-none transition-colors"
                                            />
                                        </div>

                                        <div className="space-y-1.5 sm:col-span-2">
                                            <label htmlFor="wizard-email" className="text-[#1d1d1f] font-semibold block">{__('frontend.wizard_email_label')}</label>
                                            <input
                                                id="wizard-email"
                                                type="email"
                                                placeholder="contact@company.com"
                                                value={formData.contactEmail}
                                                onChange={(e) => setFormData(prev => ({ ...prev, contactEmail: e.target.value }))}
                                                className="w-full p-3 bg-[#f5f5f7] border border-black/10 rounded-xl text-[#1d1d1f] focus:bg-white focus:ring-2 focus:ring-[#0071e3] focus:outline-none transition-colors"
                                            />
                                        </div>

                                        <div className="space-y-1.5 sm:col-span-2">
                                            <label htmlFor="wizard-docs" className="text-[#1d1d1f] font-semibold block">{__('frontend.wizard_docs_label')}</label>
                                            <input
                                                id="wizard-docs"
                                                type="url"
                                                placeholder="https://figma.com/... or https://docs.google.com/..."
                                                value={formData.figmaOrDocUrl}
                                                onChange={(e) => setFormData(prev => ({ ...prev, figmaOrDocUrl: e.target.value }))}
                                                className="w-full p-3 bg-[#f5f5f7] border border-black/10 rounded-xl text-[#1d1d1f] focus:bg-white focus:ring-2 focus:ring-[#0071e3] focus:outline-none transition-colors"
                                            />
                                        </div>

                                        <div className="space-y-1.5 sm:col-span-2">
                                            <label htmlFor="wizard-notes" className="text-[#1d1d1f] font-semibold block">{__('frontend.wizard_notes_label')}</label>
                                            <textarea
                                                id="wizard-notes"
                                                rows={4}
                                                placeholder={__('frontend.wizard_notes_placeholder')}
                                                value={formData.projectNotes}
                                                onChange={(e) => setFormData(prev => ({ ...prev, projectNotes: e.target.value }))}
                                                className="w-full p-3 bg-[#f5f5f7] border border-black/10 rounded-xl text-[#1d1d1f] focus:bg-white focus:ring-2 focus:ring-[#0071e3] focus:outline-none transition-colors"
                                            />
                                        </div>
                                    </div>
                                </motion.div>
                            )}

                            {/* STEP 5: BLUEPRINT REVIEW & SUBMISSION */}
                            {currentStep === 5 && (
                                <motion.div
                                    key="step5"
                                    initial={{ opacity: 0, x: 20 }}
                                    animate={{ opacity: 1, x: 0 }}
                                    exit={{ opacity: 0, x: -20 }}
                                    transition={{ duration: 0.3 }}
                                    className="space-y-8"
                                >
                                    <div className="space-y-1">
                                        <span className="text-[11px] font-semibold uppercase tracking-wider text-[#0071e3]">{__('frontend.wizard_step_counter', { current: '05', total: '05' })}</span>
                                        <h3 className="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] font-sans tracking-tight">
                                            {__('frontend.wizard_s5_title')}
                                        </h3>
                                        <p className="text-sm text-[#1d1d1f]/60 font-sans">
                                            {__('frontend.wizard_s5_desc')}
                                        </p>
                                    </div>

                                    {/* Structured Blueprint Card */}
                                    <div className="bg-[#f5f5f7] border border-black/5 rounded-[24px] p-6 sm:p-8 space-y-6 text-xs text-[#1d1d1f]">
                                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-black/5 pb-6">
                                            <div>
                                                <span className="text-[10px] text-[#1d1d1f]/50 uppercase tracking-widest block font-semibold">{__('frontend.wizard_project_spec')}</span>
                                                <h4 className="text-lg font-semibold text-[#1d1d1f] mt-0.5">
                                                    {formData.companyName || __('frontend.wizard_custom_project')}
                                                </h4>
                                                <span className="text-[#1d1d1f]/60 text-xs">{industryLabel(formData.industry)}</span>
                                            </div>
                                            
                                            <div className="text-start sm:text-end">
                                                <span className="text-[10px] text-[#1d1d1f]/50 uppercase tracking-widest block font-semibold">{__('frontend.wizard_step_archetype')}</span>
                                                <span className="text-[#0071e3] font-semibold text-sm">
                                                    {archetypes.find(a => a.id === formData.archetype)?.title}
                                                </span>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-2">
                                            <div>
                                                <span className="text-[#1d1d1f]/50 block mb-1 font-medium">{__('frontend.wizard_target_environments')}</span>
                                                <div className="flex flex-wrap gap-1.5">
                                                    {formData.deploymentPlatforms.map(p => (
                                                        <span key={p} className="px-2.5 py-0.5 bg-white border border-black/5 rounded-full text-[#1d1d1f] uppercase text-[10px] font-semibold shadow-2xs">
                                                            {platformTitle(p)}
                                                        </span>
                                                    ))}
                                                </div>
                                            </div>

                                            <div>
                                                <span className="text-[#1d1d1f]/50 block mb-1 font-medium">{__('frontend.wizard_timeline_target')}</span>
                                                <span className="text-[#1d1d1f] font-semibold">
                                                    {timelineOptions.find(t => t.id === formData.timeline)?.title} ({timelineOptions.find(t => t.id === formData.timeline)?.time})
                                                </span>
                                            </div>

                                            <div>
                                                <span className="text-[#1d1d1f]/50 block mb-1 font-medium">{__('frontend.wizard_budget_allocation')}</span>
                                                <span className="text-[#0071e3] font-semibold">
                                                    {budgetOptions.find(b => b.id === formData.budgetTier)?.[formData.currency === 'USD' ? 'usd' : 'egp']}
                                                </span>
                                            </div>
                                        </div>

                                        {/* Modules List */}
                                        <div className="pt-4 border-t border-black/5 space-y-2">
                                            <span className="text-[#1d1d1f]/50 block font-medium">Selected Architectural Modules ({formData.modules.length}):</span>
                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[#1d1d1f]/80">
                                                {formData.modules.map(m => (
                                                    <div key={m} className="flex items-center gap-2">
                                                        <CheckCircle2 className="w-3.5 h-3.5 text-[#0071e3] shrink-0" />
                                                        <span className="capitalize">{moduleTitle(m)}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        {/* Contact & Notes */}
                                        <div className="pt-4 border-t border-black/5 grid grid-cols-1 sm:grid-cols-2 gap-4 text-[#1d1d1f]/70">
                                            <div>
                                                <span className="text-[#1d1d1f]/50 block font-medium">{__('frontend.wizard_primary_contact')}</span>
                                                <span className="text-[#1d1d1f] font-semibold">{formData.contactName}</span> ({formData.contactPhone})
                                            </div>
                                            {formData.contactEmail && (
                                                <div>
                                                    <span className="text-[#1d1d1f]/50 block font-medium">{__('frontend.wizard_email')}</span>
                                                    <span className="text-[#1d1d1f]">{formData.contactEmail}</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Action Buttons on Step 5 */}
                                    {isSubmitted ? (
                                        <div className="p-8 rounded-[24px] bg-white border border-green-500/30 text-center space-y-4 shadow-sm">
                                            <div className="w-12 h-12 rounded-full bg-green-50 text-green-600 mx-auto flex items-center justify-center">
                                                <Check className="w-6 h-6" strokeWidth={3} />
                                            </div>
                                            <h4 className="text-xl font-semibold text-[#1d1d1f]">{__('frontend.wizard_brief_registered')}</h4>
                                            <p className="text-xs text-[#1d1d1f]/70 max-w-lg mx-auto font-sans">
                                                {__('frontend.wizard_reference_code')} <strong className="text-[#0071e3]">{briefId}</strong>. {__('frontend.wizard_brief_received')}
                                            </p>

                                            <div className="pt-4 flex items-center justify-center gap-3 flex-wrap text-xs">
                                                <a
                                                    href={generatedWhatsAppUrl}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="px-6 py-3 rounded-[980px] bg-[#25D366] hover:bg-[#20ba59] text-white font-medium shadow-md shadow-green-500/20 transition-all flex items-center gap-2"
                                                >
                                                    <MessageSquare className="w-4 h-4" />
                                                    <span>{__('frontend.wizard_open_whatsapp')}</span>
                                                </a>

                                                <button
                                                    type="button"
                                                    onClick={copyBriefSummary}
                                                    className="px-6 py-3 rounded-[980px] bg-white hover:bg-[#f5f5f7] text-[#1d1d1f] border border-black/10 font-medium transition-all flex items-center gap-2 cursor-pointer shadow-sm"
                                                >
                                                    {copied ? <CheckCheck className="w-4 h-4 text-[#0071e3]" /> : <Copy className="w-4 h-4" />}
                                                    <span>{copied ? __('frontend.wizard_copied') : __('frontend.wizard_copy_brief')}</span>
                                                </button>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                                                <button
                                                    type="button"
                                                    disabled={isSubmitting}
                                                    onClick={handleSubmitBrief}
                                                    className="w-full py-4 rounded-[980px] bg-[#0071e3] hover:bg-[#0077ed] text-white font-medium transition-all cursor-pointer flex items-center justify-center gap-2 shadow-md shadow-blue-500/20"
                                                >
                                                    <Send className="w-4 h-4" />
                                                    <span>{isSubmitting ? __('frontend.wizard_registering') : __('frontend.wizard_submit_brief')}</span>
                                                </button>

                                                <a
                                                    href={generatedWhatsAppUrl}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="w-full py-4 rounded-[980px] bg-[#25D366] hover:bg-[#20ba59] text-white font-medium transition-all flex items-center justify-center gap-2 shadow-md shadow-green-500/20"
                                                >
                                                    <MessageSquare className="w-4 h-4" />
                                                    <span>{__('frontend.wizard_send_whatsapp')} ➔</span>
                                                </a>
                                            </div>
                                        </div>
                                    )}
                                </motion.div>
                            )}

                        </AnimatePresence>

                        {/* Navigation Prev / Next Footer (Visible on steps 1-4, or step 5 if not submitted) */}
                        {!isSubmitted && (
                            <div className="mt-12 pt-8 border-t border-black/5 flex items-center justify-between text-xs">
                                {currentStep > 1 ? (
                                    <button
                                        type="button"
                                        onClick={handleBack}
                                        className="px-6 py-2.5 rounded-[980px] border border-black/10 hover:border-black/30 bg-white text-[#1d1d1f] transition-all cursor-pointer flex items-center gap-2 font-medium shadow-sm"
                                    >
                                        <ArrowLeft className="w-4 h-4" />
                                        <span>{__('frontend.wizard_back')}</span>
                                    </button>
                                ) : <div />}

                                {currentStep < 5 && (
                                    <button
                                        type="button"
                                        onClick={handleNext}
                                        className="px-8 py-2.5 rounded-[980px] bg-[#0071e3] hover:bg-[#0077ed] text-white font-medium transition-all cursor-pointer flex items-center gap-2 shadow-sm"
                                    >
                                        <span>{__('frontend.wizard_continue')}</span>
                                        <ArrowRight className="w-4 h-4" />
                                    </button>
                                )}
                            </div>
                        )}

                    </div>

                </div>

            </div>
        </PublicLayout>
    );
}
