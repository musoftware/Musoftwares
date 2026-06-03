import React, { useState, useEffect } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ArrowDownLeft, Trash2, Lightbulb, Save, Repeat, ArrowRightLeft } from 'lucide-react';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import axios from 'axios';
import { toast } from 'sonner';

interface Project {
    id: number;
    project_name: string;
}

interface Currency {
    id: number;
    currency: string;
    symbol: string;
}

interface TransferItem {
    id: number;
    start_date: Date;
    end_date: Date;
    source_project_name: string;
    target_project_name: string;
    source_project_id: number;
    target_project_id: number;
    reason: string;
    amount: number;
    currency: number;
    currency_symbol: string;
}

interface Props {
    user: any;
    activeProjects: Project[];
    currencies: Currency[];
    exchanges: any[];
}

export default function Transfer({ user, activeProjects, currencies, exchanges }: Props) {
    const { auth } = usePage().props as any;
    const [currencyId, setCurrencyId] = useState<number>(currencies[0]?.id || 1);
    const [sourceProject, setSourceProject] = useState<number | ''>('');
    const [targetProject, setTargetProject] = useState<number | ''>('');
    const [sourceBalance, setSourceBalance] = useState<number>(0);
    const [targetBalance, setTargetBalance] = useState<number>(0);
    
    const [amount, setAmount] = useState<number | ''>('');
    const [maxAmount, setMaxAmount] = useState<number>(0);
    const [showExchange, setShowExchange] = useState<boolean>(false);
    
    const [fromCurrency, setFromCurrency] = useState<number>(currencies.find(c => c.id !== currencyId)?.id || 1);
    const [fromAmount, setFromAmount] = useState<number | ''>('');
    const [toAmount, setToAmount] = useState<number>(0);

    const [itemIdCounter, setItemIdCounter] = useState(0);

    const { data, setData, post, processing, reset } = useForm({
        user: user.id,
        data: [] as TransferItem[]
    });

    const activeCurrency = currencies.find(c => c.id === currencyId) || currencies[0];
    const fromCurrencyObj = currencies.find(c => c.id === fromCurrency) || currencies[0];

    useEffect(() => {
        if (sourceProject) fetchBalance(sourceProject, setSourceBalance, setMaxAmount);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [sourceProject, currencyId]);

    useEffect(() => {
        if (targetProject) fetchBalance(targetProject, setTargetBalance);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [targetProject, currencyId]);

    const fetchBalance = async (projectId: number, setBalance: (val: number) => void, setMax?: (val: number) => void) => {
        try {
            const res = await axios.post(route('admin.project.current_timer'), {
                user: user.id,
                currency: currencyId,
                project: projectId
            });
            if (res.data.status) {
                setBalance(res.data.timer);
                if (setMax) setMax(Math.max(0, res.data.timer));
            }
        } catch (e) {
            console.error(e);
        }
    };

    const handleExchangeCalc = (amt: number, fromCurr: number) => {
        const exchange = exchanges.find(e => Number(e.currency1) === Number(fromCurr) && Number(e.currency2) === Number(currencyId));
        if (exchange) {
            setToAmount(Math.floor(amt * exchange.rate * 1000) / 1000);
        } else {
            setToAmount(0); // Add default fallback if no direct rate found, or implement inverse lookup
        }
    };

    const applyExchange = () => {
        if (toAmount <= 0) {
            toast.error('Amount must be greater than zero');
            return;
        }
        setAmount(toAmount);
        setShowExchange(false);
    };

    const handleAdd = () => {
        const numAmount = Number(amount);
        if (numAmount <= 0) {
            toast.error('Amount is zero or invalid.');
            return;
        }
        if (numAmount > maxAmount) {
            toast.error('Amount exceeds available source balance.');
            return;
        }
        if (!sourceProject || !targetProject) {
            toast.error('Select source and target projects.');
            return;
        }
        if (sourceProject === targetProject) {
            toast.error('Source and target cannot be the same.');
            return;
        }

        const sourceInfo = activeProjects.find(p => p.id === sourceProject);
        const targetInfo = activeProjects.find(p => p.id === targetProject);

        if (!sourceInfo || !targetInfo) return;

        const newItem: TransferItem = {
            id: itemIdCounter,
            start_date: new Date(),
            end_date: new Date(),
            source_project_name: sourceInfo.project_name,
            target_project_name: targetInfo.project_name,
            source_project_id: sourceInfo.id,
            target_project_id: targetInfo.id,
            reason: `Transfer ${numAmount} From ${sourceInfo.project_name} To ${targetInfo.project_name}`,
            amount: numAmount,
            currency: currencyId,
            currency_symbol: activeCurrency.symbol
        };

        setData('data', [...data.data, newItem]);
        setItemIdCounter(prev => prev + 1);
        
        toast.success(`Added ${activeCurrency.symbol}${numAmount}`);
        setAmount('');
        setSourceProject('');
        setTargetProject('');
        setSourceBalance(0);
        setTargetBalance(0);
    };

    const handleGeniusFix = async () => {
        if (!currencyId) {
            toast.error('Select currency first');
            return;
        }
        
        toast.info('Calculating Genius Fix...');
        setData('data', []);

        try {
            const res = await axios.post(route('admin.project.current_timer'), {
                user: user.id,
                currency: currencyId
            });

            if (res.data.status) {
                const timers = res.data.timers;
                let sumPos = 0, sumNeg = 0;
                
                timers.forEach((t: any) => {
                    if (t.timer > 0) sumPos += t.timer;
                    if (t.timer < 0) sumNeg += t.timer;
                });

                if (sumPos + sumNeg < 0) {
                    toast.warning('Negative amount is larger than positive amount overall. Cannot fix.');
                    return;
                }

                if (timers.length === 0 || sumNeg === 0) {
                    toast.info('Nothing needs to be fixed.');
                    return;
                }

                const newItems: TransferItem[] = [];
                let currentId = itemIdCounter;

                timers.forEach((tim: any) => {
                    if (tim.timer < 0) {
                        const negAmount = Math.abs(tim.timer);

                        if (negAmount < 2) {
                            for (const timS of timers) {
                                if (timS.timer > negAmount) {
                                    const sourceInfo = activeProjects.find(p => p.id === timS.id);
                                    const targetInfo = activeProjects.find(p => p.id === tim.id);
                                    if (sourceInfo && targetInfo) {
                                        newItems.push({
                                            id: currentId++,
                                            start_date: new Date(),
                                            end_date: new Date(),
                                            source_project_name: sourceInfo.project_name,
                                            target_project_name: targetInfo.project_name,
                                            source_project_id: sourceInfo.id,
                                            target_project_id: targetInfo.id,
                                            reason: `Transfer ${negAmount} From ${sourceInfo.project_name} To ${targetInfo.project_name}`,
                                            amount: negAmount,
                                            currency: currencyId,
                                            currency_symbol: activeCurrency.symbol
                                        });
                                    }
                                    break;
                                }
                            }
                        } else {
                            timers.forEach((timS: any) => {
                                if (timS.timer > 0) {
                                    const sourceOfAllPercentage = timS.timer * 100 / sumPos;
                                    const amount = Math.floor(Math.abs(tim.timer * sourceOfAllPercentage / 100) * 1000) / 1000;

                                    if (amount > 0) {
                                        const sourceInfo = activeProjects.find(p => p.id === timS.id);
                                        const targetInfo = activeProjects.find(p => p.id === tim.id);
                                        if (sourceInfo && targetInfo) {
                                            newItems.push({
                                                id: currentId++,
                                                start_date: new Date(),
                                                end_date: new Date(),
                                                source_project_name: sourceInfo.project_name,
                                                target_project_name: targetInfo.project_name,
                                                source_project_id: sourceInfo.id,
                                                target_project_id: targetInfo.id,
                                                reason: `Transfer ${amount} From ${sourceInfo.project_name} To ${targetInfo.project_name}`,
                                                amount: amount,
                                                currency: currencyId,
                                                currency_symbol: activeCurrency.symbol
                                            });
                                        }
                                    }
                                }
                            });
                        }
                    }
                });

                setData('data', newItems);
                setItemIdCounter(currentId);
                toast.success('Genius Fix computed! Review and save.');
            }
        } catch (e) {
            console.error(e);
            toast.error('Failed to calculate Genius Fix');
        }
    };

    const removeRow = (index: number) => {
        const newData = [...data.data];
        newData.splice(index, 1);
        setData('data', newData);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (data.data.length === 0) {
            toast.warning('Nothing to save.');
            return;
        }
        
        post(route('admin.transactions.start_transfer'), {
            onSuccess: () => {
                reset('data');
                setSourceProject('');
                setTargetProject('');
                setSourceBalance(0);
                setTargetBalance(0);
                setAmount('');
            }
        });
    };

    return (
        <AdminSidebarLayout title={__('general.swap_projects_budget')} header={__('general.swap_projects_budget')} user={auth?.user}>
            <Head title={`Swap Budget: ${user.name}`} />

            <div className="w-full max-w-6xl mx-auto py-6 space-y-6">
                <header className="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">{__('general.swap_projects_budget')}</h1>
                        <p className="text-muted-foreground mt-1">{user.name}</p>
                    </div>
                    <div>
                        <Button variant="outline" onClick={() => window.history.back()}>
                            <ArrowDownLeft className="h-4 w-4 mr-2" style={{ transform: 'rotate(45deg)' }} /> Back
                        </Button>
                    </div>
                </header>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Entry Form */}
                    <Card className="lg:col-span-1">
                        <CardHeader>
                            <CardTitle>Transfer Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label>Currency</Label>
                                <Select value={String(currencyId)} onValueChange={(v) => setCurrencyId(Number(v))}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Currency" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {currencies.map(c => (
                                            <SelectItem key={c.id} value={String(c.id)}>{c.currency}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <div className="flex justify-between items-center">
                                    <Label>Source Project</Label>
                                    <span className={`text-xs font-semibold ${sourceBalance >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                        Avail: {formatCurrency(sourceBalance, activeCurrency.currency)}
                                    </span>
                                </div>
                                <Select value={String(sourceProject)} onValueChange={(v) => setSourceProject(Number(v))}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Source Project" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {activeProjects.map(p => (
                                            <SelectItem key={p.id} value={String(p.id)}>{p.project_name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <div className="flex justify-between items-center">
                                    <Label>Target Project</Label>
                                    <span className={`text-xs font-semibold ${targetBalance >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                        Cur: {formatCurrency(targetBalance, activeCurrency.currency)}
                                    </span>
                                </div>
                                <Select value={String(targetProject)} onValueChange={(v) => setTargetProject(Number(v))}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Target Project" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {activeProjects.map(p => (
                                            <SelectItem key={p.id} value={String(p.id)}>{p.project_name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label>Amount</Label>
                                <div className="flex items-center gap-2">
                                    <Input 
                                        type="number" 
                                        min="0" 
                                        max={maxAmount}
                                        value={amount} 
                                        onChange={(e) => {
                                            let v = Number(e.target.value);
                                            if (maxAmount > 0 && v > maxAmount) v = maxAmount;
                                            setAmount(v || '');
                                        }} 
                                        className={maxAmount > 0 && Number(amount) > maxAmount ? 'border-red-500' : ''}
                                    />
                                    <Button variant="secondary" onClick={() => setShowExchange(!showExchange)}>
                                        <Repeat className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>

                            {showExchange && (
                                <div className="p-3 bg-muted rounded-md space-y-3">
                                    <div className="space-y-2">
                                        <Label className="text-xs">From Currency</Label>
                                        <Select value={String(fromCurrency)} onValueChange={(v) => {
                                            setFromCurrency(Number(v));
                                            handleExchangeCalc(Number(fromAmount), Number(v));
                                        }}>
                                            <SelectTrigger className="h-8">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {currencies.filter(c => c.id !== currencyId).map(c => (
                                                    <SelectItem key={c.id} value={String(c.id)}>{c.currency}</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-xs">Amount ({fromCurrencyObj.symbol})</Label>
                                        <Input 
                                            type="number" 
                                            className="h-8"
                                            value={fromAmount} 
                                            onChange={(e) => {
                                                setFromAmount(Number(e.target.value));
                                                handleExchangeCalc(Number(e.target.value), fromCurrency);
                                            }} 
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-xs">Yield ({activeCurrency.symbol})</Label>
                                        <div className="flex gap-2">
                                            <Input disabled value={toAmount} className="h-8" />
                                            <Button size="sm" onClick={applyExchange}>Apply</Button>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="pt-4 flex flex-col gap-2">
                                <Button className="w-full" onClick={handleAdd}>
                                    Add Transfer
                                </Button>
                                <Button variant="outline" className="w-full text-yellow-600 border-yellow-200 bg-yellow-50 hover:bg-yellow-100" onClick={handleGeniusFix}>
                                    <Lightbulb className="h-4 w-4 mr-2" />
                                    Genius Fix
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Transfer List */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle>Prepared Transfers</CardTitle>
                                {data.data.length > 0 && (
                                    <Button onClick={handleSubmit} disabled={processing} variant="default" className="bg-green-600 hover:bg-green-700">
                                        <Save className="h-4 w-4 mr-2" />
                                        Save All Transfers
                                    </Button>
                                )}
                            </div>
                            <CardDescription>Review items before saving.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {data.data.length === 0 ? (
                                <div className="text-center py-12 text-muted-foreground border border-dashed rounded-lg">
                                    <ArrowRightLeft className="h-8 w-8 mx-auto mb-3 opacity-20" />
                                    <p>No transfers added yet.</p>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm text-left">
                                        <thead className="text-xs text-muted-foreground uppercase bg-muted/50">
                                            <tr>
                                                <th className="px-4 py-3 rounded-tl-lg">Source</th>
                                                <th className="px-4 py-3">Target</th>
                                                <th className="px-4 py-3">Amount</th>
                                                <th className="px-4 py-3 rounded-tr-lg w-[50px]"></th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y">
                                            {data.data.map((item, idx) => (
                                                <tr key={item.id} className="hover:bg-muted/50 transition-colors">
                                                    <td className="px-4 py-3 font-medium">{item.source_project_name}</td>
                                                    <td className="px-4 py-3 font-medium">{item.target_project_name}</td>
                                                    <td className="px-4 py-3">
                                                        <CurrencyDisplay amount={item.amount} currency={{ currency: activeCurrency.currency, symbol: item.currency_symbol }} />
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <Button variant="ghost" size="icon" className="text-red-500 hover:text-red-700 hover:bg-red-50" onClick={() => removeRow(idx)}>
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </td>
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
        </AdminSidebarLayout>
    );
}
