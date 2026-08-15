import { useEffect } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { SeoHead } from '@/Components/ui/SeoHead';
import { Loader2 } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function CompetitorTechSpy() {
    useEffect(() => {
        window.location.href = "https://tools.musoftwares.com/tools/competitor-tech-spy";
    }, []);

    return (
        <PublicLayout>
            <SeoHead title={__('tools.spy_title')} description={__('tools.spy_desc')} />
            <div className="min-h-[60vh] flex flex-col items-center justify-center bg-[#fcfcfc] text-[#111111] pt-24 pb-16">
                <Loader2 className="h-10 w-10 animate-spin text-slate-950 mb-4" />
                <h2 className="text-xl font-bold tracking-tight mb-2">جاري توجيهك إلى الأداة...</h2>
                <p className="text-sm text-slate-500">Redirecting you to the tools platform...</p>
            </div>
        </PublicLayout>
    );
}
