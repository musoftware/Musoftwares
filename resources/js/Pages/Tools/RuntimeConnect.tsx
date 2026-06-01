import React, { useState, useEffect } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import { Monitor, Wifi, CheckCircle2, AlertCircle, Loader2, Shield, Terminal, Download } from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';

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
            <div className="min-h-screen bg-slate-950 flex items-center justify-center p-6 dark">
                <Head title={__('general.runtime_connection_required_musoftware')} />
                <div className="w-full max-w-md space-y-6">
                    <div className="text-center space-y-4">
                        <div className="mx-auto w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center">
                            <Terminal className="w-8 h-8 text-amber-400" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-semibold text-white">{__('general.device_code_required')}</h1>
                            <p className="mt-2 text-slate-400 text-sm">{__('general.this_page_needs_to_be_opened_from_the_musoftware_runtime')}<br />{__('general.running_on_your_computer')}</p>
                        </div>
                    </div>

                    <Card className="border-slate-800 bg-slate-900/60">
                        <CardContent className="p-5 space-y-4">
                            <p className="text-xs text-slate-500 uppercase tracking-wider">{__('general.how_to_connect')}</p>
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
                        </CardContent>
                    </Card>

                    <div className="flex items-start gap-2.5 text-xs text-slate-500">
                        <AlertCircle className="w-3.5 h-3.5 shrink-0 mt-0.5 text-slate-600" />
                        <span>
                            The device code is a one-time token that securely links your runtime to your account.
                            It cannot be entered manually.
                        </span>
                    </div>

                    <div className="flex gap-3">
                        <Button
                            asChild
                            variant="outline"
                            className="flex-1 border-slate-700 bg-transparent text-slate-400 hover:bg-slate-800 hover:text-white"
                        >
                            <Link href={route('tools.explore')}>{__('general.browse_tools')}</Link>
                        </Button>
                        <Button
                            asChild
                            className="flex-1 bg-indigo-600 text-white hover:bg-indigo-500 gap-2"
                        >
                            <a href={route('tools.download.agent', 'node')}>
                                <Download className="w-4 h-4" />{__('general.get_runtime')}</a>
                        </Button>
                    </div>
                </div>
            </div>
        );
    }

    if (success) {
        return (
            <div className="min-h-screen bg-slate-950 flex items-center justify-center p-6 dark">
                <Head title={__('general.runtime_connected_musoftware')} />
                <div className="w-full max-w-md text-center space-y-6">
                    <div className="mx-auto w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center">
                        <CheckCircle2 className="w-8 h-8 text-emerald-400" />
                    </div>
                    <div>
                        <h1 className="text-2xl font-semibold text-white">{__('general.connected')}</h1>
                        <p className="mt-2 text-slate-400 text-sm">{__('general.the_musoftware_runtime_is_now_connected_to_your_account')}<br />{__('general.you_can_close_this_window')}</p>
                    </div>
                    <Card className="border-slate-800 bg-slate-900/60 text-left">
                        <CardContent className="p-4 space-y-2">
                            <div className="flex items-center gap-2 text-sm">
                                <div className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
                                <span className="text-slate-300">Runtime active on port {port}</span>
                            </div>
                            <div className="flex items-center gap-2 text-sm">
                                <div className="w-2 h-2 rounded-full bg-emerald-400" />
                                <span className="text-slate-300">{__('general.syncing_your_plugins')}</span>
                            </div>
                        </CardContent>
                    </Card>
                    <p className="text-xs text-slate-600">{__('general.you_can_close_this_window_1')}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-slate-950 flex items-center justify-center p-6 dark">
            <Head title={__('general.connect_runtime_musoftware')} />

            <div className="w-full max-w-md space-y-6">
                <div className="text-center space-y-4">
                    <div className="flex items-center justify-center gap-4">
                        <div className="w-14 h-14 rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center">
                            <svg viewBox="0 0 24 24" fill="none" className="w-7 h-7 text-white" stroke="currentColor" strokeWidth={1.5}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                            </svg>
                        </div>
                        <Wifi className="w-5 h-5 text-slate-500" />
                        <div className="w-14 h-14 rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center">
                            <Monitor className="w-7 h-7 text-slate-300" />
                        </div>
                    </div>

                    <div>
                        <h1 className="text-2xl font-semibold text-white">{__('general.connect_runtime')}</h1>
                        <p className="mt-1.5 text-slate-400 text-sm">{__('general.allow_the_musoftware_runtime_running_on_your_computer')}<br />{__('general.to_access_your_account')}</p>
                    </div>
                </div>

                <Card className="border-slate-800 bg-slate-900/60">
                    <CardContent className="p-4">
                        <p className="text-xs text-slate-500 uppercase tracking-wider mb-3">{__('general.connecting_as')}</p>
                        <div className="flex items-center gap-3">
                            <div className="w-9 h-9 rounded-full bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center text-indigo-300 font-semibold text-sm">
                                {userName.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <p className="text-sm font-medium text-white">{userName}</p>
                                <p className="text-xs text-slate-500">{userEmail}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-slate-800 bg-slate-900/60">
                    <CardContent className="p-4 space-y-2.5">
                        <p className="text-xs text-slate-500 uppercase tracking-wider mb-1">{__('general.this_will_allow_the_runtime_to')}</p>
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
                    </CardContent>
                </Card>

                <div className="flex items-start gap-2.5 text-xs text-slate-500">
                    <Shield className="w-3.5 h-3.5 shrink-0 mt-0.5 text-slate-600" />
                    <span>
                        The runtime runs only on your local machine (port {port}).
                        Your credentials are never stored in plain text.
                        You can revoke this access anytime from Account → API Tokens.
                    </span>
                </div>

                {callbackError && (
                    <div className="flex flex-col gap-3 rounded-xl border border-red-500/20 bg-red-500/10 p-4">
                        <div className="flex items-start gap-2.5 text-sm text-red-400">
                            <AlertCircle className="w-5 h-5 shrink-0 mt-0.5" />
                            <div className="space-y-1">
                                <p className="font-semibold">{__('general.runtime_not_responding')}</p>
                                <p className="text-xs text-red-400/80">{__('general.make_sure_the_musoftware_runtime_is_open_on_your_computer')}</p>
                            </div>
                        </div>
                        <Button
                            asChild
                            className="w-full bg-red-500/20 text-red-400 hover:bg-red-500/30 gap-2"
                        >
                            <a href="musoftware://open">
                                <Terminal className="w-4 h-4" />{__('general.launch_runtime_app')}</a>
                        </Button>
                    </div>
                )}

                <div className="flex gap-3">
                    <Button
                        variant="outline"
                        onClick={handleDeny}
                        disabled={loading}
                        className="flex-1 border-slate-700 bg-transparent text-slate-400 hover:bg-slate-800 hover:text-white"
                    >
                        Deny
                    </Button>
                    <Button
                        onClick={handleAllow}
                        disabled={loading}
                        className="flex-1 bg-indigo-600 text-white hover:bg-indigo-500 gap-2"
                    >
                        {loading ? (
                            <>
                                <Loader2 className="w-4 h-4 animate-spin" />{__('general.connecting')}</>
                        ) : 'Allow Access'}
                    </Button>
                </div>

                <p className="text-center text-xs text-slate-600">{__('general.runtime_detected_on')}<code className="font-mono">127.0.0.1:{port}</code>
                </p>
            </div>
        </div>
    );
}
