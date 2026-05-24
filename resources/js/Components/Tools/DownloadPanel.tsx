import React from 'react';
import { Link, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { ExternalLink, Shield, Smartphone, RefreshCw, CheckCircle2, Monitor } from 'lucide-react';

interface UserSubscription {
    id: number;
    plan_name: string;
    billing_cycle: string;
    status: string;
    expires_at: string;
}

interface ToolVersion {
    version: string;
    file_size: string;
    is_latest: boolean;
}

interface DownloadPanelProps {
    toolSlug: string;
    toolTitle: string;
    userSubscription: UserSubscription;
    latestVersion?: ToolVersion | null;
}

/**
 * "Access Panel" — shown on Show.tsx when user has an active subscription.
 * Tools run via the website UI + local runtime. No file download here.
 */
export function DownloadPanel({
    toolSlug,
    toolTitle,
    userSubscription,
    latestVersion,
}: DownloadPanelProps) {
    return (
        <div className="rounded-xl border border-emerald-200 bg-emerald-50/40 overflow-hidden">
            {/* Status header */}
            <div className="flex items-center gap-2.5 px-5 py-4 border-b border-emerald-100 bg-white">
                <div className="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                    <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                </div>
                <div>
                    <p className="text-sm font-semibold text-slate-900">Active Subscription</p>
                    <p className="text-xs text-slate-500">{userSubscription.plan_name} · {userSubscription.billing_cycle}</p>
                </div>
            </div>

            <div className="p-5 space-y-4">
                {/* Version */}
                {latestVersion && (
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-slate-500">Version</span>
                        <code className="font-mono text-slate-800 font-semibold">v{latestVersion.version}</code>
                    </div>
                )}



                {/* Runtime notice */}
                <div className="flex items-start gap-2 rounded-lg bg-slate-50 border border-slate-100 p-3 text-xs text-slate-500">
                    <Monitor className="h-3.5 w-3.5 shrink-0 mt-0.5 text-slate-400" />
                    <span>
                        This tool runs on your computer via the{' '}
                        <a
                            href="http://127.0.0.1:18400/setup"
                            target="_blank"
                            rel="noreferrer"
                            className="text-indigo-600 hover:underline"
                        >
                            Musoftware Runtime
                        </a>.
                        Make sure it's running.
                    </span>
                </div>

                {/* Primary CTA — Open the tool runner page */}
                <div className="space-y-2 pt-1">
                    <Link href={route('tools.run', toolSlug)} className="block">
                        <Button className="w-full gap-2 bg-indigo-600 hover:bg-indigo-500 text-white">
                            <ExternalLink className="h-4 w-4" />
                            Open {toolTitle}
                        </Button>
                    </Link>
                    <Link href={route('tools.tutorial', toolSlug)} className="block">
                        <Button variant="outline" className="w-full gap-2 border-slate-200 text-slate-700 hover:bg-slate-50">
                            Help & Tutorial
                        </Button>
                    </Link>
                </div>

                {/* Expiry */}
                {userSubscription.expires_at && (
                    <p className="text-xs text-slate-400 text-center flex items-center justify-center gap-1">
                        <RefreshCw className="h-3 w-3" />
                        Renews {userSubscription.expires_at}
                    </p>
                )}
            </div>
        </div>
    );
}
