import React from 'react';
import { Button } from '@/Components/ui/button';
import { Send, Rocket } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function PublishStep({ data, setStep, processing }: any) {
    return (
        <div className="max-w-7xl mx-auto space-y-8 py-10">
            <div className="text-center space-y-4">
                <div className="w-20 h-20 bg-indigo-100 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-sm">
                    <Rocket className="w-10 h-10 text-indigo-600" />
                </div>
                <h2 className="text-3xl font-bold text-slate-900">{__('general.almost_there')}</h2>
                <p className="text-slate-500 max-w-7xl mx-auto leading-relaxed">{__('general.you_re_just_one_click_away_from_publishing_your_service_review_your_live_preview_on_the_right_to_make_sure_everything_looks_perfect')}</p>
            </div>

            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mt-8">
                <div className="p-6 bg-slate-50 border-b border-slate-100">
                    <h3 className="font-bold text-slate-900 flex items-center gap-2">
                        <span className="text-xl">📋</span>{__('general.final_review_checklist')}</h3>
                </div>
                <div className="p-6 space-y-4">
                    <div className="flex items-center justify-between py-2 border-b border-dashed border-slate-200">
                        <span className="text-sm font-medium text-slate-600">{__('general.title_category')}</span>
                        <button type="button" onClick={() => setStep(1)} className="text-xs font-bold text-indigo-600 hover:underline">{__('general.edit')}</button>
                    </div>
                    <div className="flex items-center justify-between py-2 border-b border-dashed border-slate-200">
                        <span className="text-sm font-medium text-slate-600">Pricing Packages ({data.packages.length})</span>
                        <button type="button" onClick={() => setStep(2)} className="text-xs font-bold text-indigo-600 hover:underline">{__('general.edit')}</button>
                    </div>
                    <div className="flex items-center justify-between py-2 border-b border-dashed border-slate-200">
                        <span className="text-sm font-medium text-slate-600">Description, FAQ ({data.faq.length}), Requirements ({data.requirements.length})</span>
                        <button type="button" onClick={() => setStep(3)} className="text-xs font-bold text-indigo-600 hover:underline">{__('general.edit')}</button>
                    </div>
                    <div className="flex items-center justify-between py-2">
                        <span className="text-sm font-medium text-slate-600">Gallery ({data.gallery.length} Images)</span>
                        <button type="button" onClick={() => setStep(4)} className="text-xs font-bold text-indigo-600 hover:underline">{__('general.edit')}</button>
                    </div>
                </div>
            </div>

            <div className="flex items-start gap-4 bg-amber-50 border border-amber-200/60 rounded-2xl p-5">
                <span className="text-2xl">⏳</span>
                <div className="text-sm text-amber-800 leading-relaxed">
                    <strong className="block mb-1 font-bold">{__('general.review_process')}</strong>{__('general.your_service_will_be_manually_reviewed_by_our_quality_team_to_ensure_it_meets_marketplace_guidelines_you_ll_be_notified_within_24_hours_once_it_goes_live')}</div>
            </div>

            <Button
                type="submit"
                size="lg"
                disabled={processing}
                className="w-full h-14 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-lg gap-2 shadow-lg shadow-indigo-200 transition-all hover:scale-[1.02]"
            >
                <Send className="w-5 h-5" />
                {processing ? 'Submitting…' : 'Submit for Review'}
            </Button>
        </div>
    );
}
