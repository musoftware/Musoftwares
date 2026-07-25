import React, { useState } from 'react';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { cn } from '@/lib/utils';
import { X } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function OverviewStep({ data, setData, errors, categories }: any) {
    const [tagInput, setTagInput] = useState('');

    const processTagText = (text: string) => {
        const items = text
            .split(/[\r\n,]+/)
            .map(t => t.trim().toLowerCase())
            .filter(t => t.length > 0);

        if (items.length === 0) return;

        const newTags = [...data.tags];
        for (const item of items) {
            if (newTags.length >= 10) break;
            if (!newTags.includes(item)) {
                newTags.push(item);
            }
        }
        setData('tags', newTags);
        setTagInput('');
    };

    const addTag = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            e.stopPropagation();
            processTagText(tagInput);
        }
    };

    const handlePaste = (e: React.ClipboardEvent) => {
        const pastedText = e.clipboardData.getData('text');
        if (pastedText && (pastedText.includes('\n') || pastedText.includes('\r') || pastedText.includes(','))) {
            e.preventDefault();
            processTagText(pastedText);
        }
    };

    const removeTag = (tag: string) => {
        setData('tags', data.tags.filter((t: string) => t !== tag));
    };

    return (
        <div className="space-y-8">
            <div>
                <h2 className="text-2xl font-bold text-slate-900 mb-1">{__('general.what_are_you_offering')}</h2>
                <p className="text-sm text-slate-500">{__('general.start_strong_with_a_clear_title_category_and_relevant_search_tags')}</p>
            </div>

            <div className="space-y-3">
                <Label className="text-sm font-semibold text-slate-700">{__('general.service_title')}<span className="text-red-500">*</span>
                </Label>
                <div className="relative">
                    <span className="absolute start-4 top-1/2 -translate-y-1/2 text-slate-500 font-medium">{__('general.i_will')}</span>
                    <Input
                        value={data.title}
                        onChange={e => setData('title', e.target.value)}
                        placeholder={__('general.design_a_professional_logo_for_your_brand')}
                        maxLength={80}
                        className={cn('h-14 text-base ps-14 font-medium', errors.title && 'border-red-400 focus-visible:ring-red-400')}
                    />
                </div>
                <div className="flex justify-between text-xs text-slate-400 font-medium">
                    <span className={data.title.length < 10 ? 'text-amber-500' : 'text-emerald-600'}>
                        {data.title.length < 10 ? `${10 - data.title.length} more characters needed` : '✓ Looks good'}
                    </span>
                    <span>{data.title.length}/80 max</span>
                </div>
                {errors.title && <p className="text-xs text-red-500 font-medium">{errors.title}</p>}
            </div>

            <div className="space-y-3">
                <Label className="text-sm font-semibold text-slate-700">
                    {__('general.category')}<span className="text-red-500">*</span>
                </Label>
                <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    {categories.map((cat: any) => (
                        <button
                            type="button"
                            key={cat.id}
                            onClick={() => setData('category_id', String(cat.id))}
                            className={cn(
                                'px-4 py-3 rounded-xl border text-sm font-medium text-start transition-all',
                                String(data.category_id) === String(cat.id)
                                    ? 'border-indigo-600 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600'
                                    : 'border-slate-200 bg-white text-slate-700 hover:border-indigo-300 hover:bg-slate-50'
                            )}
                        >
                            {cat.name}
                        </button>
                    ))}
                </div>
                {errors.category_id && <p className="text-xs text-red-500 font-medium">{errors.category_id}</p>}
            </div>

            <div className="space-y-3">
                <div className="flex items-center justify-between">
                    <Label className="text-sm font-semibold text-slate-700">{__('general.search_tags')}</Label>
                    <span className="text-xs text-slate-500">{data.tags.length}/10 tags</span>
                </div>
                <div className="flex flex-wrap gap-2 mb-2">
                    {data.tags.map((tag: string) => (
                        <span key={tag} className="flex items-center gap-1 bg-indigo-100 text-indigo-700 px-3 py-1.5 rounded-lg text-sm font-medium">
                            {tag}
                            <button type="button" onClick={() => removeTag(tag)} className="hover:text-indigo-900 transition-colors">
                                <X className="w-3.5 h-3.5" />
                            </button>
                        </span>
                    ))}
                </div>
                <Input
                    value={tagInput}
                    onChange={e => setTagInput(e.target.value)}
                    onKeyDown={addTag}
                    onPaste={handlePaste}
                    placeholder={__('general.enter_keywords_and_press_enter')}
                    disabled={data.tags.length >= 10}
                    className="h-12"
                />
                <p className="text-xs text-slate-500">{__('general.use_up_to_10_relevant_tags_so_buyers_can_easily_find_your_service') || 'Use up to 10 relevant tags so buyers can easily find your service'}</p>
                {errors.tags && <p className="text-xs text-red-500 font-medium">{errors.tags}</p>}
            </div>
        </div>
    );
}
