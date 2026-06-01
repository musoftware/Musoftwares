import React, { useState, useEffect } from 'react';
import { useForm, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import { Plus, Trash2, Calculator, ArrowRightLeft, Percent, Layers, MinusCircle } from 'lucide-react';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';

interface Project {
    id: number;
    project_name: string;
}

interface Currency {
    id: number;
    currency: string;
    symbol: string;
}

interface Props {
    user: any;
    selectedProject: any;
    activeProjects: Project[];
    type: string;
    currencies: Currency[];
    businessCurrency: Currency;
    exchanges: any[];
}

export default function TransactionEntryForm({ user, selectedProject, activeProjects, type, currencies, businessCurrency, exchanges }: Props) {
    const [stagedItems, setStagedItems] = useState<any[]>([]);
    
    // Entry Form State
    const [amount, setAmount] = useState<string>('');
    const [fee, setFee] = useState<string>('');
    const [reason, setReason] = useState<string>('');
    const [isUsed, setIsUsed] = useState<boolean>(false);
    
    // Exchange State
    const [showExchange, setShowExchange] = useState(false);
    const [exchangeFromCurrency, setExchangeFromCurrency] = useState<string>('');
    const [exchangeAmount, setExchangeAmount] = useState<string>('');
    
    // Project Split State
    const [showSplit, setShowSplit] = useState(false);
    const [projectSplits, setProjectSplits] = useState<{ projectId: string, percentage: string }[]>([]);
    
    // Global Submission State
    const [tryPayUnpaid, setTryPayUnpaid] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        user: user.id,
        project: selectedProject?.id || '',
        type: type,
        unpaid_invoices: false,
        data: [] as any[]
    });

    useEffect(() => {
        setData('type', type);
    }, [type]);

    const feeSources = [
        { id: 'upwork', label: 'Upwork', icon: 'fas fa-briefcase', color: '#14a800', hint: '20% Platform Fee', fee_rate: 0.20, fee_text: '20%' },
        { id: 'paypal', label: 'Paypal', icon: 'fab fa-paypal', color: '#003087', hint: 'Transaction Fee', fee_rate: 0.05, fee_text: '5%' },
        { id: 'gumroad', label: 'Gumroad', icon: 'fas fa-store', color: '#ff90e8', hint: 'Platform + Trans.', fee_rate: 0.135, fee_text: '13.5%' },
        { id: 'wallet', label: 'Wallet', icon: 'fas fa-wallet', color: '#dc3545', hint: 'Internal Transfer', fee_rate: 0.01, fee_text: '1%' },
        { id: 'custom', label: 'Custom', icon: 'fas fa-edit', color: '#6c757d', hint: 'Manual Entry', fee_rate: 0, fee_text: 'Manual' }
    ];

    const [selectedFeeSource, setSelectedFeeSource] = useState<string>('custom');

    // Handle Fee calculations
    const handleAmountChange = (val: string) => {
        setAmount(val);
        const numAmount = parseFloat(val);
        if (!isNaN(numAmount) && numAmount > 0) {
            const source = feeSources.find(s => s.id === selectedFeeSource);
            if (source && source.fee_rate > 0) {
                setFee((numAmount * source.fee_rate).toFixed(2));
            }
        }
    };

    const handleFeeSourceChange = (sourceId: string) => {
        setSelectedFeeSource(sourceId);
        const source = feeSources.find(s => s.id === sourceId);
        const numAmount = parseFloat(amount);
        if (source && !isNaN(numAmount) && numAmount > 0) {
            setFee((numAmount * source.fee_rate).toFixed(2));
        } else if (sourceId === 'custom') {
            setFee('');
        }
    };

    const subtractFeeFromAmount = () => {
        const numAmount = parseFloat(amount);
        const numFee = parseFloat(fee);
        if (!isNaN(numAmount) && !isNaN(numFee) && numAmount > 0 && numFee > 0) {
            setAmount((numAmount - numFee).toFixed(2));
            // When subtracting fee from amount, usually the fee itself also needs to be recalculated based on new amount,
            // or the fee is zeroed. In the old app, subtracting fee from amount just sets amount = amount - fee.
            // And then re-triggers the input event.
            const newAmount = numAmount - numFee;
            const source = feeSources.find(s => s.id === selectedFeeSource);
            if (source && source.fee_rate > 0) {
                setFee((newAmount * source.fee_rate).toFixed(2));
            }
        }
    };

    // Calculate Exchange
    const applyExchange = () => {
        const targetCurrencyId = user.currency_id || businessCurrency.id;
        const ex = exchanges.find(e => e.currency1.toString() === exchangeFromCurrency && e.currency2.toString() === targetCurrencyId.toString());
        // Fallback or complex logic here if needed, for now just simple conversion
        if (ex && exchangeAmount) {
            const num = parseFloat(exchangeAmount);
            if (!isNaN(num)) {
                setAmount((num * ex.rate).toFixed(2));
                setShowExchange(false);
            }
        }
    };

    const addItem = () => {
        if (!amount || parseFloat(amount) <= 0) return;
        
        // If splitting projects
        if (!selectedProject && projectSplits.length > 0) {
            const newItems = projectSplits.map(split => {
                const perc = parseFloat(split.percentage) / 100;
                return {
                    amount: (parseFloat(amount) * perc).toFixed(2),
                    fee: fee ? (parseFloat(fee) * perc).toFixed(2) : 0,
                    reason: reason,
                    is_used: isUsed ? 1 : 0,
                    project: split.projectId
                };
            });
            setStagedItems([...stagedItems, ...newItems]);
        } else {
            setStagedItems([...stagedItems, {
                amount: parseFloat(amount),
                fee: fee ? parseFloat(fee) : 0,
                reason: reason,
                is_used: isUsed ? 1 : 0,
                project: selectedProject?.id || null
            }]);
        }
        
        // Reset form
        setAmount('');
        setFee('');
        setReason('');
    };

    const removeItem = (index: number) => {
        setStagedItems(stagedItems.filter((_, i) => i !== index));
    };

    const submitTransactions = () => {
        data.data = stagedItems;
        data.unpaid_invoices = tryPayUnpaid;
        post(route('admin.transactions.store'), {
            onSuccess: () => {
                setStagedItems([]);
            }
        });
    };

    const netAmount = (parseFloat(amount || '0') - parseFloat(fee || '0')).toFixed(2);

    return (
        <div className="space-y-6">
            {/* Currency Exchange Sub-panel */}
            {showExchange && (
                <Card className="border-primary/20 bg-primary/5 shadow-none transition-all">
                    <CardContent className="p-4">
                        <h6 className="font-bold mb-3 text-primary flex items-center">
                            <ArrowRightLeft className="mr-2 h-4 w-4" />{__('general.currency_exchange')}</h6>
                        <div className="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                            <div className="md:col-span-5 space-y-1">
                                <Label className="text-xs text-muted-foreground uppercase tracking-wider font-bold">{__('general.from_currency')}</Label>
                                <Select value={exchangeFromCurrency} onValueChange={setExchangeFromCurrency}>
                                    <SelectTrigger className="bg-white">
                                        <SelectValue placeholder={__('general.select_currency')}>
                                            {exchangeFromCurrency ? currencies.find(c => c.id.toString() === exchangeFromCurrency)?.currency : "Select currency..."}
                                        </SelectValue>
                                    </SelectTrigger>
                                    <SelectContent>
                                        {currencies.map(c => (
                                            <SelectItem key={c.id} value={c.id.toString()}>{c.currency}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="md:col-span-5 space-y-1">
                                <Label className="text-xs text-muted-foreground uppercase tracking-wider font-bold">
                                    Amount {exchangeFromCurrency && `(${currencies.find(c => c.id.toString() === exchangeFromCurrency)?.symbol})`}
                                </Label>
                                <Input type="number" className="bg-white" value={exchangeAmount} onChange={e => setExchangeAmount(e.target.value)} onKeyDown={e => e.key === 'Enter' && applyExchange()} placeholder="0.00" />
                            </div>
                            <div className="md:col-span-2 text-right">
                                <Button type="button" onClick={applyExchange} className="w-full">
                                    Apply <Plus className="h-4 w-4 ml-1" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            )}

            <Card className="border shadow-sm">
                <CardHeader className="bg-muted/30 border-b pb-4">
                    <CardTitle className="text-lg">{__('general.entry_details')}</CardTitle>
                </CardHeader>
                <CardContent className="p-4 space-y-6">
                    
                    {/* Fee Source Row */}
                    <div className="space-y-3">
                        <Label className="text-xs uppercase tracking-wider text-muted-foreground font-bold">{__('general.payment_source_fee')}</Label>
                        <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
                            {feeSources.map(s => (
                                <div 
                                    key={s.id}
                                    onClick={() => handleFeeSourceChange(s.id)}
                                    className={`
                                        cursor-pointer border rounded-xl p-3 text-center transition-all hover:-translate-y-1 hover:shadow-sm
                                        ${selectedFeeSource === s.id ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'bg-white hover:border-primary/50'}
                                    `}
                                >
                                    <div className="mb-2 h-8 flex items-center justify-center">
                                        {/* Fallback to simple colored dot if icons are missing, or standard Lucide icons */}
                                        <div className="w-6 h-6 rounded-full flex items-center justify-center text-white" style={{ backgroundColor: s.color }}>
                                            <span className="text-xs font-bold">{s.label[0]}</span>
                                        </div>
                                    </div>
                                    <h6 className="font-bold text-sm text-foreground mb-0">{s.label}</h6>
                                    <span className="text-[10px] text-muted-foreground block">{s.hint}</span>
                                    <span className="text-xs font-bold text-destructive block mt-1">{s.fee_text}</span>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Amount & Fee */}
                    <div className="bg-muted/30 rounded-xl p-4 space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                            <div className="md:col-span-5 space-y-1">
                                <Label className="text-xs uppercase tracking-wider text-muted-foreground font-bold">Amount ({currencies.find(c => c.id === user.currency_id)?.symbol || '$'})</Label>
                                <div className="flex">
                                    <Input 
                                        type="number" 
                                        value={amount} 
                                        onChange={e => handleAmountChange(e.target.value)} 
                                        placeholder="0.00" 
                                        className="rounded-r-none font-bold text-lg h-12 bg-white"
                                    />
                                    <Button 
                                        type="button" 
                                        variant="secondary" 
                                        className="rounded-l-none h-12 px-3 border-l-0"
                                        onClick={() => setShowExchange(!showExchange)}
                                        title={__('general.currency_exchange')}
                                    >
                                        <ArrowRightLeft className="h-4 w-4 text-muted-foreground" />
                                    </Button>
                                </div>
                            </div>
                            
                            <div className="hidden md:flex md:col-span-1 items-center justify-center pb-3">
                                <div className="h-px w-4 bg-muted-foreground/30"></div>
                            </div>

                            <div className="md:col-span-6">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-1">
                                        <Label className="text-xs uppercase tracking-wider text-muted-foreground font-bold">Fee</Label>
                                        <Input 
                                            type="number" 
                                            value={fee} 
                                            onChange={e => setFee(e.target.value)} 
                                            readOnly={selectedFeeSource !== 'custom'}
                                            placeholder="0.00" 
                                            className={`h-12 text-destructive font-medium ${selectedFeeSource !== 'custom' ? 'bg-muted/50 cursor-not-allowed' : 'bg-white'}`}
                                        />
                                    </div>
                                    <div className="space-y-1">
                                        <Label className="text-xs uppercase tracking-wider text-muted-foreground font-bold">Net ({currencies.find(c => c.id === user.currency_id)?.symbol || '$'})</Label>
                                        <Input 
                                            type="text" 
                                            value={netAmount} 
                                            readOnly 
                                            className="h-12 bg-white text-success font-bold"
                                        />
                                    </div>
                                </div>
                                {selectedFeeSource !== 'custom' && fee && parseFloat(fee) > 0 && (
                                    <div className="mt-2 text-right">
                                        <button 
                                            type="button" 
                                            onClick={subtractFeeFromAmount}
                                            className="text-xs text-destructive hover:underline font-medium flex items-center justify-end w-full"
                                        >
                                            <MinusCircle className="h-3 w-3 mr-1" />{__('general.subtract_fee_from_amount')}</button>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Reason */}
                    <div className="space-y-1">
                        <Label>{__('general.reason_description')}</Label>
                        <Input 
                            value={reason} 
                            onChange={e => setReason(e.target.value)} 
                            placeholder={__('general.description_of_the_transaction')} 
                            onKeyDown={e => e.key === 'Enter' && addItem()}
                        />
                    </div>

                    {/* Project Splitter (Only if no specific project is selected initially) */}
                    {!selectedProject && activeProjects.length > 0 && (
                        <div className="border rounded-lg p-4 bg-muted/20 space-y-3">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Layers className="h-4 w-4 text-muted-foreground" />
                                    <Label className="font-semibold">{__('general.split_across_projects')}</Label>
                                </div>
                                <Button type="button" variant="ghost" size="sm" onClick={() => setProjectSplits([...projectSplits, { projectId: '', percentage: '100' }])}>
                                    <Plus className="h-3 w-3 mr-1" />{__('general.add_split')}</Button>
                            </div>
                            {projectSplits.length > 0 && (
                                <div className="space-y-2 pt-2 border-t">
                                    {projectSplits.map((split, idx) => (
                                        <div key={idx} className="flex items-center gap-2">
                                            <Select value={split.projectId} onValueChange={(val) => {
                                                const newS = [...projectSplits];
                                                newS[idx].projectId = val;
                                                setProjectSplits(newS);
                                            }}>
                                                <SelectTrigger className="flex-1">
                                                    <SelectValue placeholder={__('general.select_project')} />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {activeProjects.map(p => (
                                                        <SelectItem key={p.id} value={p.id.toString()}>{p.project_name}</SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <div className="w-24 relative">
                                                <Input 
                                                    type="number" 
                                                    value={split.percentage} 
                                                    onChange={e => {
                                                        const newS = [...projectSplits];
                                                        newS[idx].percentage = e.target.value;
                                                        setProjectSplits(newS);
                                                    }}
                                                />
                                                <Percent className="h-3 w-3 absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                                            </div>
                                            <Button type="button" variant="ghost" size="icon" className="text-destructive h-10 w-10 shrink-0" onClick={() => setProjectSplits(projectSplits.filter((_, i) => i !== idx))}>
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ))}
                                    <div className="text-right">
                                        <Button type="button" variant="link" size="sm" className="text-xs text-muted-foreground" onClick={() => {
                                            const p = (100 / projectSplits.length).toFixed(1);
                                            setProjectSplits(projectSplits.map(s => ({ ...s, percentage: p })));
                                        }}>{__('general.split_evenly')}</Button>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Receive-specific options */}
                    {type === 'timer-received' || type === 'receive' ? (
                        <div className="flex items-center gap-2">
                            <Switch id="isUsed" checked={isUsed} onCheckedChange={(c: boolean) => setIsUsed(c)} />
                            <label htmlFor="isUsed" className="text-sm text-muted-foreground cursor-pointer user-select-none">{__('general.mark_as_used_money')}<span className="text-xs opacity-70">(Funds already spent/utilized)</span>
                            </label>
                        </div>
                    ) : null}

                    <Button type="button" className="w-full" onClick={addItem} disabled={!amount || parseFloat(amount) <= 0}>
                        <Plus className="h-4 w-4 mr-2" />{__('general.add_to_list')}</Button>
                </CardContent>
            </Card>

            {/* Staging List */}
            {stagedItems.length > 0 && (
                <Card className="border shadow-sm bg-muted/10">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left">
                                <thead className="bg-muted/50 text-xs uppercase text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-3 font-semibold">Reason</th>
                                        {projectSplits.length > 0 && <th className="px-4 py-3 font-semibold">Project</th>}
                                        <th className="px-4 py-3 font-semibold text-right">Gross</th>
                                        <th className="px-4 py-3 font-semibold text-right">Fee</th>
                                        <th className="px-4 py-3 font-semibold text-right">Net</th>
                                        <th className="px-4 py-3 font-semibold text-right w-16"></th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y bg-background">
                                    {stagedItems.map((item, idx) => (
                                        <tr key={idx} className="hover:bg-muted/20">
                                            <td className="px-4 py-3 font-medium">{item.reason || '-'}</td>
                                            {projectSplits.length > 0 && (
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {activeProjects.find(p => p.id.toString() === item.project?.toString())?.project_name || '-'}
                                                </td>
                                            )}
                                            <td className="px-4 py-3 text-right font-semibold">
                                                <CurrencyDisplay amount={item.amount} currency={currencies.find(c => c.id === user.currency_id)} />
                                            </td>
                                            <td className="px-4 py-3 text-right text-destructive">
                                                {item.fee > 0 ? <CurrencyDisplay amount={item.fee} currency={currencies.find(c => c.id === user.currency_id)} /> : '-'}
                                            </td>
                                            <td className="px-4 py-3 text-right text-success font-bold">
                                                <CurrencyDisplay amount={item.amount - item.fee} currency={currencies.find(c => c.id === user.currency_id)} />
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <Button type="button" variant="ghost" size="icon" className="text-destructive h-8 w-8 hover:bg-destructive/10" onClick={() => removeItem(idx)}>
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Submission Section */}
            {stagedItems.length > 0 && (
                <div className="flex flex-col md:flex-row items-center justify-between pt-4 border-t gap-4">
                    <div className="flex items-center gap-2">
                        {(type === 'timer-received' || type === 'receive' || type === 'timer-due' || type === 'charge') && (
                            <label className="flex items-center gap-2 cursor-pointer">
                                <Switch checked={tryPayUnpaid} onCheckedChange={setTryPayUnpaid} />
                                <span className="text-sm font-medium">{__('general.try_pay_unpaid_invoices_if_user_has_balance')}</span>
                            </label>
                        )}
                    </div>
                    <Button onClick={submitTransactions} disabled={processing} size="lg" className="w-full md:w-auto min-w-[200px]">
                        {processing ? 'Processing...' : `Save ${stagedItems.length} Transaction(s)`}
                    </Button>
                </div>
            )}
        </div>
    );
}
