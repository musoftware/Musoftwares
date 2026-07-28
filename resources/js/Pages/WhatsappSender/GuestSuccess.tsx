import React from 'react';
import { Head } from '@inertiajs/react';

interface Props {
    business: {
        name: string;
    };
}

export default function GuestSuccess({ business }: Props) {
    return (
        <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center p-6 relative overflow-hidden font-sans">
            {/* Ambient background glows */}
            <div className="absolute top-[-20%] left-[-20%] w-[60%] h-[60%] rounded-full bg-indigo-500/10 blur-[120px]" />
            <div className="absolute bottom-[-20%] right-[-20%] w-[60%] h-[60%] rounded-full bg-emerald-500/10 blur-[120px]" />

            <Head>
                <title>{`Connection Successful - ${business.name}`}</title>
            </Head>

            <div className="max-w-md w-full bg-slate-900/60 backdrop-blur-xl border border-slate-800 rounded-3xl p-8 shadow-2xl relative z-10 text-center">
                {/* Success Icon */}
                <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-500/10 text-emerald-400 mb-6 border border-emerald-500/20 animate-bounce">
                    <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-emerald-400 to-teal-200 bg-clip-text text-transparent mb-2">
                    Connection Successful!
                </h1>
                <p className="text-slate-400 text-sm mb-8 leading-relaxed">
                    Your Meta WhatsApp Business account has been linked successfully to <span className="font-semibold text-slate-200">{business.name}</span>. The business manager can now send templates and manage campaigns.
                </p>

                <p className="text-xs text-slate-500">
                    You can safely close this browser window or tab now.
                </p>
            </div>

            {/* Footer */}
            <div className="mt-8 text-center text-slate-600 text-xs relative z-10">
                Powered by Musoftware Business Platform &copy; {new Date().getFullYear()}
            </div>
        </div>
    );
}
