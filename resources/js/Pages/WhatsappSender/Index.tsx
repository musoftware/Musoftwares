import React, { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Badge } from '@/Components/ui/badge';
import { useToast } from '@/Components/ui/use-toast';
import { Toaster } from '@/Components/ui/toaster';
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
    LogOut,
    User,
    Sparkles,
    Globe
} from 'lucide-react';

interface WhatsappAccount {
    id: number;
    name: string;
    phone_number_id: string;
    waba_id: string | null;
    status: string;
    facebook_user_id: string | null;
    created_at: string;
}

interface WhatsappLog {
    id: number;
    recipient_phone: string;
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
}

interface PageProps {
    accounts: WhatsappAccount[];
    logs: WhatsappLog[];
    apiToken: string;
    facebookLoginUrl: string;
}

export default function StandaloneWhatsappIndex({ accounts, logs, apiToken, facebookLoginUrl }: PageProps) {
    const { toast } = useToast();
    const { auth, flash } = usePage().props as any;
    const user = auth?.user;
    const [copiedToken, setCopiedToken] = useState(false);

    // Send Form state
    const sendForm = useForm({
        whatsapp_account_id: accounts.length > 0 ? String(accounts[0].id) : '',
        recipient_phone: '',
        message_body: '',
        message_type: 'text',
        template_name: 'hello_world',
        template_language: 'en_US',
    });

    // Account Credential Form state
    const accountForm = useForm({
        name: '',
        phone_number_id: '',
        waba_id: '',
        access_token: '',
    });

    const handleSendSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        sendForm.post(route('whatsapp.send'), {
            onSuccess: () => {
                sendForm.reset('recipient_phone', 'message_body');
                toast({
                    title: 'Message Request Submitted',
                    description: 'Your WhatsApp message request was sent to Meta API.',
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
                accountForm.reset();
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

    const handleDeleteAccount = (id: number) => {
        if (confirm('Are you sure you want to disconnect this Meta WhatsApp account?')) {
            useForm().delete(route('whatsapp.accounts.destroy', id), {
                onSuccess: () => {
                    toast({
                        title: 'Account Disconnected',
                        description: 'WhatsApp account removed successfully.',
                    });
                },
            });
        }
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
        <div className="min-h-screen bg-slate-950 text-slate-100 font-sans antialiased selection:bg-emerald-500 selection:text-white">
            <Head title="WhatsApp Sender API Service — Meta Cloud API" />
            <Toaster />

            {/* STANDALONE TOP NAVIGATION HEADER */}
            <header className="border-b border-slate-800 bg-slate-900/80 backdrop-blur-md sticky top-0 z-50">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center shadow-lg shadow-emerald-900/30">
                            <MessageSquare className="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="font-bold text-lg text-white tracking-tight">WhatsApp Sender API</h1>
                                <Badge className="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-mono px-2 py-0.5">
                                    Meta Cloud API v21.0
                                </Badge>
                            </div>
                            <p className="text-xs text-slate-400">Standalone WhatsApp Dispatch & Messaging Engine</p>
                        </div>
                    </div>

                    {user && (
                        <div className="flex items-center gap-3">
                            <div className="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800/80 border border-slate-700/60 text-xs">
                                <User className="w-3.5 h-3.5 text-emerald-400" />
                                <span className="font-medium text-slate-200">{user.name}</span>
                            </div>
                        </div>
                    )}
                </div>
            </header>

            {/* MAIN CONTENT AREA */}
            <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

                {flash?.success && (
                    <div className="p-4 bg-emerald-950/60 border border-emerald-800/80 text-emerald-200 rounded-xl flex items-center gap-3 shadow-lg">
                        <CheckCircle2 className="w-5 h-5 text-emerald-400 shrink-0" />
                        <span className="font-medium text-sm">{flash.success}</span>
                    </div>
                )}

                {flash?.error && (
                    <div className="p-4 bg-rose-950/60 border border-rose-800/80 text-rose-200 rounded-xl flex items-center gap-3 shadow-lg">
                        <XCircle className="w-5 h-5 text-rose-400 shrink-0" />
                        <span className="font-medium text-sm">{flash.error}</span>
                    </div>
                )}

                <Tabs defaultValue="send" className="w-full">
                    <TabsList className="grid w-full grid-cols-4 bg-slate-900 p-1.5 rounded-xl border border-slate-800">
                        <TabsTrigger value="send" className="flex items-center justify-center gap-2 font-medium data-[state=active]:bg-emerald-600 data-[state=active]:text-white">
                            <Send className="w-4 h-4" /> Quick Sender Form
                        </TabsTrigger>
                        <TabsTrigger value="accounts" className="flex items-center justify-center gap-2 font-medium data-[state=active]:bg-emerald-600 data-[state=active]:text-white">
                            <ShieldCheck className="w-4 h-4" /> Facebook / Meta ({accounts.length})
                        </TabsTrigger>
                        <TabsTrigger value="api" className="flex items-center justify-center gap-2 font-medium data-[state=active]:bg-emerald-600 data-[state=active]:text-white">
                            <Code className="w-4 h-4" /> API Docs & Keys
                        </TabsTrigger>
                        <TabsTrigger value="logs" className="flex items-center justify-center gap-2 font-medium data-[state=active]:bg-emerald-600 data-[state=active]:text-white">
                            <Activity className="w-4 h-4" /> Delivery Logs ({logs.length})
                        </TabsTrigger>
                    </TabsList>

                    {/* TAB 1: QUICK SENDER FORM */}
                    <TabsContent value="send" className="mt-6">
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <Card className="lg:col-span-2 border-slate-800 bg-slate-900/90 text-slate-100 shadow-xl rounded-2xl">
                                <CardHeader className="border-b border-slate-800/80 pb-4">
                                    <CardTitle className="text-lg font-semibold flex items-center gap-2 text-white">
                                        <Send className="w-5 h-5 text-emerald-400" /> Send WhatsApp Message
                                    </CardTitle>
                                    <CardDescription className="text-slate-400">
                                        Dispatch single text or template messages directly via Meta Cloud API.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="pt-6">
                                    {accounts.length === 0 ? (
                                        <div className="p-8 text-center bg-slate-950/60 rounded-2xl border border-dashed border-slate-800 space-y-4">
                                            <Smartphone className="w-12 h-12 text-slate-500 mx-auto" />
                                            <div>
                                                <h3 className="text-base font-semibold text-white">No WhatsApp Meta Account Connected</h3>
                                                <p className="text-sm text-slate-400 max-w-md mx-auto mt-1">
                                                    Connect your Facebook Login or enter your Meta Phone Number ID & Access Token to start sending.
                                                </p>
                                            </div>
                                            <Button
                                                onClick={() => (document.querySelector('[value="accounts"]') as HTMLElement)?.click()}
                                                className="bg-emerald-600 hover:bg-emerald-500 text-white font-medium px-6 py-2 rounded-xl shadow-lg shadow-emerald-950/50"
                                            >
                                                <PlusCircle className="w-4 h-4 me-2" /> Connect Account
                                            </Button>
                                        </div>
                                    ) : (
                                        <form onSubmit={handleSendSubmit} className="space-y-5">
                                            <div className="space-y-2">
                                                <Label htmlFor="whatsapp_account_id" className="text-slate-200">Sender Meta Account</Label>
                                                <select
                                                    id="whatsapp_account_id"
                                                    value={sendForm.data.whatsapp_account_id}
                                                    onChange={(e) => sendForm.setData('whatsapp_account_id', e.target.value)}
                                                    className="w-full h-11 px-3 py-2 border border-slate-700 rounded-xl text-sm bg-slate-950 text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                                                >
                                                    {accounts.map((acc) => (
                                                        <option key={acc.id} value={acc.id}>
                                                            {acc.name} (Phone ID: {acc.phone_number_id})
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="recipient_phone" className="text-slate-200">Recipient Phone Number (with Country Code)</Label>
                                                <Input
                                                    id="recipient_phone"
                                                    type="text"
                                                    placeholder="e.g. 201001234567 or +201001234567"
                                                    value={sendForm.data.recipient_phone}
                                                    onChange={(e) => sendForm.setData('recipient_phone', e.target.value)}
                                                    className="bg-slate-950 border-slate-700 text-white placeholder:text-slate-600 h-11 rounded-xl focus:ring-2 focus:ring-emerald-500"
                                                    required
                                                />
                                                <p className="text-xs text-slate-400">Include country code without leading zero or spaces (e.g., 20 for Egypt, 966 for KSA).</p>
                                            </div>

                                            <div className="grid grid-cols-2 gap-4">
                                                <div className="space-y-2">
                                                    <Label htmlFor="message_type" className="text-slate-200">Type</Label>
                                                    <select
                                                        id="message_type"
                                                        value={sendForm.data.message_type}
                                                        onChange={(e) => sendForm.setData('message_type', e.target.value)}
                                                        className="w-full h-11 px-3 py-2 border border-slate-700 rounded-xl text-sm bg-slate-950 text-white focus:ring-2 focus:ring-emerald-500 outline-none"
                                                    >
                                                        <option value="text">Direct Custom Text</option>
                                                        <option value="template">Meta Template</option>
                                                    </select>
                                                </div>

                                                {sendForm.data.message_type === 'template' && (
                                                    <div className="space-y-2">
                                                        <Label htmlFor="template_name" className="text-slate-200">Template Name</Label>
                                                        <Input
                                                            id="template_name"
                                                            placeholder="hello_world"
                                                            value={sendForm.data.template_name}
                                                            onChange={(e) => sendForm.setData('template_name', e.target.value)}
                                                            className="bg-slate-950 border-slate-700 text-white h-11 rounded-xl"
                                                        />
                                                    </div>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="message_body" className="text-slate-200">Message Content</Label>
                                                <Textarea
                                                    id="message_body"
                                                    rows={4}
                                                    placeholder="Type your WhatsApp message text here..."
                                                    value={sendForm.data.message_body}
                                                    onChange={(e) => sendForm.setData('message_body', e.target.value)}
                                                    className="bg-slate-950 border-slate-700 text-white placeholder:text-slate-600 rounded-xl focus:ring-2 focus:ring-emerald-500"
                                                    required
                                                />
                                                <div className="flex justify-between text-xs text-slate-500">
                                                    <span>Meta Cloud API supports up to 4,096 characters per message.</span>
                                                    <span>{sendForm.data.message_body.length} / 4096</span>
                                                </div>
                                            </div>

                                            <Button
                                                type="submit"
                                                disabled={sendForm.processing}
                                                className="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold h-11 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-emerald-950/60 transition-all"
                                            >
                                                <Send className="w-4 h-4" />
                                                {sendForm.processing ? 'Dispatching via Meta API...' : 'Send WhatsApp Message'}
                                            </Button>
                                        </form>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-slate-800 bg-slate-900/90 text-slate-100 shadow-xl rounded-2xl h-fit">
                                <CardHeader className="border-b border-slate-800/80 pb-4">
                                    <CardTitle className="text-base font-semibold">Engine Overview</CardTitle>
                                    <CardDescription className="text-slate-400">WhatsApp API Service Metrics</CardDescription>
                                </CardHeader>
                                <CardContent className="pt-6 space-y-4 text-sm">
                                    <div className="p-3.5 bg-emerald-950/50 border border-emerald-800/60 text-emerald-300 rounded-xl space-y-1">
                                        <div className="font-semibold flex items-center gap-2">
                                            <Sparkles className="w-4 h-4 text-emerald-400" /> Standalone Service Active
                                        </div>
                                        <p className="text-xs text-emerald-400/80 leading-relaxed">
                                            Direct HTTP/REST pipeline to Meta Graph API `v21.0` endpoints.
                                        </p>
                                    </div>

                                    <div className="border-t border-slate-800 pt-4 space-y-3">
                                        <div className="flex justify-between text-slate-400">
                                            <span>Configured Accounts:</span>
                                            <span className="font-semibold text-white">{accounts.length}</span>
                                        </div>
                                        <div className="flex justify-between text-slate-400">
                                            <span>Successful Messages:</span>
                                            <span className="font-semibold text-emerald-400">{logs.filter(l => l.status === 'sent').length}</span>
                                        </div>
                                        <div className="flex justify-between text-slate-400">
                                            <span>Failed Dispatch:</span>
                                            <span className="font-semibold text-rose-400">{logs.filter(l => l.status === 'failed').length}</span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    {/* TAB 2: ACCOUNTS & FACEBOOK LOGIN */}
                    <TabsContent value="accounts" className="mt-6">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">

                            {/* FACEBOOK LOGIN / META OAUTH */}
                            <Card className="border-slate-800 bg-slate-900/90 text-slate-100 shadow-xl rounded-2xl">
                                <CardHeader className="border-b border-slate-800/80 pb-4">
                                    <CardTitle className="text-lg font-semibold flex items-center gap-2 text-white">
                                        <ShieldCheck className="w-5 h-5 text-indigo-400" /> Facebook Login / Meta Connect
                                    </CardTitle>
                                    <CardDescription className="text-slate-400">
                                        Connect your Meta WhatsApp Business Account via Facebook Login.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="pt-6 space-y-5">
                                    <div className="p-4 bg-indigo-950/50 border border-indigo-800/60 rounded-xl space-y-3">
                                        <p className="text-xs text-indigo-200 leading-relaxed">
                                            Authorize your Meta WhatsApp Business Account automatically through Facebook Login for Business.
                                        </p>
                                        <a href={facebookLoginUrl} className="inline-block w-full">
                                            <Button className="w-full bg-[#1877F2] hover:bg-[#166fe5] text-white font-semibold h-11 rounded-xl flex items-center justify-center gap-2.5 shadow-lg shadow-blue-950/50">
                                                <svg className="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                                </svg>
                                                Connect with Facebook Login
                                            </Button>
                                        </a>
                                    </div>

                                    <div className="relative my-4">
                                        <div className="absolute inset-0 flex items-center">
                                            <span className="w-full border-t border-slate-800" />
                                        </div>
                                        <div className="relative flex justify-center text-xs uppercase">
                                            <span className="bg-slate-900 px-3 text-slate-500 font-mono">Or Manual Meta API Token Input</span>
                                        </div>
                                    </div>

                                    <form onSubmit={handleAccountSubmit} className="space-y-4">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="acc_name" className="text-slate-200">Account Label</Label>
                                            <Input
                                                id="acc_name"
                                                placeholder="e.g. Primary WhatsApp Account"
                                                value={accountForm.data.name}
                                                onChange={(e) => accountForm.setData('name', e.target.value)}
                                                className="bg-slate-950 border-slate-700 text-white h-10 rounded-xl"
                                                required
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="phone_number_id" className="text-slate-200">Meta Phone Number ID</Label>
                                            <Input
                                                id="phone_number_id"
                                                placeholder="e.g. 104829103948123"
                                                value={accountForm.data.phone_number_id}
                                                onChange={(e) => accountForm.setData('phone_number_id', e.target.value)}
                                                className="bg-slate-950 border-slate-700 text-white h-10 rounded-xl font-mono"
                                                required
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="waba_id" className="text-slate-200">WABA ID (Optional)</Label>
                                            <Input
                                                id="waba_id"
                                                placeholder="e.g. 982347109283741"
                                                value={accountForm.data.waba_id}
                                                onChange={(e) => accountForm.setData('waba_id', e.target.value)}
                                                className="bg-slate-950 border-slate-700 text-white h-10 rounded-xl font-mono"
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="access_token" className="text-slate-200">Meta System User Access Token</Label>
                                            <Textarea
                                                id="access_token"
                                                rows={3}
                                                placeholder="EAA..."
                                                value={accountForm.data.access_token}
                                                onChange={(e) => accountForm.setData('access_token', e.target.value)}
                                                className="bg-slate-950 border-slate-700 text-white font-mono text-xs rounded-xl"
                                                required
                                            />
                                        </div>

                                        <Button
                                            type="submit"
                                            disabled={accountForm.processing}
                                            className="w-full bg-slate-800 hover:bg-slate-700 text-white font-medium h-10 rounded-xl"
                                        >
                                            {accountForm.processing ? 'Saving Credentials...' : 'Save Meta Credentials'}
                                        </Button>
                                    </form>
                                </CardContent>
                            </Card>

                            {/* CONNECTED ACCOUNTS LIST */}
                            <Card className="border-slate-800 bg-slate-900/90 text-slate-100 shadow-xl rounded-2xl">
                                <CardHeader className="border-b border-slate-800/80 pb-4">
                                    <CardTitle className="text-lg font-semibold text-white">Active Connected Accounts</CardTitle>
                                    <CardDescription className="text-slate-400">Meta Cloud WhatsApp Phone Endpoints</CardDescription>
                                </CardHeader>
                                <CardContent className="pt-6">
                                    {accounts.length === 0 ? (
                                        <p className="text-sm text-slate-500 py-8 text-center">No accounts connected yet.</p>
                                    ) : (
                                        <div className="space-y-3">
                                            {accounts.map((acc) => (
                                                <div
                                                    key={acc.id}
                                                    className="p-4 border border-slate-800 rounded-xl bg-slate-950/70 flex items-center justify-between"
                                                >
                                                    <div className="space-y-1">
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-semibold text-white">{acc.name}</span>
                                                            <Badge className="bg-emerald-950 text-emerald-400 border border-emerald-800 text-[10px] uppercase">
                                                                {acc.status}
                                                            </Badge>
                                                        </div>
                                                        <p className="text-xs text-slate-400">
                                                            Phone Number ID: <code className="bg-slate-900 text-emerald-300 px-1.5 py-0.5 rounded font-mono">{acc.phone_number_id}</code>
                                                        </p>
                                                        {acc.waba_id && (
                                                            <p className="text-xs text-slate-400">
                                                                WABA ID: <code className="bg-slate-900 text-slate-300 px-1.5 py-0.5 rounded font-mono">{acc.waba_id}</code>
                                                            </p>
                                                        )}
                                                    </div>

                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDeleteAccount(acc.id)}
                                                        className="text-rose-400 hover:text-rose-300 hover:bg-rose-950/50"
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </Button>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    {/* TAB 3: API DOCS & KEYS */}
                    <TabsContent value="api" className="mt-6">
                        <Card className="border-slate-800 bg-slate-900/90 text-slate-100 shadow-xl rounded-2xl">
                            <CardHeader className="border-b border-slate-800/80 pb-4">
                                <CardTitle className="text-lg font-semibold flex items-center gap-2 text-white">
                                    <Code className="w-5 h-5 text-emerald-400" /> Programmatic REST API Integration
                                </CardTitle>
                                <CardDescription className="text-slate-400">
                                    Send WhatsApp messages programmatically using REST API endpoints.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="pt-6 space-y-6">
                                <div className="space-y-2">
                                    <Label className="text-slate-200">Your API Bearer Key</Label>
                                    <div className="flex gap-2">
                                        <Input
                                            readOnly
                                            value={apiToken}
                                            className="font-mono text-sm bg-slate-950 border-slate-700 text-emerald-400 h-11 rounded-xl"
                                        />
                                        <Button
                                            onClick={copyApiToken}
                                            variant="outline"
                                            className="shrink-0 flex items-center gap-2 border-slate-700 text-white hover:bg-slate-800 h-11 rounded-xl"
                                        >
                                            {copiedToken ? <CheckCircle2 className="w-4 h-4 text-emerald-400" /> : <Copy className="w-4 h-4" />}
                                            {copiedToken ? 'Copied' : 'Copy Key'}
                                        </Button>
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-slate-200">API Endpoint URL</Label>
                                    <div className="p-3.5 bg-slate-950 border border-slate-800 text-emerald-400 rounded-xl font-mono text-sm">
                                        POST {window.location.origin}/api/v1/whatsapp/send
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-slate-200">cURL Example Request</Label>
                                    <pre className="p-4 bg-slate-950 border border-slate-800 text-emerald-400 rounded-xl font-mono text-xs overflow-x-auto leading-relaxed">
{`curl -X POST "${window.location.origin}/api/v1/whatsapp/send" \\
  -H "Authorization: Bearer ${apiToken}" \\
  -H "Content-Type: application/json" \\
  -d '{
    "recipient_phone": "201001234567",
    "message_body": "Hello! Your code is 849201."
  }'`}
                                    </pre>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* TAB 4: MESSAGE LOGS */}
                    <TabsContent value="logs" className="mt-6">
                        <Card className="border-slate-800 bg-slate-900/90 text-slate-100 shadow-xl rounded-2xl">
                            <CardHeader className="border-b border-slate-800/80 pb-4">
                                <CardTitle className="text-lg font-semibold flex items-center gap-2 text-white">
                                    <Activity className="w-5 h-5 text-emerald-400" /> WhatsApp Message Delivery Logs
                                </CardTitle>
                                <CardDescription className="text-slate-400">Live dispatch status log from Meta Cloud API</CardDescription>
                            </CardHeader>
                            <CardContent className="pt-6">
                                {logs.length === 0 ? (
                                    <p className="text-sm text-slate-500 py-8 text-center">No message logs recorded yet.</p>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm text-left text-slate-300">
                                            <thead className="text-xs text-slate-400 uppercase bg-slate-950 border-b border-slate-800">
                                                <tr>
                                                    <th className="px-4 py-3">ID</th>
                                                    <th className="px-4 py-3">Recipient</th>
                                                    <th className="px-4 py-3">Status</th>
                                                    <th className="px-4 py-3">Message Content</th>
                                                    <th className="px-4 py-3">Meta ID / Error</th>
                                                    <th className="px-4 py-3">Timestamp</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-800/60">
                                                {logs.map((log) => (
                                                    <tr key={log.id} className="hover:bg-slate-800/40">
                                                        <td className="px-4 py-3 font-mono text-slate-500">#{log.id}</td>
                                                        <td className="px-4 py-3 font-medium text-white">{log.recipient_phone}</td>
                                                        <td className="px-4 py-3">
                                                            {log.status === 'sent' ? (
                                                                <Badge className="bg-emerald-950 text-emerald-400 border border-emerald-800 flex w-fit items-center gap-1">
                                                                    <CheckCircle2 className="w-3 h-3" /> Sent
                                                                </Badge>
                                                            ) : (
                                                                <Badge className="bg-rose-950 text-rose-400 border border-rose-800 flex w-fit items-center gap-1">
                                                                    <XCircle className="w-3 h-3" /> Failed
                                                                </Badge>
                                                            )}
                                                        </td>
                                                        <td className="px-4 py-3 max-w-xs truncate text-slate-300">{log.message_body}</td>
                                                        <td className="px-4 py-3 font-mono text-xs text-slate-400">
                                                            {log.meta_message_id || <span className="text-rose-400 truncate block max-w-xs">{log.error_message}</span>}
                                                        </td>
                                                        <td className="px-4 py-3 text-xs text-slate-500">
                                                            {new Date(log.created_at).toLocaleString()}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </main>
        </div>
    );
}
