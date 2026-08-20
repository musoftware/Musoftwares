import { useState, useMemo } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { useToast } from '@/Components/ui/use-toast';
import { 
    Globe, 
    Smartphone, 
    Monitor, 
    Sparkles, 
    Check, 
    Shield, 
    CreditCard, 
    Lock, 
    Languages, 
    MessageSquare, 
    Database, 
    Cpu, 
    Send, 
    Calculator,
    CheckCircle2,
    BellRing,
    UploadCloud,
    MapPin,
    Barcode,
    HardDrive,
    Search,
    Bug,
    Zap,
    Tag,
    Bot,
    Receipt,
    Fingerprint,
    ShieldCheck,
    CloudRain,
    LineChart,
    Share2,
    Radio,
    Clock,
    Filter,
    ArrowUpRight
} from 'lucide-react';
import axios from 'axios';

export default function ProjectEstimator({ exchangeRate = 50.0, showHeader = true, title = "Calculate Your Project Investment", subtitle = "Combine platforms and in-demand modules to calculate your transparent development estimate." }) {
    const { auth } = usePage().props || {};

    const phoneNumber = "201015218548";
    const { toast } = useToast();

    // 1. Multi-Select Platforms State (starts clean with just 'web')
    const [selectedPlatforms, setSelectedPlatforms] = useState(['web']);
    
    // 2. Dedicated Screen/Page Counts per platform
    const [platformScreens, setPlatformScreens] = useState({
        web: 5,
        mobile: 5,
        desktop: 5,
    });

    // 3. Category Filter Tab for Modules
    const [activeCategory, setActiveCategory] = useState('all');

    // 4. Dynamic Type-Specific Add-ons State
    const [selectedOptions, setSelectedOptions] = useState({});

    // 5. Currency: true for USD ($), false for EGP
    const [isUsd, setIsUsd] = useState(false);

    // 6. Lead capture form
    const [leadName, setLeadName] = useState('');
    const [leadEmail, setLeadEmail] = useState('');
    const [leadMobile, setLeadMobile] = useState('');
    const [leadBusiness, setLeadBusiness] = useState('');
    const [leadSaved, setLeadSaved] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    // Rates config
    const rates = {
        web: { title: 'Website / Web App', rate: 10, unit: 'Page', icon: Globe, badge: '$10/page' },
        mobile: { title: 'Mobile App (iOS & Android)', rate: 15, unit: 'Screen', icon: Smartphone, badge: '$15/screen' },
        desktop: { title: 'Desktop Software', rate: 25, unit: 'Screen', icon: Monitor, badge: '$25/screen' },
    };

    // Toggle platform selection (at least 1 must remain active)
    const togglePlatform = (platformKey) => {
        setSelectedPlatforms(prev => {
            if (prev.includes(platformKey)) {
                if (prev.length === 1) {
                    toast({
                        title: 'Selection Required',
                        description: 'Please keep at least one platform selected.',
                    });
                    return prev;
                }
                return prev.filter(p => p !== platformKey);
            } else {
                return [...prev, platformKey];
            }
        });
    };

    // Update screen count for a specific platform
    const updateScreens = (platformKey, val) => {
        const count = Math.max(1, Math.min(60, parseInt(val) || 1));
        setPlatformScreens(prev => ({
            ...prev,
            [platformKey]: count,
        }));
    };

    // Comprehensive Market-Proven Modules Definitions
    const optionsDefinitions = {
        web: [
            {
                id: 'web_admin_panel',
                title: 'Web Admin Dashboard',
                category: 'core',
                price: 100,
                platform: 'web',
                icon: Shield,
                desc: 'Full administrative portal to manage orders, analytics, content, and users.',
            },
            {
                id: 'web_ai_chatbot',
                title: 'AI Smart Assistant (OpenAI / GPT)',
                category: 'ai_automation',
                price: 45,
                platform: 'web',
                icon: Bot,
                desc: 'Intelligent AI chatbot trained on your business data to answer customer inquiries 24/7.',
            },
            {
                id: 'web_whatsapp_bot',
                title: 'WhatsApp Automation & OTP Alerts',
                category: 'ai_automation',
                price: 40,
                platform: 'web',
                icon: MessageSquare,
                desc: 'Instant WhatsApp notifications on new orders, OTP phone verification, and click-to-chat bot.',
            },
            {
                id: 'web_whatsapp_channels',
                title: 'WhatsApp Channels Integration',
                category: 'ai_automation',
                price: 35,
                platform: 'web',
                icon: Radio,
                desc: 'Direct broadcast and interactive communication integration with WhatsApp Channels via official APIs.',
            },
            {
                id: 'web_telegram_bot',
                title: 'Telegram Bot Management',
                category: 'ai_automation',
                price: 30,
                platform: 'web',
                icon: Send,
                desc: 'Custom Telegram bot with command workflows, interactive menus, broadcast controls, and admin management.',
            },
            {
                id: 'web_telegram_channel_collector',
                title: 'Telegram Channel Collection',
                category: 'ai_automation',
                price: 25,
                platform: 'web',
                icon: Database,
                desc: 'Automated data harvesting and continuous tracking of posts, media, and updates from Telegram channels.',
            },
            {
                id: 'web_whatsapp_channel_collector',
                title: 'WhatsApp Channel Collection',
                category: 'ai_automation',
                price: 30,
                platform: 'web',
                icon: MessageSquare,
                desc: 'Real-time and batch extraction of messages, updates, and channel broadcasts from WhatsApp.',
            },
            {
                id: 'web_auto_data_jobs',
                title: 'Automated Data Collection Jobs',
                category: 'ai_automation',
                price: 35,
                platform: 'web',
                icon: Clock,
                desc: 'Automated background cron jobs and scheduled tasks for recurring data fetching and syncing.',
            },
            {
                id: 'web_content_deduplication',
                title: 'Content Deduplication & Processing',
                category: 'ai_automation',
                price: 25,
                platform: 'web',
                icon: Filter,
                desc: 'Advanced similarity matching, duplicate removal, text filtering, and automated content cleanup pipeline.',
            },
            {
                id: 'web_gateways',
                title: 'Online Payment Gateways',
                category: 'ecommerce',
                price: 20,
                platform: 'web',
                icon: CreditCard,
                desc: 'Direct checkout integration (Paymob, Fawry, Stripe, PayPal, Tabby, Tamara, Visa/Mastercard).',
            },
            {
                id: 'web_pdf_invoicing',
                title: 'Automated PDF Invoices & Receipts',
                category: 'ecommerce',
                price: 25,
                platform: 'web',
                icon: Receipt,
                desc: 'Auto-generate branded PDF invoices and tax receipts sent directly to customer emails.',
            },
            {
                id: 'web_multi_currency',
                title: 'Multi-Currency & GeoIP Detection',
                category: 'ecommerce',
                price: 25,
                platform: 'web',
                icon: Globe,
                desc: 'Automatic visitor country detection with live exchange rates conversion (EGP, SAR, AED, USD).',
            },
            {
                id: 'web_marketing_pixels',
                title: 'Marketing Pixels & GA4 Analytics',
                category: 'marketing',
                price: 20,
                platform: 'web',
                icon: LineChart,
                desc: 'Server-side CAPI and client tracking setup for Meta Pixel, TikTok Pixel, Snap & GA4.',
            },
            {
                id: 'web_seo_speed',
                title: 'Technical SEO & Search Indexing',
                category: 'marketing',
                price: 25,
                platform: 'web',
                icon: Search,
                desc: 'Google sitemap indexing, OpenGraph rich social snippets, schema markup, and metadata tags.',
            },
            {
                id: 'web_auth_roles',
                title: 'Multi-tier Roles & Permissions',
                category: 'core',
                price: 40,
                platform: 'web',
                icon: Lock,
                desc: 'Secure signup/login with granular permissions (Super Admin, Manager, Accountant, Client).',
            },
            {
                id: 'web_multilingual',
                title: 'Multilingual Interface (AR / EN)',
                category: 'core',
                price: 30,
                platform: 'web',
                icon: Languages,
                desc: 'Seamless dual language interface with dynamic RTL and LTR direction switching.',
            },
            {
                id: 'web_security_firewall',
                title: 'Security Firewall & DDoS Shield',
                category: 'infrastructure',
                price: 40,
                platform: 'web',
                icon: ShieldCheck,
                desc: 'WAF firewall, SQL injection / XSS protection, automated rate limiting, and brute-force lockouts.',
            },
            {
                id: 'web_cloud_db',
                title: 'Cloud Database & SSL Deployment',
                category: 'infrastructure',
                price: 35,
                platform: 'web',
                icon: Database,
                desc: 'Production server provisioning, SSL certificates, automated database backups, and DNS go-live.',
            },
            {
                id: 'web_external_api',
                title: 'External REST APIs & Webhooks',
                category: 'infrastructure',
                price: 60,
                platform: 'web',
                icon: Cpu,
                desc: 'Integration with shipping companies (Aramex, Bosta, DHL), external ERPs, or custom CRMs.',
            },
            {
                id: 'web_bug_fix',
                title: 'Web Bug Fixing & Troubleshooting',
                category: 'optimization',
                price: 35,
                platform: 'web',
                icon: Bug,
                desc: 'Diagnosis and fixing of frontend UI bugs, broken responsive mobile layouts, and backend errors.',
            },
            {
                id: 'web_performance_tune',
                title: 'Speed Tuning & 90+ PageSpeed',
                category: 'optimization',
                price: 30,
                platform: 'web',
                icon: Zap,
                desc: 'Query optimization, asset minification, Redis caching, and achieving 90+ Google PageSpeed score.',
            },
        ],
        mobile: [
            {
                id: 'mobile_admin_panel',
                title: 'Mobile Web Admin & REST API',
                category: 'core',
                price: 120,
                platform: 'mobile',
                icon: Shield,
                desc: 'Dedicated cloud control panel & REST API backend to manage app content, notifications & users.',
            },
            {
                id: 'mobile_ai_assistant',
                title: 'In-App AI Conversational Bot',
                category: 'ai_automation',
                price: 50,
                platform: 'mobile',
                icon: Bot,
                desc: 'Interactive AI assistant embedded within the mobile app for smart user recommendations and support.',
            },
            {
                id: 'mobile_push_notifications',
                title: 'Push Notifications (FCM / OneSignal)',
                category: 'ai_automation',
                price: 35,
                platform: 'mobile',
                icon: BellRing,
                desc: 'Instant push notifications to engage users, announce special offers, and broadcast alerts.',
            },
            {
                id: 'mobile_biometric_auth',
                title: 'Biometric Login (FaceID & Fingerprint)',
                category: 'core',
                price: 30,
                platform: 'mobile',
                icon: Fingerprint,
                desc: 'Fast 1-touch secure authentication via Apple FaceID and Android Fingerprint sensors.',
            },
            {
                id: 'mobile_app_stores',
                title: 'App Store & Google Play Publishing',
                category: 'infrastructure',
                price: 50,
                platform: 'mobile',
                icon: UploadCloud,
                desc: 'Release bundle building, metadata formatting, store assets generation, and submission for approvals.',
            },
            {
                id: 'mobile_gateways',
                title: 'In-App Payment Gateways',
                category: 'ecommerce',
                price: 20,
                platform: 'mobile',
                icon: CreditCard,
                desc: 'Mobile in-app payments (Apple Pay, Google Pay, Paymob SDK, Stripe Mobile).',
            },
            {
                id: 'mobile_gps_maps',
                title: 'GPS Maps & Geolocation Tracking',
                category: 'core',
                price: 45,
                platform: 'mobile',
                icon: MapPin,
                desc: 'Interactive Google Maps address picker, turn-by-turn routing, and live courier tracking.',
            },
            {
                id: 'mobile_deep_linking',
                title: 'Deep Linking & Dynamic Share Links',
                category: 'marketing',
                price: 25,
                platform: 'mobile',
                icon: Share2,
                desc: 'Direct smart URLs that open specific screens, products, or offers directly inside the installed app.',
            },
            {
                id: 'mobile_multilingual',
                title: 'Multilingual App UI (AR / EN)',
                category: 'core',
                price: 30,
                platform: 'mobile',
                icon: Languages,
                desc: 'Bilingual mobile localization with RTL / LTR dynamic interface layout adjustment.',
            },
            {
                id: 'mobile_external_api',
                title: 'Mobile External REST APIs',
                category: 'infrastructure',
                price: 60,
                platform: 'mobile',
                icon: Cpu,
                desc: 'Syncing mobile app data with third-party inventory, delivery, or CRM systems.',
            },
            {
                id: 'mobile_bug_fix',
                title: 'Mobile Bug Fixing & Crash Resolution',
                category: 'optimization',
                price: 40,
                platform: 'mobile',
                icon: Bug,
                desc: 'Fixing ANR/crashes, memory leaks, and Android/iOS OS version compatibility issues.',
            },
            {
                id: 'mobile_performance_tune',
                title: 'App Speed & 60FPS Optimization',
                category: 'optimization',
                price: 35,
                platform: 'mobile',
                icon: Zap,
                desc: 'Smooth scroll performance, reduced battery drain, and minimized app package size.',
            },
        ],
        desktop: [
            {
                id: 'desktop_web_sync',
                title: 'Cloud Master Sync & Web Portal',
                category: 'infrastructure',
                price: 130,
                platform: 'desktop',
                icon: UploadCloud,
                desc: 'Centralized cloud server synchronization across all branches with online managerial reports.',
            },
            {
                id: 'desktop_e_invoicing_tax',
                title: 'Electronic Invoicing (ZATCA / ETA Tax)',
                category: 'ecommerce',
                price: 60,
                platform: 'desktop',
                icon: Receipt,
                desc: 'Integration with tax authorities (Egyptian Tax Authority ETA / Saudi ZATCA Phase 2 QR-code compliance).',
            },
            {
                id: 'desktop_whatsapp_receipts',
                title: 'WhatsApp Invoice Direct Dispatch',
                category: 'ai_automation',
                price: 40,
                platform: 'desktop',
                icon: MessageSquare,
                desc: 'Automatically send PDF tax invoices and receipts directly to customer WhatsApp right upon checkout.',
            },
            {
                id: 'desktop_pos_printing',
                title: 'Thermal POS & Barcode Label Printing',
                category: 'ecommerce',
                price: 40,
                platform: 'desktop',
                icon: Barcode,
                desc: 'Thermal receipt printing (80mm/58mm), custom invoice designer, and barcode product sticker printing.',
            },
            {
                id: 'desktop_offline_db',
                title: '100% Offline Database Engine',
                category: 'infrastructure',
                price: 45,
                platform: 'desktop',
                icon: HardDrive,
                desc: 'Local SQLite/SQL Server storage that works 100% uninterrupted without internet access.',
            },
            {
                id: 'desktop_auto_cloud_backup',
                title: 'Encrypted Cloud Auto-Backup',
                category: 'infrastructure',
                price: 30,
                platform: 'desktop',
                icon: CloudRain,
                desc: 'Automatic encrypted background backups to Google Drive, AWS S3, or private cloud storage.',
            },
            {
                id: 'desktop_bug_fix',
                title: 'Desktop Bug Fixing & Optimization',
                category: 'optimization',
                price: 40,
                platform: 'desktop',
                icon: Bug,
                desc: 'Resolving database deadlocks, slow query performance, and memory consumption in .NET apps.',
            },
        ],
    };

    // Aggregate unique available options based on currently active platforms
    const availableOptions = useMemo(() => {
        const list = [];
        const seenIds = new Set();

        selectedPlatforms.forEach(p => {
            const defs = optionsDefinitions[p] || [];
            defs.forEach(opt => {
                if (!seenIds.has(opt.id)) {
                    seenIds.add(opt.id);
                    list.push(opt);
                }
            });
        });

        return list;
    }, [selectedPlatforms]);

    // Categories filter configuration
    const categories = [
        { id: 'all', title: 'All Modules' },
        { id: 'core', title: 'Core Architecture' },
        { id: 'ai_automation', title: 'AI & WhatsApp Automation' },
        { id: 'ecommerce', title: 'FinTech & Payments' },
        { id: 'infrastructure', title: 'Infrastructure & APIs' },
        { id: 'marketing', title: 'Growth & Marketing' },
        { id: 'optimization', title: 'Troubleshooting & Speed' },
    ];

    const filteredOptions = useMemo(() => {
        if (activeCategory === 'all') return availableOptions;
        return availableOptions.filter(opt => opt.category === activeCategory);
    }, [availableOptions, activeCategory]);

    const toggleOption = (id) => {
        setSelectedOptions(prev => {
            const updated = { ...prev };
            if (updated[id]) {
                delete updated[id];
            } else {
                updated[id] = 1;
            }
            return updated;
        });
    };

    // Calculate Grand Total & Breakdown
    const costBreakdown = useMemo(() => {
        let platformsCost = 0;
        const itemizedPlatforms = [];

        selectedPlatforms.forEach(pKey => {
            const r = rates[pKey];
            const count = platformScreens[pKey] || 1;
            const cost = count * r.rate;
            platformsCost += cost;
            itemizedPlatforms.push({
                key: pKey,
                title: r.title,
                count,
                unit: r.unit,
                rate: r.rate,
                total: cost,
            });
        });

        // Addons Cost
        let addonsCost = 0;
        const itemizedAddons = [];

        Object.entries(selectedOptions).forEach(([optId, qty]) => {
            const opt = availableOptions.find(o => o.id === optId);
            if (opt && qty > 0) {
                const sub = opt.price * qty;
                addonsCost += sub;
                itemizedAddons.push({
                    id: opt.id,
                    title: opt.title,
                    price: opt.price,
                    qty,
                    subtotal: sub,
                });
            }
        });

        const subtotal = platformsCost + addonsCost;
        
        // Multi-Platform discount: 10% if 2 or more platforms selected
        const multiPlatformDiscount = selectedPlatforms.length >= 2 ? Math.round(subtotal * 0.10) : 0;
        const finalTotalUsd = Math.max(50, subtotal - multiPlatformDiscount);

        // Turnaround estimate: base 3 days + 1 day per 10 screens + 1 day per 3 addons
        const totalScreens = Object.values(platformScreens).reduce((a, b) => a + b, 0);
        const estimatedDays = Math.max(3, Math.ceil(3 + (totalScreens / 10) + (itemizedAddons.length / 3)));

        return {
            platformsCost,
            addonsCost,
            subtotal,
            multiPlatformDiscount,
            finalTotalUsd,
            finalTotalEgp: Math.round(finalTotalUsd * exchangeRate),
            estimatedDays,
            itemizedPlatforms,
            itemizedAddons,
        };
    }, [selectedPlatforms, platformScreens, selectedOptions, availableOptions, exchangeRate]);

    const formatPrice = (usdAmount) => {
        if (isUsd) {
            return `$${usdAmount.toLocaleString()}`;
        }
        return `${Math.round(usdAmount * exchangeRate).toLocaleString()} EGP`;
    };

    // Prefilled WhatsApp Message Builder
    const whatsappLink = useMemo(() => {
        const platformNames = selectedPlatforms.map(p => rates[p]?.title).join(', ');
        const totalFormatted = isUsd ? `$${costBreakdown.finalTotalUsd}` : `${costBreakdown.finalTotalEgp.toLocaleString()} EGP`;
        const modulesList = costBreakdown.itemizedAddons.map(a => `- ${a.title}`).join('\n');
        
        const message = `Hello Mahmoud! I'd like to discuss a custom engineering project scope from the Musoftwares Estimator:

*Platforms:* ${platformNames}
*Total Screens/Pages:* ${Object.entries(platformScreens).filter(([k]) => selectedPlatforms.includes(k)).map(([k, v]) => `${rates[k]?.title}: ${v}`).join(', ')}
*Selected Modules (${costBreakdown.itemizedAddons.length}):*
${modulesList || 'Standard Core Setup'}

*Estimated Investment:* ${totalFormatted}
*Estimated Timeline:* ~${costBreakdown.estimatedDays} business days

Please let me know when we can review the technical specification!`;

        return `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;
    }, [selectedPlatforms, platformScreens, costBreakdown, isUsd, phoneNumber]);

    // Handle Proposal Form Submission
    const handleLeadSubmit = async (e) => {
        e.preventDefault();
        if (!leadName || !leadMobile) {
            toast({
                title: 'Incomplete Details',
                description: 'Please provide your name and phone number.',
                variant: 'destructive',
            });
            return;
        }

        setSubmitting(true);
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await axios.post('/tools/lead-capture', {
                name: leadName,
                email: leadEmail,
                mobile: leadMobile,
                business: leadBusiness,
                scope: {
                    platforms: selectedPlatforms,
                    screens: platformScreens,
                    options: costBreakdown.itemizedAddons.map(i => i.title),
                    total_usd: costBreakdown.finalTotalUsd,
                    total_egp: costBreakdown.finalTotalEgp,
                    estimated_days: costBreakdown.estimatedDays,
                }
            }, {
                headers: {
                    'X-CSRF-TOKEN': token,
                }
            });

            if (res.data.success) {
                setLeadSaved(true);
                toast({
                    title: 'Proposal Request Received!',
                    description: 'Our engineering team will contact you with a formal quotation and timeline.',
                });
            }
        } catch (error) {
            toast({
                title: 'Submission Failed',
                description: 'Could not send your request. Please message us directly on WhatsApp.',
                variant: 'destructive',
            });
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="w-full text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white">
            <div className="mx-auto max-w-[1400px] space-y-12">
                
                {/* Optional Header */}
                {showHeader && (
                    <div className="text-center max-w-3xl mx-auto space-y-4 pt-4">
                        <span className="text-xs font-mono uppercase tracking-[0.2em] rtl:tracking-normal text-[#748660] font-bold">
                            Architecture Cost Engine
                        </span>
                        <h2 className="text-3xl sm:text-5xl font-bold tracking-tight text-white font-sans">
                            {title}
                        </h2>
                        <p className="text-sm text-zinc-400 font-sans leading-relaxed">
                            {subtitle}
                        </p>
                    </div>
                )}

                {/* Main Calculator Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    {/* Left Configuration Panel (8 Cols) */}
                    <div className="lg:col-span-8 space-y-8">
                        
                        {/* Step 1: Select Platform / Project Types (Multi-Select) */}
                        <div className="bg-[#161616] p-6 sm:p-8 border border-[#2B2B2B] space-y-6">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div className="space-y-1">
                                    <span className="text-xs font-mono font-bold uppercase tracking-wider rtl:tracking-normal text-[#748660]">Step 1</span>
                                    <h3 className="text-lg font-bold text-white font-sans">Select Project Platform(s)</h3>
                                    <p className="text-xs text-zinc-400 font-sans">
                                        Choose one or combine multiple platforms for an integrated system.
                                    </p>
                                </div>
                                <div className="flex items-center gap-2 flex-wrap">
                                    <button
                                        type="button"
                                        onClick={() => setIsAiModalOpen(true)}
                                        className="inline-flex items-center gap-2 px-3.5 py-1.5 bg-black hover:bg-[#222222] text-white text-xs font-mono border border-[#333333] transition-all cursor-pointer"
                                    >
                                        <Sparkles className="w-3.5 h-3.5 text-[#748660]" />
                                        <span>AI Scope Estimator</span>
                                    </button>
                                    {selectedPlatforms.length >= 2 && (
                                        <span className="text-xs font-mono font-bold text-[#748660] bg-[#1E2619] border border-[#748660]/40 px-3 py-1 flex items-center gap-1.5 rtl:tracking-normal">
                                            <Tag className="h-3.5 w-3.5" />
                                            10% Multi-Platform Discount
                                        </span>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 font-mono text-xs">
                                {/* Web Project */}
                                <button
                                    type="button"
                                    onClick={() => togglePlatform('web')}
                                    className={`p-5 border text-start transition-all cursor-pointer flex flex-col justify-between h-40 ${
                                        selectedPlatforms.includes('web')
                                            ? 'border-[#748660] bg-[#1A2215]'
                                            : 'border-[#2B2B2B] bg-black hover:border-zinc-500'
                                    }`}
                                >
                                    <div className="flex items-center justify-between w-full">
                                        <div className={`p-2.5 ${selectedPlatforms.includes('web') ? 'bg-[#748660] text-black' : 'bg-[#1E1E1E] text-zinc-400'}`}>
                                            <Globe className="h-5 w-5" />
                                        </div>
                                        <div className={`h-5 w-5 flex items-center justify-center border ${
                                            selectedPlatforms.includes('web')
                                                ? 'bg-[#748660] border-[#748660] text-black'
                                                : 'border-[#333333] bg-black'
                                        }`}>
                                            {selectedPlatforms.includes('web') && <Check className="h-3.5 w-3.5" strokeWidth={3} />}
                                        </div>
                                    </div>
                                    <div>
                                        <h4 className="font-bold text-white text-sm">Website / Web App</h4>
                                        <p className="text-xs text-zinc-400 mt-0.5">$10 per page</p>
                                    </div>
                                </button>

                                {/* Mobile App */}
                                <button
                                    type="button"
                                    onClick={() => togglePlatform('mobile')}
                                    className={`p-5 border text-start transition-all cursor-pointer flex flex-col justify-between h-40 ${
                                        selectedPlatforms.includes('mobile')
                                            ? 'border-[#748660] bg-[#1A2215]'
                                            : 'border-[#2B2B2B] bg-black hover:border-zinc-500'
                                    }`}
                                >
                                    <div className="flex items-center justify-between w-full">
                                        <div className={`p-2.5 ${selectedPlatforms.includes('mobile') ? 'bg-[#748660] text-black' : 'bg-[#1E1E1E] text-zinc-400'}`}>
                                            <Smartphone className="h-5 w-5" />
                                        </div>
                                        <div className={`h-5 w-5 flex items-center justify-center border ${
                                            selectedPlatforms.includes('mobile')
                                                ? 'bg-[#748660] border-[#748660] text-black'
                                                : 'border-[#333333] bg-black'
                                        }`}>
                                            {selectedPlatforms.includes('mobile') && <Check className="h-3.5 w-3.5" strokeWidth={3} />}
                                        </div>
                                    </div>
                                    <div>
                                        <h4 className="font-bold text-white text-sm">Mobile App</h4>
                                        <p className="text-xs text-zinc-400 mt-0.5">$15 per screen (iOS &amp; Android)</p>
                                    </div>
                                </button>

                                {/* Desktop Program */}
                                <button
                                    type="button"
                                    onClick={() => togglePlatform('desktop')}
                                    className={`p-5 border text-start transition-all cursor-pointer flex flex-col justify-between h-40 ${
                                        selectedPlatforms.includes('desktop')
                                            ? 'border-[#748660] bg-[#1A2215]'
                                            : 'border-[#2B2B2B] bg-black hover:border-zinc-500'
                                    }`}
                                >
                                    <div className="flex items-center justify-between w-full">
                                        <div className={`p-2.5 ${selectedPlatforms.includes('desktop') ? 'bg-[#748660] text-black' : 'bg-[#1E1E1E] text-zinc-400'}`}>
                                            <Monitor className="h-5 w-5" />
                                        </div>
                                        <div className={`h-5 w-5 flex items-center justify-center border ${
                                            selectedPlatforms.includes('desktop')
                                                ? 'bg-[#748660] border-[#748660] text-black'
                                                : 'border-[#333333] bg-black'
                                        }`}>
                                            {selectedPlatforms.includes('desktop') && <Check className="h-3.5 w-3.5" strokeWidth={3} />}
                                        </div>
                                    </div>
                                    <div>
                                        <h4 className="font-bold text-white text-sm">Desktop Software</h4>
                                        <p className="text-xs text-zinc-400 mt-0.5">$25 per screen (.NET / Native)</p>
                                    </div>
                                </button>
                            </div>
                        </div>

                        {/* Step 2: Dedicated Screen / Page Sliders */}
                        <div className="bg-[#161616] p-6 sm:p-8 border border-[#2B2B2B] space-y-8">
                            <div className="flex items-center justify-between">
                                <div className="space-y-1">
                                    <span className="text-xs font-mono font-bold uppercase tracking-wider text-[#748660]">Step 2</span>
                                    <h3 className="text-lg font-bold text-white">
                                        Screen / Page Counters
                                    </h3>
                                </div>
                                <span className="text-xs font-mono text-zinc-400 bg-black border border-[#2B2B2B] px-3 py-1">
                                    {selectedPlatforms.length} Platform{selectedPlatforms.length > 1 ? 's' : ''} Selected
                                </span>
                            </div>

                            <div className="space-y-6 divide-y divide-[#222222]">
                                {selectedPlatforms.map(pKey => {
                                    const pRate = rates[pKey];
                                    const currentCount = platformScreens[pKey] || 1;
                                    const subCost = currentCount * pRate.rate;
                                    const IconComp = pRate.icon;

                                    return (
                                        <div key={pKey} className="pt-6 first:pt-0 space-y-4">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-3">
                                                    <div className="p-2 bg-black border border-[#2B2B2B] text-zinc-300">
                                                        <IconComp className="h-5 w-5" />
                                                    </div>
                                                    <div>
                                                        <h4 className="font-bold text-sm text-white">{pRate.title}</h4>
                                                        <span className="text-xs font-mono text-zinc-400">${pRate.rate} per {pRate.unit.toLowerCase()}</span>
                                                    </div>
                                                </div>

                                                <div className="text-end font-mono">
                                                    <span className="text-xl sm:text-2xl font-bold text-[#748660]">
                                                        {currentCount}
                                                    </span>
                                                    <span className="text-xs text-zinc-400 ms-1">
                                                        {pRate.unit}s ({formatPrice(subCost)})
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="space-y-3">
                                                <input
                                                    type="range"
                                                    min="1"
                                                    max="40"
                                                    step="1"
                                                    value={currentCount}
                                                    onChange={(e) => updateScreens(pKey, e.target.value)}
                                                    className="w-full h-2 bg-black border border-[#2B2B2B] appearance-none cursor-pointer accent-[#748660]"
                                                />
                                            </div>

                                            <div className="flex items-center justify-center gap-2 pt-1 font-mono text-xs">
                                                {[3, 5, 8, 12, 20].map((preset) => (
                                                    <button
                                                        key={preset}
                                                        type="button"
                                                        onClick={() => updateScreens(pKey, preset)}
                                                        className={`px-3 py-1 border transition-all cursor-pointer ${
                                                            currentCount === preset
                                                                ? 'bg-white text-black border-white font-bold'
                                                                : 'bg-black text-zinc-400 border-[#2B2B2B] hover:border-zinc-500 hover:text-white'
                                                        }`}
                                                    >
                                                        {preset} {pRate.unit}s
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Step 3: Modules & Addons */}
                        <div className="bg-[#161616] p-6 sm:p-8 border border-[#2B2B2B] space-y-6">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div className="space-y-1">
                                    <span className="text-xs font-mono font-bold uppercase tracking-wider text-[#748660]">Step 3</span>
                                    <h3 className="text-lg font-bold text-white">
                                        Modules &amp; Engineering Services
                                    </h3>
                                </div>
                                {costBreakdown.itemizedAddons.length > 0 && (
                                    <button
                                        type="button"
                                        onClick={() => setSelectedOptions({})}
                                        className="text-xs font-mono text-zinc-400 hover:text-white underline cursor-pointer"
                                    >
                                        ✕ Clear All ({costBreakdown.itemizedAddons.length})
                                    </button>
                                )}
                            </div>

                            {/* Category Filter Tabs */}
                            <div className="flex items-center gap-1.5 overflow-x-auto pb-2 font-mono text-xs scrollbar-none">
                                {categories.map(cat => (
                                    <button
                                        key={cat.id}
                                        type="button"
                                        onClick={() => setActiveCategory(cat.id)}
                                        className={`px-3.5 py-1.5 border whitespace-nowrap transition-all cursor-pointer ${
                                            activeCategory === cat.id
                                                ? 'bg-white text-black border-white font-bold'
                                                : 'bg-black text-zinc-400 border-[#2B2B2B] hover:border-zinc-500 hover:text-white'
                                        }`}
                                    >
                                        {cat.title}
                                    </button>
                                ))}
                            </div>

                            {/* Modules Grid */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {filteredOptions.map((option) => {
                                    const IconComponent = option.icon;
                                    const isSelected = !!selectedOptions[option.id];
                                    return (
                                        <div
                                            key={option.id}
                                            onClick={() => toggleOption(option.id)}
                                            className={`p-4 border transition-all cursor-pointer flex items-start gap-3.5 ${
                                                isSelected
                                                    ? 'border-[#748660] bg-[#1A2215]'
                                                    : 'border-[#2B2B2B] bg-black hover:border-zinc-500'
                                            }`}
                                        >
                                            <div className={`p-2 shrink-0 mt-0.5 ${isSelected ? 'bg-[#748660] text-black' : 'bg-[#1A1A1A] text-zinc-400'}`}>
                                                <IconComponent className="h-4 w-4" />
                                            </div>
                                            <div className="flex-1 font-mono">
                                                <div className="flex items-center justify-between">
                                                    <h4 className="font-bold text-xs text-white">{option.title}</h4>
                                                    <span className="text-xs font-bold text-[#748660]">
                                                        {option.price > 0 ? `+${formatPrice(option.price)}` : 'Included'}
                                                    </span>
                                                </div>
                                                <p className="text-[11px] text-zinc-400 mt-1 leading-relaxed">
                                                    {option.desc}
                                                </p>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </div>

                    {/* Right Sticky Summary & Action Card (4 Cols) */}
                    <div className="lg:col-span-4 sticky top-24 space-y-6 font-mono">
                        
                        {/* Summary Card */}
                        <div className="bg-[#161616] text-white p-6 sm:p-8 border border-[#2B2B2B] space-y-6">
                            <div className="flex items-center justify-between border-b border-[#2B2B2B] pb-4">
                                <div className="flex items-center gap-2">
                                    <Calculator className="h-4 w-4 text-[#748660]" />
                                    <h3 className="font-bold text-sm uppercase tracking-wider text-white">Estimated Budget</h3>
                                </div>
                                
                                {/* Currency Switcher */}
                                <div className="flex items-center border border-[#333333] bg-black">
                                    <button
                                        type="button"
                                        onClick={() => setIsUsd(true)}
                                        className={`px-2.5 py-1 text-xs font-bold transition-all cursor-pointer ${
                                            isUsd ? 'bg-white text-black' : 'text-zinc-400 hover:text-white'
                                        }`}
                                    >
                                        USD
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setIsUsd(false)}
                                        className={`px-2.5 py-1 text-xs font-bold transition-all cursor-pointer ${
                                            !isUsd ? 'bg-white text-black' : 'text-zinc-400 hover:text-white'
                                        }`}
                                    >
                                        EGP
                                    </button>
                                </div>
                            </div>

                            {/* Total Display */}
                            <div className="space-y-1">
                                <span className="text-xs text-zinc-400 block uppercase tracking-wider">Estimated Total</span>
                                <div className="text-3xl sm:text-4xl font-black text-white">
                                    {isUsd ? `$${costBreakdown.finalTotalUsd.toLocaleString()}` : `${costBreakdown.finalTotalEgp.toLocaleString()} EGP`}
                                </div>
                                <div className="flex items-center justify-between text-xs text-zinc-400 pt-2 border-t border-[#222222] mt-3">
                                    <span>Estimated Timeline</span>
                                    <span className="text-[#748660] font-bold">~{costBreakdown.estimatedDays} business days</span>
                                </div>
                            </div>

                            {/* Line Items Breakdown */}
                            <div className="space-y-3 pt-4 border-t border-[#2B2B2B] text-xs">
                                <div className="text-zinc-400 font-bold uppercase tracking-wider">Architecture Scope</div>
                                
                                {costBreakdown.itemizedPlatforms.map(item => (
                                    <div key={item.key} className="flex justify-between text-zinc-300">
                                        <span>{item.title} ({item.count} {item.unit}s)</span>
                                        <span>{formatPrice(item.total)}</span>
                                    </div>
                                ))}

                                {costBreakdown.itemizedAddons.map(item => (
                                    <div key={item.id} className="flex justify-between text-zinc-400 text-[11px]">
                                        <span className="truncate max-w-[200px]">{item.title}</span>
                                        <span>+{formatPrice(item.subtotal)}</span>
                                    </div>
                                ))}

                                {costBreakdown.multiPlatformDiscount > 0 && (
                                    <div className="flex justify-between text-[#748660] font-bold pt-2 border-t border-[#222222]">
                                        <span>Multi-Platform Discount (10%)</span>
                                        <span>-{formatPrice(costBreakdown.multiPlatformDiscount)}</span>
                                    </div>
                                )}
                            </div>

                            {/* CTAs */}
                            <div className="space-y-3 pt-4">
                                <a
                                    href={whatsappLink}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="w-full flex items-center justify-center gap-2 py-3.5 bg-[#748660] hover:bg-[#60704E] text-[#0F140A] font-bold text-xs uppercase tracking-wider transition-colors shadow-lg shadow-[#748660]/20"
                                >
                                    <MessageSquare className="w-4 h-4" />
                                    <span>Discuss Scope on WhatsApp</span>
                                </a>

                                <Link
                                    href="/estimator"
                                    className="w-full flex items-center justify-center gap-2 py-3 bg-black hover:bg-[#222222] text-zinc-300 hover:text-white border border-[#333333] text-xs font-bold uppercase tracking-wider transition-colors"
                                >
                                    <span>Open Dedicated Estimator Page</span>
                                    <ArrowUpRight className="w-3.5 h-3.5" />
                                </Link>
                            </div>
                        </div>

                        {/* Direct Lead Capture Proposal Form */}
                        <div className="bg-[#161616] p-6 border border-[#2B2B2B] space-y-4">
                            <div className="space-y-1">
                                <h4 className="font-bold text-xs uppercase tracking-wider text-white">
                                    Request Official Engineering Proposal
                                </h4>
                                <p className="text-[11px] text-zinc-400">
                                    Submit your scope and our lead architect will prepare a formal timeline &amp; NDA.
                                </p>
                            </div>

                            {leadSaved ? (
                                <div className="p-4 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs flex items-center gap-2">
                                    <CheckCircle2 className="h-4 w-4 shrink-0" />
                                    <span>Proposal requested! We will reach out within 24 hours.</span>
                                </div>
                            ) : (
                                <form onSubmit={handleLeadSubmit} className="space-y-3 text-xs">
                                    <input
                                        type="text"
                                        placeholder="Your Full Name *"
                                        required
                                        value={leadName}
                                        onChange={(e) => setLeadName(e.target.value)}
                                        className="w-full px-3 py-2 bg-black border border-[#2B2B2B] text-white focus:border-[#748660] focus:outline-none"
                                    />
                                    <input
                                        type="tel"
                                        placeholder="Phone / WhatsApp Number *"
                                        required
                                        value={leadMobile}
                                        onChange={(e) => setLeadMobile(e.target.value)}
                                        className="w-full px-3 py-2 bg-black border border-[#2B2B2B] text-white focus:border-[#748660] focus:outline-none"
                                    />
                                    <input
                                        type="email"
                                        placeholder="Work Email (Optional)"
                                        value={leadEmail}
                                        onChange={(e) => setLeadEmail(e.target.value)}
                                        className="w-full px-3 py-2 bg-black border border-[#2B2B2B] text-white focus:border-[#748660] focus:outline-none"
                                    />
                                    <button
                                        type="submit"
                                        disabled={submitting}
                                        className="w-full py-2.5 bg-white hover:bg-zinc-200 text-black font-bold text-xs uppercase tracking-wider transition-colors cursor-pointer disabled:opacity-50"
                                    >
                                        {submitting ? 'Submitting Scope...' : 'Submit Engineering Scope ➔'}
                                    </button>
                                </form>
                            )}
                        </div>

                    </div>

                </div>

            </div>
        </div>
    );
}
