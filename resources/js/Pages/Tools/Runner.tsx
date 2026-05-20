import React, { Suspense, lazy } from 'react';
import { Head } from '@inertiajs/react';

// Dynamic import for tool runner components based on the DB field `runner_component`
const components: Record<string, React.ElementType> = {
    'ScreenshotFeedbackRunner': lazy(() => import('./ScreenshotFeedbackRunner')),
    'WhatsAppSenderRunner':     lazy(() => import('./WhatsAppSenderRunner')),
    'whatsapp-sender-pro':      lazy(() => import('./WhatsAppSenderRunner')),
    'whatsapp-sender':          lazy(() => import('./WhatsAppSenderRunner')),
    'b2b-prospector':           lazy(() => import('./B2BProspectorRunner')),
    'viral-autopsy':            lazy(() => import('./ViralAutopsyRunner')),
    'hook-analyzer':            lazy(() => import('./HookAnalyzerRunner')),
    'format-extractor':         lazy(() => import('./FormatExtractorRunner')),
    'IPTVDownloaderRunner':     lazy(() => import('./IPTVDownloaderRunner')),
    'iptv-downloader':          lazy(() => import('./IPTVDownloaderRunner')),
    'opensooq':                 lazy(() => import('./OpensooqRunner')),
    'SnapDownloaderRunner':     lazy(() => import('./SnapDownloaderRunner')),
};

export default function Runner({ tool, subscription, runtimePort, pluginSlug }: any) {
    const Component = components[tool.runner_component];

    if (!Component) {
        return (
            <div className="min-h-screen bg-slate-50 flex items-center justify-center p-8 font-sans">
                <div className="text-center max-w-md">
                    <h1 className="text-2xl font-bold text-slate-900 mb-2">Component Not Found</h1>
                    <p className="text-slate-500">The interface for {tool.title} could not be loaded because the component "{tool.runner_component}" does not exist.</p>
                </div>
            </div>
        );
    }

    return (
        <>
            <Head title={tool.title} />
            <Suspense fallback={
                <div className="min-h-screen bg-slate-50 flex items-center justify-center">
                    <div className="w-8 h-8 border-2 border-slate-900 border-t-transparent rounded-full animate-spin" />
                </div>
            }>
                <Component tool={tool} subscription={subscription} runtimePort={runtimePort} pluginSlug={pluginSlug} />
            </Suspense>
        </>
    );
}
