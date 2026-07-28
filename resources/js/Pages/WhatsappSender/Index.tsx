import React, { useState, useEffect } from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Badge } from '@/Components/ui/badge';
import { useToast } from '@/Components/ui/use-toast';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import {
    MessageSquare,
    Send,
    ShieldCheck,
    PlusCircle,
    Trash2,
    Copy,
    CheckCircle2,
    XCircle,
    Code,
    Smartphone,
    Activity,
    AlertCircle,
    Info,
    Wallet,
    Building2,
    DollarSign,
    History,
    HelpCircle,
    BookOpen,
    ExternalLink,
    Key
} from 'lucide-react';
import { MetaSetupGuideModal } from '@/Components/WhatsappSender/MetaSetupGuideModal';

interface WhatsappBusiness {
    id: number;
    name: string;
    wallet_balance: string | number;
    currency: string;
    per_message_fee: string | number;
    status: string;
    webhook_verify_token?: string;
    accounts_count?: number;
    created_at: string;
}

interface WhatsappAccount {
    id: number;
    whatsapp_business_id: number | null;
    name: string;
    phone_number_id: string;
    waba_id: string | null;
    status: string;
    facebook_user_id: string | null;
    metadata?: {
        status?: string;
        [key: string]: any;
    } | null;
    created_at: string;
}

interface WhatsappTransaction {
    id: number;
    whatsapp_business_id: number;
    type: 'credit_recharge' | 'debit_message_fee' | 'refund';
    amount: string | number;
    balance_after: string | number;
    description: string;
    created_at: string;
    business?: {
        name: string;
    };
}

interface WhatsappLog {
    id: number;
    whatsapp_business_id: number | null;
    recipient_phone: string;
    cost_charged: string | number;
    message_type: string;
    message_body: string;
    status: 'sent' | 'failed' | 'pending';
    meta_message_id: string | null;
    error_message: string | null;
    created_at: string;
    account?: {
        name: string;
        phone_number_id: string;
    };
    business?: {
        name: string;
    };
}

interface PageProps {
    businesses?: WhatsappBusiness[];
    accounts?: WhatsappAccount[];
    logs?: WhatsappLog[];
    transactions?: WhatsappTransaction[];
    apiToken?: string;
    facebookLoginUrl?: string;
    fbOauthToken?: string | null;
    webhookUrl?: string;
    webhookVerifyToken?: string;
}

