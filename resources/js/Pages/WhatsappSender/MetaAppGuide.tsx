import { __ } from '@/lib/i18n';
import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Link2, Wrench } from 'lucide-react';

export default function MetaAppGuide() {
    const appCallback = `${window.location.origin}/whatsapp-sender/auth/facebook/callback`;
    const guestCallback = `${window.location.origin}/whatsapp-sender/guest/connect-callback`;

    return (
        <AuthenticatedLayout>
            <Head title={__('whatsapp.meta_guide_page_title')} />

            <div className="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-12">
                {/* Header Section */}
                <div className="space-y-4 text-center">
                    <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-xs font-semibold">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                            <circle cx="12" cy="12" r="12" fill="#25D366" />
                            <path d="M12.012 5.5c-3.585 0-6.5 2.915-6.5 6.5 0 1.144.298 2.257.865 3.242L5.5 18.5l3.429-.9c.945.516 2.012.79 3.083.79 3.585 0 6.5-2.915 6.5-6.5s-2.915-6.5-6.5-6.5z" fill="#FFF" />
                        </svg>
                        WhatsApp WABA Cloud API
                    </div>
                    <h1 className="text-4xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-50 font-sans">
                        {__('whatsapp.meta_guide_title')}
                    </h1>
                    <p className="text-base text-zinc-500 dark:text-zinc-400 max-w-2xl mx-auto">
                        {__('whatsapp.meta_guide_subtitle')}
                    </p>
                </div>

                {/* Redirect URLs Box */}
                <div className="bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 md:p-8 space-y-6">
                    <h3 className="text-lg font-bold text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                        <Link2 className="w-5 h-5 text-emerald-500" aria-hidden="true" />
                        {__('whatsapp.meta_guide_redirects_title')}
                    </h3>
                    <p className="text-sm text-zinc-500 dark:text-zinc-400">
                        {__('whatsapp.meta_guide_redirects_desc')}
                    </p>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-850 p-4 rounded-2xl space-y-2">
                            <span className="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block">
                                {__('whatsapp.meta_guide_workspace_callback')}
                            </span>
                            <div className="flex items-center gap-2 bg-zinc-50 dark:bg-zinc-900 p-2 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-mono select-all overflow-x-auto text-zinc-700 dark:text-zinc-300">
                                {appCallback}
                            </div>
                        </div>

                        <div className="bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-850 p-4 rounded-2xl space-y-2">
                            <span className="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider block">
                                {__('whatsapp.meta_guide_guest_callback')}
                            </span>
                            <div className="flex items-center gap-2 bg-zinc-50 dark:bg-zinc-900 p-2 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-mono select-all overflow-x-auto text-zinc-700 dark:text-zinc-300">
                                {guestCallback}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Step-by-Step Guide */}
                <div className="space-y-8">
                    <h2 className="text-2xl font-bold text-zinc-900 dark:text-zinc-50 flex items-center gap-2"><Wrench className="w-6 h-6 text-zinc-500" aria-hidden="true" />{__('whatsapp.meta_guide_steps_title')}</h2>

                    <div className="space-y-6">
                        {/* Step 1 */}
                        <div className="flex gap-6 items-start">
                            <div className="w-10 h-10 rounded-full bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 flex items-center justify-center font-bold font-mono shrink-0 shadow-md">
                                01
                            </div>
                            <div className="space-y-2 pt-1.5">
                                <h4 className="text-base font-bold text-zinc-900 dark:text-zinc-50">{__('whatsapp.meta_guide_step1_title')}</h4>
                                <p className="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">
                                    {__('whatsapp.meta_guide_step1_desc')}{' '}
                                    <a href="https://developers.facebook.com/" target="_blank" rel="noopener noreferrer" className="text-emerald-500 hover:underline font-semibold">Meta for Developers</a>
                                </p>
                            </div>
                        </div>

                        {/* Step 2 */}
                        <div className="flex gap-6 items-start">
                            <div className="w-10 h-10 rounded-full bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 flex items-center justify-center font-bold font-mono shrink-0 shadow-md">
                                02
                            </div>
                            <div className="space-y-2 pt-1.5">
                                <h4 className="text-base font-bold text-zinc-900 dark:text-zinc-50">{__('whatsapp.meta_guide_step2_title')}</h4>
                                <p className="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">
                                    {__('whatsapp.meta_guide_step2_desc')}
                                </p>
                            </div>
                        </div>

                        {/* Step 3 */}
                        <div className="flex gap-6 items-start">
                            <div className="w-10 h-10 rounded-full bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 flex items-center justify-center font-bold font-mono shrink-0 shadow-md">
                                03
                            </div>
                            <div className="space-y-2 pt-1.5">
                                <h4 className="text-base font-bold text-zinc-900 dark:text-zinc-50">{__('whatsapp.meta_guide_step3_title')}</h4>
                                <p className="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">
                                    {__('whatsapp.meta_guide_step3_desc')}
                                </p>
                            </div>
                        </div>

                        {/* Step 4 */}
                        <div className="flex gap-6 items-start">
                            <div className="w-10 h-10 rounded-full bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 flex items-center justify-center font-bold font-mono shrink-0 shadow-md">
                                04
                            </div>
                            <div className="space-y-2 pt-1.5">
                                <h4 className="text-base font-bold text-zinc-900 dark:text-zinc-50">{__('whatsapp.meta_guide_step4_title')}</h4>
                                <p className="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">
                                    {__('whatsapp.meta_guide_step4_desc')}
                                </p>
                            </div>
                        </div>

                        {/* Step 5 */}
                        <div className="flex gap-6 items-start">
                            <div className="w-10 h-10 rounded-full bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 flex items-center justify-center font-bold font-mono shrink-0 shadow-md">
                                05
                            </div>
                            <div className="space-y-2 pt-1.5">
                                <h4 className="text-base font-bold text-zinc-900 dark:text-zinc-50">{__('whatsapp.meta_guide_step5_title')}</h4>
                                <p className="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">
                                    {__('whatsapp.meta_guide_step5_desc')}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Call to action */}
                <div className="flex justify-center gap-4 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                    <Link
                        href="/whatsapp-sender"
                        className="bg-zinc-900 hover:bg-zinc-800 dark:bg-zinc-100 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 text-sm font-bold px-6 py-3 rounded-2xl transition shadow-md"
                    >
                        {__('whatsapp.meta_guide_back')}
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
