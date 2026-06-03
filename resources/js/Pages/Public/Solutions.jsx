import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Briefcase, Building2, Server, GraduationCap, Code2, ShieldCheck } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Solutions() {
    return (
        <PublicLayout>
            <Head>
                <title>{__('frontend.solutions.meta_title')}</title>
                <meta name="description" content={__('frontend.solutions.meta_description')} />
            </Head>

            <section className="pt-32 pb-24 lg:pt-48 lg:pb-32 bg-white border-b border-slate-100">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="mb-20 max-w-3xl">
                        <h1 className="text-5xl lg:text-7xl font-extrabold text-slate-900 tracking-tight leading-[1.1] mb-6">
                            {__('frontend.solutions.title')}
                        </h1>
                        <p className="text-xl text-slate-500 font-light">
                            {__('frontend.solutions.subtitle')}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        {[
                            { title: __('frontend.solutions.i1_title'), icon: <Building2 className="w-6 h-6" />, text: __('frontend.solutions.i1_desc') },
                            { title: __('frontend.solutions.i2_title'), icon: <Briefcase className="w-6 h-6" />, text: __('frontend.solutions.i2_desc') },
                            { title: __('frontend.solutions.i3_title'), icon: <Server className="w-6 h-6" />, text: __('frontend.solutions.i3_desc') },
                            { title: __('frontend.solutions.i4_title'), icon: <GraduationCap className="w-6 h-6" />, text: __('frontend.solutions.i4_desc') },
                            { title: __('frontend.solutions.i5_title'), icon: <Code2 className="w-6 h-6" />, text: __('frontend.solutions.i5_desc') },
                            { title: __('frontend.solutions.i6_title'), icon: <ShieldCheck className="w-6 h-6" />, text: __('frontend.solutions.i6_desc') }
                        ].map((ind, i) => (
                            <div key={i} className="p-8 rounded-3xl bg-slate-50 border border-slate-100 transition-all group hover:bg-slate-100">
                                <div className="text-slate-900 mb-6">{ind.icon}</div>
                                <h4 className="text-xl font-bold text-slate-900 mb-2">{ind.title}</h4>
                                <p className="text-slate-500 font-light leading-relaxed">{ind.text}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <section className="py-24 lg:py-32 bg-white">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="mb-16 max-w-3xl">
                        <h2 className="text-4xl font-extrabold text-slate-900 tracking-tight mb-4">{__('frontend.solutions.portfolio_title')}</h2>
                        <p className="text-xl text-slate-500 font-light">
                            {__('frontend.solutions.portfolio_subtitle')}
                        </p>
                    </div>

                    <div className="space-y-12">
                        {/* Case Study 1 */}
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 p-10 rounded-3xl bg-slate-50 border border-slate-100 transition-all hover:bg-slate-100">
                            <div className="lg:col-span-1 border-b lg:border-b-0 lg:border-r border-slate-200 pb-8 lg:pb-0 lg:pr-8">
                                <span className="text-xs font-bold uppercase tracking-widest text-slate-900 mb-2 block">{__('frontend.solutions.cs1_cat')}</span>
                                <h3 className="text-2xl font-bold text-slate-900">{__('frontend.solutions.cs1_title')}</h3>
                            </div>
                            <div className="lg:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-8">
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">{__('frontend.solutions.cs1_h1')}</h4>
                                    <p className="text-sm text-slate-500 font-light leading-relaxed">{__('frontend.solutions.cs1_d1')}</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">{__('frontend.solutions.cs1_h2')}</h4>
                                    <p className="text-sm text-slate-500 font-light leading-relaxed">{__('frontend.solutions.cs1_d2')}</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">{__('frontend.solutions.cs1_h3')}</h4>
                                    <p className="text-sm text-slate-500 font-light leading-relaxed">{__('frontend.solutions.cs1_d3')}</p>
                                </div>
                            </div>
                        </div>

                        {/* Case Study 2 */}
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 p-10 rounded-3xl bg-slate-50 border border-slate-100 transition-all hover:bg-slate-100">
                            <div className="lg:col-span-1 border-b lg:border-b-0 lg:border-r border-slate-200 pb-8 lg:pb-0 lg:pr-8">
                                <span className="text-xs font-bold uppercase tracking-widest text-slate-900 mb-2 block">{__('frontend.solutions.cs2_cat')}</span>
                                <h3 className="text-2xl font-bold text-slate-900">{__('frontend.solutions.cs2_title')}</h3>
                            </div>
                            <div className="lg:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-8">
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">{__('frontend.solutions.cs2_h1')}</h4>
                                    <p className="text-sm text-slate-500 font-light leading-relaxed">{__('frontend.solutions.cs2_d1')}</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">{__('frontend.solutions.cs2_h2')}</h4>
                                    <p className="text-sm text-slate-500 font-light leading-relaxed">{__('frontend.solutions.cs2_d2')}</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">{__('frontend.solutions.cs2_h3')}</h4>
                                    <p className="text-sm text-slate-500 font-light leading-relaxed">{__('frontend.solutions.cs2_d3')}</p>
                                </div>
                            </div>
                        </div>

                        {/* Case Study 3 */}
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 p-10 rounded-3xl bg-slate-50 border border-slate-100 transition-all hover:bg-slate-100">
                            <div className="lg:col-span-1 border-b lg:border-b-0 lg:border-r border-slate-200 pb-8 lg:pb-0 lg:pr-8">
                                <span className="text-xs font-bold uppercase tracking-widest text-slate-900 mb-2 block">{__('frontend.solutions.cs3_cat')}</span>
                                <h3 className="text-2xl font-bold text-slate-900">{__('frontend.solutions.cs3_title')}</h3>
                            </div>
                            <div className="lg:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-8">
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">{__('frontend.solutions.cs3_h1')}</h4>
                                    <p className="text-sm text-slate-500 font-light leading-relaxed">{__('frontend.solutions.cs3_d1')}</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">{__('frontend.solutions.cs3_h2')}</h4>
                                    <p className="text-sm text-slate-500 font-light leading-relaxed">{__('frontend.solutions.cs3_d2')}</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">{__('frontend.solutions.cs3_h3')}</h4>
                                    <p className="text-sm text-slate-500 font-light leading-relaxed">{__('frontend.solutions.cs3_d3')}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
