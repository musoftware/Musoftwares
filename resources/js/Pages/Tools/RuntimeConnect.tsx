import React, { useState, useEffect } from 'react';
import { Head, router, usePage, Link } from '@inertiajs/react';
import { Monitor, Wifi, CheckCircle2, AlertCircle, Loader2, Shield, Terminal, ArrowRight, Download, ExternalLink } from 'lucide-react';

interface Props {
    code:        string;
    port:        number;
    userName:    string;
    userEmail:   string;
    success?:    boolean;
    missingCode?: boolean;
    errors?:     Record<string, string>;
}

export default function RuntimeConnect({ code, port, userName, userEmail, success, missingCode, errors }: Props) {
    const [loading, setLoading] = useState(false);
    const [localError, setLocalError] = useState<string | null>(null);

    function handleAllow() {
        setLoading(true);
        setLocalError(null);
        router.post(route('runtime.authorize'), { code, port }, {
            onError: (errs) => {
                setLocalError(errs.callback || 'Connection failed. Make sure the runtime is running.');
                setLoading(false);
            },
            onFinish: () => setLoading(false),
        });
    }

    function handleDeny() {
        window.close();
        // Fallback if window.close() is blocked
        router.visit('/');
    }

    const callbackError = errors?.callback || localError;

    useEffect(() => {
        if (callbackError) {
            window.location.assign('musoftware://open');
        }
    }, [callbackError]);

    if (missingCode) {
        return (
            <div className="min-h-screen bg-slate-950 flex items-center justify-center p-6">
                <Head title="Runtime Connection Required — musoftware" />
                <div className="w-full max-w-md space-y-6">
                    {/* Icon */}
                    <div className="text-center space-y-4">
                        <div className="mx-auto w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center">
                            <Terminal className="w-8 h-8 text-amber-400" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-semibold text-white">Device Code Required</h1>
                            <p className="mt-2 text-slate-400 text-sm">
                                This page needs to be opened from the Musoftware Runtime<br />
                                running on your computer.
                            </p>
                        </div>
                    </div>

                    {/* Steps card */}
                    <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-5 space-y-4">
                        <p className="text-xs text-slate-500 uppercase tracking-wider">How to connect</p>
                        {[
                            { step: '1', text: 'Open the Musoftware Runtime on your desktop' },
                            { step: '2', text: 'Right-click the tray icon and select "Connect Account"' },
                            { step: '3', text: 'A browser window will open with the correct link' },
                        ].map(({ step, text }) => (
                            <div key={step} className="flex items-start gap-3">
                                <div className="w-6 h-6 rounded-full bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center shrink-0">
                                    <span className="text-xs font-semibold text-indigo-300">{step}</span>
                                </div>
                                <p className="text-sm text-slate-300 pt-0.5">{text}</p>
                            </div>
                        ))}
                    </div>

                    {/* Info */}
                    <div className="flex items-start gap-2.5 text-xs text-slate-500">
                        <AlertCircle className="w-3.5 h-3.5 shrink-0 mt-0.5 text-slate-600" />
                        <span>
                            The device code is a one-time token that securely links your runtime to your account.
                            It cannot be entered manually.
                        </span>
                    </div>

                    {/* Actions */}
                    <div className="flex gap-3">
                        <Link
                            href={route('tools.explore')}
                            className="flex-1 rounded-lg border border-slate-700 bg-transparent px-4 py-2.5 text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition-colors text-center"
                        >
                            Browse Tools
                        </Link>
                        <a
                            href={route('tools.download.agent', 'node')}
                            className="flex-1 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors flex items-center justify-center gap-2"
                        >
                            <Download className="w-4 h-4" />
                            Get Runtime
                        </a>
                    </div>
                </div>
            </div>
        );
    }

    if (success) {
        return (
            <div className="min-h-screen bg-slate-950 flex items-center justify-center p-6">
                <Head title="Runtime Connected — musoftware" />
                <div className="w-full max-w-md text-center space-y-6">
                    <div className="mx-auto w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center">
                        <CheckCircle2 className="w-8 h-8 text-emerald-400" />
                    </div>
                    <div>
                        <h1 className="text-2xl font-semibold text-white">Connected!</h1>
                        <p className="mt-2 text-slate-400 text-sm">
                            The Musoftware Runtime is now connected to your account.<br />
                            You can close this window.
                        </p>
                    </div>
                    <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-4 text-left space-y-2">
                        <div className="flex items-center gap-2 text-sm">
                            <div className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
                            <span className="text-slate-300">Runtime active on port {port}</span>
                        </div>
                        <div className="flex items-center gap-2 text-sm">
                            <div className="w-2 h-2 rounded-full bg-emerald-400" />
                            <span className="text-slate-300">Syncing your plugins...</span>
                        </div>
                    </div>
                    <p className="text-xs text-slate-600">You can close this window</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-slate-950 flex items-center justify-center p-6">
            <Head title="Connect Runtime — musoftware" />

            <div className="w-full max-w-md space-y-6">
                {/* Header */}
                <div className="text-center space-y-4">
                    <div className="flex items-center justify-center gap-4">
                        {/* Platform logo */}
                        <div className="w-14 h-14 rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center">
                            <svg viewBox="0 0 24 24" fill="none" className="w-7 h-7 text-white" stroke="currentColor" strokeWidth={1.5}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                            </svg>
                        </div>
                        {/* Arrow */}
                        <Wifi className="w-5 h-5 text-slate-500" />
                        {/* Runtime icon */}
                        <div className="w-14 h-14 rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center">
                            <Monitor className="w-7 h-7 text-slate-300" />
                        </div>
                    </div>

                    <div>
                        <h1 className="text-2xl font-semibold text-white">Connect Runtime</h1>
                        <p className="mt-1.5 text-slate-400 text-sm">
                            Allow the Musoftware Runtime running on your computer<br />
                            to access your account.
                        </p>
                    </div>
                </div>

                {/* Account card */}
                <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
                    <p className="text-xs text-slate-500 uppercase tracking-wider mb-3">Connecting as</p>
                    <div className="flex items-center gap-3">
                        <div className="w-9 h-9 rounded-full bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center text-indigo-300 font-semibold text-sm">
                            {userName.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <p className="text-sm font-medium text-white">{userName}</p>
                            <p className="text-xs text-slate-500">{userEmail}</p>
                        </div>
                    </div>
                </div>

                {/* Permissions */}
                <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-4 space-y-2.5">
                    <p className="text-xs text-slate-500 uppercase tracking-wider mb-1">This will allow the runtime to</p>
                    {[
                        'Sync your subscribed plugins automatically',
                        'Verify plugin licenses before execution',
                        'Check for runtime updates',
                    ].map((p) => (
                        <div key={p} className="flex items-start gap-2.5 text-sm text-slate-300">
                            <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                            <span>{p}</span>
                        </div>
                    ))}
                </div>

                {/* Security note */}
                <div className="flex items-start gap-2.5 text-xs text-slate-500">
                    <Shield className="w-3.5 h-3.5 shrink-0 mt-0.5 text-slate-600" />
                    <span>
                        The runtime runs only on your local machine (port {port}).
                        Your credentials are never stored in plain text.
                        You can revoke this access anytime from Account → API Tokens.
                    </span>
                </div>

                {/* Error */}
                {callbackError && (
                    <div className="flex flex-col gap-3 rounded-xl border border-red-500/20 bg-red-500/10 p-4">
                        <div className="flex items-start gap-2.5 text-sm text-red-400">
                            <AlertCircle className="w-5 h-5 shrink-0 mt-0.5" />
                            <div className="space-y-1">
                                <p className="font-semibold">Runtime not responding</p>
                                <p className="text-xs text-red-400/80">Make sure the Musoftware Runtime is open on your computer.</p>
                            </div>
                        </div>
                        <a
                            href="musoftware://open"
                            className="inline-flex items-center justify-center gap-2 rounded-lg bg-red-500/20 px-4 py-2.5 text-sm font-medium text-red-400 hover:bg-red-500/30 transition-colors"
                        >
                            <Terminal className="w-4 h-4" />
                            Launch Runtime App
                        </a>
                    </div>
                )}

                {/* Actions */}
                <div className="flex gap-3">
                    <button
                        onClick={handleDeny}
                        disabled={loading}
                        className="flex-1 rounded-lg border border-slate-700 bg-transparent px-4 py-2.5 text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition-colors disabled:opacity-50"
                    >
                        Deny
                    </button>
                    <button
                        onClick={handleAllow}
                        disabled={loading}
                        className="flex-1 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                    >
                        {loading ? (
                            <>
                                <Loader2 className="w-4 h-4 animate-spin" />
                                Connecting...
                            </>
                        ) : 'Allow Access'}
                    </button>
                </div>

                {/* Runtime info */}
                <p className="text-center text-xs text-slate-600">
                    Runtime detected on <code className="font-mono">127.0.0.1:{port}</code>
                </p>
            </div>
        </div>
    );
}
