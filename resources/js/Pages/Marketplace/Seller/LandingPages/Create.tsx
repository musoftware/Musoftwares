import React from 'react';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { ArrowLeft, Globe, Sparkles, Layout } from 'lucide-react';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { SellerNav } from '@/Components/Marketplace/Seller/SellerNav';
import { __ } from '@/lib/i18n';

interface CreateLandingPageProps {
    service: {
        id: number;
        title: string;
        tagline?: string;
    };
}

export default function Create({ service }: CreateLandingPageProps) {
    const { data, setData, post, processing, errors } = useForm({
        hero_title: service?.title || '',
        hero_description: service?.tagline || '',
        hero_cta_text: __('marketplace.lp_default_cta'),
        slug: (service?.title || 'landing-page').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''),
        template: 'modern',
        meta_title: service?.title || '',
        meta_description: service?.tagline || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/services/${service.id}/landing-page`);
    };

    const templates = [
        { id: 'modern', name: __('marketplace.lp_tpl_modern_name'), desc: __('marketplace.lp_tpl_modern_desc') },
        { id: 'glassmorphism', name: __('marketplace.lp_tpl_glass_name'), desc: __('marketplace.lp_tpl_glass_desc') },
        { id: 'cyberpunk', name: __('marketplace.lp_tpl_cyberpunk_name'), desc: __('marketplace.lp_tpl_cyberpunk_desc') },
        { id: 'minimal', name: __('marketplace.lp_tpl_minimal_name'), desc: __('marketplace.lp_tpl_minimal_desc') },
        { id: 'dark-mode', name: __('marketplace.lp_tpl_dark_name'), desc: __('marketplace.lp_tpl_dark_desc') },
    ];

    return (
        <MarketplaceLayout>
            <Head title={__('marketplace.lp_create_page_title', { title: service?.title })} />

            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                <div className="flex items-center justify-between">
                    <Link
                        href="/marketplace/landing-pages"
                        className="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-900 transition"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        {__('general.back_to_landing_pages')}
                    </Link>
                </div>

                <ModulePageHeader
                    title={__('marketplace.lp_create_heading', { title: service?.title })}
                    description={__('marketplace.lp_create_desc')}
                />

                <SellerNav />

                <OperationalCard
                    title={__('marketplace.lp_setup_title')}
                    description={__('marketplace.lp_setup_desc')}
                >
                    <form onSubmit={handleSubmit} className="p-6 space-y-6">
                        <div className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">
                                    {__('marketplace.lp_hero_title')} <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.hero_title}
                                    onChange={(e) => setData('hero_title', e.target.value)}
                                    className="w-full rounded-xl border border-slate-200 py-2.5 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                    placeholder={__('marketplace.lp_hero_title_placeholder')}
                                    aria-label={__('marketplace.lp_hero_title')}
                                    required
                                />
                                {errors.hero_title && <p className="text-xs text-red-500 mt-1">{errors.hero_title}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">
                                    {__('marketplace.lp_hero_subtitle')}
                                </label>
                                <textarea
                                    rows={3}
                                    value={data.hero_description}
                                    onChange={(e) => setData('hero_description', e.target.value)}
                                    className="w-full rounded-xl border border-slate-200 p-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                    placeholder={__('marketplace.lp_hero_subtitle_placeholder')}
                                    aria-label={__('marketplace.lp_hero_subtitle')}
                                />
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">
                                        {__('marketplace.lp_url_slug')} <span className="text-red-500">*</span>
                                    </label>
                                    <div className="flex items-center">
                                        <span className="bg-slate-100 border border-r-0 border-slate-200 text-slate-500 px-3 py-2.5 rounded-l-xl text-xs font-mono">
                                            /s/
                                        </span>
                                        <input
                                            type="text"
                                            value={data.slug}
                                            aria-label={__('marketplace.lp_url_slug')}
                                            onChange={(e) => setData('slug', e.target.value)}
                                            className="w-full rounded-r-xl border border-slate-200 py-2.5 px-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                            required
                                        />
                                    </div>
                                    {errors.slug && <p className="text-xs text-red-500 mt-1">{errors.slug}</p>}
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">
                                        {__('marketplace.lp_cta_text')}
                                    </label>
                                    <input
                                        type="text"
                                        value={data.hero_cta_text}
                                        aria-label={__('marketplace.lp_cta_text')}
                                        onChange={(e) => setData('hero_cta_text', e.target.value)}
                                        className="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                </div>
                            </div>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold uppercase text-slate-500 mb-3">
                                {__('marketplace.lp_choose_template')}
                            </label>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                {templates.map((tmpl) => (
                                    <label
                                        key={tmpl.id}
                                        className={`cursor-pointer border rounded-xl p-4 flex flex-col justify-between transition ${
                                            data.template === tmpl.id
                                                ? 'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20'
                                                : 'border-slate-200 hover:border-slate-300 bg-white'
                                        }`}
                                    >
                                        <div className="flex items-center justify-between mb-1">
                                            <span className="font-bold text-slate-900 text-sm">{tmpl.name}</span>
                                            <input
                                                type="radio"
                                                name="template"
                                                value={tmpl.id}
                                                checked={data.template === tmpl.id}
                                                onChange={(e) => setData('template', e.target.value)}
                                                className="text-indigo-600 focus:ring-indigo-500"
                                            />
                                        </div>
                                        <p className="text-xs text-slate-500">{tmpl.desc}</p>
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                            <Link
                                href="/marketplace/landing-pages"
                                className="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900"
                            >
                                {__('general.cancel')}
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 transition shadow-sm"
                            >
                                <Sparkles className="w-4 h-4" />
                                {__('marketplace.lp_create_button')}
                            </button>
                        </div>
                    </form>
                </OperationalCard>
            </div>
        </MarketplaceLayout>
    );
}
