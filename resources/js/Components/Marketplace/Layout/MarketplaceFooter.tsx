import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Globe, MessageCircle, Share2, Mail, Users, ShieldCheck, Lock, ExternalLink } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { __ } from '@/lib/i18n';

export default function MarketplaceFooter() {
    const { locale } = usePage().props as any;
    const currentLocale = locale || (typeof document !== 'undefined' ? document.documentElement.lang : 'en');

    const handleLanguageChange = (newLang: string) => {
        const url = new URL(window.location.href);
        url.searchParams.set('lang', newLang);
        window.location.href = url.toString();
    };

    return (
        <footer className="border-t border-slate-200 bg-white pt-16 pb-8 mt-auto">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                {/* Top Link Columns */}
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-8 mb-16">
                    <div>
                        <h4 className="font-bold text-slate-900 mb-6 text-sm uppercase tracking-wider">{__('general.categories') || 'Categories'}</h4>
                        <ul className="space-y-3.5 text-sm text-slate-500">
                            <li><Link href="/marketplace/services?category=web-development" className="hover:text-indigo-600 transition-colors">{__('general.programming_tech') || 'Web & Software Tech'}</Link></li>
                            <li><Link href="/marketplace/services?category=graphic-design" className="hover:text-indigo-600 transition-colors">{__('general.graphics_design') || 'Graphics & UI/UX Design'}</Link></li>
                            <li><Link href="/marketplace/services?category=digital-marketing" className="hover:text-indigo-600 transition-colors">{__('general.digital_marketing') || 'Digital Marketing'}</Link></li>
                            <li><Link href="/marketplace/services?category=video-animation" className="hover:text-indigo-600 transition-colors">{__('general.video_animation') || 'Video & Animation'}</Link></li>
                            <li><Link href="/marketplace/services?category=writing-translation" className="hover:text-indigo-600 transition-colors">{__('general.writing_translation') || 'Writing & Translation'}</Link></li>
                        </ul>
                    </div>

                    <div>
                        <h4 className="font-bold text-slate-900 mb-6 text-sm uppercase tracking-wider">{__('general.sellers_vendors') || 'Sellers & Vendors'}</h4>
                        <ul className="space-y-3.5 text-sm text-slate-500">
                            <li><Link href="/marketplace/services/create" className="hover:text-indigo-600 font-semibold text-indigo-600 transition-colors">{__('general.add_marketplace_item') || 'Add Marketplace Item'}</Link></li>
                            <li><Link href="/marketplace" className="hover:text-indigo-600 transition-colors">{__('general.seller_dashboard') || 'Seller Workspace'}</Link></li>
                            <li><Link href="/marketplace/orders" className="hover:text-indigo-600 transition-colors">{__('general.order_fulfillment') || 'Order Fulfillment'}</Link></li>
                        </ul>
                    </div>

                    <div>
                        <h4 className="font-bold text-slate-900 mb-6 text-sm uppercase tracking-wider">{__('general.buyers_escrow') || 'Buyers & Escrow'}</h4>
                        <ul className="space-y-3.5 text-sm text-slate-500">
                            <li><Link href="/marketplace/services" className="hover:text-indigo-600 transition-colors">{__('general.browse_all_services') || 'Browse Services'}</Link></li>
                            <li><Link href="/marketplace/favorites" className="hover:text-indigo-600 transition-colors">{__('general.saved_favorites') || 'Saved Wishlist'}</Link></li>
                            <li className="flex items-center gap-1.5 text-slate-400 text-xs mt-2 pt-2 border-t border-slate-100">
                                <Lock className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                <span>{__('general.escrow_protection') || '100% Escrow Protected'}</span>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 className="font-bold text-slate-900 mb-6 text-sm uppercase tracking-wider">{__('general.trust_safety') || 'Trust & Security'}</h4>
                        <ul className="space-y-3.5 text-sm text-slate-500">
                            <li className="flex items-center gap-1.5 text-xs text-slate-600">
                                <ShieldCheck className="w-4 h-4 text-indigo-600 shrink-0" />
                                <span>{__('general.verified_deliverables') || 'Verified Deliverables'}</span>
                            </li>
                            <li><Link href="/terms" className="hover:text-indigo-600 transition-colors">{__('general.terms_of_service') || 'Terms of Service'}</Link></li>
                            <li><Link href="/privacy" className="hover:text-indigo-600 transition-colors">{__('general.privacy_policy') || 'Privacy Policy'}</Link></li>
                        </ul>
                    </div>

                    <div>
                        <h4 className="font-bold text-slate-900 mb-6 text-sm uppercase tracking-wider">{__('general.ecosystem') || 'Musoftware Ecosystem'}</h4>
                        <ul className="space-y-3.5 text-sm text-slate-500">
                            <li>
                                <Link href="/dashboard" className="inline-flex items-center gap-1.5 hover:text-indigo-600 font-medium text-slate-700 transition-colors">
                                    <span>{__('general.main_saas_app') || 'Main SaaS App'}</span>
                                    <ExternalLink className="w-3.5 h-3.5 text-slate-400" />
                                </Link>
                            </li>
                            <li><Link href="/erp" className="hover:text-indigo-600 transition-colors">ERP Suite</Link></li>
                            <li><Link href="/crm" className="hover:text-indigo-600 transition-colors">CRM Portal</Link></li>
                        </ul>
                    </div>
                </div>

                {/* Bottom Row */}
                <div className="flex flex-col md:flex-row items-center justify-between border-t border-slate-200 pt-8 gap-4">
                    <div className="flex items-center gap-3">
                        <div className="p-1 rounded-lg bg-indigo-50 border border-indigo-100">
                            <ApplicationLogo className="h-5 w-auto fill-current text-indigo-600" />
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="text-sm font-bold text-slate-900">musoftware</span>
                            <span className="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-full border border-indigo-100 uppercase">Marketplace</span>
                        </div>
                        <span className="text-xs text-slate-400 ms-2">© {new Date().getFullYear()} Musoftware International Ltd.</span>
                    </div>

                    <div className="flex items-center gap-6">
                        <div className="flex items-center gap-4">
                            <a href="#" className="text-slate-400 hover:text-indigo-600 transition-colors"><Share2 className="h-4.5 w-4.5" /></a>
                            <a href="#" className="text-slate-400 hover:text-indigo-600 transition-colors"><MessageCircle className="h-4.5 w-4.5" /></a>
                            <a href="#" className="text-slate-400 hover:text-indigo-600 transition-colors"><Mail className="h-4.5 w-4.5" /></a>
                            <a href="#" className="text-slate-400 hover:text-indigo-600 transition-colors"><Users className="h-4.5 w-4.5" /></a>
                        </div>
                        
                        <div className="hidden md:flex items-center gap-4 ms-4">
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" size="sm" className="flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-indigo-600 px-2 py-1 h-auto focus-visible:ring-0">
                                        <Globe className="h-4 w-4 text-slate-500" />
                                        <span>{currentLocale === 'ar' ? 'العربية' : 'English'}</span>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-36">
                                    <DropdownMenuItem 
                                        onClick={() => handleLanguageChange('en')}
                                        className={`cursor-pointer ${currentLocale === 'en' ? 'font-bold text-indigo-600 bg-indigo-50' : ''}`}
                                    >
                                        English
                                    </DropdownMenuItem>
                                    <DropdownMenuItem 
                                        onClick={() => handleLanguageChange('ar')}
                                        className={`cursor-pointer ${currentLocale === 'ar' ? 'font-bold text-indigo-600 bg-indigo-50' : ''}`}
                                    >
                                        العربية
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    );
}