export default function Index({
    businesses = [],
    accounts = [],
    logs = [],
    transactions = [],
    apiToken = '',
    facebookLoginUrl = '',
    fbOauthToken = null,
    webhookUrl = '',
    webhookVerifyToken = 'musoftware_whatsapp_verify_token_2026',
}: PageProps) {
    const { toast } = useToast();
    const { flash } = usePage().props as any;
    const [copiedToken, setCopiedToken] = useState(false);
    const [copiedWebhookUrl, setCopiedWebhookUrl] = useState(false);
    const [copiedVerifyToken, setCopiedVerifyToken] = useState(false);
    const [showGuideModal, setShowGuideModal] = useState(false);

    const webhookForm = useForm({
        webhook_verify_token: webhookVerifyToken,
    });

    // Defensive array guards
    const safeBusinesses = Array.isArray(businesses) ? businesses : [];
    const safeAccounts = Array.isArray(accounts) ? accounts : [];
    const safeLogs = Array.isArray(logs) ? logs : [];
    const safeTransactions = Array.isArray(transactions) ? transactions : [];

    const [selectedBusinessId, setSelectedBusinessId] = useState<number>(
        safeBusinesses.length > 0 ? safeBusinesses[0].id : 0
    );

    // Active Business object
    const activeBusiness = safeBusinesses.find(b => b.id === selectedBusinessId) || safeBusinesses[0] || null;

    // Filter accounts for active business
    const activeAccounts = safeAccounts.filter(acc => !acc.whatsapp_business_id || acc.whatsapp_business_id === activeBusiness?.id);

    // Send Form state
    const sendForm = useForm({
        whatsapp_account_id: activeAccounts.length > 0 ? String(activeAccounts[0].id) : (safeAccounts.length > 0 ? String(safeAccounts[0].id) : ''),
        recipient_phone: '',
        message_body: '',
        message_type: 'text',
        template_name: 'hello_world',
        template_language: 'en_US',
    });

    // Account Credential Form state
    const accountForm = useForm({
        whatsapp_business_id: String(activeBusiness?.id || ''),
        name: '',
        phone_number_id: '',
        waba_id: '',
        access_token: fbOauthToken || '',
    });

    // New Business Form state
    const [showNewBizModal, setShowNewBizModal] = useState(false);
    const newBizForm = useForm({
        name: '',
        initial_balance: '10.00',
    });

    // Recharge Wallet Form state
    const rechargeForm = useForm({
        amount: '10.00',
    });

    // Account registration state
    const [registerAccount, setRegisterAccount] = useState<WhatsappAccount | null>(null);
    const registerForm = useForm({
        pin: '',
    });

    useEffect(() => {
        if (activeBusiness) {
            accountForm.setData('whatsapp_business_id', String(activeBusiness.id));
            if (activeAccounts.length > 0) {
                sendForm.setData('whatsapp_account_id', String(activeAccounts[0].id));
            }
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedBusinessId]);

    useEffect(() => {
        if (fbOauthToken) {
            accountForm.setData('access_token', fbOauthToken);
            const tabBtn = document.querySelector('[value="accounts"]') as HTMLElement;
            if (tabBtn) tabBtn.click();
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [fbOauthToken]);

    const handleSendSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        sendForm.post(route('whatsapp.send'), {
            onSuccess: () => {
                sendForm.reset('recipient_phone', 'message_body');
                toast({
                    title: 'Message Request Submitted',
                    description: 'Message sent via Meta API. Platform fee ($0.0010 USD) deducted.',
                });
            },
            onError: (errors) => {
                toast({
                    title: 'Send Error',
                    description: Object.values(errors)[0] as string || 'Failed to send message.',
                    variant: 'destructive',
                });
            },
        });
    };

    const handleAccountSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        accountForm.post(route('whatsapp.accounts.store'), {
            onSuccess: () => {
                accountForm.reset('name', 'phone_number_id', 'waba_id');
                toast({
                    title: 'Account Saved',
                    description: 'WhatsApp Meta account saved successfully.',
                });
            },
            onError: (errors) => {
                toast({
                    title: 'Save Error',
                    description: Object.values(errors)[0] as string || 'Failed to save account credentials.',
                    variant: 'destructive',
                });
            },
        });
    };

    useEffect(() => {
        if (activeBusiness?.webhook_verify_token) {
            webhookForm.setData('webhook_verify_token', activeBusiness.webhook_verify_token);
        }
    }, [activeBusiness?.id, activeBusiness?.webhook_verify_token]);

    const currentBusinessWebhookUrl = activeBusiness
        ? `${window.location.origin}/api/v1/whatsapp/webhook/biz/${activeBusiness.id}`
        : `${window.location.origin}/api/v1/whatsapp/webhook`;

    const copyWebhookUrl = () => {
        navigator.clipboard.writeText(currentBusinessWebhookUrl);
        setCopiedWebhookUrl(true);
        setTimeout(() => setCopiedWebhookUrl(false), 2000);
        toast({ title: 'Copied', description: `Callback URL for ${activeBusiness?.name || 'Business'} copied to clipboard.` });
    };

    const copyVerifyToken = () => {
        navigator.clipboard.writeText(webhookForm.data.webhook_verify_token);
        setCopiedVerifyToken(true);
        setTimeout(() => setCopiedVerifyToken(false), 2000);
        toast({ title: 'Copied', description: `Verify token for ${activeBusiness?.name || 'Business'} copied to clipboard.` });
    };

    const handleWebhookSettingsSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!activeBusiness) return;
        webhookForm.post(route('whatsapp.businesses.webhook-token', activeBusiness.id), {
            onSuccess: () => {
                toast({
                    title: 'Business Webhook Token Saved',
                    description: `Verify token for "${activeBusiness.name}" updated successfully.`,
                });
            },
            onError: (errors) => {
                toast({
                    title: 'Save Error',
                    description: Object.values(errors)[0] as string || 'Failed to update business verify token.',
                    variant: 'destructive',
                });
            },
        });
    };

    const handleNewBusinessSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        newBizForm.post(route('whatsapp.businesses.store'), {
            onSuccess: () => {
                newBizForm.reset();
                setShowNewBizModal(false);
                toast({
                    title: 'Business Client Created',
                    description: 'New Business profile added successfully.',
                });
            },
        });
    };

    const handleRechargeSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!activeBusiness) return;
        rechargeForm.post(route('whatsapp.businesses.recharge', activeBusiness.id), {
            onSuccess: () => {
                toast({
                    title: 'Wallet Recharged',
                    description: `Successfully added $${rechargeForm.data.amount} USD to business wallet.`,
                });
            },
        });
    };

    const handleDeleteAccount = (id: number) => {
        if (confirm('Are you sure you want to disconnect this Meta WhatsApp account?')) {
            router.delete(route('whatsapp.accounts.destroy', id), {
                onSuccess: () => {
                    toast({
                        title: 'Account Disconnected',
                        description: 'WhatsApp account removed successfully.',
                    });
                },
            });
        }
    };

    const handleSyncAccount = (id: number) => {
        router.post(route('whatsapp.accounts.sync', id), {}, {
            onSuccess: () => {
                toast({
                    title: 'Account Synced',
                    description: 'WhatsApp account status synced from Meta.',
                });
            },
            onError: (errors) => {
                toast({
                    title: 'Sync Failed',
                    description: Object.values(errors)[0] as string || 'Failed to sync account status.',
                    variant: 'destructive',
                });
            },
        });
    };

    const handleRegisterSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!registerAccount) return;

        registerForm.post(route('whatsapp.accounts.register', registerAccount.id), {
            onSuccess: () => {
                setRegisterAccount(null);
                registerForm.reset();
                toast({
                    title: 'Registration Successful',
                    description: 'WhatsApp number registered and activated successfully.',
                });
            },
            onError: (errors) => {
                toast({
                    title: 'Registration Failed',
                    description: Object.values(errors)[0] as string || 'Failed to register phone number.',
                    variant: 'destructive',
                });
            },
        });
    };

    const copyApiToken = () => {
        navigator.clipboard.writeText(apiToken);
        setCopiedToken(true);
        setTimeout(() => setCopiedToken(false), 2000);
        toast({
            title: 'API Token Copied',
            description: 'Your API token has been copied to clipboard.',
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="font-semibold text-xl text-slate-900 leading-tight flex items-center gap-2">
                            <MessageSquare className="w-6 h-6 text-emerald-600" />
                            WhatsApp Sender API Engine
                        </h2>
                        <p className="text-sm text-slate-500 mt-1">
                            Multi-Business Client Management with Per-Message Wallet Monetization ($0.0010/msg)
                        </p>
                    </div>

                    <div className="flex items-center gap-2 shrink-0">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setShowGuideModal(true)}
                            className="border-indigo-300 text-indigo-700 bg-indigo-50 hover:bg-indigo-100 font-medium px-3.5 py-2 rounded-lg flex items-center gap-2 text-sm shadow-sm"
                        >
                            <HelpCircle className="w-4 h-4 text-indigo-600" />
                            الشرح ودليل الخطوات
                        </Button>
                        <a href={facebookLoginUrl || '#'} className="shrink-0">
                            <Button className="bg-[#1877F2] hover:bg-[#166fe5] text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 shadow-sm text-sm">
                                <svg className="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                                Connect with Facebook Login
                            </Button>
                        </a>
                    </div>
                </div>
            }
        >
            <Head title="WhatsApp Sender API Service" />

            <div className="py-8 w-full px-4 sm:px-6 lg:px-8 space-y-6">

                {flash?.success && (
                    <div className="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center gap-3 shadow-sm">
                        <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0" />
                        <span className="font-medium text-sm">{flash.success}</span>
                    </div>
                )}

                {flash?.info && (
                    <div className="p-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-xl flex items-center gap-3 shadow-sm">
                        <Info className="w-5 h-5 text-blue-600 shrink-0" />
                        <span className="font-medium text-sm leading-relaxed">{flash.info}</span>
                    </div>
                )}

                {flash?.error && (
                    <div className="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-center gap-3 shadow-sm">
                        <AlertCircle className="w-5 h-5 text-rose-600 shrink-0" />
                        <span className="font-medium text-sm leading-relaxed">{flash.error}</span>
                    </div>
                )}

                {/* MAIN GRID: LEFT SIDEBAR (1/4) + RIGHT WORKSPACE (3/4) */}
                <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">

                    {/* LEFT SIDEBAR: BUSINESS CLIENTS & ACCOUNTS LIST */}
                    <div className="space-y-6">
                        
                        {/* BUSINESS CLIENTS LIST CARD */}
                        <Card className="border-slate-200 shadow-sm rounded-xl bg-white">
                            <CardHeader className="border-b border-slate-100 pb-3 flex flex-row items-center justify-between">
                                <div>
                                    <CardTitle className="text-base font-semibold text-slate-900 flex items-center gap-2">
                                        <Building2 className="w-4 h-4 text-emerald-600" /> Business Clients
                                    </CardTitle>
                                    <CardDescription className="text-xs">Client Profiles & Wallets</CardDescription>
                                </div>
                                <Button
                                    size="sm"
                                    onClick={() => setShowNewBizModal(!showNewBizModal)}
                                    className="bg-emerald-600 hover:bg-emerald-700 text-white h-8 text-xs px-2.5"
                                >
                                    <PlusCircle className="w-3.5 h-3.5 me-1" /> Add
                                </Button>
                            </CardHeader>
                            <CardContent className="pt-4 space-y-3">
                                
                                {showNewBizModal && (
                                    <form onSubmit={handleNewBusinessSubmit} className="p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-3 mb-3">
                                        <div className="space-y-1">
                                            <Label className="text-xs text-slate-700">Business Name</Label>
                                            <Input
                                                placeholder="e.g. Client E-Commerce"
                                                value={newBizForm.data.name}
                                                onChange={(e) => newBizForm.setData('name', e.target.value)}
                                                className="h-8 text-xs bg-white"
                                                required
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <Label className="text-xs text-slate-700">Initial Balance ($ USD)</Label>
                                            <Input
                                                type="number"
                                                step="0.01"
                                                placeholder="10.00"
                                                value={newBizForm.data.initial_balance}
                                                onChange={(e) => newBizForm.setData('initial_balance', e.target.value)}
                                                className="h-8 text-xs bg-white"
                                            />
                                        </div>
                                        <div className="flex gap-2 pt-1">
                                            <Button type="submit" size="sm" className="h-7 text-xs bg-slate-900 text-white w-full">Save</Button>
                                            <Button type="button" size="sm" variant="ghost" onClick={() => setShowNewBizModal(false)} className="h-7 text-xs text-slate-500">Cancel</Button>
                                        </div>
                                    </form>
                                )}

                                <div className="space-y-2">
                                    {safeBusinesses.map((biz) => {
                                        const isSelected = biz.id === activeBusiness?.id;
                                        const balance = Number(biz.wallet_balance || 0);
                                        const isLow = balance < 0.001;

                                        return (
                                            <div
                                                key={biz.id}
                                                onClick={() => setSelectedBusinessId(biz.id)}
                                                className={`p-3 rounded-xl border cursor-pointer transition-all ${
                                                    isSelected
                                                        ? 'border-emerald-500 bg-emerald-50/50 shadow-sm'
                                                        : 'border-slate-200 bg-white hover:border-slate-300'
                                                }`}
                                            >
                                                <div className="flex items-center justify-between">
                                                    <span className="font-semibold text-sm text-slate-900 truncate">{biz.name}</span>
                                                    <Badge
                                                        variant="outline"
                                                        className={`text-[10px] px-2 py-0.5 font-mono ${
                                                            isLow
                                                                ? 'bg-rose-50 text-rose-700 border-rose-200'
                                                                : 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                        }`}
                                                    >
                                                        ${Number(biz.wallet_balance || 0).toFixed(3)}
                                                    </Badge>
                                                </div>
                                                <div className="flex justify-between items-center text-xs text-slate-500 mt-1.5">
                                                    <span>Accounts: {biz.accounts_count ?? safeAccounts.filter(a => a.whatsapp_business_id === biz.id).length}</span>
                                                    <span className="text-[10px] text-slate-400">$0.001/msg</span>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </CardContent>
                        </Card>

                        {/* CONNECTED ACCOUNTS FOR SELECTED BUSINESS */}
                        <Card className="border-slate-200 shadow-sm rounded-xl bg-white">
                            <CardHeader className="border-b border-slate-100 pb-3">
                                <CardTitle className="text-base font-semibold text-slate-900 flex items-center gap-2">
                                    <Smartphone className="w-4 h-4 text-indigo-600" /> Phone Numbers
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    {activeBusiness?.name || 'Default'} Meta Endpoints
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="pt-4 space-y-3">
                                {activeAccounts.length === 0 ? (
                                    <div className="text-center py-4 space-y-2">
                                        <p className="text-xs text-slate-400">No Meta phone number linked to this business.</p>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => (document.querySelector('[value="accounts"]') as HTMLElement)?.click()}
                                            className="text-xs h-7 border-slate-300"
                                        >
                                            <PlusCircle className="w-3 h-3 me-1 text-emerald-600" /> Connect Number
                                        </Button>
                                    </div>
                                ) : (
                                    activeAccounts.map((acc) => (
                                        <div key={acc.id} className="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2 text-xs">
                                            <div className="flex items-center justify-between gap-2">
                                                <span className="font-semibold text-slate-900 truncate" title={acc.name}>{acc.name}</span>
                                                <div className="flex items-center gap-1 shrink-0">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        title="Sync status from Meta"
                                                        onClick={() => handleSyncAccount(acc.id)}
                                                        className="h-6 w-6 p-0 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded"
                                                    >
                                                        <Activity className="w-3.5 h-3.5" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        title="Disconnect Account"
                                                        onClick={() => handleDeleteAccount(acc.id)}
                                                        className="h-6 w-6 p-0 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded"
                                                    >
                                                        <Trash2 className="w-3.5 h-3.5" />
                                                    </Button>
                                                </div>
                                            </div>
                                            <div className="space-y-0.5">
                                                <p className="text-[10px] text-slate-500 font-mono">Phone ID: {acc.phone_number_id}</p>
                                                {acc.waba_id && <p className="text-[10px] text-slate-500 font-mono">WABA ID: {acc.waba_id}</p>}
                                            </div>
                                            <div className="flex items-center justify-between gap-2 pt-1.5 border-t border-slate-200/60">
                                                <div className="flex items-center gap-1">
                                                    {acc.status === 'unregistered' ? (
                                                        <span className="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200" title="This number needs to be registered with a 6-digit PIN on Meta API">
                                                            <AlertCircle className="w-2.5 h-2.5 shrink-0" /> Needs Activation
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                            <CheckCircle2 className="w-2.5 h-2.5 shrink-0" /> Connected
                                                        </span>
                                                    )}
                                                    {acc.metadata?.status && acc.metadata.status !== 'CONNECTED' && (
                                                        <span className="text-[10px] text-rose-600 font-semibold font-mono truncate max-w-[80px]" title={`Meta Status: ${acc.metadata.status}`}>
                                                            ({acc.metadata.status})
                                                        </span>
                                                    )}
                                                </div>

                                                <div className="flex items-center gap-1">
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => handleSyncAccount(acc.id)}
                                                        className="h-6 text-[10px] px-2 rounded font-medium border-slate-300 text-slate-700 hover:bg-slate-100 shrink-0"
                                                        title="Sync status and validate credentials from Meta"
                                                    >
                                                        Validate
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        onClick={() => setRegisterAccount(acc)}
                                                        className="h-6 bg-slate-900 hover:bg-slate-800 text-white text-[10px] px-2 rounded font-medium shrink-0"
                                                        title="Register this phone number on Meta Cloud API using a 6-digit PIN"
                                                    >
                                                        Register/Fix
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* RIGHT WORKSPACE: ACTIVE BUSINESS TABS (3/4) */}
                    <div className="lg:col-span-3 space-y-6">

                        {/* ACTIVE BUSINESS WALLET BANNER */}
                        {activeBusiness && (
                            <div className="p-4 bg-gradient-to-r from-slate-900 to-slate-800 text-white rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <Building2 className="w-4 h-4 text-emerald-400" />
                                        <span className="font-semibold text-base">{activeBusiness.name}</span>
                                        <Badge className="bg-emerald-500/20 text-emerald-300 border-emerald-500/30 text-[10px]">
                                            Active Client
                                        </Badge>
                                    </div>
                                    <p className="text-xs text-slate-300 mt-1">
                                        Platform Fee: <span className="font-mono text-emerald-400 font-semibold">$0.0010 USD</span> per message deduction
                                    </p>
                                </div>

                                <div className="flex items-center gap-4">
                                    <div className="text-right">
                                        <div className="text-xs text-slate-400">Business Wallet Balance</div>
                                        <div className="text-xl font-bold font-mono text-emerald-400">
                                            ${Number(activeBusiness.wallet_balance || 0).toFixed(4)} <span className="text-xs font-normal text-slate-300">USD</span>
                                        </div>
                                    </div>
                                    <Button
                                        size="sm"
                                        onClick={() => (document.querySelector('[value="wallet"]') as HTMLElement)?.click()}
                                        className="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium px-3 h-9 shrink-0"
                                    >
                                        <Wallet className="w-3.5 h-3.5 me-1.5" /> Recharge Wallet
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* WORKSPACE TABS */}
                        <Tabs defaultValue="send" className="w-full">
                            <TabsList className="grid w-full grid-cols-4 bg-slate-100 p-1 rounded-xl">
                                <TabsTrigger value="send" className="flex items-center justify-center gap-2 font-medium">
                                    <Send className="w-4 h-4" /> Quick Sender Form
                                </TabsTrigger>
                                <TabsTrigger value="wallet" className="flex items-center justify-center gap-2 font-medium">
                                    <Wallet className="w-4 h-4" /> Wallet & Top-up
                                </TabsTrigger>
                                <TabsTrigger value="accounts" className="flex items-center justify-center gap-2 font-medium">
                                    <ShieldCheck className="w-4 h-4" /> Facebook / Meta ({safeAccounts.length})
                                </TabsTrigger>
                                <TabsTrigger value="api" className="flex items-center justify-center gap-2 font-medium">
                                    <Code className="w-4 h-4" /> API Docs & Keys
                                </TabsTrigger>
                            </TabsList>

                            {/* TAB 1: QUICK SENDER FORM */}
                            <TabsContent value="send" className="mt-6">
                                <Card className="border-slate-200 shadow-sm rounded-xl bg-white">
                                    <CardHeader className="border-b border-slate-100 pb-4">
                                        <CardTitle className="text-lg font-semibold flex items-center gap-2 text-slate-900">
                                            <Send className="w-5 h-5 text-emerald-600" /> Send WhatsApp Message ({activeBusiness?.name || 'Default'})
                                        </CardTitle>
                                        <CardDescription>
                                            Dispatch single custom text or approved template messages via Meta WhatsApp API.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="pt-6">
                                        {activeAccounts.length === 0 ? (
                                            <div className="p-8 text-center bg-slate-50/80 rounded-xl border border-dashed border-slate-300 space-y-4">
                                                <Smartphone className="w-12 h-12 text-slate-400 mx-auto" />
                                                <div>
                                                    <h3 className="text-base font-semibold text-slate-800">No Meta Account Linked to {activeBusiness?.name || 'Business'}</h3>
                                                    <p className="text-sm text-slate-500 max-w-md mx-auto mt-1">
                                                        Connect your Facebook Login or enter your Meta Phone Number ID & Access Token in the accounts tab.
                                                    </p>
                                                </div>
                                                <Button
                                                    onClick={() => (document.querySelector('[value="accounts"]') as HTMLElement)?.click()}
                                                    className="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-5 py-2 rounded-lg"
                                                >
                                                    <PlusCircle className="w-4 h-4 me-2" /> Connect Account
                                                </Button>
                                            </div>
                                        ) : (
                                            <form onSubmit={handleSendSubmit} className="space-y-5">
                                                <div className="space-y-2">
                                                    <Label htmlFor="whatsapp_account_id" className="text-slate-700">Sender Meta Phone Account</Label>
                                                    <select
                                                        id="whatsapp_account_id"
                                                        value={sendForm.data.whatsapp_account_id}
                                                        onChange={(e) => sendForm.setData('whatsapp_account_id', e.target.value)}
                                                        className="w-full h-10 px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white text-slate-900 focus:ring-2 focus:ring-emerald-500"
                                                    >
                                                        {activeAccounts.map((acc) => (
                                                            <option key={acc.id} value={acc.id}>
                                                                {acc.name} (Phone ID: {acc.phone_number_id})
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="recipient_phone" className="text-slate-700">Recipient Phone Number (with Country Code)</Label>
                                                    <Input
                                                        id="recipient_phone"
                                                        type="text"
                                                        placeholder="e.g. 201001234567 or +201001234567"
                                                        value={sendForm.data.recipient_phone}
                                                        onChange={(e) => sendForm.setData('recipient_phone', e.target.value)}
                                                        className="bg-white border-slate-300 text-slate-900"
                                                        required
                                                    />
                                                    <p className="text-xs text-slate-500">Include country code without spaces (e.g. 20 for Egypt, 966 for KSA).</p>
                                                </div>

                                                <div className="grid grid-cols-2 gap-4">
                                                    <div className="space-y-2">
                                                        <Label htmlFor="message_type" className="text-slate-700">Type</Label>
                                                        <select
                                                            id="message_type"
                                                            value={sendForm.data.message_type}
                                                            onChange={(e) => sendForm.setData('message_type', e.target.value)}
                                                            className="w-full h-10 px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white text-slate-900 focus:ring-2 focus:ring-emerald-500"
                                                        >
                                                            <option value="text">Direct Custom Text</option>
                                                            <option value="template">Meta Template</option>
                                                        </select>
                                                    </div>

                                                    {sendForm.data.message_type === 'template' && (
                                                        <div className="space-y-2">
                                                            <Label htmlFor="template_name" className="text-slate-700">Template Name</Label>
                                                            <Input
                                                                id="template_name"
                                                                placeholder="hello_world"
                                                                value={sendForm.data.template_name}
                                                                onChange={(e) => sendForm.setData('template_name', e.target.value)}
                                                                className="bg-white border-slate-300 text-slate-900"
                                                            />
                                                        </div>
                                                    )}
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="message_body" className="text-slate-700">Message Content</Label>
                                                    <Textarea
                                                        id="message_body"
                                                        rows={4}
                                                        placeholder="Type your WhatsApp message text here..."
                                                        value={sendForm.data.message_body}
                                                        onChange={(e) => sendForm.setData('message_body', e.target.value)}
                                                        className="bg-white border-slate-300 text-slate-900 focus:ring-2 focus:ring-emerald-500"
                                                        required
                                                    />
                                                </div>

                                                <div className="p-3 bg-emerald-50/80 border border-emerald-200 rounded-lg flex items-center justify-between text-xs text-emerald-900">
                                                    <span className="flex items-center gap-1.5 font-medium">
                                                        <DollarSign className="w-4 h-4 text-emerald-600" /> Platform Message Fee: $0.0010 USD
                                                    </span>
                                                    <span>Wallet Balance After: <strong className="font-mono text-emerald-700">${Math.max(0, Number(activeBusiness?.wallet_balance || 0) - 0.001).toFixed(4)} USD</strong></span>
                                                </div>

                                                <Button
                                                    type="submit"
                                                    disabled={sendForm.processing || Number(activeBusiness?.wallet_balance || 0) < 0.001}
                                                    className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 rounded-lg flex items-center justify-center gap-2"
                                                >
                                                    <Send className="w-4 h-4" />
                                                    {sendForm.processing ? 'Sending via Meta API...' : 'Send WhatsApp Message'}
                                                </Button>
                                            </form>
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            {/* TAB 2: WALLET & RECHARGE */}
                            <TabsContent value="wallet" className="mt-6 space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <Card className="border-slate-200 shadow-sm rounded-xl bg-white">
                                        <CardHeader className="pb-2">
                                            <CardTitle className="text-sm font-medium text-slate-500">Current Wallet Balance</CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="text-3xl font-bold font-mono text-emerald-600">
                                                ${Number(activeBusiness?.wallet_balance || 0).toFixed(4)}
                                            </div>
                                            <p className="text-xs text-slate-400 mt-1">Available for message dispatch</p>
                                        </CardContent>
                                    </Card>

                                    <Card className="border-slate-200 shadow-sm rounded-xl bg-white md:col-span-2">
                                        <CardHeader className="pb-2">
                                            <CardTitle className="text-base font-semibold text-slate-900 flex items-center gap-2">
                                                <Wallet className="w-4 h-4 text-emerald-600" /> Recharge Business Wallet ({activeBusiness?.name || 'Default'})
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent className="pt-2">
                                            <form onSubmit={handleRechargeSubmit} className="flex gap-3 items-end">
                                                <div className="space-y-1 grow">
                                                    <Label className="text-xs text-slate-700">Top-up Amount ($ USD)</Label>
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        min="0.10"
                                                        placeholder="10.00"
                                                        value={rechargeForm.data.amount}
                                                        onChange={(e) => rechargeForm.setData('amount', e.target.value)}
                                                        className="bg-white border-slate-300 font-mono text-sm"
                                                        required
                                                    />
                                                </div>
                                                <div className="flex gap-1.5">
                                                    {['10.00', '25.00', '50.00', '100.00'].map((amt) => (
                                                        <Button
                                                            key={amt}
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => rechargeForm.setData('amount', amt)}
                                                            className="text-xs h-9 border-slate-300"
                                                        >
                                                            +${parseInt(amt)}
                                                        </Button>
                                                    ))}
                                                </div>
                                                <Button
                                                    type="submit"
                                                    disabled={rechargeForm.processing}
                                                    className="bg-emerald-600 hover:bg-emerald-700 text-white text-xs h-9 px-4 shrink-0"
                                                >
                                                    Top Up Balance
                                                </Button>
                                            </form>
                                        </CardContent>
                                    </Card>
                                </div>

                                {/* FINANCIAL LEDGER TABLE */}
                                <Card className="border-slate-200 shadow-sm rounded-xl bg-white">
                                    <CardHeader className="border-b border-slate-100 pb-4">
                                        <CardTitle className="text-base font-semibold text-slate-900 flex items-center gap-2">
                                            <History className="w-4 h-4 text-emerald-600" /> Wallet Financial Ledger
                                        </CardTitle>
                                        <CardDescription>Transaction History for {activeBusiness?.name || 'Default'}</CardDescription>
                                    </CardHeader>
                                    <CardContent className="pt-4">
                                        {safeTransactions.filter(t => t.whatsapp_business_id === activeBusiness?.id).length === 0 ? (
                                            <p className="text-sm text-slate-500 py-6 text-center">No financial transactions recorded yet.</p>
                                        ) : (
                                            <div className="overflow-x-auto">
                                                <table className="w-full text-xs text-left text-slate-600">
                                                    <thead className="text-[11px] text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                                                        <tr>
                                                            <th className="px-3 py-2">ID</th>
                                                            <th className="px-3 py-2">Type</th>
                                                            <th className="px-3 py-2">Amount</th>
                                                            <th className="px-3 py-2">Balance After</th>
                                                            <th className="px-3 py-2">Description</th>
                                                            <th className="px-3 py-2">Date</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y divide-slate-100">
                                                        {safeTransactions
                                                            .filter(t => t.whatsapp_business_id === activeBusiness?.id)
                                                            .map((t) => (
                                                                <tr key={t.id} className="hover:bg-slate-50/50">
                                                                    <td className="px-3 py-2 font-mono text-slate-400">#{t.id}</td>
                                                                    <td className="px-3 py-2">
                                                                        {t.type === 'credit_recharge' ? (
                                                                            <Badge className="bg-emerald-100 text-emerald-700 border-0 text-[10px]">
                                                                                + Credit
                                                                            </Badge>
                                                                        ) : (
                                                                            <Badge className="bg-slate-100 text-slate-700 border-0 text-[10px]">
                                                                                - Fee ($0.001)
                                                                            </Badge>
                                                                        )}
                                                                    </td>
                                                                    <td className={`px-3 py-2 font-mono font-semibold ${t.type === 'credit_recharge' ? 'text-emerald-600' : 'text-slate-800'}`}>
                                                                        {t.type === 'credit_recharge' ? '+' : '-'}${Number(t.amount || 0).toFixed(4)}
                                                                    </td>
                                                                    <td className="px-3 py-2 font-mono text-slate-700">${Number(t.balance_after || 0).toFixed(4)}</td>
                                                                    <td className="px-3 py-2 text-slate-700">{t.description}</td>
                                                                    <td className="px-3 py-2 text-slate-400">{new Date(t.created_at).toLocaleString()}</td>
                                                                </tr>
                                                            ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            {/* TAB 3: ACCOUNTS & FACEBOOK CONNECT */}
                            <TabsContent value="accounts" className="mt-6">
                                <Card className="border-slate-200 shadow-sm rounded-xl bg-white">
                                    <CardHeader className="border-b border-slate-100 pb-4 flex flex-row items-center justify-between gap-4">
                                        <div>
                                            <CardTitle className="text-lg font-semibold flex items-center gap-2 text-slate-900">
                                                <ShieldCheck className="w-5 h-5 text-indigo-600" /> Connect Meta Credentials ({activeBusiness?.name || 'Default'})
                                            </CardTitle>
                                            <CardDescription>
                                                Link Meta Phone Number ID & Access Token for {activeBusiness?.name || 'Business'}
                                            </CardDescription>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setShowGuideModal(true)}
                                            className="border-indigo-300 text-indigo-700 bg-indigo-50 hover:bg-indigo-100 font-medium text-xs h-9 px-3 gap-1.5 shrink-0"
                                        >
                                            <HelpCircle className="w-4 h-4 text-indigo-600" />
                                            الشرح ودليل الخطوات
                                        </Button>
                                    </CardHeader>
                                    <CardContent className="pt-6">
                                        <form onSubmit={handleAccountSubmit} className="space-y-4 max-w-xl">
                                            <div className="space-y-1.5">
                                                <Label htmlFor="acc_name" className="text-slate-700">Account Label</Label>
                                                <Input
                                                    id="acc_name"
                                                    placeholder="e.g. Primary WhatsApp Line"
                                                    value={accountForm.data.name}
                                                    onChange={(e) => accountForm.setData('name', e.target.value)}
                                                    className="bg-white border-slate-300 text-slate-900"
                                                    required
                                                />
                                            </div>

                                            <div className="space-y-1.5">
                                                <Label htmlFor="phone_number_id" className="text-slate-700">Meta Phone Number ID</Label>
                                                <Input
                                                    id="phone_number_id"
                                                    placeholder="e.g. 104829103948123"
                                                    value={accountForm.data.phone_number_id}
                                                    onChange={(e) => accountForm.setData('phone_number_id', e.target.value)}
                                                    className="bg-white border-slate-300 text-slate-900 font-mono"
                                                    required
                                                />
                                            </div>

                                            <div className="space-y-1.5">
                                                <Label htmlFor="waba_id" className="text-slate-700">WABA ID (Optional)</Label>
                                                <Input
                                                    id="waba_id"
                                                    placeholder="e.g. 982347109283741"
                                                    value={accountForm.data.waba_id}
                                                    onChange={(e) => accountForm.setData('waba_id', e.target.value)}
                                                    className="bg-white border-slate-300 text-slate-900 font-mono"
                                                />
                                            </div>

                                            <div className="space-y-1.5">
                                                <Label htmlFor="access_token" className="text-slate-700">Meta Access Token</Label>
                                                <Textarea
                                                    id="access_token"
                                                    rows={3}
                                                    placeholder="EAA..."
                                                    value={accountForm.data.access_token}
                                                    onChange={(e) => accountForm.setData('access_token', e.target.value)}
                                                    className="bg-white border-slate-300 text-slate-900 font-mono text-xs"
                                                    required
                                                />
                                            </div>

                                            <Button
                                                type="submit"
                                                disabled={accountForm.processing}
                                                className="w-full bg-slate-900 hover:bg-slate-800 text-white font-medium"
                                            >
                                                {accountForm.processing ? 'Saving Credentials...' : 'Save Meta Credentials'}
                                            </Button>
                                        </form>
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            {/* TAB 4: API DOCS & WEBHOOKS */}
                            <TabsContent value="api" className="mt-6 space-y-6">
                                {/* META WEBHOOK SETUP CARD (Step 2 Production setup) */}
                                <Card className="border-slate-200 shadow-sm rounded-xl bg-white">
                                    <CardHeader className="border-b border-slate-100 pb-4 flex flex-row items-center justify-between gap-4">
                                        <div>
                                            <CardTitle className="text-lg font-semibold flex items-center gap-2 text-slate-900">
                                                <Activity className="w-5 h-5 text-indigo-600" /> Meta Webhooks Configuration ({activeBusiness?.name || 'Business'})
                                            </CardTitle>
                                            <CardDescription>
                                                Configure Webhooks in Meta for Developers Console to receive incoming messages & status updates for {activeBusiness?.name || 'this business profile'}.
                                            </CardDescription>
                                        </div>
                                        <Badge className="bg-indigo-50 text-indigo-700 border-indigo-200 text-xs font-semibold px-2.5 py-1">
                                            Per-Business Webhook
                                        </Badge>
                                    </CardHeader>
                                    <CardContent className="pt-6 space-y-6">
                                        {/* Helper Banner matching Meta screenshot */}
                                        <div className="p-4 bg-amber-50/80 border border-amber-200 rounded-xl flex items-start gap-3 text-xs text-amber-900">
                                            <AlertCircle className="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                                            <div className="space-y-1">
                                                <strong className="font-bold text-amber-950 block">إعدادات الـ Webhooks في Meta Developer Console لـ ({activeBusiness?.name}):</strong>
                                                <p className="leading-relaxed text-amber-800">
                                                    انتقل إلى منصة <a href="https://developers.facebook.com/apps/" target="_blank" rel="noreferrer" className="underline font-bold text-amber-950">Meta for Developers</a> 👈 تطبيقك 👈 <strong className="text-amber-950">WhatsApp 👈 Configuration / API Setup</strong>، ثم ألصق الـ <strong className="text-amber-950">Callback URL</strong> والـ <strong className="text-amber-950 font-mono">Verify Token</strong> الموضحين بالأسفل لـ <strong className="text-indigo-950 underline">{activeBusiness?.name}</strong> واضغط على <strong className="text-amber-950">Verify and save</strong>.
                                                </p>
                                            </div>
                                        </div>

                                        <form onSubmit={handleWebhookSettingsSubmit} className="space-y-4">
                                            {/* Field 1: Callback URL */}
                                            <div className="space-y-1.5">
                                                <Label htmlFor="webhook_callback_url" className="text-slate-800 font-semibold flex items-center gap-2">
                                                    Callback URL ({activeBusiness?.name})
                                                    <span className="text-xs text-slate-400 font-normal">(عنوان الـ Webhook المخصص لملف هذا الـ Business في Meta)</span>
                                                </Label>
                                                <div className="flex gap-2">
                                                    <Input
                                                        id="webhook_callback_url"
                                                        readOnly
                                                        value={currentBusinessWebhookUrl}
                                                        className="font-mono text-xs bg-slate-50 border-slate-300 text-slate-900 font-semibold"
                                                    />
                                                    <Button
                                                        type="button"
                                                        onClick={copyWebhookUrl}
                                                        variant="outline"
                                                        className="shrink-0 flex items-center gap-2 border-slate-300 text-slate-700 hover:bg-slate-100"
                                                    >
                                                        {copiedWebhookUrl ? <CheckCircle2 className="w-4 h-4 text-emerald-600" /> : <Copy className="w-4 h-4" />}
                                                        {copiedWebhookUrl ? 'Copied URL' : 'Copy Callback URL'}
                                                    </Button>
                                                </div>
                                            </div>

                                            {/* Field 2: Verify Token */}
                                            <div className="space-y-1.5">
                                                <Label htmlFor="webhook_verify_token" className="text-slate-800 font-semibold flex items-center gap-2">
                                                    Verify token ({activeBusiness?.name})
                                                    <span className="text-xs text-slate-400 font-normal">(رمز التوثيق السري الخاص بملف هذا الـ Business)</span>
                                                </Label>
                                                <div className="flex gap-2">
                                                    <Input
                                                        id="webhook_verify_token"
                                                        value={webhookForm.data.webhook_verify_token}
                                                        onChange={(e) => webhookForm.setData('webhook_verify_token', e.target.value)}
                                                        placeholder="biz_wt_..."
                                                        className="font-mono text-xs bg-white border-slate-300 text-slate-900"
                                                        required
                                                    />
                                                    <Button
                                                        type="button"
                                                        onClick={copyVerifyToken}
                                                        variant="outline"
                                                        className="shrink-0 flex items-center gap-2 border-slate-300 text-slate-700 hover:bg-slate-100"
                                                    >
                                                        {copiedVerifyToken ? <CheckCircle2 className="w-4 h-4 text-emerald-600" /> : <Copy className="w-4 h-4" />}
                                                        {copiedVerifyToken ? 'Copied Token' : 'Copy Token'}
                                                    </Button>
                                                </div>
                                                {webhookForm.errors.webhook_verify_token && (
                                                    <span className="text-xs text-rose-600">{webhookForm.errors.webhook_verify_token}</span>
                                                )}
                                            </div>

                                            <div className="flex justify-end pt-2">
                                                <Button
                                                    type="submit"
                                                    disabled={webhookForm.processing}
                                                    className="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs px-5"
                                                >
                                                    {webhookForm.processing ? 'Saving...' : `Save ${activeBusiness?.name || 'Business'} Verify Token`}
                                                </Button>
                                            </div>
                                        </form>
                                    </CardContent>
                                </Card>

                                <Card className="border-slate-200 shadow-sm rounded-xl bg-white">
                                    <CardHeader className="border-b border-slate-100 pb-4">
                                        <CardTitle className="text-lg font-semibold flex items-center gap-2 text-slate-900">
                                            <Code className="w-5 h-5 text-emerald-600" /> Programmatic REST API Integration
                                        </CardTitle>
                                        <CardDescription>
                                            Send WhatsApp messages programmatically using REST API endpoints.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="pt-6 space-y-6">
                                        <div className="space-y-2">
                                            <Label className="text-slate-700">Your API Bearer Key</Label>
                                            <div className="flex gap-2">
                                                <Input
                                                    readOnly
                                                    value={apiToken}
                                                    className="font-mono text-sm bg-slate-50 border-slate-300 text-slate-900"
                                                />
                                                <Button
                                                    onClick={copyApiToken}
                                                    variant="outline"
                                                    className="shrink-0 flex items-center gap-2 border-slate-300 text-slate-700 hover:bg-slate-100"
                                                >
                                                    {copiedToken ? <CheckCircle2 className="w-4 h-4 text-emerald-600" /> : <Copy className="w-4 h-4" />}
                                                    {copiedToken ? 'Copied' : 'Copy Key'}
                                                </Button>
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label className="text-slate-700">API Endpoint URL</Label>
                                            <div className="p-3.5 bg-slate-900 text-emerald-400 rounded-xl font-mono text-sm">
                                                POST {window.location.origin}/api/v1/whatsapp/send
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label className="text-slate-700">cURL Example Request</Label>
                                            <pre className="p-4 bg-slate-950 text-emerald-400 rounded-xl font-mono text-xs overflow-x-auto leading-relaxed">
{`curl -X POST "${window.location.origin}/api/v1/whatsapp/send" \\
  -H "Authorization: Bearer ${apiToken}" \\
  -H "Content-Type: application/json" \\
  -d '{
    "whatsapp_account_id": "${activeAccounts[0]?.id || 1}",
    "recipient_phone": "201001234567",
    "message_body": "Hello! Your code is 849201."
  }'`}
                                            </pre>
                                        </div>
                                    </CardContent>
                                </Card>
                            </TabsContent>
                        </Tabs>

                        {/* DELIVERY LOGS TABLE BELOW WORKSPACE */}
                        <Card className="border-slate-200 shadow-sm rounded-xl bg-white">
                            <CardHeader className="border-b border-slate-100 pb-4">
                                <CardTitle className="text-base font-semibold flex items-center gap-2 text-slate-900">
                                    <Activity className="w-4 h-4 text-emerald-600" /> Delivery Logs ({activeBusiness?.name || 'Default'})
                                </CardTitle>
                                <CardDescription>Live dispatch status log from Meta Cloud API</CardDescription>
                            </CardHeader>
                            <CardContent className="pt-4">
                                {safeLogs.filter(l => !l.whatsapp_business_id || l.whatsapp_business_id === activeBusiness?.id).length === 0 ? (
                                    <p className="text-sm text-slate-500 py-6 text-center">No message logs recorded yet.</p>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-xs text-left text-slate-600">
                                            <thead className="text-[11px] text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                                                <tr>
                                                    <th className="px-3 py-2">ID</th>
                                                    <th className="px-3 py-2">Recipient</th>
                                                    <th className="px-3 py-2">Status</th>
                                                    <th className="px-3 py-2">Fee</th>
                                                    <th className="px-3 py-2">Message Body</th>
                                                    <th className="px-3 py-2">Timestamp</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {safeLogs
                                                    .filter(l => !l.whatsapp_business_id || l.whatsapp_business_id === activeBusiness?.id)
                                                    .map((log) => (
                                                        <tr key={log.id} className="hover:bg-slate-50/50">
                                                            <td className="px-3 py-2 font-mono text-slate-400">#{log.id}</td>
                                                            <td className="px-3 py-2 font-medium text-slate-900">{log.recipient_phone}</td>
                                                            <td className="px-3 py-2">
                                                                {log.status === 'sent' ? (
                                                                    <Badge className="bg-emerald-100 text-emerald-700 border-0 flex w-fit items-center gap-1 text-[10px]">
                                                                        <CheckCircle2 className="w-3 h-3" /> Sent
                                                                    </Badge>
                                                                ) : (
                                                                    <Badge className="bg-rose-100 text-rose-700 border-0 flex w-fit items-center gap-1 text-[10px]">
                                                                        <XCircle className="w-3 h-3" /> Failed
                                                                    </Badge>
                                                                )}
                                                            </td>
                                                            <td className="px-3 py-2 font-mono text-slate-700">${Number(log.cost_charged || 0).toFixed(4)}</td>
                                                            <td className="px-3 py-2 max-w-xs truncate text-slate-700">{log.message_body}</td>
                                                            <td className="px-3 py-2 text-slate-400">{new Date(log.created_at).toLocaleString()}</td>
                                                        </tr>
                                                    ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* META API GUIDE MODAL */}
                <MetaSetupGuideModal
                    isOpen={showGuideModal}
                    onClose={() => setShowGuideModal(false)}
                />

                {/* REGISTER PHONE NUMBER MODAL */}
                <Dialog open={!!registerAccount} onOpenChange={(open) => !open && setRegisterAccount(null)}>
                    <DialogContent className="sm:max-w-md bg-white text-slate-900 border-slate-200 rounded-2xl">
                        <DialogHeader>
                            <DialogTitle className="text-lg font-bold flex items-center gap-2 text-slate-900">
                                <ShieldCheck className="w-5 h-5 text-amber-600" /> Register Phone Number on Meta
                            </DialogTitle>
                            <DialogDescription className="text-slate-500 text-sm">
                                Registering the phone number <span className="font-bold text-slate-800">{registerAccount?.name}</span> (ID: {registerAccount?.phone_number_id}) to activate it for sending messages on the WhatsApp Cloud API.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleRegisterSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="register_pin" className="text-slate-700 font-medium">
                                    Two-Step Verification PIN (6-digit)
                                </Label>
                                <Input
                                    id="register_pin"
                                    type="password"
                                    pattern="[0-9]*"
                                    inputMode="numeric"
                                    maxLength={6}
                                    placeholder="Enter 6-digit PIN"
                                    value={registerForm.data.pin}
                                    onChange={(e) => registerForm.setData('pin', e.target.value.replace(/[^0-9]/g, ''))}
                                    className="bg-white border-slate-300 text-slate-900 font-mono text-center text-lg tracking-widest"
                                    required
                                />
                                <p className="text-xs text-slate-500">
                                    Enter the 6-digit PIN set up during two-step verification in your Meta WhatsApp Manager.
                                </p>
                            </div>

                            <DialogFooter className="pt-2 flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setRegisterAccount(null)}
                                    className="border-slate-300 text-slate-700 hover:bg-slate-100"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={registerForm.processing || registerForm.data.pin.length !== 6}
                                    className="bg-amber-600 hover:bg-amber-700 text-white font-medium"
                                >
                                    {registerForm.processing ? 'Registering...' : 'Register Phone Number'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AuthenticatedLayout>
    );
}
