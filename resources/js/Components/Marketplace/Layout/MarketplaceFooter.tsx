import React from 'react';
import { Link } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Globe, DollarSign, MessageCircle, Share2, Mail, Users } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function MarketplaceFooter() {
    return (
        <footer className="border-t border-gray-200 bg-white pt-16 pb-8 mt-auto">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                {/* Top Link Columns */}
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-8 mb-16">
                    <div>
                        <h4 className="font-bold text-gray-900 mb-6">{__('general.categories')}</h4>
                        <ul className="space-y-4 text-sm text-gray-500">
                            <li><Link href="/marketplace/services?category=graphics" className="hover:underline">{__('general.graphics_design')}</Link></li>
                            <li><Link href="/marketplace/services?category=programming" className="hover:underline">{__('general.programming_tech')}</Link></li>
                            <li><Link href="/marketplace/services?category=marketing" className="hover:underline">{__('general.digital_marketing')}</Link></li>
                            <li><Link href="/marketplace/services?category=video" className="hover:underline">{__('general.video_animation')}</Link></li>
                            <li><Link href="/marketplace/services?category=writing" className="hover:underline">{__('general.writing_translation')}</Link></li>
                        </ul>
                    </div>

                    <div>
                        <h4 className="font-bold text-gray-900 mb-6">{__('general.about')}</h4>
                        <ul className="space-y-4 text-sm text-gray-500">
                            <li><Link href="/about" className="hover:underline">{__('general.careers')}</Link></li>
                            <li><Link href="/terms" className="hover:underline">{__('general.terms_of_service')}</Link></li>
                            <li><Link href="/privacy" className="hover:underline">{__('general.privacy_policy')}</Link></li>
                            <li><Link href="/investors" className="hover:underline">{__('general.investor_relations')}</Link></li>
                        </ul>
                    </div>

                    <div>
                        <h4 className="font-bold text-gray-900 mb-6">{__('general.support')}</h4>
                        <ul className="space-y-4 text-sm text-gray-500">
                            <li><Link href="/support" className="hover:underline">{__('general.help_support')}</Link></li>
                            <li><Link href="/trust-safety" className="hover:underline">{__('general.trust_safety')}</Link></li>
                            <li><Link href="/marketplace/services/create" className="hover:underline">{__('general.selling_on_musoftware')}</Link></li>
                            <li><Link href="/marketplace" className="hover:underline">{__('general.buying_on_musoftware')}</Link></li>
                        </ul>
                    </div>

                    <div>
                        <h4 className="font-bold text-gray-900 mb-6">{__('general.community')}</h4>
                        <ul className="space-y-4 text-sm text-gray-500">
                            <li><Link href="/blog" className="hover:underline">{__('general.blog')}</Link></li>
                            <li><Link href="/forum" className="hover:underline">{__('general.forum')}</Link></li>
                            <li><Link href="/community" className="hover:underline">{__('general.community_standards')}</Link></li>
                            <li><Link href="/podcast" className="hover:underline">{__('general.podcast')}</Link></li>
                        </ul>
                    </div>

                    <div>
                        <h4 className="font-bold text-gray-900 mb-6">{__('general.more_from_musoftware')}</h4>
                        <ul className="space-y-4 text-sm text-gray-500">
                            <li><Link href="/erp" className="hover:underline">{__('general.musoftware_erp')}</Link></li>
                            <li><Link href="/crm" className="hover:underline">{__('general.musoftware_crm')}</Link></li>
                            <li><Link href="/pos" className="hover:underline">{__('general.point_of_sale')}</Link></li>
                            <li><Link href="/business" className="hover:underline">{__('general.musoftware_business')}</Link></li>
                        </ul>
                    </div>
                </div>

                {/* Bottom Row */}
                <div className="flex flex-col md:flex-row items-center justify-between border-t border-gray-200 pt-8 gap-4">
                    <div className="flex items-center gap-4">
                        <ApplicationLogo className="h-6 w-auto fill-current text-gray-400 grayscale" />
                        <span className="text-sm text-gray-400">© {new Date().getFullYear()} Musoftware International Ltd.</span>
                    </div>

                    <div className="flex items-center gap-6">
                        <div className="flex items-center gap-4">
                            <a href="#" className="text-gray-400 hover:text-gray-900"><Share2 className="h-5 w-5" /></a>
                            <a href="#" className="text-gray-400 hover:text-gray-900"><MessageCircle className="h-5 w-5" /></a>
                            <a href="#" className="text-gray-400 hover:text-gray-900"><Mail className="h-5 w-5" /></a>
                            <a href="#" className="text-gray-400 hover:text-gray-900"><Users className="h-5 w-5" /></a>
                        </div>
                        
                        <div className="hidden md:flex items-center gap-4 ms-4">
                            <button className="flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-900">
                                <Globe className="h-4 w-4" /> {__('general.english')}</button>
                            <button className="flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-900">
                                <DollarSign className="h-4 w-4" /> {__('general.usd')}</button>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    );
}
