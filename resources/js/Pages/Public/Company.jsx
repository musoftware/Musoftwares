import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { __ } from '@/lib/i18n';

export default function Company() {
    return (
        <PublicLayout>
            <Head>
                <title>{__('frontend.company.meta_title')}</title>
                <meta name="description" content={__('frontend.company.meta_description')} />
            </Head>

            <section className="pt-32 pb-24 lg:pt-48 lg:pb-32 bg-white">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    
                    {/* Header */}
                    <div className="max-w-4xl mb-24">
                        <h1 className="text-5xl lg:text-7xl font-extrabold text-slate-900 tracking-tight leading-[1.1] mb-6">
                            {__('frontend.company.title')}
                        </h1>
                        <p className="text-xl text-slate-500 font-light">
                            {__('frontend.company.subtitle')}
                        </p>
                    </div>

                    {/* History Section */}
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-24">
                        
                        {/* Left Column: Timeline / Stats */}
                        <div className="lg:col-span-4 border-t lg:border-t-0 lg:border-r border-slate-100 pt-12 lg:pt-0 lg:pr-12">
                            <h2 className="text-sm font-bold text-slate-900 tracking-widest uppercase mb-12">{__('frontend.company.timeline_title')}</h2>
                            
                            <div className="space-y-12">
                                <div>
                                    <h3 className="text-2xl font-bold text-slate-900 mb-2">{__('frontend.company.y2013')}</h3>
                                    <p className="text-slate-500 font-light">{__('frontend.company.y2013_desc')}</p>
                                </div>
                                <div>
                                    <h3 className="text-2xl font-bold text-slate-900 mb-2">{__('frontend.company.y2014')}</h3>
                                    <p className="text-slate-500 font-light">{__('frontend.company.y2014_desc')}</p>
                                </div>
                                <div>
                                    <h3 className="text-2xl font-bold text-slate-900 mb-2">{__('frontend.company.today')}</h3>
                                    <p className="text-slate-500 font-light">{__('frontend.company.today_desc')}</p>
                                </div>
                            </div>
                        </div>

                        {/* Right Column: Narrative */}
                        <div className="lg:col-span-8 pt-12 lg:pt-0">
                            <h2 className="text-3xl font-bold text-slate-900 mb-8">{__('frontend.company.background_title')}</h2>
                            
                            <div className="prose prose-slate prose-lg text-slate-500 font-light max-w-none">
                                <p className="mb-6">{__('frontend.company.p1')}</p>
                                <p className="mb-6">{__('frontend.company.p2')}</p>
                                
                                <ul className="list-disc pl-5 mb-8 space-y-2">
                                    <li>{__('frontend.company.l1')}</li>
                                    <li>{__('frontend.company.l2')}</li>
                                    <li>{__('frontend.company.l3')}</li>
                                    <li>{__('frontend.company.l4')}</li>
                                    <li>{__('frontend.company.l5')}</li>
                                </ul>

                                <p className="mb-6">{__('frontend.company.p3')}</p>

                                <div className="p-8 mt-12 bg-slate-50 border border-slate-100 rounded-3xl">
                                    <blockquote className="text-2xl font-light text-slate-900 leading-relaxed mb-6">
                                        {__('frontend.company.quote')}
                                    </blockquote>
                                    <p className="text-sm font-bold text-slate-900 uppercase tracking-widest">{__('frontend.company.ceo')}</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    {/* Process Section */}
                    <div className="mt-32 pt-24 border-t border-slate-100">
                        <div className="max-w-3xl mb-16">
                            <h2 className="text-sm font-bold text-slate-900 tracking-widest uppercase mb-4">{__('frontend.company.how_we_work')}</h2>
                            <h3 className="text-4xl font-extrabold text-slate-900 tracking-tight">{__('frontend.company.process_title')}</h3>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                            <div className="relative p-8 rounded-3xl bg-slate-50 border border-slate-100 transition-all hover:bg-slate-100">
                                <span className="text-5xl font-extrabold text-slate-200 absolute top-6 right-6">0</span>
                                <h4 className="text-xl font-bold text-slate-900 mb-4 mt-8">{__('frontend.company.s0')}</h4>
                                <p className="text-slate-500 font-light leading-relaxed">
                                    {__('frontend.company.s0_desc')}
                                </p>
                            </div>
                            
                            <div className="relative p-8 rounded-3xl bg-slate-50 border border-slate-100 transition-all hover:bg-slate-100">
                                <span className="text-5xl font-extrabold text-slate-200 absolute top-6 right-6">1</span>
                                <h4 className="text-xl font-bold text-slate-900 mb-4 mt-8">{__('frontend.company.s1')}</h4>
                                <p className="text-slate-500 font-light leading-relaxed">
                                    {__('frontend.company.s1_desc')}
                                </p>
                            </div>
                            
                            <div className="relative p-8 rounded-3xl bg-slate-50 border border-slate-100 transition-all hover:bg-slate-100">
                                <span className="text-5xl font-extrabold text-slate-200 absolute top-6 right-6">2</span>
                                <h4 className="text-xl font-bold text-slate-900 mb-4 mt-8">{__('frontend.company.s2')}</h4>
                                <p className="text-slate-500 font-light leading-relaxed">
                                    {__('frontend.company.s2_desc')}
                                </p>
                            </div>
                            
                            <div className="relative p-8 rounded-3xl bg-slate-50 border border-slate-100 transition-all hover:bg-slate-100">
                                <span className="text-5xl font-extrabold text-slate-200 absolute top-6 right-6">3</span>
                                <h4 className="text-xl font-bold text-slate-900 mb-4 mt-8">{__('frontend.company.s3')}</h4>
                                <p className="text-slate-500 font-light leading-relaxed">
                                    {__('frontend.company.s3_desc')}
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </PublicLayout>
    );
}
