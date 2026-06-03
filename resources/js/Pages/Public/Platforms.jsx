import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { LayoutDashboard, Workflow, Globe, Monitor, ArrowRight } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Platforms() {
    return (
        <PublicLayout>
            <Head>
                <title>{__('frontend.platforms.meta_title')}</title>
                <meta name="description" content={__('frontend.platforms.meta_description')} />
            </Head>

            <section className="pt-32 pb-24 lg:pt-48 lg:pb-32 bg-white border-b border-slate-100">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="mb-20 max-w-3xl">
                        <h1 className="text-5xl lg:text-7xl font-extrabold text-slate-900 tracking-tight leading-[1.1] mb-6">
                            {__('frontend.platforms.title')}
                        </h1>
                        <p className="text-xl text-slate-500 font-light">
                            {__('frontend.platforms.subtitle')}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div className="p-10 rounded-3xl bg-slate-50 border border-slate-100 transition-colors group hover:bg-slate-100">
                            <LayoutDashboard className="w-10 h-10 text-slate-900 mb-6" />
                            <h3 className="text-3xl font-bold text-slate-900 mb-4">{__('frontend.platforms.p1_title')}</h3>
                            <p className="text-lg text-slate-500 font-light mb-6">
                                {__('frontend.platforms.p1_desc')}
                            </p>
                            <ul className="space-y-3 mb-8">
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>{__('frontend.platforms.p1_l1')}</li>
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>{__('frontend.platforms.p1_l2')}</li>
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>{__('frontend.platforms.p1_l3')}</li>
                            </ul>
                            <Link href="/pricing" className="inline-flex items-center gap-2 font-semibold text-slate-900 hover:text-slate-600 transition-colors">
                                {__('frontend.platforms.view_pricing')} <ArrowRight className="w-4 h-4" />
                            </Link>
                        </div>
                        
                        <div className="p-10 rounded-3xl bg-slate-50 border border-slate-100 transition-colors group hover:bg-slate-100">
                            <Workflow className="w-10 h-10 text-slate-900 mb-6" />
                            <h3 className="text-3xl font-bold text-slate-900 mb-4">{__('frontend.platforms.p2_title')}</h3>
                            <p className="text-lg text-slate-500 font-light mb-6">
                                {__('frontend.platforms.p2_desc')}
                            </p>
                            <ul className="space-y-3 mb-8">
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>{__('frontend.platforms.p2_l1')}</li>
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>{__('frontend.platforms.p2_l2')}</li>
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>{__('frontend.platforms.p2_l3')}</li>
                            </ul>
                            <Link href="/pricing" className="inline-flex items-center gap-2 font-semibold text-slate-900 hover:text-slate-600 transition-colors">
                                {__('frontend.platforms.view_pricing')} <ArrowRight className="w-4 h-4" />
                            </Link>
                        </div>

                        <div className="p-10 rounded-3xl bg-slate-50 border border-slate-100 transition-colors group hover:bg-slate-100">
                            <Globe className="w-10 h-10 text-slate-900 mb-6" />
                            <h3 className="text-3xl font-bold text-slate-900 mb-4">{__('frontend.platforms.p3_title')}</h3>
                            <p className="text-lg text-slate-500 font-light mb-6">
                                {__('frontend.platforms.p3_desc')}
                            </p>
                            <ul className="space-y-3 mb-8">
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>{__('frontend.platforms.p3_l1')}</li>
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>{__('frontend.platforms.p3_l2')}</li>
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>{__('frontend.platforms.p3_l3')}</li>
                            </ul>
                            <Link href="/pricing" className="inline-flex items-center gap-2 font-semibold text-slate-900 hover:text-slate-600 transition-colors">
                                {__('frontend.platforms.view_pricing')} <ArrowRight className="w-4 h-4" />
                            </Link>
                        </div>

                        <div className="p-10 rounded-3xl bg-slate-50 border border-slate-100 transition-colors group hover:bg-slate-100">
                            <Monitor className="w-10 h-10 text-slate-900 mb-6" />
                            <h3 className="text-3xl font-bold text-slate-900 mb-4">{__('frontend.platforms.p4_title')}</h3>
                            <p className="text-lg text-slate-500 font-light mb-6">
                                {__('frontend.platforms.p4_desc')}
                            </p>
                            <ul className="space-y-3 mb-8">
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>{__('frontend.platforms.p4_l1')}</li>
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>{__('frontend.platforms.p4_l2')}</li>
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>{__('frontend.platforms.p4_l3')}</li>
                            </ul>
                            <Link href="/pricing" className="inline-flex items-center gap-2 font-semibold text-slate-900 hover:text-slate-600 transition-colors">
                                {__('frontend.platforms.view_pricing')} <ArrowRight className="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
