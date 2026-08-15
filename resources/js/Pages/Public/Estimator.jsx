import { useState, useRef, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Button } from '@/Components/ui/button';
import { Calculator, ArrowRight, CheckCircle2, Sparkles, ShieldCheck, Clock, Layers, MessageSquare } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Estimator() {
    const phoneNumber = "201015218548";
    const [scale, setScale] = useState('multi_page');
    const [customPrompt, setCustomPrompt] = useState('');
    const [addons, setAddons] = useState({
        whatsapp: false,
        payment: false,
        sms: false,
        admin: false,
    });

    useEffect(() => {
        if (typeof window !== 'undefined') {
            const params = new URLSearchParams(window.location.search);
            const prompt = params.get('prompt') || params.get('prefill_desc');
            if (prompt) {
                setCustomPrompt(prompt);
            }
        }
    }, []);

    const prices = {
        single_page: { base: 3500, days: 1 },
        multi_page: { base: 8000, days: 3 },
        full_portal: { base: 20000, days: 7 },
    };

    const addonsPrices = {
        whatsapp: { price: 4000, days: 1 },
        payment: { price: 3000, days: 1 },
        sms: { price: 2500, days: 0 },
        admin: { price: 3500, days: 1 },
    };

    let total = prices[scale].base;
    let days = prices[scale].days;

    Object.keys(addons).forEach((key) => {
        if (addons[key]) {
            total += addonsPrices[key].price;
            days += addonsPrices[key].days;
        }
    });

    const prefillQuery = `I want a ${scale.replace('_', ' ')} project with these features: ${Object.keys(addons)
        .filter((k) => addons[k])
        .join(', ')}`;

    return (
        <PublicLayout>
            <Head title={__('home.estimator_title') || 'Project Budget Estimator | Musoftware'}>
                <meta name="description" content="Calculate your software development budget and delivery timeline transparently." />
            </Head>

            <div className="w-full bg-[#fcfcfc] text-zinc-900 font-sans selection:bg-zinc-950 selection:text-white py-16 sm:py-24">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    {/* Header */}
                    <div className="text-center mb-12 sm:mb-16">
                        <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-zinc-100 border border-zinc-200 text-zinc-700 text-xs font-semibold mb-4">
                            <Calculator className="w-3.5 h-3.5 text-zinc-900" />
                            <span>{__('home.estimator_badge') || 'Transparent Pricing'}</span>
                        </div>
                        <h1 className="text-3xl sm:text-4xl md:text-5xl font-black tracking-tight text-zinc-950 mb-4">
                            {__('home.estimator_title') || 'Estimate Your Project Budget & Timeline'}
                        </h1>
                        <p className="text-zinc-600 text-sm sm:text-base max-w-xl mx-auto leading-relaxed">
                            {__('home.estimator_subtitle') || 'Select your desired scale and required integrations for an instant, transparent estimate.'}
                        </p>
                    </div>

                    {/* Estimator Box */}
                    <div className="p-6 sm:p-10 rounded-3xl bg-white border border-zinc-200 shadow-sm">
                        <div className="grid md:grid-cols-12 gap-8 text-start">
                            <div className="md:col-span-7 space-y-6">
                                {/* Scale Selector */}
                                <div>
                                    <label className="text-xs font-extrabold uppercase tracking-wider text-zinc-500 mb-3 block">
                                        {__('home.project_scale') || 'System Scope & Scale'}
                                    </label>
                                    <div className="grid grid-cols-3 gap-2.5">
                                        {[
                                            { key: 'single_page', label: __('home.scale_single') || 'Single Page' },
                                            { key: 'multi_page', label: __('home.scale_multi') || 'Multi-Page' },
                                            { key: 'full_portal', label: __('home.scale_portal') || 'Full Portal' }
                                        ].map((s) => (
                                            <button
                                                key={s.key}
                                                type="button"
                                                onClick={() => setScale(s.key)}
                                                className={`px-3 py-3 text-xs font-bold rounded-xl border text-center transition cursor-pointer leading-tight ${
                                                    scale === s.key
                                                        ? 'bg-zinc-950 text-white border-zinc-950 shadow-sm'
                                                        : 'bg-white text-zinc-700 border-zinc-200 hover:bg-zinc-50'
                                                }`}
                                            >
                                                {s.label}
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                {/* Addons Selector */}
                                <div>
                                    <label className="text-xs font-extrabold uppercase tracking-wider text-zinc-500 mb-3 block">
                                        {__('home.integrations_addons') || 'Integrations & Features'}
                                    </label>
                                    <div className="space-y-2.5">
                                        {[
                                            { key: 'whatsapp', label: __('home.addon_whatsapp') || 'WhatsApp Official API (+4,000 EGP)' },
                                            { key: 'payment', label: __('home.addon_payment') || 'Payment Gateway (+3,000 EGP)' },
                                            { key: 'sms', label: __('home.addon_sms') || 'SMS & OTP Gateway (+2,500 EGP)' },
                                            { key: 'admin', label: __('home.addon_admin') || 'Multi-Role Admin Console (+3,500 EGP)' },
                                        ].map((addon) => (
                                            <label
                                                key={addon.key}
                                                className={`flex items-center gap-3 p-3.5 rounded-xl border transition cursor-pointer select-none ${
                                                    addons[addon.key]
                                                        ? 'bg-zinc-50 border-zinc-900 shadow-xs'
                                                        : 'bg-white border-zinc-200 hover:bg-zinc-50/50'
                                                }`}
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={addons[addon.key]}
                                                    onChange={(e) => setAddons((prev) => ({ ...prev, [addon.key]: e.target.checked }))}
                                                    className="h-4 w-4 rounded border-zinc-300 text-zinc-950 focus:ring-zinc-950"
                                                />
                                                <span className="text-xs sm:text-sm text-zinc-800 font-semibold">{addon.label}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            {/* Estimate Result Card */}
                            <div className="md:col-span-5 flex flex-col justify-between p-6 sm:p-8 rounded-2xl bg-zinc-950 text-white border border-zinc-800 text-center">
                                <div>
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-emerald-400 mb-2 block">
                                        {__('home.estimated_total') || 'Instant Estimation'}
                                    </span>
                                    <div className="text-3xl sm:text-5xl font-black text-white my-4">
                                        {total.toLocaleString()} <span className="text-sm font-semibold text-zinc-400">EGP</span>
                                    </div>
                                    <div className="text-xs sm:text-sm text-zinc-300 font-medium mb-4">
                                        {__('home.delivery_time') || 'Expected Delivery:'}{' '}
                                        <span className="text-emerald-400 font-bold">
                                            {days} {days === 1 ? (__('home.day_one') || 'day') : (__('home.days_count', { count: days }) || `${days} days`)}
                                        </span>
                                    </div>
                                    <p className="text-[11px] text-zinc-400 leading-relaxed italic">
                                        {__('home.estimator_note') || '* This estimate is generated instantly based on typical architecture.'}
                                    </p>
                                </div>

                                <div className="mt-6 space-y-2.5">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            const msg = encodeURIComponent(`Hello Mahmoud, I calculated an estimate on Musoftware:\n• System: ${scale.replace('_', ' ')}\n• Features: ${Object.keys(addons).filter(k => addons[k]).join(', ') || 'Standard'}\n• Estimated Total: ${total.toLocaleString()} EGP\n• Timeline: ${days} days\nI'd like to discuss starting!`);
                                            window.open(`https://wa.me/${phoneNumber}?text=${msg}`, '_blank');
                                        }}
                                        className="w-full h-12 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-black text-xs uppercase tracking-wider cursor-pointer flex items-center justify-center gap-2 transition shadow-md"
                                    >
                                        <MessageSquare className="w-4 h-4 fill-zinc-950" />
                                        <span>{__('home.final_cta_btn') || 'Discuss on WhatsApp'}</span>
                                    </button>

                                    <Link href={`/register?prefill_desc=${encodeURIComponent(prefillQuery)}`} className="block">
                                        <Button variant="outline" className="w-full h-10 rounded-xl bg-transparent border-zinc-700 text-zinc-300 hover:bg-zinc-800 text-[11px] font-bold uppercase tracking-wider">
                                            <span>{__('home.claim_estimate') || 'Create Account / Register'}</span>
                                            <ArrowRight className="w-3 h-3 ms-1 rtl:rotate-180" />
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Quality Guarantees */}
                    <div className="grid sm:grid-cols-3 gap-6 mt-12 text-center sm:text-start">
                        <div className="p-5 rounded-2xl bg-white border border-zinc-200 flex items-start gap-4">
                            <ShieldCheck className="w-6 h-6 text-emerald-600 shrink-0 mt-0.5" />
                            <div>
                                <h4 className="text-xs font-bold text-zinc-900 mb-1">Fixed Scope & Delivery</h4>
                                <p className="text-[11px] text-zinc-500 leading-relaxed">No hidden fees or unexpected delays after contract signoff.</p>
                            </div>
                        </div>
                        <div className="p-5 rounded-2xl bg-white border border-zinc-200 flex items-start gap-4">
                            <Clock className="w-6 h-6 text-blue-600 shrink-0 mt-0.5" />
                            <div>
                                <h4 className="text-xs font-bold text-zinc-900 mb-1">Rapid Deployment</h4>
                                <p className="text-[11px] text-zinc-500 leading-relaxed">Production-grade architecture with staging milestone updates.</p>
                            </div>
                        </div>
                        <div className="p-5 rounded-2xl bg-white border border-zinc-200 flex items-start gap-4">
                            <Layers className="w-6 h-6 text-purple-600 shrink-0 mt-0.5" />
                            <div>
                                <h4 className="text-xs font-bold text-zinc-900 mb-1">Full Source & Scaling</h4>
                                <p className="text-[11px] text-zinc-500 leading-relaxed">Clean architecture with isolated schemas and documented APIs.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </PublicLayout>
    );
}
