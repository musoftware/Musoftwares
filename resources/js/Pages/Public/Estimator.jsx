import { useState, useMemo } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import AiEstimatorModal from '@/Components/Estimator/AiEstimatorModal';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useToast } from '@/Components/ui/use-toast';
import { 
    Globe, 
    Smartphone, 
    Monitor, 
    Sparkles, 
    Check, 
    Plus, 
    Minus, 
    Shield, 
    CreditCard, 
    Lock, 
    Languages, 
    MessageSquare, 
    Database, 
    Cpu, 
    Send, 
    Printer, 
    Calculator,
    CheckCircle2,
    BellRing,
    UploadCloud,
    MapPin,
    Barcode,
    FileSpreadsheet,
    HardDrive,
    KeyRound,
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
    Layers,
    Share2,
    SlidersHorizontal
} from 'lucide-react';
import axios from 'axios';

export default function Estimator({ exchangeRate = 50.0 }) {
    const { auth } = usePage().props;
    const isAdmin = !!(auth?.user?.is_admin);
    const [isAiModalOpen, setIsAiModalOpen] = useState(false);

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

    const handleApplyAi = (aiData) => {
        if (aiData.platforms && aiData.platforms.length > 0) {
            setSelectedPlatforms(aiData.platforms);
        }
        if (aiData.platformScreens) {
            setPlatformScreens(prev => ({
                ...prev,
                ...aiData.platformScreens
            }));
        }
        if (aiData.selectedOptions) {
            setSelectedOptions(aiData.selectedOptions);
        }
    };

    // 3. Category Filter Tab for Modules
    const [activeCategory, setActiveCategory] = useState('all');

    // 4. Dynamic Type-Specific Add-ons State (starts 100% empty / unchecked)
    const [selectedOptions, setSelectedOptions] = useState({});

    // 5. Currency: true for USD ($), false for EGP
    const [isUsd, setIsUsd] = useState(true);

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
                id: 'web_gateways',
                title: 'Online Payment Gateways',
                category: 'ecommerce',
                price: 20,
                platform: 'web',
                isCounter: true,
                max: 5,
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
                title: 'Multi-Currency & GeoIP Country Detection',
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
                desc: 'Server-side CAPI and client tracking setup for Meta Pixel, TikTok Pixel, Snap & Google Analytics 4.',
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
                isCounter: true,
                max: 5,
                icon: CreditCard,
                desc: 'Mobile in-app payments (Apple Pay, Google Pay, Paymob SDK, Stripe Mobile).',
            },
            {
                id: 'mobile_social_auth',
                title: 'Social & Apple/Google Login',
                category: 'core',
                price: 35,
                platform: 'mobile',
                icon: Lock,
                desc: '1-tap sign-in with Google, Apple ID, Phone OTP, and email auth.',
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
                id: 'mobile_live_chat',
                title: 'In-App Live Support Chat',
                category: 'ai_automation',
                price: 35,
                platform: 'mobile',
                icon: MessageSquare,
                desc: 'Real-time WebSocket chat between users and customer service support agents.',
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
                desc: 'Automated daily backup of system database to Google Drive / AWS S3 with AES-256 encryption.',
            },
            {
                id: 'desktop_serial_license',
                title: 'Hardware Serial Key & Licensing',
                category: 'infrastructure',
                price: 50,
                platform: 'desktop',
                icon: KeyRound,
                desc: 'Machine hardware lock protection with encrypted serial license key activation and trial period.',
            },
            {
                id: 'desktop_user_roles',
                title: 'Cashier Shifts & Anti-Fraud Audit Log',
                category: 'core',
                price: 40,
                platform: 'desktop',
                icon: Lock,
                desc: 'Shift closing receipts, supervisor override permissions, and strict audit logs preventing cashier fraud.',
            },
            {
                id: 'desktop_reporting_export',
                title: 'Excel / PDF Export & Financial BI',
                category: 'core',
                price: 30,
                platform: 'desktop',
                icon: FileSpreadsheet,
                desc: 'Financial profit/loss charts, sales trends, inventory valuation, and 1-click Excel/PDF export.',
            },
            {
                id: 'desktop_device_scanner',
                title: 'Weight Scale & Scanner Hardware Sync',
                category: 'infrastructure',
                price: 50,
                platform: 'desktop',
                icon: Cpu,
                desc: 'Direct COM port / USB communication with electronic weight scales, cash drawers, and scanners.',
            },
            {
                id: 'desktop_bug_fix',
                title: 'Desktop Bug Fixing & Data Repair',
                category: 'optimization',
                price: 45,
                platform: 'desktop',
                icon: Bug,
                desc: 'Fixing unhandled exceptions/crashes, database index corruption, and printer communication errors.',
            },
            {
                id: 'desktop_performance_tune',
                title: 'Fast Query & Index Tuning',
                category: 'optimization',
                price: 40,
                platform: 'desktop',
                icon: Zap,
                desc: 'Instant search indexing and fast loading under heavy transaction loads (100k+ records).',
            },
        ],
    };

    // Categories config for intuitive filtering
    const categories = [
        { id: 'all', title: 'All Modules' },
        { id: 'ai_automation', title: 'AI & Automation' },
        { id: 'ecommerce', title: 'E-Commerce & Payments' },
        { id: 'core', title: 'Core & Management' },
        { id: 'infrastructure', title: 'Cloud & Security' },
        { id: 'marketing', title: 'Marketing & SEO' },
        { id: 'optimization', title: 'Speed & Bug Fixing' },
    ];

    // Calculate totals dynamically across all selected platforms
    const costBreakdown = useMemo(() => {
        let screensTotalUsd = 0;
        const platformItems = [];

        selectedPlatforms.forEach(pKey => {
            const count = platformScreens[pKey] || 1;
            const rate = rates[pKey].rate;
            const cost = count * rate;
            screensTotalUsd += cost;
            platformItems.push({
                key: pKey,
                title: rates[pKey].title,
                count,
                unit: rates[pKey].unit,
                rate,
                cost,
            });
        });

        // Collect all available options for active platforms
        const activeOptions = [];
        selectedPlatforms.forEach(pKey => {
            if (optionsDefinitions[pKey]) {
                activeOptions.push(...optionsDefinitions[pKey]);
            }
        });

        let addonsTotalUsd = 0;
        const itemizedAddons = [];

        activeOptions.forEach(opt => {
            if (opt.isCounter) {
                const count = selectedOptions[opt.id] || 0;
                if (count > 0) {
                    const cost = count * opt.price;
                    addonsTotalUsd += cost;
                    itemizedAddons.push({
                        title: `${opt.title} (${count})`,
                        cost,
                    });
                }
            } else {
                const isSelected = !!selectedOptions[opt.id];
                if (isSelected) {
                    addonsTotalUsd += opt.price;
                    itemizedAddons.push({
                        title: opt.title,
                        cost: opt.price,
                    });
                }
            }
        });

        const subtotalUsd = screensTotalUsd + addonsTotalUsd;

        // 10% Ecosystem Bundle Discount if >= 2 platforms selected
        const isBundleDiscount = selectedPlatforms.length >= 2;
        const discountUsd = isBundleDiscount ? Math.round(subtotalUsd * 0.10) : 0;
        const totalUsd = Math.max(0, subtotalUsd - discountUsd);
        const totalEgp = Math.round(totalUsd * exchangeRate);

        return {
            platformItems,
            screensTotalUsd,
            addonsTotalUsd,
            itemizedAddons,
            subtotalUsd,
            isBundleDiscount,
            discountUsd,
            totalUsd,
            totalEgp,
            activeOptions,
        };
    }, [selectedPlatforms, platformScreens, selectedOptions, exchangeRate]);

    // Filtered options based on category tab
    const filteredOptions = useMemo(() => {
        if (activeCategory === 'all') {
            return costBreakdown.activeOptions;
        }
        return costBreakdown.activeOptions.filter(opt => opt.category === activeCategory);
    }, [costBreakdown.activeOptions, activeCategory]);

    const formatPrice = (usdVal) => {
        if (!isUsd) {
            const egpVal = Math.round(usdVal * exchangeRate);
            return `${egpVal.toLocaleString()} EGP`;
        }
        return `$${usdVal.toLocaleString()}`;
    };

    const toggleOption = (id) => {
        setSelectedOptions(prev => ({
            ...prev,
            [id]: !prev[id],
        }));
    };

    const setCounterOption = (id, delta, min = 0, max = 5) => {
        setSelectedOptions(prev => {
            const current = prev[id] || 0;
            const next = Math.max(min, Math.min(max, current + delta));
            return {
                ...prev,
                [id]: next,
            };
        });
    };

    // WhatsApp quick direct message
    const getWhatsAppMessage = () => {
        const platformsStr = costBreakdown.platformItems
            .map(p => `- ${p.title}: ${p.count} ${p.unit}s ($${p.cost})`)
            .join('\n');

        const addonsList = costBreakdown.itemizedAddons
            .map(a => `- ${a.title}: $${a.cost}`)
            .join('\n');

        const discountStr = costBreakdown.isBundleDiscount
            ? `\n*10% Multi-Platform Bundle Discount:* -$${costBreakdown.discountUsd}`
            : '';

        const msg = `Hello Mahmoud, I calculated my project development budget on Musoftware Estimator:

*Platforms & Screens:*
${platformsStr}

*Selected Modules & Engineering Services:*
${addonsList || '- Standard architecture'}
${discountStr}

*Estimated Total:* $${costBreakdown.totalUsd} (${costBreakdown.totalEgp.toLocaleString()} EGP)

Let's discuss starting this project!`;
        return encodeURIComponent(msg);
    };

    // Generate Dedicated Official Printable Quotation
    const [generatingQuote, setGeneratingQuote] = useState(false);

    const handleGeneratePrintableQuotation = async () => {
        setGeneratingQuote(true);
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await axios.post('/estimator/generate-quotation', {
                platform_items: costBreakdown.platformItems,
                itemized_addons: costBreakdown.itemizedAddons,
                subtotal_usd: costBreakdown.subtotalUsd,
                is_bundle_discount: costBreakdown.isBundleDiscount,
                discount_usd: costBreakdown.discountUsd,
                total_usd: costBreakdown.totalUsd,
                total_egp: costBreakdown.totalEgp,
                exchange_rate: exchangeRate,
                is_usd: isUsd,
                client_name: leadName || undefined,
                client_business: leadBusiness || undefined,
                client_mobile: leadMobile || undefined,
                client_email: leadEmail || undefined,
                platforms_summary: selectedPlatforms.map(p => rates[p].title).join(' + '),
            }, {
                headers: {
                    'X-CSRF-TOKEN': token,
                }
            });

            if (res.data.success && res.data.url) {
                window.open(res.data.url, '_blank');
                toast({
                    title: `Official Quotation #${res.data.code}`,
                    description: 'Your formal quotation document has been generated and opened in a new tab.',
                });
            }
        } catch (error) {
            toast({
                title: 'Generation Failed',
                description: 'Could not generate quotation document. Please try again.',
                variant: 'destructive',
            });
        } finally {
            setGeneratingQuote(false);
        }
    };

    // Handle lead submission
    const handleLeadSubmit = async (e) => {
        e.preventDefault();
        if (!leadName || !leadEmail || !leadMobile) {
            toast({
                title: 'Missing Fields',
                description: 'Please fill in your name, email, and mobile number.',
                variant: 'destructive',
            });
            return;
        }

        setSubmitting(true);
        const platformsStr = costBreakdown.platformItems
            .map(p => `- ${p.title}: ${p.count} ${p.unit}s ($${p.cost})`)
            .join('\n');

        const addonsList = costBreakdown.itemizedAddons
            .map(a => `- ${a.title}: $${a.cost}`)
            .join('\n');

        const discountStr = costBreakdown.isBundleDiscount
            ? `\n- 10% Multi-Platform Bundle Discount: -$${costBreakdown.discountUsd}`
            : '';

        const breakdownText = `Estimated Project Scope & Budget:
*Platforms:*
${platformsStr}

*Selected Add-ons / Services:*
${addonsList || '- Standard architecture'}
${discountStr}

*Total Budget:* $${costBreakdown.totalUsd} (${costBreakdown.totalEgp.toLocaleString()} EGP @ ${exchangeRate} EGP/USD)`;

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await axios.post('/tools/lead-capture', {
                name: leadName,
                email: leadEmail,
                mobile: leadMobile,
                business_name: leadBusiness,
                tool_name: `Project Estimator (${selectedPlatforms.map(p => rates[p].title).join(' + ')})`,
                breakdown: breakdownText,
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
        <PublicLayout>
            <Head>
                <title>Project Cost & Budget Estimator - Multi-Platform Software Pricing | Musoftware</title>
                <meta name="description" content="Calculate your website, desktop software, or mobile app development budget with AI, e-invoicing, payments, and transparent unit pricing." />
            </Head>

            <div className="w-full bg-slate-50 text-slate-900 font-sans min-h-screen pt-12 pb-24">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 space-y-12">
                    
                    {/* Header Section */}
                    <div className="text-center max-w-3xl mx-auto space-y-4 pt-4">
                        <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-50 text-xs font-semibold text-blue-700 border border-blue-200/80 shadow-sm">
                            <Sparkles className="h-4 w-4 text-blue-600" />
                            <span>Multi-Platform Project Budget Estimator</span>
                        </div>
                        <h1 className="text-3xl sm:text-5xl font-extrabold tracking-tight text-slate-900">
                            Calculate Your Project Cost
                        </h1>
                        <p className="text-base sm:text-lg text-slate-600 font-light leading-relaxed">
                            Select one or multiple platforms (e.g. Desktop Software + Cloud Web Platform) to calculate your integrated system budget with instant 10% bundle discount.
                        </p>
                    </div>

                    {/* Main Calculator Grid */}
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                        
                        {/* Left Configuration Panel (8 Cols) */}
                        <div className="lg:col-span-8 space-y-8">
                            
                            {/* Step 1: Select Platform / Project Types (Multi-Select) */}
                            <div className="rounded-3xl bg-white p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
                                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div className="space-y-1">
                                        <span className="text-xs font-bold uppercase tracking-wider text-blue-600">Step 1</span>
                                        <h2 className="text-xl font-bold text-slate-900">Select Project Platform(s)</h2>
                                        <p className="text-xs text-slate-500 font-light">
                                            Choose one or combine multiple platforms (e.g. Web + Desktop) for an integrated system.
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2 flex-wrap">
                                        {isAdmin && (
                                            <button
                                                type="button"
                                                onClick={() => setIsAiModalOpen(true)}
                                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-black text-white text-xs font-semibold shadow-sm hover:shadow transition-all border border-slate-700 cursor-pointer group"
                                            >
                                                <Sparkles className="w-3.5 h-3.5 text-amber-300 group-hover:rotate-12 transition-transform duration-200" />
                                                <span>AI Estimator (Admin)</span>
                                            </button>
                                        )}
                                        {selectedPlatforms.length >= 2 && (
                                            <span className="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full flex items-center gap-1.5 animate-pulse">
                                                <Tag className="h-3.5 w-3.5" />
                                                10% Bundle Discount Active
                                            </span>
                                        )}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    {/* Web Project */}
                                    <button
                                        type="button"
                                        onClick={() => togglePlatform('web')}
                                        className={`relative p-5 rounded-2xl border-2 text-start transition-all cursor-pointer flex flex-col justify-between h-40 ${
                                            selectedPlatforms.includes('web')
                                                ? 'border-blue-600 bg-blue-50/50 shadow-md shadow-blue-500/10 ring-2 ring-blue-500/20'
                                                : 'border-slate-200 bg-white hover:border-slate-300 opacity-75 hover:opacity-100'
                                        }`}
                                    >
                                        <div className="flex items-center justify-between w-full">
                                            <div className={`p-2.5 rounded-xl ${selectedPlatforms.includes('web') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600'}`}>
                                                <Globe className="h-5 w-5" />
                                            </div>
                                            <div className={`h-5 w-5 rounded-md flex items-center justify-center border ${
                                                selectedPlatforms.includes('web')
                                                    ? 'bg-blue-600 border-blue-600 text-white'
                                                    : 'border-slate-300 bg-slate-50'
                                            }`}>
                                                {selectedPlatforms.includes('web') && <Check className="h-3.5 w-3.5" strokeWidth={3} />}
                                            </div>
                                        </div>
                                        <div>
                                            <h3 className="font-bold text-slate-900 text-base">Website / Web App</h3>
                                            <p className="text-xs text-slate-500 font-medium mt-0.5">$10 per page</p>
                                        </div>
                                    </button>

                                    {/* Mobile App */}
                                    <button
                                        type="button"
                                        onClick={() => togglePlatform('mobile')}
                                        className={`relative p-5 rounded-2xl border-2 text-start transition-all cursor-pointer flex flex-col justify-between h-40 ${
                                            selectedPlatforms.includes('mobile')
                                                ? 'border-blue-600 bg-blue-50/50 shadow-md shadow-blue-500/10 ring-2 ring-blue-500/20'
                                                : 'border-slate-200 bg-white hover:border-slate-300 opacity-75 hover:opacity-100'
                                        }`}
                                    >
                                        <div className="flex items-center justify-between w-full">
                                            <div className={`p-2.5 rounded-xl ${selectedPlatforms.includes('mobile') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600'}`}>
                                                <Smartphone className="h-5 w-5" />
                                            </div>
                                            <div className={`h-5 w-5 rounded-md flex items-center justify-center border ${
                                                selectedPlatforms.includes('mobile')
                                                    ? 'bg-blue-600 border-blue-600 text-white'
                                                    : 'border-slate-300 bg-slate-50'
                                            }`}>
                                                {selectedPlatforms.includes('mobile') && <Check className="h-3.5 w-3.5" strokeWidth={3} />}
                                            </div>
                                        </div>
                                        <div>
                                            <h3 className="font-bold text-slate-900 text-base">Mobile App</h3>
                                            <p className="text-xs text-slate-500 font-medium mt-0.5">$15 per screen (iOS &amp; Android)</p>
                                        </div>
                                    </button>

                                    {/* Desktop Program */}
                                    <button
                                        type="button"
                                        onClick={() => togglePlatform('desktop')}
                                        className={`relative p-5 rounded-2xl border-2 text-start transition-all cursor-pointer flex flex-col justify-between h-40 ${
                                            selectedPlatforms.includes('desktop')
                                                ? 'border-blue-600 bg-blue-50/50 shadow-md shadow-blue-500/10 ring-2 ring-blue-500/20'
                                                : 'border-slate-200 bg-white hover:border-slate-300 opacity-75 hover:opacity-100'
                                        }`}
                                    >
                                        <div className="flex items-center justify-between w-full">
                                            <div className={`p-2.5 rounded-xl ${selectedPlatforms.includes('desktop') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600'}`}>
                                                <Monitor className="h-5 w-5" />
                                            </div>
                                            <div className={`h-5 w-5 rounded-md flex items-center justify-center border ${
                                                selectedPlatforms.includes('desktop')
                                                    ? 'bg-blue-600 border-blue-600 text-white'
                                                    : 'border-slate-300 bg-slate-50'
                                            }`}>
                                                {selectedPlatforms.includes('desktop') && <Check className="h-3.5 w-3.5" strokeWidth={3} />}
                                            </div>
                                        </div>
                                        <div>
                                            <h3 className="font-bold text-slate-900 text-base">Desktop Software</h3>
                                            <p className="text-xs text-slate-500 font-medium mt-0.5">$25 per screen (.NET / Native)</p>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            {/* Step 2: Dedicated Screen / Page Sliders for each selected platform */}
                            <div className="rounded-3xl bg-white p-6 sm:p-8 border border-slate-200 shadow-sm space-y-8">
                                <div className="flex items-center justify-between">
                                    <div className="space-y-1">
                                        <span className="text-xs font-bold uppercase tracking-wider text-blue-600">Step 2</span>
                                        <h2 className="text-xl font-bold text-slate-900">
                                            Screen / Page Counters
                                        </h2>
                                    </div>
                                    <span className="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
                                        {selectedPlatforms.length} Platform{selectedPlatforms.length > 1 ? 's' : ''} Selected
                                    </span>
                                </div>

                                <div className="space-y-6 divide-y divide-slate-100">
                                    {selectedPlatforms.map(pKey => {
                                        const pRate = rates[pKey];
                                        const currentCount = platformScreens[pKey] || 1;
                                        const subCost = currentCount * pRate.rate;
                                        const IconComp = pRate.icon;

                                        return (
                                            <div key={pKey} className="pt-6 first:pt-0 space-y-4">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center gap-3">
                                                        <div className="p-2 rounded-xl bg-blue-50 text-blue-600">
                                                            <IconComp className="h-5 w-5" />
                                                        </div>
                                                        <div>
                                                            <h3 className="font-bold text-base text-slate-900">{pRate.title}</h3>
                                                            <span className="text-xs text-slate-500">${pRate.rate} per {pRate.unit.toLowerCase()}</span>
                                                        </div>
                                                    </div>

                                                    <div className="text-end">
                                                        <span className="text-xl sm:text-2xl font-extrabold text-blue-600">
                                                            {currentCount}
                                                        </span>
                                                        <span className="text-xs text-slate-500 font-medium ms-1">
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
                                                        className="w-full h-2.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600"
                                                    />
                                                    <div className="flex items-center justify-between text-xs text-slate-400 font-medium">
                                                        <span>1 {pRate.unit}</span>
                                                        <span>10 {pRate.unit}s</span>
                                                        <span>25+ {pRate.unit}s</span>
                                                    </div>
                                                </div>

                                                <div className="flex items-center justify-center gap-3 pt-1">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => updateScreens(pKey, currentCount - 1)}
                                                        className="h-8 w-8 p-0 rounded-full"
                                                    >
                                                        <Minus className="h-3.5 w-3.5" />
                                                    </Button>
                                                    <div className="flex gap-2">
                                                        {[3, 5, 8, 12, 20].map((preset) => (
                                                            <button
                                                                key={preset}
                                                                type="button"
                                                                onClick={() => updateScreens(pKey, preset)}
                                                                className={`px-3 py-1 rounded-full text-xs font-semibold transition-all ${
                                                                    currentCount === preset
                                                                        ? 'bg-slate-900 text-white'
                                                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                                                }`}
                                                            >
                                                                {preset} {pRate.unit}s
                                                            </button>
                                                        ))}
                                                    </div>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => updateScreens(pKey, currentCount + 1)}
                                                        className="h-8 w-8 p-0 rounded-full"
                                                    >
                                                        <Plus className="h-3.5 w-3.5" />
                                                    </Button>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* Step 3: Comprehensive Modules & Addons with Category Filter */}
                            <div className="rounded-3xl bg-white p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
                                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div className="space-y-1">
                                        <span className="text-xs font-bold uppercase tracking-wider text-blue-600">Step 3</span>
                                        <h2 className="text-xl font-bold text-slate-900">
                                            Modules &amp; Engineering Services
                                        </h2>
                                        <p className="text-xs text-slate-500 font-light">
                                            Filter by category to explore in-demand modules for your system.
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2 self-start sm:self-auto">
                                        {costBreakdown.itemizedAddons.length > 0 && (
                                            <button
                                                type="button"
                                                onClick={() => setSelectedOptions({})}
                                                className="text-xs text-slate-500 hover:text-red-600 font-semibold px-2.5 py-1 rounded-lg hover:bg-red-50 transition-colors"
                                            >
                                                ✕ Clear Add-ons ({costBreakdown.itemizedAddons.length})
                                            </button>
                                        )}
                                        {selectedPlatforms.length >= 2 && (
                                            <span className="text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200/80 px-3 py-1 rounded-full">
                                                Unified Multi-Platform Suite
                                            </span>
                                        )}
                                    </div>
                                </div>

                                {/* Category Filter Tabs */}
                                <div className="flex items-center gap-1.5 overflow-x-auto pb-2 scrollbar-none">
                                    {categories.map(cat => (
                                        <button
                                            key={cat.id}
                                            type="button"
                                            onClick={() => setActiveCategory(cat.id)}
                                            className={`px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer ${
                                                activeCategory === cat.id
                                                    ? 'bg-slate-900 text-white shadow-sm'
                                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
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
                                        
                                        if (option.isCounter) {
                                            const currentCount = selectedOptions[option.id] || 0;
                                            return (
                                                <div key={option.id} className="p-4 rounded-2xl border-2 border-slate-200 bg-white space-y-3">
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex items-center gap-3">
                                                            <div className="p-2 rounded-xl bg-slate-100 text-slate-600">
                                                                <IconComponent className="h-4 w-4" />
                                                            </div>
                                                            <div>
                                                                <h4 className="font-bold text-sm text-slate-900">{option.title}</h4>
                                                                <span className="text-xs text-slate-500">${option.price} / each</span>
                                                            </div>
                                                        </div>
                                                        <span className="text-xs font-bold text-blue-600">
                                                            {currentCount > 0 ? `+$${currentCount * option.price}` : '$0'}
                                                        </span>
                                                    </div>
                                                    <p className="text-xs text-slate-500 leading-relaxed">{option.desc}</p>
                                                    <div className="flex items-center justify-between pt-2 border-t border-slate-100">
                                                        <span className="text-xs text-slate-600 font-medium">Selected Count:</span>
                                                        <div className="flex items-center gap-2">
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => setCounterOption(option.id, -1, 0, option.max || 5)}
                                                                className="h-7 w-7 p-0 rounded-full"
                                                            >
                                                                <Minus className="h-3 w-3" />
                                                            </Button>
                                                            <span className="text-sm font-bold text-slate-900 w-4 text-center">
                                                                {currentCount}
                                                            </span>
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => setCounterOption(option.id, 1, 0, option.max || 5)}
                                                                className="h-7 w-7 p-0 rounded-full"
                                                            >
                                                                <Plus className="h-3 w-3" />
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </div>
                                            );
                                        }

                                        const isSelected = !!selectedOptions[option.id];
                                        return (
                                            <div
                                                key={option.id}
                                                onClick={() => toggleOption(option.id)}
                                                className={`p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3.5 ${
                                                    isSelected
                                                        ? 'border-blue-600 bg-blue-50/40 shadow-sm ring-1 ring-blue-500/20'
                                                        : 'border-slate-200 bg-white hover:border-slate-300'
                                                }`}
                                            >
                                                <div className={`p-2 rounded-xl shrink-0 mt-0.5 ${isSelected ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500'}`}>
                                                    <IconComponent className="h-4 w-4" />
                                                </div>
                                                <div className="flex-1">
                                                    <div className="flex items-center justify-between">
                                                        <h4 className="font-bold text-sm text-slate-900">{option.title}</h4>
                                                        <span className="text-xs font-bold text-blue-600">
                                                            {option.price > 0 ? `+$${option.price}` : 'Included'}
                                                        </span>
                                                    </div>
                                                    <p className="text-xs text-slate-500 mt-1 leading-relaxed">
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
                        <div className="lg:col-span-4 sticky top-24 space-y-6">
                            
                            {/* Summary Card */}
                            <div className="rounded-3xl bg-slate-900 text-white p-6 sm:p-8 shadow-xl border border-slate-800 space-y-6">
                                <div className="flex items-center justify-between border-b border-slate-800 pb-4">
                                    <div className="flex items-center gap-2">
                                        <Calculator className="h-5 w-5 text-blue-400" />
                                        <h3 className="font-bold text-lg text-white">Estimated Budget</h3>
                                    </div>
                                    
                                    {/* Currency Switcher */}
                                    <div className="flex items-center p-1 bg-slate-800 rounded-xl border border-slate-700">
                                        <button
                                            type="button"
                                            onClick={() => setIsUsd(true)}
                                            className={`px-2.5 py-1 text-xs font-bold rounded-lg transition-all ${
                                                isUsd ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'
                                            }`}
                                        >
                                            USD ($)
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setIsUsd(false)}
                                            className={`px-2.5 py-1 text-xs font-bold rounded-lg transition-all ${
                                                !isUsd ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'
                                            }`}
                                        >
                                            EGP
                                        </button>
                                    </div>
                                </div>

                                {/* Total Price Display */}
                                <div className="space-y-1">
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs uppercase tracking-wider text-slate-400 font-semibold">Total Development Cost</span>
                                        {costBreakdown.isBundleDiscount && (
                                            <span className="text-[11px] font-bold text-emerald-400 bg-emerald-950/80 border border-emerald-800 px-2 py-0.5 rounded-md">
                                                10% Multi-Platform Bundle
                                            </span>
                                        )}
                                    </div>
                                    <div className="flex items-baseline gap-2">
                                        <span className="text-3xl sm:text-4xl font-extrabold text-white">
                                            {formatPrice(costBreakdown.totalUsd)}
                                        </span>
                                        {costBreakdown.isBundleDiscount && (
                                            <span className="text-sm line-through text-slate-500 font-normal">
                                                {formatPrice(costBreakdown.subtotalUsd)}
                                            </span>
                                        )}
                                    </div>
                                    <p className="text-xs text-slate-400 font-light pt-1">
                                        Exchange rate reference: ~{exchangeRate} EGP / 1 USD
                                    </p>
                                </div>

                                {/* Itemized Breakdown */}
                                <div className="space-y-2.5 pt-4 border-t border-slate-800 text-xs">
                                    {costBreakdown.platformItems.map((p) => (
                                        <div key={p.key} className="flex justify-between text-slate-300">
                                            <span>{p.count} {p.unit}s ({p.title})</span>
                                            <span className="font-semibold text-white">{formatPrice(p.cost)}</span>
                                        </div>
                                    ))}

                                    {costBreakdown.itemizedAddons.map((addon, idx) => (
                                        <div key={idx} className="flex justify-between text-slate-300">
                                            <span>{addon.title}</span>
                                            <span className="font-semibold text-white">{formatPrice(addon.cost)}</span>
                                        </div>
                                    ))}

                                    {costBreakdown.isBundleDiscount && (
                                        <div className="flex justify-between text-emerald-400 font-semibold pt-1 border-t border-slate-800">
                                            <span>10% Ecosystem Bundle Discount</span>
                                            <span>-{formatPrice(costBreakdown.discountUsd)}</span>
                                        </div>
                                    )}
                                </div>

                                {/* Primary Action: WhatsApp Direct Discussion */}
                                <div className="space-y-3 pt-4 border-t border-slate-800">
                                    <a
                                        href={`https://wa.me/${phoneNumber}?text=${getWhatsAppMessage()}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="w-full flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#20bd5a] text-slate-950 font-bold h-12 rounded-xl transition-all shadow-lg hover:scale-[1.02]"
                                    >
                                        <MessageSquare className="h-5 w-5 fill-current" />
                                        <span>Start on WhatsApp Now</span>
                                    </a>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={generatingQuote}
                                        onClick={handleGeneratePrintableQuotation}
                                        className="w-full border-slate-700 bg-slate-800 text-slate-200 hover:bg-slate-700 hover:text-white rounded-xl h-11 text-xs font-semibold flex items-center justify-center gap-2"
                                    >
                                        <Printer className="h-4 w-4" />
                                        <span>{generatingQuote ? 'Generating Official Quote...' : 'Print / Save Official Quotation'}</span>
                                    </Button>
                                </div>
                            </div>

                            {/* Lead Capture Form Card */}
                            <div className="rounded-3xl bg-white p-6 border border-slate-200 shadow-sm space-y-4">
                                <div className="space-y-1">
                                    <h4 className="font-bold text-base text-slate-900">Request Official Quotation</h4>
                                    <p className="text-xs text-slate-500 font-light">
                                        Send your calculated scope directly to our software engineering team.
                                    </p>
                                </div>

                                {leadSaved ? (
                                    <div className="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 space-y-2 text-center">
                                        <div className="h-8 w-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
                                            <CheckCircle2 className="h-5 w-5" />
                                        </div>
                                        <p className="text-xs font-bold">Proposal Request Received!</p>
                                        <p className="text-[11px] text-emerald-700">We will review your multi-platform scope and get in touch within 24 hours.</p>
                                    </div>
                                ) : (
                                    <form onSubmit={handleLeadSubmit} className="space-y-3">
                                        <div>
                                            <Label htmlFor="leadName" className="text-xs font-semibold text-slate-700">Your Name *</Label>
                                            <Input
                                                id="leadName"
                                                placeholder="e.g. Ahmed Ali"
                                                required
                                                value={leadName}
                                                onChange={(e) => setLeadName(e.target.value)}
                                                className="h-10 text-xs mt-1 rounded-xl"
                                            />
                                        </div>
                                        <div>
                                            <Label htmlFor="leadEmail" className="text-xs font-semibold text-slate-700">Business Email *</Label>
                                            <Input
                                                id="leadEmail"
                                                type="email"
                                                placeholder="name@company.com"
                                                required
                                                value={leadEmail}
                                                onChange={(e) => setLeadEmail(e.target.value)}
                                                className="h-10 text-xs mt-1 rounded-xl"
                                            />
                                        </div>
                                        <div>
                                            <Label htmlFor="leadMobile" className="text-xs font-semibold text-slate-700">WhatsApp / Mobile *</Label>
                                            <Input
                                                id="leadMobile"
                                                placeholder="+20 10..."
                                                required
                                                value={leadMobile}
                                                onChange={(e) => setLeadMobile(e.target.value)}
                                                className="h-10 text-xs mt-1 rounded-xl"
                                            />
                                        </div>
                                        <div>
                                            <Label htmlFor="leadBusiness" className="text-xs font-semibold text-slate-700">Company / Project Name</Label>
                                            <Input
                                                id="leadBusiness"
                                                placeholder="Optional"
                                                value={leadBusiness}
                                                onChange={(e) => setLeadBusiness(e.target.value)}
                                                className="h-10 text-xs mt-1 rounded-xl"
                                            />
                                        </div>
                                        <Button
                                            type="submit"
                                            disabled={submitting}
                                            className="w-full bg-slate-900 hover:bg-slate-800 text-white rounded-xl h-11 text-xs font-bold flex items-center justify-center gap-2 shadow-sm"
                                        >
                                            <Send className="h-3.5 w-3.5" />
                                            <span>{submitting ? 'Submitting Scope...' : 'Submit Scope for Review'}</span>
                                        </Button>
                                    </form>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {isAdmin && (
                <AiEstimatorModal
                    isOpen={isAiModalOpen}
                    onClose={() => setIsAiModalOpen(false)}
                    onApply={handleApplyAi}
                    optionsDefinitions={optionsDefinitions}
                />
            )}
        </PublicLayout>
    );
}
