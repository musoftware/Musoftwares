import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import {
    Smartphone,
    CheckCircle2,
    Clock,
    AlertTriangle,
    Plus,
    MessageSquare,
    ExternalLink,
    MoreVertical,
    RefreshCw,
    Shield,
    Trash2,
    Edit3
} from 'lucide-react';

interface Account {
    id: number;
    name: string;
    phone_number_id: string;
    waba_id: string | null;
    status: string;
    display_phone_number?: string | null;
    metadata?: any;
}

interface Business {
    id: number;
    name: string;
    wallet_balance: string;
    currency: string;
}

interface Props {
    business: Business;
    accounts: Account[];
    facebookLoginUrl: string;
    hasFacebookApp: boolean;
    onOpenInbox: () => void;
    onAddAccount: () => void;
    onEditAccount: (acc: Account) => void;
    onTestAccount: (id: number) => void;
    testingAccountId: number | null;
    onReconnectAccount: (acc: Account) => void;
    onDeleteAccount: (id: number) => void;
    onManageProfile?: (acc: Account) => void;
}

export default function AccountsHealthTab({
    business,
    accounts,
    facebookLoginUrl,
    hasFacebookApp,
    onOpenInbox,
    onAddAccount,
    onEditAccount,
    onTestAccount,
    testingAccountId,
    onReconnectAccount,
    onDeleteAccount,
    onManageProfile,
}: Props) {
    const [openMenuId, setOpenMenuId] = useState<number | null>(null);

    const activeCount = accounts.filter(a => a.status === 'active').length;
    const pendingCount = accounts.filter(a => a.status === 'unregistered').length;
    const issueCount = accounts.filter(a => a.status !== 'active' && a.status !== 'unregistered').length;

    return (
        <div className="space-y-6 text-zinc-900 dark:text-zinc-100">
            {/* KPI Overview Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm space-y-2">
                    <div className="flex items-center justify-between text-zinc-500 text-xs font-semibold">
                        <span>Active WhatsApp Numbers</span>
                        <Smartphone className="w-5 h-5 text-emerald-500" />
                    </div>
                    <div className="flex items-baseline gap-2">
                        <span className="text-3xl font-black font-mono text-zinc-950 dark:text-zinc-50">{activeCount}</span>
                        <span className="text-xs text-zinc-400 font-normal">/ {accounts.length} Total</span>
                    </div>
                </div>

                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm space-y-2">
                    <div className="flex items-center justify-between text-zinc-500 text-xs font-semibold">
                        <span>Pending Registration</span>
                        <Clock className="w-5 h-5 text-amber-500" />
                    </div>
                    <div className="flex items-baseline gap-2">
                        <span className="text-3xl font-black font-mono text-amber-500">{pendingCount}</span>
                        <span className="text-xs text-zinc-400 font-normal">Action Required</span>
                    </div>
                </div>

                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm space-y-2">
                    <div className="flex items-center justify-between text-zinc-500 text-xs font-semibold">
                        <span>Disconnected / Action Needed</span>
                        <AlertTriangle className="w-5 h-5 text-rose-500" />
                    </div>
                    <div className="flex items-baseline gap-2">
                        <span className="text-3xl font-black font-mono text-rose-500">{issueCount}</span>
                        <span className="text-xs text-zinc-400 font-normal">Needs PIN Verification</span>
                    </div>
                </div>

                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm space-y-2">
                    <div className="flex items-center justify-between text-zinc-500 text-xs font-semibold">
                        <span>Platform Wallet Balance</span>
                        <Shield className="w-5 h-5 text-sky-500" />
                    </div>
                    <div className="flex items-baseline gap-2">
                        <span className="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400">${parseFloat(business.wallet_balance).toFixed(2)}</span>
                        <span className="text-xs text-zinc-400 uppercase font-mono">{business.currency}</span>
                    </div>
                </div>
            </div>

            {/* Main Action Bar */}
            <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div className="space-y-0.5">
                    <h3 className="text-base font-bold text-zinc-950 dark:text-zinc-50">Connected Phone Numbers & Health Status</h3>
                    <p className="text-xs text-zinc-500">
                        Manage Meta WABA Cloud API phone numbers, test API endpoints, and complete 6-digit PIN registrations.
                    </p>
                </div>

                <div className="flex items-center gap-2 shrink-0 flex-wrap">
                    <button
                        onClick={onAddAccount}
                        className="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition duration-200 shadow-sm flex items-center gap-1.5 cursor-pointer"
                    >
                        <Plus className="w-4 h-4" />
                        Add Number (Manual WABA)
                    </button>

                    {hasFacebookApp && (
                        <a
                            href={facebookLoginUrl}
                            className="px-4 py-2 bg-[#1877F2] hover:bg-[#166FE5] text-white text-xs font-bold rounded-xl transition duration-200 shadow-sm flex items-center gap-1.5"
                        >
                            Log in with Facebook
                        </a>
                    )}
                </div>
            </div>

            {/* Accounts List Cards */}
            <div className="space-y-3">
                {accounts.length === 0 ? (
                    <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-12 text-center text-zinc-500 space-y-3 shadow-sm">
                        <Smartphone className="w-10 h-10 text-zinc-400 mx-auto" />
                        <h4 className="text-sm font-bold text-zinc-700 dark:text-zinc-300">No WhatsApp Numbers Connected Yet</h4>
                        <p className="text-xs text-zinc-400 max-w-sm mx-auto">
                            Connect your Meta WABA account to start sending automated broadcasts and receiving customer live chats.
                        </p>
                    </div>
                ) : (
                    accounts.map(acc => {
                        const isActive = acc.status === 'active';
                        const isPending = acc.status === 'unregistered';
                        return (
                            <div
                                key={acc.id}
                                className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shadow-sm hover:border-zinc-300 dark:hover:border-zinc-700 transition"
                            >
                                <div className="flex items-start gap-4">
                                    <div className={`w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg shrink-0 shadow-sm ${
                                        isActive ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' :
                                        isPending ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800' :
                                        'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-800'
                                    }`}>
                                        <Smartphone className="w-6 h-6" />
                                    </div>

                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2 flex-wrap">
                                            <h4 className="text-sm font-bold text-zinc-950 dark:text-zinc-50">{acc.name}</h4>
                                            
                                            {/* Status Badge */}
                                            {isActive ? (
                                                <span className="px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-[11px] font-bold flex items-center gap-1.5">
                                                    <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    Active & Ready
                                                </span>
                                            ) : isPending ? (
                                                <span className="px-2.5 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 text-[11px] font-bold flex items-center gap-1.5">
                                                    <span className="w-2 h-2 rounded-full bg-amber-500"></span>
                                                    Needs Setup / Registration
                                                </span>
                                            ) : (
                                                <span className="px-2.5 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 text-[11px] font-bold flex items-center gap-1.5">
                                                    <span className="w-2 h-2 rounded-full bg-rose-500"></span>
                                                    Action Required
                                                </span>
                                            )}
                                        </div>

                                        <div className="text-xs text-zinc-500 flex items-center gap-2 flex-wrap font-mono">
                                            {acc.display_phone_number && (
                                                <>
                                                    <span className="font-bold text-zinc-700 dark:text-zinc-300">Number: {acc.display_phone_number}</span>
                                                    <span>&bull;</span>
                                                </>
                                            )}
                                            <span>WABA: {acc.waba_id || 'N/A'}</span>
                                            <span>&bull;</span>
                                            <span>Phone ID: {acc.phone_number_id}</span>
                                        </div>
                                    </div>
                                </div>

                                {/* Primary Action & Context Menu */}
                                <div className="flex items-center gap-2 shrink-0 self-end md:self-center">
                                    {isActive ? (
                                        <a
                                            href={`/whatsapp-sender/businesses/${business.id}/live-chat?account_id=${acc.id}`}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl flex items-center gap-1.5 transition shadow-sm"
                                        >
                                            <MessageSquare className="w-4 h-4" />
                                            Open WhatsApp Web
                                        </a>
                                    ) : (
                                        <button
                                            onClick={() => onReconnectAccount(acc)}
                                            className="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs rounded-xl flex items-center gap-1.5 transition shadow-sm"
                                        >
                                            <Shield className="w-4 h-4" />
                                            Register PIN Now
                                        </button>
                                    )}

                                    {/* Secondary 3-Dots Dropdown Menu */}
                                    <div className="relative">
                                        <button
                                            onClick={() => setOpenMenuId(openMenuId === acc.id ? null : acc.id)}
                                            className="p-2 rounded-xl bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300 transition"
                                        >
                                            <MoreVertical className="w-4 h-4" />
                                        </button>

                                        {openMenuId === acc.id && (
                                            <div className="absolute right-0 mt-2 w-48 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl z-20 py-1 text-xs">
                                                {onManageProfile && (
                                                    <button
                                                        onClick={() => { onManageProfile(acc); setOpenMenuId(null); }}
                                                        className="w-full text-left px-4 py-2 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 flex items-center gap-2 font-semibold"
                                                    >
                                                        <Edit3 className="w-3.5 h-3.5 text-emerald-500" />
                                                        Manage Business Profile
                                                    </button>
                                                )}
                                                <button
                                                    onClick={() => { onTestAccount(acc.id); setOpenMenuId(null); }}
                                                    className="w-full text-left px-4 py-2 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center gap-2"
                                                >
                                                    <RefreshCw className={`w-3.5 h-3.5 ${testingAccountId === acc.id ? 'animate-spin text-emerald-500' : ''}`} />
                                                    Test Meta Graph API
                                                </button>
                                                <button
                                                    onClick={() => { onEditAccount(acc); setOpenMenuId(null); }}
                                                    className="w-full text-left px-4 py-2 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center gap-2"
                                                >
                                                    <Edit3 className="w-3.5 h-3.5 text-sky-500" />
                                                    Edit WABA Credentials
                                                </button>
                                                <button
                                                    onClick={() => { onReconnectAccount(acc); setOpenMenuId(null); }}
                                                    className="w-full text-left px-4 py-2 text-amber-600 dark:text-amber-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center gap-2"
                                                >
                                                    <Shield className="w-3.5 h-3.5" />
                                                    Re-verify 6-Digit PIN
                                                </button>
                                                <div className="my-1 border-t border-zinc-200 dark:border-zinc-800"></div>
                                                <button
                                                    onClick={() => {
                                                        if (confirm(`Are you sure you want to delete account "${acc.name}"?`)) {
                                                            onDeleteAccount(acc.id);
                                                            setOpenMenuId(null);
                                                        }
                                                    }}
                                                    className="w-full text-left px-4 py-2 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 flex items-center gap-2 font-medium"
                                                >
                                                    <Trash2 className="w-3.5 h-3.5 text-rose-600 dark:text-rose-400" />
                                                    Delete Account
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        );
                    })
                )}
            </div>
        </div>
    );
}
