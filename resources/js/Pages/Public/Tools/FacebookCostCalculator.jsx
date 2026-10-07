import { useEffect } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { SeoHead } from '@/Components/ui/SeoHead';
import { Loader2 } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function FacebookCostCalculator() {
    useEffect(() => {
        window.location.href = "https://tools.musoftwares.com/tools/facebook-page-cost";
    }, []);

    return (
        <PublicLayout>
            <SeoHead title={__('tools.fb_title')} description={__('tools.fb_desc')} />
            <div className="min-h-[60vh] flex flex-col items-center justify-center bg-[#fcfcfc] text-[#111111] pt-24 pb-16">
                <Loader2 className="h-10 w-10 animate-spin text-slate-950 mb-4" />
                <h2 className="text-xl font-bold tracking-tight mb-2">{__('tools.redirect_to_tool_heading')}</h2>
                <p className="text-sm text-slate-500">{__('tools.redirect_to_tool_platform')}</p>
            </div>
        </PublicLayout>
    );
}
