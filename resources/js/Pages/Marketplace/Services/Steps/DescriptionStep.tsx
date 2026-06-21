import React from 'react';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { cn } from '@/lib/utils';
import { Plus, Trash2, HelpCircle, ListChecks } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function DescriptionStep({ data, setData, errors }: any) {
    const addFaq = () => {
        if (data.faq.length < 10) setData('faq', [...data.faq, { question: '', answer: '' }]);
    };
    
    const updateFaq = (idx: number, field: string, val: string) => {
        const newFaq = [...data.faq];
        newFaq[idx] = { ...newFaq[idx], [field]: val };
        setData('faq', newFaq);
    };

    const removeFaq = (idx: number) => {
        setData('faq', data.faq.filter((_: any, i: number) => i !== idx));
    };

    const addReq = () => {
        if (data.requirements.length < 10) setData('requirements', [...data.requirements, '']);
    };

    const updateReq = (idx: number, val: string) => {
        const newReq = [...data.requirements];
        newReq[idx] = val;
        setData('requirements', newReq);
    };

    const removeReq = (idx: number) => {
        setData('requirements', data.requirements.filter((_: any, i: number) => i !== idx));
    };

    return (
        <div className="space-y-10">
            <div className="space-y-4">
                <div>
                    <h2 className="text-2xl font-bold text-slate-900 mb-1">Description</h2>
                    <p className="text-sm text-slate-500">{__('general.briefly_describe_your_service')}</p>
                </div>

                <div className="space-y-2">
                    <textarea
                        value={data.description}
                        onChange={e => setData('description', e.target.value)}
                        rows={12}
                        placeholder={__('general.tell_buyers_exactly_what_you_will_deliver')}
                        className={cn(
                            'w-full rounded-2xl border bg-white px-5 py-4 text-sm resize-y focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all leading-relaxed shadow-sm',
                            errors.description ? 'border-red-400' : 'border-slate-200'
                        )}
                    />
                    <div className="flex justify-between text-xs font-medium">
                        <span className={data.description.length < 100 ? 'text-amber-500' : 'text-emerald-600'}>
                            {data.description.length < 100 ? `${100 - data.description.length} more characters needed` : '✓ Looking great!'}
                        </span>
                        <span className="text-slate-400">{data.description.length} characters</span>
                    </div>
                    {errors.description && <p className="text-xs text-red-500 font-medium">{errors.description}</p>}
                </div>
            </div>

            <div className="border-t border-slate-200 pt-10 space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h3 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <ListChecks className="w-5 h-5 text-indigo-500" />{__('general.buyer_requirements')}</h3>
                        <p className="text-sm text-slate-500">{__('general.tell_buyers_what_you_need_to_get_started')}</p>
                    </div>
                    {data.requirements.length < 10 && (
                        <button type="button" onClick={addReq} className="text-sm font-bold text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition-colors">
                            + Add Requirement
                        </button>
                    )}
                </div>

                <div className="space-y-3">
                    {data.requirements.map((req: string, i: number) => (
                        <div key={i} className="flex gap-2 items-start bg-slate-50 p-2 rounded-xl border border-slate-100">
                            <Input 
                                value={req} 
                                onChange={e => updateReq(i, e.target.value)} 
                                placeholder={__('general.e_g_please_provide_your_brand_guidelines')}
                                className="bg-white"
                            />
                            <button type="button" onClick={() => removeReq(i)} className="p-2.5 text-slate-400 hover:text-red-500 hover:bg-white rounded-lg transition-colors">
                                <Trash2 className="w-4 h-4" />
                            </button>
                        </div>
                    ))}
                    {data.requirements.length === 0 && (
                        <div className="text-center py-6 border-2 border-dashed border-slate-200 rounded-2xl text-slate-400 text-sm">{__('general.no_requirements_added_yet')}</div>
                    )}
                </div>
            </div>

            <div className="border-t border-slate-200 pt-10 space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h3 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <HelpCircle className="w-5 h-5 text-indigo-500" />{__('general.frequently_asked_questions')}</h3>
                        <p className="text-sm text-slate-500">{__('general.add_q_a_to_answer_common_questions_from_buyers')}</p>
                    </div>
                    {data.faq.length < 10 && (
                        <button type="button" onClick={addFaq} className="text-sm font-bold text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition-colors">
                            + Add FAQ
                        </button>
                    )}
                </div>

                <div className="space-y-4">
                    {data.faq.map((item: any, i: number) => (
                        <div key={i} className="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm space-y-3 relative group">
                            <button type="button" onClick={() => removeFaq(i)} className="absolute end-4 top-4 text-slate-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all">
                                <Trash2 className="w-4 h-4" />
                            </button>
                            <div className="pe-8">
                                <Label className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Question</Label>
                                <Input 
                                    value={item.question} 
                                    onChange={e => updateFaq(i, 'question', e.target.value)} 
                                    placeholder={__('general.e_g_do_you_provide_source_files')}
                                    className="h-10 font-medium"
                                />
                            </div>
                            <div>
                                <Label className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Answer</Label>
                                <textarea
                                    value={item.answer}
                                    onChange={e => updateFaq(i, 'answer', e.target.value)}
                                    placeholder={__('general.e_g_yes_all_packages_include_the_source_files')}
                                    rows={2}
                                    className="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                                />
                            </div>
                        </div>
                    ))}
                    {data.faq.length === 0 && (
                        <div className="text-center py-6 border-2 border-dashed border-slate-200 rounded-2xl text-slate-400 text-sm">{__('general.no_faqs_added_yet')}</div>
                    )}
                </div>
            </div>
        </div>
    );
}
