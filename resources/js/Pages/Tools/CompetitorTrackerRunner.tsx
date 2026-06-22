import React from 'react';
import { Radar } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';

export default function CompetitorTrackerRunner({ tool }: any) {
    return (
        <div className="min-h-screen bg-slate-950 font-sans text-slate-100 flex flex-col items-center justify-center p-8">
            <div className="max-w-md text-center space-y-6">
                <div className="w-16 h-16 bg-cyan-500/10 border border-cyan-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <Radar className="w-8 h-8 text-cyan-400" />
                </div>
                <h1 className="text-2xl font-bold">{__('general.competitor_analytics')}</h1>
                <p className="text-sm text-slate-400">
                    {__('general.this_tool_allows_you_to_track_competitor')}</p>
                <div className="pt-8 border-t border-slate-800">
                    <p className="text-xs text-slate-500 uppercase tracking-widest font-black mb-4">{__('general.coming_soon')}</p>
                    <Button variant="outline" className="bg-slate-900 border-slate-800 text-slate-300 hover:bg-slate-800">{__('general.view_documentation')}</Button>
                </div>
            </div>
        </div>
    );
}
