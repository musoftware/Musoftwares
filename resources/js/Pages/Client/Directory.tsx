import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    Building2, Users, MessageSquare, Megaphone, Smartphone, 
    Calendar, Coins, Share2, FileCheck2, ShoppingBag, 
    Store, Wallet, FileText, ArrowRightLeft, Ticket, 
    ArrowUpRight, Award, ShieldCheck, Search, ArrowRight, Sparkles 
} from 'lucide-react';
import { __ } from '@/lib/i18n';

interface DirectoryItem {
    name: string;
    category: string;
    desc: string;
    icon: React.ComponentType<{ className?: string }>;
    href: string;
    btnText: string;
    badge?: string;
}

export default function Directory() {
    const [searchQuery, setSearchQuery] = useState('');
    const [activeCategory, setActiveCategory] = useState('ALL');

    const directoryItems: DirectoryItem[] = [
        {
            name: 'ERP System',
            category: 'Core SaaS',
            desc: 'نظام إدارة المؤسسات والحسابات المالية والفواتير وشجرة الحسابات المتكاملة.',
            icon: Building2,
            href: '/sso/erp',
            btnText: 'Launch ERP',
            badge: 'Enterprise'
        },
        {
            name: 'CRM System',
            category: 'Core SaaS',
            desc: 'إدارة العملاء والقيادة، متابعة العروض وسجل التفاعلات والاتصالات والمراحل البيعية.',
            icon: Users,
            href: '/sso/crm',
            btnText: 'Launch CRM',
            badge: 'Active'
        },
        {
            name: 'WhatsApp Sender',
            category: 'Marketing',
            desc: 'منصة إرسال وتأتمة الحملات الترويجية ورسائل الواتساب الجماعية للعملاء.',
            icon: MessageSquare,
            href: '/whatsapp-sender',
            btnText: 'Open WhatsApp',
            badge: 'Cloud API'
        },
        {
            name: 'FB Marketing System',
            category: 'Marketing',
            desc: 'أدوات التسويق واستخراج البيانات وإدارة الحملات الإعلانية على فيسبوك.',
            icon: Megaphone,
            href: '/fbmb',
            btnText: 'Open FB Marketing'
        },
        {
            name: 'SMS Gateway',
            category: 'Messaging',
            desc: 'بوابة إرسال الرسائل النصية القصيرة OTP وإشعارات الفواتير والتحقق.',
            icon: Smartphone,
            href: '/sms-payment-gateway',
            btnText: 'Open SMS Gateway'
        },
        {
            name: 'Booking System',
            category: 'Core SaaS',
            desc: 'منصة حجز المواعيد والاستشارات والجداول الزمانية والمواعيد التلقائية.',
            icon: Calendar,
            href: '/sso/bookingsys',
            btnText: 'Open Bookings'
        },
        {
            name: 'Gold POS System',
            category: 'POS Engine',
            desc: 'نظام كاشير ونقاط بيع وتداول الذهب والمجوهرات ومتابعة أسعار البورصة الحية.',
            icon: Coins,
            href: '/sso/goldsaversys',
            btnText: 'Open Gold POS'
        },
        {
            name: 'Affiliate POS System',
            category: 'POS Engine',
            desc: 'نظام إدارة المسوقين ونقاط البيع بالعمولة وتوزيع الأرباح التلقائي.',
            icon: Share2,
            href: '/sso/affsys',
            btnText: 'Open Affiliate POS'
        },
        {
            name: 'Contracts & Proposals',
            category: 'Legal',
            desc: 'إدارة العقود الإلكترونية وشروط الاتفاقيات والمقترحات الفنية الموثقة.',
            icon: FileCheck2,
            href: '/isaas/contracts',
            btnText: 'Open Contracts'
        },
        {
            name: 'Marketplace Services',
            category: 'App Store',
            desc: 'كتالوج المتجر لشراء الإضافات والخدمات والملحقات والتكاملات البرمجية.',
            icon: ShoppingBag,
            href: '/marketplace/services',
            btnText: 'Browse Marketplace',
            badge: 'Store'
        },
        {
            name: 'Seller Portal',
            category: 'App Store',
            desc: 'بوابة البائعين لرفع ونشر أدواتك ومنتجاتك الرقمية في المتجر.',
            icon: Store,
            href: '/marketplace/dashboard',
            btnText: 'Seller Portal'
        },
        {
            name: 'Recharge Wallet',
            category: 'Finance',
            desc: 'شحن رصيد المحفظة عبر وسائل الدفع الإلكترونية واستخدام الرصيد في الاشتراكات.',
            icon: Wallet,
            href: '/financial/add-balance',
            btnText: 'Add Balance'
        },
        {
            name: 'Invoices & Settlements',
            category: 'Finance',
            desc: 'سجل الفواتير الصادرة والمستحقة وسداد المبالغ وتنزيل كشوفات الحساب.',
            icon: FileText,
            href: '/billing/invoices',
            btnText: 'View Invoices'
        },
        {
            name: 'Transactions Audit Log',
            category: 'Finance',
            desc: 'سجل حركة الحساب المالي والتسويات والإيداعات والسحوبات التفصيلية.',
            icon: ArrowRightLeft,
            href: '/financial/transactions',
            btnText: 'View Log'
        },
        {
            name: 'Vouchers & Promo Codes',
            category: 'Rewards',
            desc: 'شحن أكواد الخصم والقسائم الشرائية الترويجية وإيداع رصيد مجاني.',
            icon: Ticket,
            href: '/vouchers',
            btnText: 'Redeem Vouchers'
        },
        {
            name: 'Earnings Withdrawals',
            category: 'Finance',
            desc: 'طلب سحب أرباحك وعمولات التسويق المكتسبة إلى حسابك البنكي أو المحفظة.',
            icon: ArrowUpRight,
            href: '/financial/withdrawals',
            btnText: 'Withdraw Funds'
        },
        {
            name: 'Points & Rewards',
            category: 'Rewards',
            desc: 'استبدال نقاط النشاط والمكافآت برصيد مجاني أو اشتراكات أدوات إضافية.',
            icon: Award,
            href: '/points',
            btnText: 'Points Store'
        },
        {
            name: 'KYC Account Verification',
            category: 'Security',
            desc: 'رفع مستندات إثبات الشخصية لتوثيق الحساب ورفع حدود السحب والعمليات.',
            icon: ShieldCheck,
            href: '/kyc',
            btnText: 'Verify Account'
        }
    ];

    const categories = ['ALL', 'CORE SAAS', 'MARKETING', 'MESSAGING', 'POS ENGINE', 'APP STORE', 'FINANCE', 'REWARDS', 'SECURITY', 'LEGAL'];

    const filteredItems = directoryItems.filter(item => {
        const matchesSearch = item.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            item.desc.toLowerCase().includes(searchQuery.toLowerCase());
        const matchesCategory = activeCategory === 'ALL' || item.category.toUpperCase() === activeCategory.toUpperCase();
        return matchesSearch && matchesCategory;
    });

    return (
        <AuthenticatedLayout>
            <Head title="Systems & Tools Directory — Musoftwares Studio" />

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* Hero Header */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div className="space-y-2">
                            <div className="flex items-center gap-3">
                                <span className="px-3 py-1 bg-[#0071e3]/10 text-[#0071e3] text-xs font-semibold rounded-full border border-[#0071e3]/20 flex items-center gap-1.5">
                                    <Sparkles className="w-3.5 h-3.5" />
                                    Studio Ecosystem Directory
                                </span>
                                <span className="text-xs font-sans text-[#1d1d1f]/60 font-medium">
                                    {directoryItems.length} Integrated Modules
                                </span>
                            </div>
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                                Applications &amp; Features Directory
                            </h1>
                            <p className="text-xs sm:text-sm text-[#1d1d1f]/70 font-sans max-w-2xl">
                                Explore and launch all core SaaS applications, automation tools, financial portals, and platform extensions from one centralized index.
                            </p>
                        </div>

                        {/* Search Input */}
                        <div className="w-full md:w-80 relative">
                            <Search className="w-4 h-4 text-[#1d1d1f]/40 absolute start-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                type="text"
                                placeholder="Search applications & tools..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="w-full h-11 ps-10 pe-4 bg-[#f5f5f7] border border-black/5 rounded-[980px] text-xs sm:text-sm text-[#1d1d1f] placeholder:text-[#1d1d1f]/40 focus:outline-none focus:ring-2 focus:ring-[#0071e3] focus:bg-white transition-all shadow-inner"
                            />
                        </div>
                    </div>
                </div>

                {/* Main Content Area */}
                <div className="max-w-[1400px] mx-auto px-6 sm:px-10 py-8 space-y-6">
                    
                    {/* Category Filter Pills */}
                    <div className="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                        {categories.map((cat) => {
                            const isSelected = activeCategory === cat;
                            return (
                                <button
                                    key={cat}
                                    onClick={() => setActiveCategory(cat)}
                                    className={`px-4 py-2 rounded-full text-xs font-semibold whitespace-nowrap transition-all cursor-pointer ${
                                        isSelected
                                            ? 'bg-[#1d1d1f] text-white shadow-sm'
                                            : 'bg-white text-[#1d1d1f]/70 border border-black/5 hover:bg-[#f5f5f7] hover:text-[#1d1d1f]'
                                    }`}
                                >
                                    {cat}
                                </button>
                            );
                        })}
                    </div>

                    {/* Bento Grid */}
                    {filteredItems.length === 0 ? (
                        <div className="bg-white border border-black/5 rounded-[24px] p-12 text-center shadow-sm">
                            <div className="w-12 h-12 rounded-full bg-[#f5f5f7] flex items-center justify-center mx-auto text-[#1d1d1f]/40 mb-3">
                                <Search className="w-6 h-6" />
                            </div>
                            <h3 className="text-base font-semibold text-[#1d1d1f]">No applications match your search</h3>
                            <p className="text-xs text-[#1d1d1f]/60 mt-1">Try searching with a different keyword or select another category.</p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {filteredItems.map((item, idx) => {
                                const IconComponent = item.icon;
                                return (
                                    <div
                                        key={idx}
                                        className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-7 flex flex-col justify-between group hover:border-[#0071e3]/30 hover:shadow-md transition-all shadow-sm relative overflow-hidden"
                                    >
                                        <div>
                                            <div className="flex items-center justify-between mb-5">
                                                <div className="w-11 h-11 rounded-2xl bg-[#0071e3]/10 border border-[#0071e3]/15 flex items-center justify-center text-[#0071e3] group-hover:bg-[#0071e3] group-hover:text-white transition-all">
                                                    <IconComponent className="w-5 h-5" />
                                                </div>
                                                <span className="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-[#f5f5f7] border border-black/5 text-[#1d1d1f]/60 font-mono">
                                                    {item.category}
                                                </span>
                                            </div>

                                            <div className="space-y-1.5 mb-6">
                                                <div className="flex items-center gap-2">
                                                    <h3 className="text-base font-bold text-[#1d1d1f] font-sans group-hover:text-[#0071e3] transition-colors">
                                                        {item.name}
                                                    </h3>
                                                    {item.badge && (
                                                        <span className="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                                            {item.badge}
                                                        </span>
                                                    )}
                                                </div>
                                                <p className="text-xs text-[#1d1d1f]/60 font-sans leading-relaxed">
                                                    {item.desc}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="pt-4 border-t border-black/5 flex items-center justify-between">
                                            <Link
                                                href={item.href}
                                                className="w-full flex items-center justify-between text-xs font-semibold text-[#0071e3] group-hover:text-[#0077ed] py-1"
                                            >
                                                <span>{item.btnText}</span>
                                                <ArrowRight className="w-4 h-4 transform group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform" />
                                            </Link>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}

                </div>

            </div>
        </AuthenticatedLayout>
    );
}
