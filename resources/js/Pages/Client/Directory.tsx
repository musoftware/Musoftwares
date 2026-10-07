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
    id: string;
    category: string;
    icon: React.ComponentType<{ className?: string }>;
    href: string;
    badge?: string;
}

const DIRECTORY_ITEMS: DirectoryItem[] = [
    { id: 'erp', category: 'core_saas', icon: Building2, href: '/sso/erp', badge: 'enterprise' },
    { id: 'crm', category: 'core_saas', icon: Users, href: '/sso/crm', badge: 'active' },
    { id: 'whatsapp', category: 'marketing', icon: MessageSquare, href: '/whatsapp-sender', badge: 'cloud_api' },
    { id: 'fb_marketing', category: 'marketing', icon: Megaphone, href: '/fbmb' },
    { id: 'sms', category: 'messaging', icon: Smartphone, href: '/sms-payment-gateway' },
    { id: 'booking', category: 'core_saas', icon: Calendar, href: '/sso/bookingsys' },
    { id: 'gold_pos', category: 'pos_engine', icon: Coins, href: '/sso/goldsaversys' },
    { id: 'affiliate_pos', category: 'pos_engine', icon: Share2, href: '/sso/affsys' },
    { id: 'contracts', category: 'legal', icon: FileCheck2, href: '/isaas/contracts' },
    { id: 'marketplace', category: 'app_store', icon: ShoppingBag, href: '/marketplace/services', badge: 'store' },
    { id: 'seller', category: 'app_store', icon: Store, href: '/marketplace/dashboard' },
    { id: 'recharge', category: 'finance', icon: Wallet, href: '/financial/add-balance' },
    { id: 'invoices', category: 'finance', icon: FileText, href: '/billing/invoices' },
    { id: 'transactions', category: 'finance', icon: ArrowRightLeft, href: '/financial/transactions' },
    { id: 'vouchers', category: 'rewards', icon: Ticket, href: '/vouchers' },
    { id: 'withdrawals', category: 'finance', icon: ArrowUpRight, href: '/financial/withdrawals' },
    { id: 'points', category: 'rewards', icon: Award, href: '/points' },
    { id: 'kyc', category: 'security', icon: ShieldCheck, href: '/kyc' },
];

const CATEGORY_IDS = ['all', 'core_saas', 'marketing', 'messaging', 'pos_engine', 'app_store', 'finance', 'rewards', 'security', 'legal'];

const itemText = (item: DirectoryItem, field: 'name' | 'desc' | 'btn') => __(`client.directory_${item.id}_${field}`);
const categoryLabel = (categoryId: string) => __(`client.directory_cat_${categoryId}`);

export default function Directory() {
    const [searchQuery, setSearchQuery] = useState('');
    const [activeCategory, setActiveCategory] = useState('all');

    const categories = CATEGORY_IDS.map((id) => ({ id, label: categoryLabel(id) }));
    const query = searchQuery.toLowerCase();

    const filteredItems = DIRECTORY_ITEMS.filter(item => {
        const matchesSearch = itemText(item, 'name').toLowerCase().includes(query) ||
            itemText(item, 'desc').toLowerCase().includes(query);
        const matchesCategory = activeCategory === 'all' || item.category === activeCategory;
        return matchesSearch && matchesCategory;
    });

    return (
        <AuthenticatedLayout>
            <Head title={`${__('client.directory_page_title')} — Musoftwares Studio`} />

            <div className="w-full">
                {/* Hero Header */}
                <PageHeroHeader
                    badge={__('client.directory_hero_badge')}
                    title={__('client.directory_hero_title')}
                    description={__('client.directory_hero_description')}
                    searchValue={searchQuery}
                    onSearchChange={setSearchQuery}
                    searchPlaceholder={__('client.directory_search_placeholder')}
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
                            <h3 className="text-base font-semibold text-[#1d1d1f] dark:text-[#f8fafc]">{__('client.directory_empty_title')}</h3>
                            <p className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 mt-1">{__('client.directory_empty_hint')}</p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {filteredItems.map((item) => {
                                const IconComponent = item.icon;
                                return (
                                    <div
                                        key={item.id}
                                        className="bg-white dark:bg-zinc-900/80 border border-black/5 dark:border-white/10 rounded-[24px] p-6 sm:p-7 flex flex-col justify-between group hover:border-[#0071e3]/30 dark:hover:border-[#2997ff]/40 hover:shadow-md transition-all shadow-sm relative overflow-hidden"
                                    >
                                        <div>
                                            <div className="flex items-center justify-between mb-5">
                                                <div className="w-11 h-11 rounded-2xl bg-[#0071e3]/10 dark:bg-[#0071e3]/20 border border-[#0071e3]/15 flex items-center justify-center text-[#0071e3] dark:text-[#2997ff] group-hover:bg-[#0071e3] group-hover:text-white transition-all">
                                                    <IconComponent className="w-5 h-5" />
                                                </div>
                                                <span className="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-[#f5f5f7] dark:bg-zinc-800 border border-black/5 dark:border-white/10 text-[#1d1d1f]/60 dark:text-zinc-300 font-mono">
                                                    {categoryLabel(item.category)}
                                                </span>
                                            </div>

                                            <div className="space-y-1.5 mb-6">
                                                <div className="flex items-center gap-2">
                                                    <h3 className="text-base font-bold text-[#1d1d1f] dark:text-[#f8fafc] font-sans group-hover:text-[#0071e3] dark:group-hover:text-[#2997ff] transition-colors">
                                                        {itemText(item, 'name')}
                                                    </h3>
                                                    {item.badge && (
                                                        <span className="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                                                            {__(`client.directory_badge_${item.badge}`)}
                                                        </span>
                                                    )}
                                                </div>
                                                <p className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-sans leading-relaxed">
                                                    {itemText(item, 'desc')}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="pt-4 border-t border-black/5 dark:border-white/10 flex items-center justify-between">
                                            <Link
                                                href={item.href}
                                                className="w-full flex items-center justify-between text-xs font-semibold text-[#0071e3] dark:text-[#2997ff] group-hover:text-[#0077ed] dark:group-hover:text-[#52a9ff] py-1"
                                            >
                                                <span>{itemText(item, 'btn')}</span>
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
