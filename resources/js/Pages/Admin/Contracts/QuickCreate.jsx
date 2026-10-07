import React, { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { Calculator, FileText, Share2, Copy, Check, Sparkles, UserPlus, ArrowRight, ShieldCheck, DollarSign, Clock } from 'lucide-react';
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
import axios from 'axios';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

export default function QuickCreate({ currencies = [] }) {
    const { flash } = usePage().props;
    const [calculating, setCalculating] = useState(false);
    const [valuation, setValuation] = useState(null);
    const [copied, setCopied] = useState(false);
    const [showClarificationModal, setShowClarificationModal] = useState(false);
    const [selectedAnswer, setSelectedAnswer] = useState('');
    const [customAnswer, setCustomAnswer] = useState('');

    const { data, setData, post, processing, errors } = useForm({
        description: '',
        client_id: '',
        currency_id: 1,
    });

    const handleCalculate = async (customCurrencyId = null, selectedAns = null) => {
        if (!data.description || data.description.trim().length < 5) {
            return;
        }
        setCalculating(true);
        const targetCurrency = customCurrencyId || data.currency_id;
        try {
            const res = await axios.post(route('admin.contracts.quick-calculate'), {
                description: data.description,
                currency_id: targetCurrency,
                selected_answer: selectedAns,
            });
            if (res.data.ok) {
                setValuation(res.data.valuation);
                if (res.data.valuation.needs_clarification && !selectedAns) {
                    setShowClarificationModal(true);
                }
            }
        } catch {
            toast.error(__('admin.quick_contract_calculate_failed'));
        } finally {
            setCalculating(false);
        }
    };

    const handleCurrencyChange = (newCurrencyId) => {
        setData('currency_id', newCurrencyId);
        if (valuation && data.description && data.description.trim().length >= 5) {
            handleCalculate(newCurrencyId);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('admin.contracts.quick-store'));
    };

    const copyToClipboard = (text) => {
        navigator.clipboard.writeText(text);
        setCopied(true);
        setTimeout(() => setCopied(false), 3000);
    };

    return (
        <AdminSidebarLayout>
            <Head title={__('admin.quick_contract_title')} />

            <div className="space-y-8 max-w-6xl mx-auto py-6 px-4">
                {/* Header Title */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b pb-5 border-slate-200">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <Sparkles className="w-5 h-5 text-amber-500" />
                            <span className="text-xs font-black uppercase tracking-widest text-amber-600">{__('admin.quick_contract_engine_label')}</span>
                        </div>
                        <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">{__('admin.quick_contract_title')}</h1>
                        <p className="text-sm text-slate-500 mt-1">
                            {__('admin.quick_contract_intro')}
                        </p>
                    </div>
                </div>

                {/* Flash Shareable URL Notice Modal/Banner */}
                {flash?.shareable_url && (
                    <Card className="border-2 border-emerald-500 bg-emerald-50/50 shadow-md">
                        <CardHeader className="pb-3">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold">
                                    <Check className="w-5 h-5" />
                                </div>
                                <div>
                                    <CardTitle className="text-lg text-emerald-900 font-extrabold">{__('admin.quick_contract_created_title')}</CardTitle>
                                    <CardDescription className="text-emerald-700 text-xs">
                                        {__('admin.quick_contract_reference')}: <span className="font-mono font-bold text-emerald-950">{flash.contract_ref}</span>
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center gap-2 bg-white p-3 rounded-lg border border-emerald-200">
                                <Input
                                    readOnly
                                    value={flash.shareable_url}
                                    className="font-mono text-xs text-slate-800 bg-slate-50 border-none focus-visible:ring-0"
                                />
                                <Button
                                    type="button"
                                    onClick={() => copyToClipboard(flash.shareable_url)}
                                    className="bg-emerald-600 hover:bg-emerald-700 text-white shrink-0 text-xs font-bold gap-2"
                                >
                                    {copied ? <Check className="w-4 h-4" /> : <Copy className="w-4 h-4" />}
                                    {copied ? __('general.link_copied') : __('admin.copy_link')}
                                </Button>
                            </div>

                            <div className="flex gap-3">
                                <Button
                                    type="button"
                                    onClick={() => window.open(`https://wa.me/?text=${encodeURIComponent(__('admin.quick_contract_whatsapp_message', { url: flash.shareable_url }))}`, '_blank')}
                                    className="bg-[#25D366] hover:bg-[#1da851] text-white font-bold text-xs gap-2 rounded-full px-6"
                                >
                                    <Share2 className="w-4 h-4" /> {__('admin.quick_contract_share_whatsapp')}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                <form onSubmit={handleSubmit} className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    {/* Left Section — Input Description & Client Selection */}
                    <div className="lg:col-span-7 space-y-6">
                        <Card className="border border-slate-200 shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-lg font-bold flex items-center gap-2">
                                    <FileText className="w-5 h-5 text-slate-700" />
                                    1. {__('admin.quick_contract_step_description')}
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    {__('admin.quick_contract_step_description_hint')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label className="text-xs font-bold text-slate-700">{__('admin.quick_contract_description_label')} *</Label>
                                    <Textarea
                                        rows={7}
                                        placeholder={__('admin.quick_contract_description_placeholder')}
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        className="text-sm font-sans leading-relaxed border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                    />
                                    {errors.description && <p className="text-xs text-red-500 font-bold">{errors.description}</p>}
                                </div>

                                <div className="flex justify-end">
                                    <Button
                                        type="button"
                                        onClick={() => handleCalculate()}
                                        disabled={calculating || !data.description || data.description.trim().length < 5}
                                        className="bg-amber-500 hover:bg-amber-600 text-black font-extrabold text-xs gap-2 rounded-full px-6"
                                    >
                                        <Calculator className="w-4 h-4" />
                                        {calculating ? __('admin.quick_contract_calculating') : __('admin.quick_contract_calculate_now')}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Client & Currency Selection */}
                        <Card className="border border-slate-200 shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-lg font-bold flex items-center gap-2">
                                    <UserPlus className="w-5 h-5 text-slate-700" />
                                    2. {__('admin.quick_contract_step_client')}
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    {__('admin.quick_contract_step_client_hint')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label className="text-xs font-bold text-slate-700">{__('admin.quick_contract_client_optional')}</Label>
                                    <PremiumCombobox
                                        value={data.client_id ? String(data.client_id) : ''}
                                        onChange={(val) => setData('client_id', val ? String(val) : '')}
                                        asyncEndpoint={route('admin.users.search')}
                                        placeholder={`-- ${__('admin.quick_contract_client_auto_link')} --`}
                                        searchPlaceholder={__('admin.quick_contract_search_client')}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-xs font-bold text-slate-700">{__('admin.quick_contract_currency')}</Label>
                                    <select
                                        value={data.currency_id}
                                        onChange={(e) => handleCurrencyChange(e.target.value)}
                                        className="w-full h-10 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:outline-none font-bold text-slate-900"
                                    >
                                        {currencies.map((curr) => (
                                            <option key={curr.id} value={curr.id}>
                                                {curr.currency} ({curr.symbol})
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Section — Live Valuation Output & Contract Actions */}
                    <div className="lg:col-span-5 space-y-6">
                        <Card className="border-2 border-slate-900 bg-white shadow-xl sticky top-24">
                            <CardHeader className="bg-slate-900 text-white rounded-t-lg py-5">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-lg font-bold flex items-center gap-2">
                                        <ShieldCheck className="w-5 h-5 text-amber-400" />
                                        {__('admin.quick_contract_results_title')}
                                    </CardTitle>
                                    <span className="text-[10px] font-extrabold uppercase tracking-widest bg-amber-400 text-slate-950 px-2.5 py-1 rounded-full">
                                        {__('admin.quick_contract_two_level_engine')}
                                    </span>
                                </div>
                            </CardHeader>
                            <CardContent className="pt-6 space-y-6">
                                {valuation ? (
                                    <>
                                        {/* Main Summary Numbers */}
                                        <div className="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200 text-center">
                                            <div>
                                                <p className="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">{__('admin.quick_contract_total_cost')}</p>
                                                <p className="text-2xl font-black text-slate-900 mt-1">
                                                    {valuation.converted_amount} <span className="text-xs font-bold text-amber-600">{valuation.currency_symbol}</span>
                                                </p>
                                                <p className="text-xs font-bold text-slate-400 mt-0.5">
                                                    ~ ${valuation.recommended_usd} USD
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">{__('admin.quick_contract_first_payment')}</p>
                                                <p className="text-2xl font-black text-emerald-600 mt-1">
                                                    {valuation.deposit_converted || (valuation.converted_amount * 0.5).toFixed(2)} <span className="text-xs font-bold text-emerald-700">{valuation.currency_symbol}</span>
                                                </p>
                                                <p className="text-[10px] font-bold text-emerald-700 mt-0.5">{__('admin.quick_contract_required_to_activate')}</p>
                                            </div>
                                        </div>

                                        <div className="flex items-center justify-between text-xs text-slate-600 border-b pb-3">
                                            <span className="flex items-center gap-1.5 font-bold"><Clock className="w-4 h-4 text-slate-400" /> {__('admin.quick_contract_expected_duration')}:</span>
                                            <span className="font-extrabold text-slate-900">{__('admin.quick_contract_days_and_hours', { days: valuation.estimated_days, hours: valuation.total_hours })}</span>
                                        </div>

                                        {/* AI Summary & Tech Stack Card */}
                                        {valuation.ai_summary && (
                                            <div className="bg-amber-50/70 border border-amber-200/80 rounded-xl p-4 space-y-3 text-xs">
                                                <div className="flex items-center gap-1.5 font-extrabold text-amber-900">
                                                    <Sparkles className="w-4 h-4 text-amber-600 shrink-0" />
                                                    <span>{__('admin.quick_contract_ai_scope_analysis')}:</span>
                                                </div>
                                                <p className="text-slate-700 leading-relaxed font-sans">
                                                    {valuation.ai_summary}
                                                </p>
                                                {valuation.tech_stack && (
                                                    <div className="pt-1 flex items-center gap-2 flex-wrap">
                                                        <span className="text-[10px] font-bold text-slate-500">{__('admin.quick_contract_suggested_tech_stack')}:</span>
                                                        <span className="bg-white text-slate-800 border border-amber-300 font-mono text-[10px] px-2 py-0.5 rounded-md font-bold">
                                                            {valuation.tech_stack}
                                                        </span>
                                                    </div>
                                                )}
                                            </div>
                                        )}

                                        {/* Key Deliverables List */}
                                        {valuation.key_features && valuation.key_features.length > 0 && (
                                            <div className="space-y-2">
                                                <p className="text-xs font-black uppercase tracking-wider text-slate-500">{__('admin.quick_contract_key_deliverables')}:</p>
                                                <div className="grid grid-cols-1 gap-1.5">
                                                    {valuation.key_features.map((feat, fIdx) => (
                                                        <div key={fIdx} className="flex items-center gap-2 text-xs text-slate-800 bg-slate-50 px-3 py-1.5 rounded-md border border-slate-100">
                                                            <Check className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                                            <span className="font-bold">{feat}</span>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}

                                        {/* Itemized Micro-Components */}
                                        <div className="space-y-3">
                                            <p className="text-xs font-black uppercase tracking-wider text-slate-500">{__('admin.quick_contract_micro_components')}:</p>
                                            <div className="max-h-72 overflow-y-auto space-y-2.5 pr-1">
                                                {valuation.micro_components?.map((comp, idx) => (
                                                    <div key={idx} className="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                                                        <div className="flex items-start justify-between gap-2">
                                                            <div>
                                                                <div className="flex items-center gap-2 flex-wrap">
                                                                    <p className="font-extrabold text-slate-900 text-xs">{comp.name_ar}</p>
                                                                    {comp.name_en && (
                                                                        <span className="text-[10px] text-slate-400 font-mono">({comp.name_en})</span>
                                                                    )}
                                                                    {comp.is_new_item && (
                                                                        <span className="bg-amber-100 text-amber-900 border border-amber-300 font-extrabold px-2 py-0.5 rounded text-[9px] flex items-center gap-1">
                                                                            <Sparkles className="w-3 h-3" /> {__('admin.quick_contract_new_price_item')}
                                                                        </span>
                                                                    )}
                                                                </div>
                                                                {comp.description_ar && (
                                                                    <p className="text-[11px] text-slate-600 mt-1 leading-relaxed">{comp.description_ar}</p>
                                                                )}
                                                            </div>
                                                            <div className="text-end shrink-0">
                                                                <p className="font-mono font-black text-slate-900 text-sm">
                                                                    {comp.converted_cost} <span className="text-[11px] font-bold text-amber-600">{comp.currency_symbol}</span>
                                                                </p>
                                                                <p className="text-[10px] font-bold text-slate-400 mt-0.5">
                                                                    ~ ${comp.cost_usd} USD
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div className="flex items-center justify-between pt-1 border-t border-slate-200/60 text-[10px]">
                                                            <span className="text-slate-500 font-bold inline-flex items-center gap-1"><Clock className="w-3 h-3" /> {__('admin.quick_contract_estimated_hours')}: <strong className="text-slate-800">{__('admin.quick_contract_hours_count', { hours: comp.estimated_hours })}</strong></span>
                                                            {comp.complexity && (
                                                                <span className="bg-slate-200/70 text-slate-700 font-bold px-2 py-0.5 rounded uppercase">
                                                                    {__('admin.price_item_complexity')}: {comp.complexity}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        {/* Clarification Trigger Banner */}
                                        {valuation.needs_clarification && (
                                            <div className="bg-gradient-to-r from-amber-500/10 via-amber-400/15 to-amber-500/10 border border-amber-400/40 rounded-xl p-3.5 flex items-center justify-between gap-3 text-xs">
                                                <div className="flex items-center gap-2">
                                                    <Sparkles className="w-4 h-4 text-amber-600 shrink-0 animate-pulse" />
                                                    <span className="font-extrabold text-amber-950">{__('admin.quick_contract_multiple_scope_options')}</span>
                                                </div>
                                                <Button
                                                    type="button"
                                                    onClick={() => setShowClarificationModal(true)}
                                                    className="bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-[11px] px-3.5 py-1.5 rounded-lg shrink-0 shadow-sm gap-1"
                                                >
                                                    {__('admin.quick_contract_customize_scope')} <ArrowRight className="w-3.5 h-3.5 rtl:rotate-180" />
                                                </Button>
                                            </div>
                                        )}

                                        {/* Action Button */}
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="w-full bg-slate-900 hover:bg-slate-800 text-white font-extrabold py-6 rounded-xl text-xs uppercase tracking-wider gap-2 shadow-lg"
                                        >
                                            <ArrowRight className="w-4 h-4 rtl:rotate-180" />
                                            {processing ? __('admin.quick_contract_creating') : __('admin.quick_contract_create')}
                                        </Button>
                                    </>
                                ) : (
                                    <div className="text-center py-10 space-y-3">
                                        <Calculator className="w-12 h-12 text-slate-300 mx-auto" />
                                        <p className="text-sm font-bold text-slate-600">{__('admin.quick_contract_waiting_title')}</p>
                                        <p className="text-xs text-slate-400 max-w-xs mx-auto">
                                            {__('admin.quick_contract_waiting_hint')}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </form>

                {/* AI Clarification Modal */}
                <Dialog open={showClarificationModal} onOpenChange={setShowClarificationModal}>
                    <DialogContent className="max-w-xl bg-white border border-slate-200 shadow-2xl rounded-2xl p-6">
                        <DialogHeader className="pb-3 border-b border-slate-100">
                            <div className="flex items-center gap-2 text-amber-600 font-extrabold text-xs uppercase tracking-wider">
                                <Sparkles className="w-4 h-4 text-amber-500 animate-pulse" />
                                {__('admin.quick_contract_ai_clarification')}
                            </div>
                            <DialogTitle className="text-base font-extrabold text-slate-900 mt-1 leading-relaxed">
                                {valuation?.clarifying_question || __('admin.quick_contract_default_clarifying_question')}
                            </DialogTitle>
                            <DialogDescription className="text-xs text-slate-500 mt-1">
                                {__('admin.quick_contract_choose_option_hint')}
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-3 py-4">
                            {valuation?.suggested_answers?.map((ans, aIdx) => (
                                <div
                                    key={aIdx}
                                    onClick={() => {
                                        setSelectedAnswer(ans);
                                        setCustomAnswer('');
                                    }}
                                    className={`p-3.5 rounded-xl border cursor-pointer transition-all flex items-start gap-3 text-xs ${
                                        selectedAnswer === ans
                                            ? 'border-amber-500 bg-amber-50/90 ring-2 ring-amber-500/20 text-slate-950 font-bold shadow-sm'
                                            : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80 text-slate-800 font-medium'
                                    }`}
                                >
                                    <div className={`w-5 h-5 rounded-full border flex items-center justify-center shrink-0 mt-0.5 ${
                                        selectedAnswer === ans ? 'border-amber-600 bg-amber-500 text-slate-950 font-extrabold text-[10px]' : 'border-slate-300 bg-white'
                                    }`}>
                                        {selectedAnswer === ans ? <Check className="w-3 h-3" /> : aIdx + 1}
                                    </div>
                                    <span className="leading-relaxed">{ans}</span>
                                </div>
                            ))}

                            {/* Custom Answer Input */}
                            <div className="pt-2">
                                <Label className="text-[11px] font-bold text-slate-700">{__('admin.quick_contract_custom_answer_label')}:</Label>
                                <Input
                                    type="text"
                                    placeholder={__('admin.quick_contract_custom_answer_placeholder')}
                                    value={customAnswer}
                                    onChange={(e) => {
                                        setCustomAnswer(e.target.value);
                                        setSelectedAnswer(e.target.value);
                                    }}
                                    className="text-xs mt-1.5 border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                />
                            </div>
                        </div>

                        <DialogFooter className="flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setShowClarificationModal(false)}
                                className="text-xs font-bold text-slate-600 border-slate-300 hover:bg-slate-100"
                            >
                                {__('admin.quick_contract_skip_clarification')}
                            </Button>
                            <Button
                                type="button"
                                disabled={!selectedAnswer && !customAnswer}
                                onClick={() => {
                                    const ansToSubmit = customAnswer.trim() || selectedAnswer;
                                    if (ansToSubmit) {
                                        setShowClarificationModal(false);
                                        handleCalculate(data.currency_id, ansToSubmit);
                                    }
                                }}
                                className="bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs px-6 py-2.5 rounded-xl shadow-md gap-2"
                            >
                                {__('admin.quick_contract_confirm_clarification')} <ArrowRight className="w-4 h-4 rtl:rotate-180" />
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AdminSidebarLayout>
    );
}
