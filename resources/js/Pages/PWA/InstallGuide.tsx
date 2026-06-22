import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Share, PlusSquare } from 'lucide-react';
import { __ } from '@/lib/i18n';
import ApplicationLogo from '@/Components/ApplicationLogo';

export default function InstallGuide() {
    return (
        <div className="min-h-screen bg-slate-50 flex flex-col font-sans">
            <Head title={__('general.install_app')} />

            <div className="max-w-7xl mx-auto w-full flex-1 flex flex-col p-6 mt-8 sm:mt-12 bg-white rounded-2xl shadow-sm border border-slate-100">
                <div className="mb-8">
                    <Link href="/" className="inline-flex items-center text-slate-500 hover:text-slate-900 transition-colors">
                        <ArrowLeft className="w-5 h-5 me-2" />
                        <span className="font-medium">{__('general.back')}</span>
                    </Link>
                </div>

                <div className="flex flex-col items-center text-center mb-8">
                    <div className="w-20 h-20 bg-emerald-50 rounded-2xl flex items-center justify-center mb-6 shadow-sm border border-emerald-100">
                        <ApplicationLogo className="w-12 h-12 text-emerald-600" />
                    </div>
                    <h1 className="text-2xl font-bold text-slate-900 mb-3">{__('general.install_app_title')}</h1>
                    <p className="text-slate-600 leading-relaxed">
                        {__('general.install_app_description')}
                    </p>
                </div>

                <div className="space-y-6 flex-1">
                    <div className="flex items-start gap-4">
                        <div className="flex-shrink-0 w-8 h-8 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-sm">
                            1
                        </div>
                        <div>
                            <p className="text-slate-800 font-medium mb-1">
                                {__('general.install_app_step_1')}
                            </p>
                            <p className="text-sm text-slate-500 mb-2">
                                {__('general.install_app_step_1_desc')}
                            </p>
                            <div className="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-slate-100 text-slate-700 border border-slate-200">
                                <Share className="w-5 h-5" />
                            </div>
                        </div>
                    </div>

                    <div className="flex items-start gap-4">
                        <div className="flex-shrink-0 w-8 h-8 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-sm">
                            2
                        </div>
                        <div>
                            <p className="text-slate-800 font-medium mb-1">
                                {__('general.install_app_step_2')}
                            </p>
                            <p className="text-sm text-slate-500 mb-2">
                                {__('general.install_app_step_2_desc')}
                            </p>
                            <div className="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-slate-100 text-slate-700 border border-slate-200">
                                <PlusSquare className="w-5 h-5" />
                            </div>
                        </div>
                    </div>

                    <div className="flex items-start gap-4">
                        <div className="flex-shrink-0 w-8 h-8 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-sm">
                            3
                        </div>
                        <div>
                            <p className="text-slate-800 font-medium mb-1">
                                {__('general.install_app_step_3')}
                            </p>
                            <p className="text-sm text-slate-500">
                                {__('general.install_app_step_3_desc')}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
