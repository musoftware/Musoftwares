import { __ } from '@/lib/i18n';
import { useMemo, useState } from 'react';
import { Globe, Sparkles, Smartphone, Monitor, Check, Minus, Plus, Info, Calculator, MessageSquare } from 'lucide-react';
import { STUDIO_PHONE } from '@/lib/whatsapp';
import EstimatorLeadForm from './EstimatorLeadForm';
import CurrencySwitch from './CurrencySwitch';
import {
    CHANGE_TYPES, CODE_REVIEW_FEE, SYSTEM_TYPES, TECH_STACKS, CODE_ACCESS,
    clampQty, calculateModification, formatMoney,
} from './modificationPricing';

const SYSTEM_ICONS = { web: Globe, landing: Sparkles, mobile: Smartphone, desktop: Monitor };
const cardClass = 'bg-white p-6 sm:p-8 rounded-[24px] border border-black/5 shadow-[0_10px_30px_rgba(0,0,0,0.03)] space-y-6';
const chipClass = (active) => `px-3.5 py-2 min-h-9 rounded-full text-xs transition-all cursor-pointer border ${
    active ? 'bg-[#1d1d1f] text-white border-[#1d1d1f] font-medium' : 'bg-white text-[#1d1d1f]/70 border-black/10 hover:border-black/30'
}`;

const changeTitle = (id) => __(`frontend.estimator_mod_change_${id}_title`);

