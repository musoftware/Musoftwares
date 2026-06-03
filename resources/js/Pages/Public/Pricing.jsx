import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Check, Info, ArrowRight } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';

export default function Pricing({ currency = 'USD' }) {
    return (
        <PublicLayout>
            <Head>
                <title>{__('frontend.pricing.meta_title')}</title>
                <meta name="description" content={__('frontend.pricing.meta_description')} />
            </Head>

            <section className="pt-32 pb-16 lg:pt-48 lg:pb-24 bg-white border-b border-slate-100">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-20">
                        <h1 className="text-5xl lg:text-7xl font-extrabold text-slate-900 tracking-tight leading-[1.1] mb-6">
                            {__('frontend.pricing.title')}
                        </h1>
                        <p className="text-xl text-slate-500 font-light">
                            {__('frontend.pricing.subtitle')}
                        </p>
                    </div>

                    {/* SaaS Subscriptions Section */}
                    <div className="mb-12">
                        <h2 className="text-3xl font-bold text-slate-900 mb-4 text-center">{__('frontend.pricing.saas_plans')}</h2>
                        <p className="text-slate-500 font-light text-center mb-12">{__('frontend.pricing.saas_subtitle')}</p>
                        
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                            {/* Starter */}
                            <div className="flex flex-col p-8 rounded-3xl bg-white border border-slate-200 hover:border-slate-300 transition-all shadow-sm">
                                <div className="mb-8">
                                    <h3 className="text-2xl font-bold text-slate-900 mb-2">{__('frontend.pricing.starter.title')}</h3>
                                    <p className="text-slate-500 font-light mb-6">{__('frontend.pricing.starter.desc')}</p>
                                    <div className="flex items-baseline gap-2">
                                        <span className="text-4xl font-extrabold text-slate-900">{__('frontend.pricing.starter.price')}</span>
                                        <span className="text-slate-500 font-light">{__('frontend.pricing.starter.interval')}</span>
                                    </div>
                                </div>
                                <div className="flex-1">
                                    <ul className="space-y-4 mb-8">
                                        <li className="flex gap-3 text-slate-600 font-light"><Check className="w-5 h-5 text-slate-400 shrink-0" /> {__('frontend.pricing.starter.f1')}</li>
                                        <li className="flex gap-3 text-slate-600 font-light"><Check className="w-5 h-5 text-slate-400 shrink-0" /> {__('frontend.pricing.starter.f2')}</li>
                                        <li className="flex gap-3 text-slate-600 font-light"><Check className="w-5 h-5 text-slate-400 shrink-0" /> {__('frontend.pricing.starter.f3')}</li>
                                    </ul>
                                </div>
                                <Button className="w-full bg-slate-50 hover:bg-slate-100 text-slate-900 border border-slate-200 rounded-full h-12 font-semibold">
                                    {__('frontend.pricing.starter.cta')}
                                </Button>
                            </div>

                            {/* Pro */}
                            <div className="flex flex-col p-8 rounded-3xl bg-slate-900 text-white shadow-xl relative transform md:-translate-y-4">
                                <div className="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2">
                                    <span className="bg-slate-100 text-slate-900 text-xs font-bold uppercase tracking-widest py-1 px-3 rounded-full">
                                        {__('frontend.pricing.pro.badge')}
                                    </span>
                                </div>
                                <div className="mb-8 mt-2">
                                    <h3 className="text-2xl font-bold text-white mb-2">{__('frontend.pricing.pro.title')}</h3>
                                    <p className="text-slate-400 font-light mb-6">{__('frontend.pricing.pro.desc')}</p>
                                    <div className="flex items-baseline gap-2">
                                        <span className="text-4xl font-extrabold text-white">{__('frontend.pricing.pro.price')}</span>
                                        <span className="text-slate-400 font-light">{__('frontend.pricing.pro.interval')}</span>
                                    </div>
                                </div>
                                <div className="flex-1">
                                    <ul className="space-y-4 mb-8">
                                        <li className="flex gap-3 text-slate-300 font-light"><Check className="w-5 h-5 text-slate-500 shrink-0" /> {__('frontend.pricing.pro.f1')}</li>
                                        <li className="flex gap-3 text-slate-300 font-light"><Check className="w-5 h-5 text-slate-500 shrink-0" /> {__('frontend.pricing.pro.f2')}</li>
                                        <li className="flex gap-3 text-slate-300 font-light"><Check className="w-5 h-5 text-slate-500 shrink-0" /> {__('frontend.pricing.pro.f3')}</li>
                                    </ul>
                                </div>
                                <Button className="w-full bg-white hover:bg-slate-100 text-slate-900 rounded-full h-12 font-bold">
                                    {__('frontend.pricing.pro.cta')}
                                </Button>
                            </div>

                            {/* Enterprise */}
                            <div className="flex flex-col p-8 rounded-3xl bg-white border border-slate-200 hover:border-slate-300 transition-all shadow-sm">
                                <div className="mb-8">
                                    <h3 className="text-2xl font-bold text-slate-900 mb-2">{__('frontend.pricing.enterprise.title')}</h3>
                                    <p className="text-slate-500 font-light mb-6">{__('frontend.pricing.enterprise.desc')}</p>
                                    <div className="flex items-baseline gap-2">
                                        <span className="text-4xl font-extrabold text-slate-900">{__('frontend.pricing.enterprise.price')}</span>
                                        <span className="text-slate-500 font-light">{__('frontend.pricing.enterprise.interval')}</span>
                                    </div>
                                </div>
                                <div className="flex-1">
                                    <ul className="space-y-4 mb-8">
                                        <li className="flex gap-3 text-slate-600 font-light"><Check className="w-5 h-5 text-slate-400 shrink-0" /> {__('frontend.pricing.enterprise.f1')}</li>
                                        <li className="flex gap-3 text-slate-600 font-light"><Check className="w-5 h-5 text-slate-400 shrink-0" /> {__('frontend.pricing.enterprise.f2')}</li>
                                        <li className="flex gap-3 text-slate-600 font-light"><Check className="w-5 h-5 text-slate-400 shrink-0" /> {__('frontend.pricing.enterprise.f3')}</li>
                                    </ul>
                                </div>
                                <Button className="w-full bg-slate-50 hover:bg-slate-100 text-slate-900 border border-slate-200 rounded-full h-12 font-semibold">
                                    {__('frontend.pricing.enterprise.cta')}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="py-24 lg:py-32 bg-slate-50">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <h2 className="text-4xl font-extrabold text-slate-900 mb-4">{__('frontend.pricing.engineering_blocks')}</h2>
                        <p className="text-lg text-slate-500 font-light">{__('frontend.pricing.blocks_subtitle')}</p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                        
                        {/* Maintenance Block */}
                        <div className="flex flex-col p-8 rounded-3xl bg-white border border-slate-200 hover:border-slate-300 transition-all shadow-sm">
                            <div className="mb-8">
                                <h3 className="text-2xl font-bold text-slate-900 mb-2">{__('frontend.pricing.maintenance.title')}</h3>
                                <p className="text-slate-500 font-light mb-6">{__('frontend.pricing.maintenance.desc')}</p>
                                <div className="flex items-baseline gap-2">
                                    <span className="text-4xl font-extrabold text-slate-900">{__('frontend.pricing.maintenance.hrs')}</span>
                                </div>
                            </div>
                            <div className="flex-1">
                                <ul className="space-y-4 mb-8">
                                    <li className="flex gap-3 text-slate-600 font-light"><Check className="w-5 h-5 text-slate-400 shrink-0" /> {__('frontend.pricing.maintenance.f1')}</li>
                                    <li className="flex gap-3 text-slate-600 font-light"><Check className="w-5 h-5 text-slate-400 shrink-0" /> {__('frontend.pricing.maintenance.f2')}</li>
                                    <li className="flex gap-3 text-slate-600 font-light"><Check className="w-5 h-5 text-slate-400 shrink-0" /> {__('frontend.pricing.maintenance.f3')}</li>
                                </ul>
                            </div>
                            <Button className="w-full bg-slate-50 hover:bg-slate-100 text-slate-900 border border-slate-200 rounded-full h-12 font-semibold">
                                {__('frontend.pricing.maintenance.cta')}
                            </Button>
                        </div>

                        {/* Implementation Block (Most Popular) */}
                        <div className="flex flex-col p-8 rounded-3xl bg-slate-900 text-white shadow-xl relative transform md:-translate-y-4">
                            <div className="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2">
                                <span className="bg-slate-100 text-slate-900 text-xs font-bold uppercase tracking-widest py-1 px-3 rounded-full">
                                    {__('frontend.pricing.implementation.badge')}
                                </span>
                            </div>
                            <div className="mb-8 mt-2">
                                <h3 className="text-2xl font-bold text-white mb-2">{__('frontend.pricing.implementation.title')}</h3>
                                <p className="text-slate-400 font-light mb-6">{__('frontend.pricing.implementation.desc')}</p>
                                <div className="flex items-baseline gap-2">
                                    <span className="text-4xl font-extrabold text-white">{__('frontend.pricing.implementation.hrs')}</span>
                                </div>
                            </div>
                            <div className="flex-1">
                                <ul className="space-y-4 mb-8">
                                    <li className="flex gap-3 text-slate-300 font-light"><Check className="w-5 h-5 text-slate-500 shrink-0" /> {__('frontend.pricing.implementation.f1')}</li>
                                    <li className="flex gap-3 text-slate-300 font-light"><Check className="w-5 h-5 text-slate-500 shrink-0" /> {__('frontend.pricing.implementation.f2')}</li>
                                    <li className="flex gap-3 text-slate-300 font-light"><Check className="w-5 h-5 text-slate-500 shrink-0" /> {__('frontend.pricing.implementation.f3')}</li>
                                </ul>
                            </div>
                            <Button className="w-full bg-white hover:bg-slate-100 text-slate-900 rounded-full h-12 font-bold">
                                {__('frontend.pricing.implementation.cta')}
                            </Button>
                        </div>

                        {/* Growth Partner */}
                        <div className="flex flex-col p-8 rounded-3xl bg-white border border-slate-200 hover:border-slate-300 transition-all shadow-sm">
                            <div className="mb-8">
                                <h3 className="text-2xl font-bold text-slate-900 mb-2">{__('frontend.pricing.growth.title')}</h3>
                                <p className="text-slate-500 font-light mb-6">{__('frontend.pricing.growth.desc')}</p>
                                <div className="flex items-baseline gap-2">
                                    <span className="text-4xl font-extrabold text-slate-900">{__('frontend.pricing.growth.hrs')}</span>
                                </div>
                            </div>
                            <div className="flex-1">
                                <ul className="space-y-4 mb-8">
                                    <li className="flex gap-3 text-slate-600 font-light"><Check className="w-5 h-5 text-slate-400 shrink-0" /> {__('frontend.pricing.growth.f1')}</li>
                                    <li className="flex gap-3 text-slate-600 font-light"><Check className="w-5 h-5 text-slate-400 shrink-0" /> {__('frontend.pricing.growth.f2')}</li>
                                    <li className="flex gap-3 text-slate-600 font-light"><Check className="w-5 h-5 text-slate-400 shrink-0" /> {__('frontend.pricing.growth.f3')}</li>
                                </ul>
                            </div>
                            <Button className="w-full bg-slate-50 hover:bg-slate-100 text-slate-900 border border-slate-200 rounded-full h-12 font-semibold">
                                {__('frontend.pricing.growth.cta')}
                            </Button>
                        </div>

                    </div>

                    {/* Disclaimer */}
                    <div className="mt-16 mb-24 text-center max-w-2xl mx-auto flex items-start sm:items-center justify-center gap-3 p-6 bg-white rounded-2xl border border-slate-100 shadow-sm">
                        <Info className="w-5 h-5 text-slate-400 shrink-0 mt-0.5 sm:mt-0" />
                        <p className="text-sm text-slate-600 font-light text-left sm:text-center" dangerouslySetInnerHTML={{ __html: __('frontend.pricing.disclaimer') }}></p>
                    </div>

                    {/* FAQ Section */}
                    <div className="max-w-4xl mx-auto pt-10">
                        <h2 className="text-3xl font-bold text-slate-900 mb-12 text-center">{__('frontend.pricing.faq.title')}</h2>
                        
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-10">
                            <div>
                                <h4 className="text-lg font-bold text-slate-900 mb-2">{__('frontend.pricing.faq.q1')}</h4>
                                <p className="text-slate-500 font-light leading-relaxed">{__('frontend.pricing.faq.a1')}</p>
                            </div>
                            
                            <div>
                                <h4 className="text-lg font-bold text-slate-900 mb-2">{__('frontend.pricing.faq.q2')}</h4>
                                <p className="text-slate-500 font-light leading-relaxed">{__('frontend.pricing.faq.a2')}</p>
                            </div>
                            
                            <div>
                                <h4 className="text-lg font-bold text-slate-900 mb-2">{__('frontend.pricing.faq.q3')}</h4>
                                <p className="text-slate-500 font-light leading-relaxed">{__('frontend.pricing.faq.a3')}</p>
                            </div>
                            
                            <div>
                                <h4 className="text-lg font-bold text-slate-900 mb-2">{__('frontend.pricing.faq.q4')}</h4>
                                <p className="text-slate-500 font-light leading-relaxed">{__('frontend.pricing.faq.a4')}</p>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </PublicLayout>
    );
}
