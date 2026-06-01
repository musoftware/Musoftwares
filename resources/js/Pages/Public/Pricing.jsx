import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Check, Info } from 'lucide-react';
import { Button } from '@/Components/ui/button';

export default function Pricing({ currency = 'USD' }) {
    return (
        <PublicLayout>
            <Head>
                <title>Engineering Blocks & Pricing - Musoftware</title>
                <meta name="description" content="Pay for focused work blocks — no retainers, no surprises." />
            </Head>

            <section className="pt-32 pb-24 lg:pt-48 lg:pb-32 bg-white">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-20">
                        <h1 className="text-5xl lg:text-7xl font-extrabold text-slate-900 tracking-tight leading-[1.1] mb-6">
                            Engineering Blocks
                        </h1>
                        <p className="text-xl text-slate-500 font-light">
                            Hire our engineers by the hour. Pay for focused work blocks — no retainers, no surprises.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                        
                        {/* Maintenance Block */}
                        <div className="flex flex-col p-8 rounded-3xl bg-white border border-slate-200 hover:border-slate-300 transition-all shadow-sm">
                            <div className="mb-8">
                                <h3 className="text-2xl font-bold text-slate-900 mb-2">Maintenance Block</h3>
                                <p className="text-slate-500 font-light mb-6">Best for bug fixes, security patches, and small UI improvements.</p>
                                <div className="flex items-baseline gap-2">
                                    <span className="text-4xl font-extrabold text-slate-900">5 hrs</span>
                                </div>
                            </div>
                            <div className="flex-1">
                                <ul className="space-y-4 mb-8">
                                    <li className="flex gap-3 text-slate-600 font-light">
                                        <Check className="w-5 h-5 text-slate-400 shrink-0" /> Bug Fixes
                                    </li>
                                    <li className="flex gap-3 text-slate-600 font-light">
                                        <Check className="w-5 h-5 text-slate-400 shrink-0" /> UI Polishes
                                    </li>
                                    <li className="flex gap-3 text-slate-600 font-light">
                                        <Check className="w-5 h-5 text-slate-400 shrink-0" /> Security Audit
                                    </li>
                                </ul>
                            </div>
                            <Button className="w-full bg-slate-50 hover:bg-slate-100 text-slate-900 border border-slate-200 rounded-full h-12 font-semibold">
                                Request Block
                            </Button>
                        </div>

                        {/* Implementation Block (Most Popular) */}
                        <div className="flex flex-col p-8 rounded-3xl bg-slate-900 text-white shadow-xl relative transform md:-translate-y-4">
                            <div className="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2">
                                <span className="bg-slate-100 text-slate-900 text-xs font-bold uppercase tracking-widest py-1 px-3 rounded-full">
                                    Most Popular
                                </span>
                            </div>
                            <div className="mb-8 mt-2">
                                <h3 className="text-2xl font-bold text-white mb-2">Implementation Block</h3>
                                <p className="text-slate-400 font-light mb-6">Best for new features, API integrations, or a scoped build sprint.</p>
                                <div className="flex items-baseline gap-2">
                                    <span className="text-4xl font-extrabold text-white">12 hrs</span>
                                </div>
                            </div>
                            <div className="flex-1">
                                <ul className="space-y-4 mb-8">
                                    <li className="flex gap-3 text-slate-300 font-light">
                                        <Check className="w-5 h-5 text-slate-500 shrink-0" /> New Feature Engine
                                    </li>
                                    <li className="flex gap-3 text-slate-300 font-light">
                                        <Check className="w-5 h-5 text-slate-500 shrink-0" /> API Integration
                                    </li>
                                    <li className="flex gap-3 text-slate-300 font-light">
                                        <Check className="w-5 h-5 text-slate-500 shrink-0" /> Scaling Audit
                                    </li>
                                </ul>
                            </div>
                            <Button className="w-full bg-white hover:bg-slate-100 text-slate-900 rounded-full h-12 font-bold">
                                Request Block
                            </Button>
                        </div>

                        {/* Growth Partner */}
                        <div className="flex flex-col p-8 rounded-3xl bg-white border border-slate-200 hover:border-slate-300 transition-all shadow-sm">
                            <div className="mb-8">
                                <h3 className="text-2xl font-bold text-slate-900 mb-2">Growth Partner</h3>
                                <p className="text-slate-500 font-light mb-6">Ongoing development partner with weekly strategy and priority access.</p>
                                <div className="flex items-baseline gap-2">
                                    <span className="text-4xl font-extrabold text-slate-900">Custom</span>
                                </div>
                            </div>
                            <div className="flex-1">
                                <ul className="space-y-4 mb-8">
                                    <li className="flex gap-3 text-slate-600 font-light">
                                        <Check className="w-5 h-5 text-slate-400 shrink-0" /> Priority Support
                                    </li>
                                    <li className="flex gap-3 text-slate-600 font-light">
                                        <Check className="w-5 h-5 text-slate-400 shrink-0" /> Weekly Strategy Calls
                                    </li>
                                    <li className="flex gap-3 text-slate-600 font-light">
                                        <Check className="w-5 h-5 text-slate-400 shrink-0" /> On-demand Development
                                    </li>
                                </ul>
                            </div>
                            <Button className="w-full bg-slate-50 hover:bg-slate-100 text-slate-900 border border-slate-200 rounded-full h-12 font-semibold">
                                Contact Sales
                            </Button>
                        </div>

                    </div>

                    {/* Disclaimer */}
                    <div className="mt-16 mb-24 text-center max-w-2xl mx-auto flex items-start sm:items-center justify-center gap-3 p-6 bg-slate-50 rounded-2xl border border-slate-100">
                        <Info className="w-5 h-5 text-slate-400 shrink-0 mt-0.5 sm:mt-0" />
                        <p className="text-sm text-slate-600 font-light text-left sm:text-center">
                            <strong>Phase 0 strategy is always free.</strong> Every project starts with a free scope call before any billing occurs to define scope, budget, and success criteria.
                        </p>
                    </div>

                    {/* FAQ Section */}
                    <div className="max-w-4xl mx-auto border-t border-slate-100 pt-20">
                        <h2 className="text-3xl font-bold text-slate-900 mb-12 text-center">Frequently Asked Questions</h2>
                        
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-10">
                            <div>
                                <h4 className="text-lg font-bold text-slate-900 mb-2">How do we start?</h4>
                                <p className="text-slate-500 font-light leading-relaxed">
                                    Every project starts with a free Phase 0 strategy call to define scope, budget, and success criteria. No billing occurs until we have a signed, scoped brief.
                                </p>
                            </div>
                            
                            <div>
                                <h4 className="text-lg font-bold text-slate-900 mb-2">Are there any hidden fees?</h4>
                                <p className="text-slate-500 font-light leading-relaxed">
                                    No. You pay only for the engineering blocks you consume. There are no surprise retainers, mandatory monthly fees, or hidden costs.
                                </p>
                            </div>
                            
                            <div>
                                <h4 className="text-lg font-bold text-slate-900 mb-2">Who owns the code?</h4>
                                <p className="text-slate-500 font-light leading-relaxed">
                                    You do. Upon project completion, we hand over the full source code and all assets. You own every line of code we write for you.
                                </p>
                            </div>
                            
                            <div>
                                <h4 className="text-lg font-bold text-slate-900 mb-2">What about maintenance?</h4>
                                <p className="text-slate-500 font-light leading-relaxed">
                                    We offer a 30-day free support period after delivery. After that, you can request a Maintenance Block (5 hrs) for updates, patches, or small features.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </PublicLayout>
    );
}
