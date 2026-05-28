import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { ArrowLeft, ArrowDownLeft, ArrowUpRight, RotateCcw } from 'lucide-react';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { __ } from '@/lib/i18n';

const OPERATION_CONFIG = {
    receive: {
        title: () => __('Receive Money'),
        description: () => __('Record a payment received from this client.'),
        route: 'erp.clients.wallet.receive',
        icon: ArrowDownLeft,
        iconColor: 'text-emerald-600',
        buttonClass: 'bg-emerald-600 hover:bg-emerald-700 text-white',
    },
    send: {
        title: () => __('Send Money'),
        description: () => __('Record money sent or deducted from this client.'),
        route: 'erp.clients.wallet.send',
        icon: ArrowUpRight,
        iconColor: 'text-amber-600',
        buttonClass: 'bg-amber-600 hover:bg-amber-700 text-white',
    },
    refund: {
        title: () => __('Refund'),
        description: () => __('Process a refund for this client.'),
        route: 'erp.clients.wallet.refund',
        icon: RotateCcw,
        iconColor: 'text-blue-600',
        buttonClass: 'bg-blue-600 hover:bg-blue-700 text-white',
    },
} as const;

type OperationType = keyof typeof OPERATION_CONFIG;

export default function AdjustWallet({ client, wallet }: { client: any, wallet: any }) {
    const searchParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
    const rawType = searchParams?.get('type') || 'receive';
    const operationType: OperationType = (rawType in OPERATION_CONFIG) ? rawType as OperationType : 'receive';
    const projectId = searchParams ? searchParams.get('project_id') : null;

    const config = OPERATION_CONFIG[operationType];
    const IconComponent = config.icon;

    const backRoute = projectId 
        ? route('erp.projects.show', projectId) 
        : route('erp.clients.show', client?.id);

    const [form, setForm] = useState({
        amount: '',
        note: ''
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        
        const endpoint = route(config.route, client.id);
        
        router.post(endpoint, {
            amount: form.amount,
            note: form.note,
            ...(projectId ? { project_id: projectId } : {})
        }, {
            onSuccess: () => setIsSubmitting(false),
            onError: (errs) => {
                setErrors(errs);
                setIsSubmitting(false);
            }
        });
    };

    const currencyCode = client?.currency?.currency;
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('clients');

    return (
        <ERPLayout title={`${config.title()}: ${client?.name}`} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={backRoute} className="text-slate-400 hover:text-slate-900 transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div className="flex items-center gap-3">
                        <div className={`w-10 h-10 rounded-lg bg-slate-50 flex items-center justify-center`}>
                            <IconComponent className={`w-5 h-5 ${config.iconColor}`} />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">{config.title()}</h1>
                            <p className="text-slate-500 text-sm mt-0.5">{config.description()} — {client?.name}</p>
                        </div>
                    </div>
                </div>

                {/* Operation Type Tabs */}
                <div className="flex gap-2 border-b border-slate-200 pb-0">
                    {(Object.keys(OPERATION_CONFIG) as OperationType[]).map((op) => {
                        const opConfig = OPERATION_CONFIG[op];
                        const OpIcon = opConfig.icon;
                        const isActive = op === operationType;
                        return (
                            <Link
                                key={op}
                                href={route('erp.clients.wallet.adjust', client?.id) + `?type=${op}${projectId ? `&project_id=${projectId}` : ''}`}
                                className={`flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-[1px] ${
                                    isActive
                                        ? 'border-slate-900 text-slate-900'
                                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                }`}
                            >
                                <OpIcon className={`w-3.5 h-3.5 ${isActive ? opConfig.iconColor : 'text-slate-400'}`} />
                                {opConfig.title()}
                            </Link>
                        );
                    })}
                </div>

                <div className="space-y-6">
                    {/* Current Balance Card */}
                    <Card className="bg-white border border-slate-200 shadow-sm">
                        <CardContent className="p-6">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div className="space-y-1">
                                    <p className="text-sm font-medium text-slate-500">{__('Available Balance')}</p>
                                    <div className="text-3xl font-bold tracking-tight text-slate-900">
                                        <CurrencyDisplay amount={wallet?.balance !== undefined ? parseFloat(wallet.balance) : 0} currency={currencyCode} />
                                    </div>
                                </div>
                                <div className="flex items-center gap-6 text-sm text-slate-500">
                                    <div className="text-center">
                                        <p className="text-xs font-medium text-slate-400 uppercase tracking-wider">{__('Locked')}</p>
                                        <p className="text-base font-semibold text-slate-700 mt-0.5">
                                            <CurrencyDisplay amount={wallet?.locked_balance ?? 0} currency={currencyCode} />
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Transaction Form */}
                    <Card className="bg-white border border-slate-200 shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-slate-900 flex items-center gap-2">
                                <IconComponent className={`w-5 h-5 ${config.iconColor}`} /> {config.title()}
                            </CardTitle>
                            <CardDescription className="text-slate-500">
                                {config.description()}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium text-slate-700">{__('Amount')} <span className="text-red-500">*</span></label>
                                        <div className="relative">
                                            {currencyCode && (
                                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span className="text-slate-400 text-xs font-medium">{currencyCode}</span>
                                                </div>
                                            )}
                                            <Input 
                                                required
                                                type="number"
                                                min="0.01"
                                                step="0.01"
                                                value={form.amount} 
                                                onChange={e => setForm({...form, amount: e.target.value})} 
                                                placeholder="0.00" 
                                                className={`bg-white border-slate-200 text-slate-900 ${currencyCode ? 'pl-12' : ''}`}
                                            />
                                        </div>
                                        {errors.amount && <p className="text-xs text-red-500">{errors.amount}</p>}
                                    </div>
                                    <div className="space-y-2 md:col-span-1">
                                        <label className="text-sm font-medium text-slate-700">{__('Note')} <span className="text-red-500">*</span></label>
                                        <Input 
                                            required
                                            value={form.note} 
                                            onChange={e => setForm({...form, note: e.target.value})} 
                                            placeholder={__('Reason for this transaction...')}
                                            className="bg-white border-slate-200 text-slate-900"
                                        />
                                        {errors.note && <p className="text-xs text-red-500">{errors.note}</p>}
                                    </div>
                                </div>
                                
                                <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                                    <Link href={backRoute}>
                                        <Button type="button" variant="ghost" className="text-slate-500 hover:text-slate-900 hover:bg-slate-100">
                                            {__('Cancel')}
                                        </Button>
                                    </Link>
                                    <Button type="submit" disabled={isSubmitting} className={config.buttonClass}>
                                        {isSubmitting ? __('Processing...') : config.title()}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </ERPLayout>
    );
}
