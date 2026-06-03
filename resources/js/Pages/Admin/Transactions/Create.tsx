import React, { useState } from 'react';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ArrowDownLeft, ArrowUpRight, Undo2, Receipt, Coins, Plus, Trash2, Calculator, StickyNote, TrendingUp } from 'lucide-react';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import TransactionEntryForm from './Components/TransactionEntryForm';
import { __ } from '@/lib/i18n';

interface Props {
    user: any;
    selectedProject: any;
    type: string;
    currencies: any[];
    businessCurrency: any;
    exchanges: any[];
    activeProjects: any[];
    hourRate?: number;
    recommendedHourRate?: number;
}

export default function Create({ user, selectedProject, type, currencies, businessCurrency, exchanges, activeProjects, hourRate, recommendedHourRate }: Props) {
    const defaultTab = () => {
        if (type === 'receive') return 'timer-received';
        if (type === 'send-money' || type === 'send') return 'send';
        if (type === 'refund') return 'refund';
        if (type === 'charge') return 'timer-due';
        if (type === 'earn' || type === 'earned') return 'earned';
        return 'timer-received';
    };

    const [activeTab, setActiveTab] = useState<string>(defaultTab());

    const { data, setData, post, processing, errors, reset } = useForm({
        user: user.id,
        project: selectedProject?.id || '',
        type: activeTab,
        data: [
            { amount: '', reason: '', currency: user.currency_id || businessCurrency.id }
        ]
    });

    const handleTabChange = (value: string) => {
        setActiveTab(value);
        setData('type', value);
        router.visit(route('admin.transactions.create', { 
            user: user.id, 
            project: selectedProject?.id, 
            type: value.replace('timer-received', 'receive').replace('timer-due', 'charge').replace('earned', 'earn') 
        }), { preserveState: true, preserveScroll: true, replace: true });
    };

    const addRow = () => {
        setData('data', [...data.data, { amount: '', reason: '', currency: user.currency_id || businessCurrency.id }]);
    };

    const removeRow = (index: number) => {
        if ((data.data as any).length > 1) {
            const newData = [...data.data];
            newData.splice(index, 1);
            setData('data', newData);
        }
    };

    const updateRow = (index: number, field: string, value: any) => {
        const newData = [...data.data];
        newData[index] = { ...newData[index], [field]: value };
        setData('data', newData);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.transactions.store'), {
            onSuccess: () => {
                reset('data');
                setData('data', [{ amount: '', reason: '', currency: user.currency_id || businessCurrency.id }]);
            }
        });
    };

    const { auth } = usePage().props as any;

    return (
        <AdminSidebarLayout title={__('general.new_transaction')} header="New Transaction" user={auth?.user}>
            <Head title={`Adjust Wallet: ${user.name}`} />

            <div className="w-full max-w-6xl mx-auto py-6 space-y-6">
                <header className="mb-4">
                    <div className="flex items-center gap-2 mb-1">
                        <span className="text-xs font-semibold px-2 py-1 bg-primary/10 text-primary rounded-md uppercase tracking-wider">
                            Finance
                        </span>
                    </div>
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">{__('general.wallet_adjustments')}</h1>
                            <p className="text-muted-foreground mt-1">
                                {user.name} {selectedProject ? `(Project: ${selectedProject.project_name})` : ''}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button variant="outline" onClick={() => window.history.back()}>
                                <ArrowDownLeft className="h-4 w-4 mr-2" style={{ transform: 'rotate(45deg)' }} /> Back
                            </Button>
                        </div>
                    </div>
                </header>

                <div className="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
                    {/* Stats Sidebar */}
                    <aside className="xl:col-span-3 order-2 xl:order-1 space-y-4">
                        <Card>
                            <CardContent className="p-4 flex items-center gap-3">
                                <div className="h-10 w-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center shrink-0">
                                    <Coins className="h-5 w-5" />
                                </div>
                                <div className="min-w-0">
                                    <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">{__('general.user_balance')}</p>
                                    <p className="font-bold text-sm truncate">
                                        <CurrencyDisplay amount={user.user_balance || 0} currency={currencies.find(c => c.id === (user.currency_id || businessCurrency.id))} />
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="p-4 flex items-center gap-3">
                                <div className="h-10 w-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                                    <TrendingUp className="h-5 w-5" />
                                </div>
                                <div className="min-w-0">
                                    <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">{__('general.pending_earns')}</p>
                                    <p className="font-bold text-sm truncate">
                                        <CurrencyDisplay amount={user.pending_commission || 0} currency={currencies.find(c => c.id === (user.currency_id || businessCurrency.id))} />
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="p-4 flex items-center gap-3">
                                <div className="h-10 w-10 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center shrink-0">
                                    <Receipt className="h-5 w-5" />
                                </div>
                                <div className="min-w-0">
                                    <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">{__('general.cleared_earns')}</p>
                                    <p className="font-bold text-sm truncate">
                                        <CurrencyDisplay amount={user.available_commission || 0} currency={currencies.find(c => c.id === (user.currency_id || businessCurrency.id))} />
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="p-4 flex items-center gap-3">
                                <div className="h-10 w-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                                    <ArrowUpRight className="h-5 w-5" />
                                </div>
                                <div className="min-w-0">
                                    <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Withdrawn</p>
                                    <p className="font-bold text-sm truncate">
                                        <CurrencyDisplay amount={user.withdrawn_commission || 0} currency={currencies.find(c => c.id === (user.currency_id || businessCurrency.id))} />
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </aside>

                    {/* Main Form Area */}
                    <div className="xl:col-span-9 order-1 xl:order-2">

                <Tabs value={activeTab} onValueChange={handleTabChange} className="w-full">
                    <TabsList className="grid w-full grid-cols-5 mb-6">
                        <TabsTrigger value="timer-received" className="flex items-center gap-2">
                            <ArrowDownLeft className="h-4 w-4 text-green-500" />
                            <span className="hidden sm:inline">Receive</span>
                        </TabsTrigger>
                        <TabsTrigger value="timer-due" className="flex items-center gap-2">
                            <Receipt className="h-4 w-4 text-orange-500" />
                            <span className="hidden sm:inline">Charge</span>
                        </TabsTrigger>
                        <TabsTrigger value="send" className="flex items-center gap-2">
                            <ArrowUpRight className="h-4 w-4 text-blue-500" />
                            <span className="hidden sm:inline">Send</span>
                        </TabsTrigger>
                        <TabsTrigger value="refund" className="flex items-center gap-2">
                            <Undo2 className="h-4 w-4 text-red-500" />
                            <span className="hidden sm:inline">Refund</span>
                        </TabsTrigger>
                        <TabsTrigger value="earned" className="flex items-center gap-2">
                            <Coins className="h-4 w-4 text-purple-500" />
                            <span className="hidden sm:inline">{__('general.add_earn')}</span>
                        </TabsTrigger>
                    </TabsList>

                    <TransactionEntryForm 
                        user={user}
                        selectedProject={selectedProject}
                        activeProjects={activeProjects}
                        type={activeTab}
                        currencies={currencies}
                        businessCurrency={businessCurrency}
                        exchanges={exchanges}
                    />
                </Tabs>
                    </div>
                </div>

                {/* Footer Quick Links */}
                <div className="mt-6 pt-6 border-t flex flex-wrap items-center justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <Button variant="secondary" asChild className="rounded-full">
                            <a href={route('admin.users.notes.index', user.id)} target="_blank" rel="noopener noreferrer">
                                <StickyNote className="h-4 w-4 mr-2" />{__('general.user_notes')}</a>
                        </Button>
                        <Button variant="secondary" asChild className="rounded-full">
                            <a href={route('admin.finance.index', { user_id: user.id })} target="_blank" rel="noopener noreferrer">
                                <Coins className="h-4 w-4 mr-2" /> Transactions
                            </a>
                        </Button>
                        <Button variant="secondary" asChild className="rounded-full">
                            <a href={route('admin.invoices.index', { client_id: user.id })} target="_blank" rel="noopener noreferrer">
                                <Receipt className="h-4 w-4 mr-2" /> Invoices
                            </a>
                        </Button>
                    </div>
                    {(hourRate !== undefined || recommendedHourRate !== undefined) && (
                        <div className="flex items-center gap-4 text-sm bg-muted/30 px-4 py-2 rounded-full border">
                            <span className="font-semibold text-muted-foreground uppercase tracking-wider text-xs">Rates Info:</span>
                            {hourRate !== undefined && (
                                <span className="flex items-center gap-1">Client Rate: <strong className="text-foreground"><CurrencyDisplay amount={hourRate} currency={currencies.find(c => c.id === (user.currency_id || businessCurrency.id))} /></strong></span>
                            )}
                            {hourRate !== undefined && recommendedHourRate !== undefined && (
                                <span className="text-muted-foreground/50">|</span>
                            )}
                            {recommendedHourRate !== undefined && (
                                <span className="flex items-center gap-1">Recommended: <strong className="text-foreground"><CurrencyDisplay amount={recommendedHourRate} currency={currencies.find(c => c.id === (user.currency_id || businessCurrency.id))} /></strong></span>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
