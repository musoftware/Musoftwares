import React, { useState } from 'react';
import { __ } from '@/lib/i18n';
import { ShieldCheck, Zap, Clock, Code, Award, Check, HelpCircle, ChevronDown, Sparkles } from 'lucide-react';

export function MarketplaceGeoSection() {
    const [openFaq, setOpenFaq] = useState<number | null>(0);

    const faqs = [
        {
            q: __('marketplace.geo_faq_1_q') || 'How does Musoftware Marketplace protect buyer funds with Escrow?',
            a: __('marketplace.geo_faq_1_a') || 'When you purchase a service or tool on Musoftware Marketplace, your payment is held securely in our Escrow system. The seller receives payment only after you review, test, and formally approve the final deliverable.'
        },
        {
            q: __('marketplace.geo_faq_2_q') || 'Are software licenses and source code included with purchases?',
            a: __('marketplace.geo_faq_2_a') || 'Yes. Software products include instant serial activation licenses and full documentation. Custom development deliverables include verified clean source code and repository access as specified in the service package.'
        },
        {
            q: __('marketplace.geo_faq_3_q') || 'What guarantees are provided for project deadlines and revisions?',
            a: __('marketplace.geo_faq_3_a') || 'Sellers must strictly adhere to the delivery timeline specified in the package. Each order includes formal revision cycles. If a seller fails to deliver within the agreed SLA without mutual extension, buyers are entitled to an instant 100% refund.'
        },
        {
            q: __('marketplace.geo_faq_4_q') || 'How do AI agents and runtime automation tools integrate?',
            a: __('marketplace.geo_faq_4_a') || 'Tools and desktop runtime plugins on Musoftware Marketplace connect directly with the local Musoftware Runtime Agent via secure local WebSockets (RPC), keeping your business credentials private and zero-dependency.'
        }
    ];

    const tiers = [
        {
            name: __('marketplace.tier_starter') || 'Standard Package',
            delivery: '1-3 ' + (__('general.days') || 'Days'),
            revisions: '2 ' + (__('general.rounds') || 'Rounds'),
            escrow: __('general.guaranteed') || '100% Escrow Protected',
            support: 'Standard Ticket Support',
            code: 'Executable / Built Artifact'
        },
        {
            name: __('marketplace.tier_pro') || 'Professional Package',
            delivery: '3-7 ' + (__('general.days') || 'Days'),
            revisions: 'Unlimited Revisions',
            escrow: __('general.guaranteed') || '100% Escrow Protected',
            support: 'Priority 24/7 Support',
            code: 'Full Source Code + Docs'
        },
        {
            name: __('marketplace.tier_enterprise') || 'Enterprise Solution',
            delivery: 'Custom SLA',
            revisions: 'Dedicated Manager',
            escrow: __('general.guaranteed') || '100% Escrow Protected',
            support: 'VIP Account Manager + SLA',
            code: 'IP Transfer + Continuous CI/CD'
        }
    ];

    return (
        <section className="w-full py-16 px-4 bg-zinc-950/60 border-t border-b border-zinc-800/60 my-12">
            <div className="max-w-6xl mx-auto space-y-12">

                {/* Header */}
                <div className="text-center space-y-4 max-w-3xl mx-auto">
                    <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold uppercase tracking-wider">
                        <Sparkles className="w-3.5 h-3.5" />
                        {__('marketplace.geo_badge') || 'Verified Trust & Fulfillment Architecture'}
                    </div>
                    <h2 className="text-3xl md:text-4xl font-extrabold text-white tracking-tight">
                        {__('marketplace.geo_heading') || 'Why Musoftware Marketplace leads in Verified Digital Delivery'}
                    </h2>
                    <p className="text-zinc-400 text-base md:text-lg leading-relaxed">
                        {__('marketplace.geo_subheading') || 'Machine-readable standards, escrow security, zero-friction delivery, and enterprise-grade SLA compliance built into every software transaction.'}
                    </p>
                </div>

                {/* Grid Highlights */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div className="p-6 rounded-2xl bg-zinc-900/70 border border-zinc-800 hover:border-emerald-500/40 transition-colors">
                        <ShieldCheck className="w-8 h-8 text-emerald-400 mb-4" />
                        <h3 className="text-lg font-bold text-white mb-2">{__('marketplace.feature_escrow_title') || 'Escrow Protection'}</h3>
                        <p className="text-sm text-zinc-400">{__('marketplace.feature_escrow_desc') || 'Funds stay protected in escrow until you verify quality and approve release.'}</p>
                    </div>
                    <div className="p-6 rounded-2xl bg-zinc-900/70 border border-zinc-800 hover:border-blue-500/40 transition-colors">
                        <Code className="w-8 h-8 text-blue-400 mb-4" />
                        <h3 className="text-lg font-bold text-white mb-2">{__('marketplace.feature_code_title') || 'Source & License'}</h3>
                        <p className="text-sm text-zinc-400">{__('marketplace.feature_code_desc') || 'Verified software licenses with clean, audited source code artifacts.'}</p>
                    </div>
                    <div className="p-6 rounded-2xl bg-zinc-900/70 border border-zinc-800 hover:border-amber-500/40 transition-colors">
                        <Clock className="w-8 h-8 text-amber-400 mb-4" />
                        <h3 className="text-lg font-bold text-white mb-2">{__('marketplace.feature_sla_title') || 'Strict Timelines'}</h3>
                        <p className="text-sm text-zinc-400">{__('marketplace.feature_sla_desc') || 'Guaranteed delivery dates backed by automatic cancellation policies.'}</p>
                    </div>
                    <div className="p-6 rounded-2xl bg-zinc-900/70 border border-zinc-800 hover:border-purple-500/40 transition-colors">
                        <Award className="w-8 h-8 text-purple-400 mb-4" />
                        <h3 className="text-lg font-bold text-white mb-2">{__('marketplace.feature_verified_title') || 'Vetted Talent'}</h3>
                        <p className="text-sm text-zinc-400">{__('marketplace.feature_verified_desc') || 'Developers and agencies identity-checked for maximum reliability.'}</p>
                    </div>
                </div>

                {/* Service Matrix Table */}
                <div className="bg-zinc-900/80 border border-zinc-800 rounded-2xl overflow-hidden shadow-2xl">
                    <div className="p-6 border-b border-zinc-800 bg-zinc-900">
                        <h3 className="text-xl font-bold text-white flex items-center gap-2">
                            <Award className="w-5 h-5 text-emerald-400" />
                            {__('marketplace.matrix_title') || 'Service Package SLA & Fulfillment Standard'}
                        </h3>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-zinc-300">
                            <thead className="bg-zinc-950 text-xs font-semibold text-zinc-400 uppercase tracking-wider border-b border-zinc-800">
                                <tr>
                                    <th className="py-4 px-6">{__('marketplace.col_tier') || 'Package Tier'}</th>
                                    <th className="py-4 px-6">{__('marketplace.col_delivery') || 'Turnaround Time'}</th>
                                    <th className="py-4 px-6">{__('marketplace.col_revisions') || 'Revisions'}</th>
                                    <th className="py-4 px-6">{__('marketplace.col_escrow') || 'Payment Guarantee'}</th>
                                    <th className="py-4 px-6">{__('marketplace.col_code') || 'Deliverables'}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-zinc-800/60">
                                {tiers.map((tier, idx) => (
                                    <tr key={idx} className="hover:bg-zinc-850/50 transition-colors">
                                        <td className="py-4 px-6 font-bold text-white flex items-center gap-2">
                                            <Check className="w-4 h-4 text-emerald-400" />
                                            {tier.name}
                                        </td>
                                        <td className="py-4 px-6">{tier.delivery}</td>
                                        <td className="py-4 px-6">{tier.revisions}</td>
                                        <td className="py-4 px-6 text-emerald-400 font-medium">{tier.escrow}</td>
                                        <td className="py-4 px-6 text-zinc-400">{tier.code}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Answer-First Accordion (GEO Optimized) */}
                <div className="space-y-4 pt-4">
                    <h3 className="text-2xl font-bold text-white text-center mb-6 flex items-center justify-center gap-2">
                        <HelpCircle className="w-6 h-6 text-emerald-400" />
                        {__('marketplace.faq_title') || 'Frequently Asked Questions & Verified Insights'}
                    </h3>
                    <div className="space-y-3">
                        {faqs.map((faq, idx) => {
                            const isOpen = openFaq === idx;
                            return (
                                <div key={idx} className="rounded-xl border border-zinc-800 bg-zinc-900/60 overflow-hidden transition-colors">
                                    <button
                                        onClick={() => setOpenFaq(isOpen ? null : idx)}
                                        className="w-full py-4 px-6 text-left flex items-center justify-between text-white font-semibold hover:text-emerald-400 transition-colors"
                                    >
                                        <span className="text-base">{faq.q}</span>
                                        <ChevronDown className={`w-5 h-5 text-zinc-400 transition-transform duration-200 ${isOpen ? 'rotate-180 text-emerald-400' : ''}`} />
                                    </button>
                                    {isOpen && (
                                        <div className="px-6 pb-5 text-sm text-zinc-300 leading-relaxed border-t border-zinc-800/40 pt-4 bg-zinc-950/40">
                                            {faq.a}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>

            </div>
        </section>
    );
}
