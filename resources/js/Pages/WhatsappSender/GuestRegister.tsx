import React from 'react';
import { Head } from '@inertiajs/react';

interface Props {
    business: {
        name: string;
        uuid: string;
    };
    facebookLoginUrl: string;
}

export default function GuestRegister({ business, facebookLoginUrl }: Props) {
    return (
        <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center p-6 relative overflow-hidden font-sans">
            {/* Ambient background glows */}
            <div className="absolute top-[-20%] left-[-20%] w-[60%] h-[60%] rounded-full bg-indigo-500/10 blur-[120px]" />
            <div className="absolute bottom-[-20%] right-[-20%] w-[60%] h-[60%] rounded-full bg-emerald-500/10 blur-[120px]" />

            <Head>
                <title>{`Connect WhatsApp to ${business.name} - Musoftware`}</title>
                <meta name="description" content={`Link your business WhatsApp phone number to ${business.name} securely.`} />
            </Head>

            <div className="max-w-md w-full bg-slate-900/60 backdrop-blur-xl border border-slate-800 rounded-3xl p-8 shadow-2xl relative z-10 text-center">
                {/* Meta Icon / Logo */}
                <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-500/10 text-indigo-400 mb-6 border border-indigo-500/20">
                    <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                </div>

                <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-white to-slate-400 bg-clip-text text-transparent mb-2">
                    Connect WhatsApp Number
                </h1>
                <p className="text-slate-400 text-sm mb-8 leading-relaxed">
                    You have been invited to link your Facebook WhatsApp Business account to <span className="font-semibold text-slate-200">{business.name}</span>. This allows managing your numbers and sending template messages.
                </p>

                <div className="space-y-4">
                    <a
                        href={facebookLoginUrl}
                        className="w-full flex items-center justify-center gap-3 px-6 py-4 rounded-2xl bg-blue-600 hover:bg-blue-500 active:scale-[0.98] transition-all font-semibold shadow-lg shadow-blue-600/20 text-white group"
                    >
                        {/* Facebook Custom Icon */}
                        <svg className="w-5 h-5 fill-current" viewBox="0 0 24 24">
                            <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/>
                        </svg>
                        <span>Connect with Facebook</span>
                    </a>

                    <div className="flex items-center justify-center gap-2 text-xs text-slate-500 mt-6">
                        <svg className="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Secure OAuth integration via Meta Cloud API</span>
                    </div>
                </div>
            </div>

            {/* Footer */}
            <div className="mt-8 text-center text-slate-600 text-xs relative z-10">
                Powered by Musoftware Business Platform &copy; {new Date().getFullYear()}
            </div>
        </div>
    );
}