function StepHeading({ step, title, desc }) {
    return (
        <div className="space-y-0.5">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-[#0071e3]">{__('frontend.estimator_step', { step })}</span>
            <h3 className="text-[18px] sm:text-[20px] font-semibold text-[#1d1d1f]">{title}</h3>
            {desc && <p className="text-[13px] text-[#1d1d1f]/60">{desc}</p>}
        </div>
    );
}

function SystemStep({ systemType, onSystemType, techStack, onTechStack }) {
    return (
        <div className={cardClass}>
            <StepHeading step={1} title={__('frontend.estimator_mod_step1_title')} desc={__('frontend.estimator_mod_step1_desc')} />
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                {SYSTEM_TYPES.map((key) => {
                    const Icon = SYSTEM_ICONS[key];
                    const active = systemType === key;
                    return (
                        <button key={key} type="button" aria-pressed={active} onClick={() => onSystemType(key)}
                            className={`p-4 rounded-[18px] border text-start transition-all cursor-pointer flex items-center gap-3 ${
                                active ? 'border-2 border-[#0071e3] bg-[#0071e3]/5' : 'border-black/10 bg-white hover:border-black/30'
                            }`}>
                            <Icon className={`h-5 w-5 shrink-0 ${active ? 'text-[#0071e3]' : 'text-[#1d1d1f]'}`} />
                            <span className="font-semibold text-sm text-[#1d1d1f]">{__(`frontend.estimator_platform_${key}`)}</span>
                        </button>
                    );
                })}
            </div>
            <div className="space-y-2">
                <span className="text-xs font-semibold text-[#1d1d1f]">{__('frontend.estimator_mod_stack_label')}</span>
                <div className="flex flex-wrap gap-2">
                    {TECH_STACKS.map((key) => (
                        <button key={key} type="button" aria-pressed={techStack === key} onClick={() => onTechStack(key)} className={chipClass(techStack === key)}>
                            {__(`frontend.estimator_mod_stack_${key}`)}
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
}

function QtyStepper({ id, qty, onChange }) {
    const btn = 'h-9 w-9 rounded-full flex items-center justify-center border border-black/10 bg-white hover:bg-[#f5f5f7] cursor-pointer disabled:opacity-40';
    return (
        <div className="flex items-center gap-2 shrink-0">
            <button type="button" className={btn} disabled={qty === 0} onClick={() => onChange(id, qty - 1)} aria-label={__('frontend.estimator_mod_decrease')}>
                <Minus className="h-4 w-4" />
            </button>
            <input type="number" min="0" inputMode="numeric" value={qty} onChange={(e) => onChange(id, e.target.value)}
                aria-label={changeTitle(id)}
                className="w-14 px-1 text-center text-sm font-semibold rounded-lg border border-black/10 py-1.5 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
            <button type="button" className={btn} onClick={() => onChange(id, qty + 1)} aria-label={__('frontend.estimator_mod_increase')}>
                <Plus className="h-4 w-4" />
            </button>
        </div>
    );
}

function OnceToggle({ id, qty, onChange }) {
    const active = qty > 0;
    return (
        <button type="button" aria-pressed={active} onClick={() => onChange(id, active ? 0 : 1)}
            className={`shrink-0 min-h-9 px-4 rounded-full text-xs font-medium border cursor-pointer flex items-center gap-1.5 ${
                active ? 'bg-[#0071e3] text-white border-[#0071e3]' : 'bg-white text-[#1d1d1f] border-black/10 hover:border-black/30'
            }`}>
            {active && <Check className="h-3.5 w-3.5" strokeWidth={3} />}
            {active ? __('frontend.estimator_mod_added') : __('frontend.estimator_mod_add')}
        </button>
    );
}

function ChangeRow({ type, qty, onChange, price }) {
    const Icon = type.icon;
    const priceLabel = type.per === 'item'
        ? __('frontend.estimator_mod_per_item', { price, unit: __(`frontend.estimator_mod_change_${type.id}_unit`) })
        : __('frontend.estimator_mod_once', { price });
    const Control = type.per === 'item' ? QtyStepper : OnceToggle;

    return (
        <div className={`p-4 rounded-[18px] border flex flex-col sm:flex-row sm:items-center gap-3 transition-all ${qty > 0 ? 'border-[#0071e3] bg-[#0071e3]/5' : 'border-black/5'}`}>
            <div className="flex items-start gap-3 flex-1 min-w-0">
                <div className={`p-2 rounded-xl shrink-0 ${qty > 0 ? 'bg-[#0071e3] text-white' : 'bg-[#f5f5f7] text-[#1d1d1f]'}`}>
                    <Icon className="h-4 w-4" />
                </div>
                <div className="min-w-0">
                    <h4 className="font-semibold text-sm text-[#1d1d1f]">{changeTitle(type.id)}</h4>
                    <p className="text-xs text-[#1d1d1f]/60 mt-0.5 leading-relaxed">{__(`frontend.estimator_mod_change_${type.id}_desc`)}</p>
                    <span className="text-xs font-semibold text-[#0071e3] mt-1 inline-block">{priceLabel}</span>
                </div>
            </div>
            <Control id={type.id} qty={qty} onChange={onChange} />
        </div>
    );
}

function DetailsStep({ details, onDetails, codeAccess, onCodeAccess }) {
    return (
        <div className={cardClass}>
            <StepHeading step={3} title={__('frontend.estimator_mod_step3_title')} />
            <textarea rows={4} maxLength={3000} value={details} onChange={(e) => onDetails(e.target.value)}
                placeholder={__('frontend.estimator_mod_details_placeholder')} aria-label={__('frontend.estimator_mod_step3_title')}
                className="w-full px-4 py-3 bg-white rounded-2xl border border-black/10 text-sm text-[#1d1d1f] focus:ring-2 focus:ring-[#0071e3] focus:outline-none" />
            <div className="space-y-2">
                <span className="text-xs font-semibold text-[#1d1d1f]">{__('frontend.estimator_mod_access_label')}</span>
                <div className="flex flex-wrap gap-2">
                    {CODE_ACCESS.map((key) => (
                        <button key={key} type="button" aria-pressed={codeAccess === key} onClick={() => onCodeAccess(key)} className={chipClass(codeAccess === key)}>
                            {__(`frontend.estimator_mod_access_${key}`)}
                        </button>
                    ))}
                </div>
                {codeAccess !== 'yes' && (
                    <p className="text-xs text-[#1d1d1f]/70 bg-[#f5f5f7] rounded-xl p-3 flex gap-2">
                        <Info className="h-4 w-4 shrink-0 text-[#0071e3]" />
                        <span>{__('frontend.estimator_mod_access_no_note')}</span>
                    </p>
                )}
            </div>
        </div>
    );
}

function SummaryLines({ estimate, money }) {
    if (estimate.items.length === 0) {
        return <p className="text-xs text-[#1d1d1f]/60">{__('frontend.estimator_mod_empty')}</p>;
    }
    return (
        <div className="space-y-2 text-xs">
            {estimate.items.map((item) => (
                <div key={item.id} className="flex justify-between gap-3 text-[#1d1d1f]/80">
                    <span className="min-w-0">{changeTitle(item.id)}{item.per === 'item' ? ` × ${item.qty}` : ''}</span>
                    <span className="font-medium shrink-0">{money(item.subtotal)}</span>
                </div>
            ))}
            <div className="flex justify-between gap-3 text-[#1d1d1f]/60">
                <span>{__('frontend.estimator_mod_review_fee')}</span>
                <span className="font-medium shrink-0">{money(CODE_REVIEW_FEE)}</span>
            </div>
        </div>
    );
}

function buildWhatsappLink({ systemType, techStack, codeAccess, details, estimate, money }) {
    const lines = estimate.items.map((item) => `- ${changeTitle(item.id)}${item.per === 'item' ? ` x ${item.qty}` : ''}`);
    const message = [
        'Hello! I want to change an existing project (from the Musoftwares estimator):',
        '',
        `*System:* ${__(`frontend.estimator_platform_${systemType}`)} (${__(`frontend.estimator_mod_stack_${techStack}`)})`,
        `*Source code access:* ${__(`frontend.estimator_mod_access_${codeAccess}`)}`,
        `*Changes:*`,
        lines.join('\n') || '-',
        details.trim() ? `\n*Details:* ${details.trim()}` : '',
        '',
        `*Starting price:* ${money(estimate.totalUsd)}`,
        `*Timeline:* ~${estimate.estimatedDays} business days`,
    ].join('\n');

    return `https://wa.me/${STUDIO_PHONE}?text=${encodeURIComponent(message)}`;
}

function ModificationSummary({ estimate, isUsd, onCurrency, money, whatsappLink }) {
    const empty = estimate.items.length === 0;
    return (
        <div className="bg-white p-6 sm:p-8 rounded-[28px] border border-black/5 shadow-[0_20px_60px_rgba(0,0,0,0.06)] space-y-5">
            <div className="flex items-center justify-between border-b border-black/5 pb-4 gap-3">
                <div className="flex items-center gap-2">
                    <Calculator className="h-4 w-4 text-[#0071e3]" />
                    <h3 className="font-semibold text-sm text-[#1d1d1f]">{__('frontend.estimator_budget_title')}</h3>
                </div>
                <CurrencySwitch isUsd={isUsd} onChange={onCurrency} />
            </div>
            <div className="space-y-1">
                <span className="text-xs text-[#1d1d1f]/60 block font-medium">{__('frontend.estimator_mod_starting_price')}</span>
                <div className="text-[34px] sm:text-[40px] font-semibold text-[#1d1d1f] tracking-tight leading-none">{money(estimate.totalUsd)}</div>
                {!empty && (
                    <div className="flex items-center justify-between text-xs text-[#1d1d1f]/70 pt-2 border-t border-black/5 mt-3">
                        <span>{__('frontend.estimator_timeline')}</span>
                        <span className="text-[#0071e3] font-semibold">{__('frontend.estimator_business_days', { days: estimate.estimatedDays })}</span>
                    </div>
                )}
            </div>
            <SummaryLines estimate={estimate} money={money} />
            <p className="text-[11px] text-[#1d1d1f]/60 leading-relaxed">{__('frontend.estimator_mod_final_note')}</p>
            <a href={whatsappLink} target="_blank" rel="noopener noreferrer"
                className="w-full flex items-center justify-center gap-2 py-3.5 rounded-[980px] bg-[#25D366] hover:bg-[#20ba59] text-white font-medium text-xs transition-all">
                <MessageSquare className="w-4 h-4" />
                <span>{__('frontend.estimator_discuss_whatsapp')}</span>
            </a>
        </div>
    );
}

export default function ModificationEstimator({ exchangeRate = 50.0 }) {
    const [systemType, setSystemType] = useState('web');
    const [techStack, setTechStack] = useState('unknown');
    const [quantities, setQuantities] = useState({});
    const [details, setDetails] = useState('');
    const [codeAccess, setCodeAccess] = useState('yes');
    const [isUsd, setIsUsd] = useState(false);

    const estimate = useMemo(() => calculateModification(quantities, exchangeRate), [quantities, exchangeRate]);
    const money = (usd) => formatMoney(usd, isUsd, exchangeRate);
    const setQty = (id, value) => setQuantities((prev) => ({ ...prev, [id]: clampQty(value) }));
    const whatsappLink = buildWhatsappLink({ systemType, techStack, codeAccess, details, estimate, money });

    const getScope = () => ({
        request_type: 'Change to an existing project',
        system: systemType,
        built_with: techStack,
        source_code_access: codeAccess,
        changes: estimate.items.map((item) => `${changeTitle(item.id)} x ${item.qty}`),
        details: details.trim(),
        starting_price_usd: estimate.totalUsd,
        starting_price_egp: estimate.totalEgp,
        estimated_days: estimate.estimatedDays,
    });

    return (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div className="lg:col-span-8 space-y-6 min-w-0">
                <SystemStep systemType={systemType} onSystemType={setSystemType} techStack={techStack} onTechStack={setTechStack} />
                <div className={cardClass}>
                    <StepHeading step={2} title={__('frontend.estimator_mod_step2_title')} desc={__('frontend.estimator_mod_step2_desc')} />
                    <div className="space-y-3">
                        {CHANGE_TYPES.map((type) => (
                            <ChangeRow key={type.id} type={type} qty={quantities[type.id] || 0} onChange={setQty} price={money(type.price)} />
                        ))}
                    </div>
                </div>
                <DetailsStep details={details} onDetails={setDetails} codeAccess={codeAccess} onCodeAccess={setCodeAccess} />
            </div>
            <div className="lg:col-span-4 lg:sticky lg:top-24 space-y-6 min-w-0">
                <ModificationSummary estimate={estimate} isUsd={isUsd} onCurrency={setIsUsd} money={money} whatsappLink={whatsappLink} />
                <EstimatorLeadForm toolName="Estimator - change request" getScope={getScope}
                    title={__('frontend.estimator_mod_request_title')} description={__('frontend.estimator_mod_request_desc')} />
            </div>
        </div>
    );
}
