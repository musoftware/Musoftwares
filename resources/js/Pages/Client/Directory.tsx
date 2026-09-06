import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageShell } from '@/Components/ui/PageShell';
import { PageHeroHeader } from '@/Components/ui/PageHeroHeader';
import { FilterPillGroup } from '@/Components/ui/FilterPillGroup';
import { 
    Building2, Users, MessageSquare, Megaphone, Smartphone, 
    Calendar, Coins, Share2, FileCheck2, ShoppingBag, 
    Store, Wallet, FileText, ArrowRightLeft, Ticket, 
    ArrowUpRight, Award, ShieldCheck, Search, ArrowRight 
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

            <div className="w-full">
                {/* Hero Header */}
                <PageHeroHeader
                    badge="Studio Ecosystem Directory"
                    title="Applications & Features Directory"
                    description="Explore and launch all core SaaS applications, automation tools, financial portals, and platform extensions from one centralized index."
                    searchValue={searchQuery}
                    onSearchChange={setSearchQuery}
                    searchPlaceholder="Search applications & tools..."
                />

                {/* Main Content Area */}
                <PageShell maxWidth="7xl" className="space-y-6">
                    {/* Category Filter Pills */}
                    <FilterPillGroup
                        options={categories}
                        selected={activeCategory}
                        onChange={setActiveCategory}
                    />

                    {/* Bento Grid */}
                    {filteredItems.length === 0 ? (
                        <div className="bg-white dark:bg-zinc-900/80 border border-black/5 dark:border-white/10 rounded-[24px] p-12 text-center shadow-sm">
                            <div className="w-12 h-12 rounded-full bg-[#f5f5f7] dark:bg-zinc-800 flex items-center justify-center mx-auto text-[#1d1d1f]/40 dark:text-zinc-500 mb-3">
                                <Search className="w-6 h-6" />
                            </div>
                            <h3 className="text-base font-semibold text-[#1d1d1f] dark:text-[#f8fafc]">No applications match your search</h3>
                            <p className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 mt-1">Try searching with a different keyword or select another category.</p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {filteredItems.map((item, idx) => {
                                const IconComponent = item.icon;
                                return (
                                    <div
                                        key={idx}
                                        className="bg-white dark:bg-zinc-900/80 border border-black/5 dark:border-white/10 rounded-[24px] p-6 sm:p-7 flex flex-col justify-between group hover:border-[#0071e3]/30 dark:hover:border-[#2997ff]/40 hover:shadow-md transition-all shadow-sm relative overflow-hidden"
                                    >
                                        <div>
                                            <div className="flex items-center justify-between mb-5">
                                                <div className="w-11 h-11 rounded-2xl bg-[#0071e3]/10 dark:bg-[#0071e3]/20 border border-[#0071e3]/15 flex items-center justify-center text-[#0071e3] dark:text-[#2997ff] group-hover:bg-[#0071e3] group-hover:text-white transition-all">
                                                    <IconComponent className="w-5 h-5" />
                                                </div>
                                                <span className="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-[#f5f5f7] dark:bg-zinc-800 border border-black/5 dark:border-white/10 text-[#1d1d1f]/60 dark:text-zinc-300 font-mono">
                                                    {item.category}
                                                </span>
                                            </div>

                                            <div className="space-y-1.5 mb-6">
                                                <div className="flex items-center gap-2">
                                                    <h3 className="text-base font-bold text-[#1d1d1f] dark:text-[#f8fafc] font-sans group-hover:text-[#0071e3] dark:group-hover:text-[#2997ff] transition-colors">
                                                        {item.name}
                                                    </h3>
                                                    {item.badge && (
                                                        <span className="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                                                            {item.badge}
                                                        </span>
                                                    )}
                                                </div>
                                                <p className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-sans leading-relaxed">
                                                    {item.desc}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="pt-4 border-t border-black/5 dark:border-white/10 flex items-center justify-between">
                                            <Link
                                                href={item.href}
                                                className="w-full flex items-center justify-between text-xs font-semibold text-[#0071e3] dark:text-[#2997ff] group-hover:text-[#0077ed] dark:group-hover:text-[#52a9ff] py-1"
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
                </PageShell>
            </div>
        </AuthenticatedLayout>
    );
}
