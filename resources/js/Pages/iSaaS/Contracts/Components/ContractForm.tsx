import React, { useState, useEffect } from 'react';
import { useForm, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Trash2, Plus, Wand2, Calculator, Search, X } from 'lucide-react';
import { Switch } from '@/Components/ui/switch';
import { toast } from 'sonner';
import { CurrencySelect } from '@/Components/CurrencySelect';
import { __ } from '@/lib/i18n';

interface PricingItem {
    item: string;
    description: string;
    type: string;
    frequency: string;
    hours: number;
    hourly_rate_egp: number;
    subtotal_egp: number;
}

interface ContractFormProps {
    contract?: any;
    currencies: Array<{ id: number; name: string; symbol: string }>;
}

export default function ContractForm({ contract, currencies }: ContractFormProps) {
    const isEdit = !!contract;
    
    const [isGenerating, setIsGenerating] = useState(false);
    const [isReviewing, setIsReviewing] = useState(false);
    const [clientSearch, setClientSearch] = useState('');
    const [clientSearchResults, setClientSearchResults] = useState<any[]>([]);

    const { data, setData, post, put, processing, errors } = useForm({
        project_name: contract?.project_name || '',
        project_description: contract?.project_description || '',
        reference: contract?.reference || `QT-${new Date().toISOString().slice(0,10).replace(/-/g,'')}-${Math.random().toString(36).substring(2,6).toUpperCase()}`,
        prepared_by: contract?.prepared_by || '',
        valid_until: contract?.valid_until ? contract.valid_until.substring(0, 10) : '',
        duration: contract?.duration || '',
        total_amount: contract?.total_amount || 0,
        deposit_amount: contract?.deposit_amount || 0,
        deposit_paid: contract?.deposit_paid || false,
        currency_id: contract?.currency_id || 2,
        status: contract?.status || 'draft',
        user_id: contract?.user_id || null,
        description: contract?.description || '',
        payment_terms: contract?.payment_terms || "20% before ( unrefund )\n30% when working and client see result\n50% after finished",
        notes: contract?.notes || '',
        terms: contract?.terms || '',
        includes_hosting: contract?.includes_hosting || false,
        hosting_duration: contract?.hosting_duration || '',
        includes_support: contract?.includes_support || true,
        support_duration: contract?.support_duration || '',
        start_date: contract?.start_date ? contract.start_date.substring(0, 10) : '',
        end_date: contract?.end_date ? contract.end_date.substring(0, 10) : '',
        project_id: contract?.project_id || '',
        client_name: contract?.client_name || '',
        lang: contract?.content?.lang || 'ar',
        features: Array.isArray(contract?.features) ? contract.features : [],
        items: Array.isArray(contract?.items) ? contract.items : [],
    });

    const [selectedClientName, setSelectedClientName] = useState(
        contract?.user ? `${contract.user.name} (${contract.user.email})` : ''
    );

    // Dynamic Arrays Logic
    const addFeature = () => {
        setData('features', [...data.features, '']);
    };

    const removeFeature = (index: number) => {
        const newFeatures = [...data.features];
        newFeatures.splice(index, 1);
        setData('features', newFeatures);
    };

    const updateFeature = (index: number, value: string) => {
        const newFeatures = [...data.features];
        newFeatures[index] = value;
        setData('features', newFeatures);
    };

    const addItem = () => {
        setData('items', [
            ...data.items,
            { item: '', description: '', type: 'labor', frequency: 'One-time', hours: 0, hourly_rate_egp: 0, subtotal_egp: 0 }
        ]);
    };

    const removeItem = (index: number) => {
        const newItems = [...data.items];
        newItems.splice(index, 1);
        setData('items', newItems);
        recalculateTotal(newItems);
    };

    const updateItem = (index: number, field: keyof PricingItem, value: any) => {
        const newItems = [...data.items];
        newItems[index] = { ...newItems[index], [field]: value };
        
        // Auto calculate subtotal if hours and rate change
        if (field === 'hours' || field === 'hourly_rate_egp') {
            const h = parseFloat(newItems[index].hours.toString() || '0');
            const r = parseFloat(newItems[index].hourly_rate_egp.toString() || '0');
            if (h > 0 && r > 0) {
                newItems[index].subtotal_egp = h * r;
            }
        }
        
        setData('items', newItems);
    };

    const recalculateTotal = (itemsToCalc = data.items) => {
        let total = 0;
        const newItems = itemsToCalc.map(item => {
            const h = parseFloat(item.hours?.toString() || '0');
            const r = parseFloat(item.hourly_rate_egp?.toString() || '0');
            let subtotal = parseFloat(item.subtotal_egp?.toString() || '0');
            
            if (h > 0 && r > 0) {
                subtotal = h * r;
                item.subtotal_egp = subtotal;
            }
            total += subtotal;
            return item;
        });

        setData(prev => ({
            ...prev,
            items: newItems,
            total_amount: total,
            deposit_amount: total * 0.20
        }));
    };

    // Client Search Logic
    useEffect(() => {
        if (clientSearch.length > 2) {
            const delayDebounceFn = setTimeout(() => {
                fetch(route('isaas.contracts.search-users', { search: clientSearch }))
                    .then(res => res.json())
                    .then(res => {
                        setClientSearchResults(res.data || res || []);
                    })
                    .catch(err => console.error("Error fetching users:", err));
            }, 300);

            return () => clearTimeout(delayDebounceFn);
        } else {
            setClientSearchResults([]);
        }
    }, [clientSearch]);

    const selectClient = (user: any) => {
        setData('user_id', user.id);
        setSelectedClientName(`${user.name} (${user.email})`);
        setClientSearch('');
        setClientSearchResults([]);
    };

    // AI Logic
    const handleAiGenerate = async () => {
        if (!data.project_name) {
            toast.error('Project Name is required to generate content.');
            return;
        }

        setIsGenerating(true);
        try {
            const response = await window.axios.post(route('isaas.contracts.ai.generate'), {
                project_name: data.project_name
            });
            
            const aiData = response.data.data;
            if (aiData) {
                const newData = { ...data };
                if (!newData.project_description && aiData.project_description) newData.project_description = aiData.project_description;
                if (!newData.description && aiData.description) newData.description = aiData.description;
                if (!newData.payment_terms && aiData.payment_terms) newData.payment_terms = aiData.payment_terms;
                if (!newData.terms && aiData.terms) newData.terms = aiData.terms;
                if (!newData.notes && aiData.notes) newData.notes = aiData.notes;
                if (!newData.duration && aiData.duration) newData.duration = aiData.duration;
                if (!newData.valid_until && aiData.valid_until) newData.valid_until = aiData.valid_until;
                if (aiData.includes_hosting !== undefined) newData.includes_hosting = aiData.includes_hosting;
                if (!newData.hosting_duration && aiData.hosting_duration) newData.hosting_duration = aiData.hosting_duration;
                if (aiData.includes_support !== undefined) newData.includes_support = aiData.includes_support;
                if (!newData.support_duration && aiData.support_duration) newData.support_duration = aiData.support_duration;
                if (!newData.client_name && aiData.client_name) newData.client_name = aiData.client_name;
                
                if (newData.features.length === 0 && aiData.key_features) newData.features = aiData.key_features;
                if (newData.items.length === 0 && aiData.pricing_items) {
                    newData.items = aiData.pricing_items.map((i: any) => ({
                        item: i.item || '',
                        description: i.description || '',
                        type: 'labor',
                        frequency: 'One-time',
                        hours: i.hours || 0,
                        hourly_rate_egp: i.hourly_rate_egp || 0,
                        subtotal_egp: (i.hours || 0) * (i.hourly_rate_egp || 0)
                    }));
                }
                
                setData(newData);
                if (newData.items.length > 0) recalculateTotal(newData.items);
                toast.success('Empty fields populated by AI.');
            }
        } catch (error: any) {
            toast.error((error as any).response?.data?.error || 'Failed to generate content with AI');
        } finally {
            setIsGenerating(false);
        }
    };

    const handleAiReview = async () => {
        if (!data.description || data.description.length < 50) {
            toast.error('Description is too short for AI review.');
            return;
        }

        setIsReviewing(true);
        try {
            const response = await window.axios.post(route('isaas.contracts.ai.review'), {
                description: data.description
            });
            
            const aiData = response.data.data;
            if (aiData && aiData.refined_content) {
                setData('description', aiData.refined_content);
                toast.success('Contract reviewed by AI.', {
                    description: `Issues: ${aiData.critical_issues?.length || 0}, Suggestions: ${aiData.suggestions?.length || 0}`
                });
            }
        } catch (error: any) {
            toast.error((error as any).response?.data?.error || 'Failed to review content with AI');
        } finally {
            setIsReviewing(false);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            put(route('isaas.contracts.update', contract.id));
        } else {
            post(route('isaas.contracts.store'));
        }
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                {/* Main Content Area */}
                <div className="md:col-span-2 space-y-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>{__('general.contract_details')}</CardTitle>
                            <Button 
                                type="button" 
                                variant="outline" 
                                size="sm" 
                                onClick={handleAiGenerate}
                                disabled={isGenerating}
                            >
                                <Wand2 className="w-4 h-4 me-2" />
                                {isGenerating ? 'Generating...' : 'AI Auto-Fill'}
                            </Button>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            
                            <div>
                                <Label htmlFor="project_name">{__('general.project_name')}<span className="text-red-500">*</span></Label>
                                <Input id="project_name" value={data.project_name} onChange={e => setData('project_name', e.target.value)} />
                                {errors.project_name && <p className="text-sm text-red-500 mt-1">{errors.project_name}</p>}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <CurrencySelect 
                                        currencies={currencies as any[]}
                                        value={data.currency_id}
                                        onChange={(val) => setData('currency_id', parseInt(val))}
                                        valueKey="id"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="total_amount">{__('general.total_amount')}</Label>
                                    <div className="flex">
                                        <Input type="number" step="0.01" id="total_amount" value={data.total_amount} onChange={e => setData('total_amount', parseFloat(e.target.value))} className="rounded-e-none" />
                                        <Button type="button" variant="outline" className="rounded-s-none" onClick={() => recalculateTotal()}>
                                            <Calculator className="w-4 h-4" />
                                        </Button>
                                    </div>
                                    {errors.total_amount && <p className="text-sm text-red-500 mt-1">{errors.total_amount}</p>}
                                </div>
                                <div>
                                    <Label htmlFor="deposit_amount">{__('general.deposit_amount')}</Label>
                                    <Input type="number" step="0.01" id="deposit_amount" value={data.deposit_amount} onChange={e => setData('deposit_amount', parseFloat(e.target.value))} />
                                </div>
                            </div>

                            <div className="flex items-center space-x-2 pt-2">
                                <Switch id="deposit_paid" checked={data.deposit_paid} onCheckedChange={c => setData('deposit_paid', c)} />
                                <Label htmlFor="deposit_paid">{__('general.deposit_paid')}</Label>
                            </div>

                            <div className="pt-4 border-t">
                                <h3 className="text-sm font-medium mb-3">Pricing Breakdown (Items)</h3>
                                
                                <div className="space-y-3">
                                    {data.items.map((item, index) => (
                                        <div key={index} className="flex flex-col md:flex-row gap-2 items-start border p-3 rounded-md bg-gray-50">
                                            <div className="flex-1 space-y-2">
                                                <Input placeholder={__('general.item_name_e_g_ui_design')} value={item.item} onChange={e => updateItem(index, 'item', e.target.value)} />
                                                <Input placeholder={__('general.short_description')} value={item.description} onChange={e => updateItem(index, 'description', e.target.value)} />
                                            </div>
                                            <div className="w-24">
                                                <Input type="number" placeholder={__('general.hours')} value={item.hours} onChange={e => updateItem(index, 'hours', parseFloat(e.target.value))} />
                                            </div>
                                            <div className="w-32 space-y-2">
                                                <Input type="number" placeholder={__('general.rate_fixed')} value={item.hourly_rate_egp} onChange={e => updateItem(index, 'hourly_rate_egp', parseFloat(e.target.value))} />
                                                <Select value={item.frequency} onValueChange={v => updateItem(index, 'frequency', v)}>
                                                    <SelectTrigger><SelectValue/></SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="One-time">{__('general.one_time')}</SelectItem>
                                                        <SelectItem value="Monthly">{__('general.monthly')}</SelectItem>
                                                        <SelectItem value="Yearly">{__('general.yearly')}</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div className="w-32">
                                                <Input type="number" placeholder={__('general.subtotal')} value={item.subtotal_egp} onChange={e => updateItem(index, 'subtotal_egp', parseFloat(e.target.value))} />
                                            </div>
                                            <Button type="button" variant="ghost" size="icon" className="text-red-500" onClick={() => removeItem(index)}>
                                                <Trash2 className="w-4 h-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                                <Button type="button" variant="outline" size="sm" className="mt-3" onClick={addItem}>
                                    <Plus className="w-4 h-4 me-2" />{__('general.add_item')}</Button>
                            </div>

                            <div className="pt-4 border-t">
                                <h3 className="text-sm font-medium mb-3">{__('general.key_features')}<span className="text-gray-400 font-normal">(Quotation Bullet Points)</span></h3>
                                <div className="space-y-2">
                                    {data.features.map((feature, index) => (
                                        <div key={index} className="flex gap-2">
                                            <Input value={feature} onChange={e => updateFeature(index, e.target.value)} />
                                            <Button type="button" variant="outline" size="icon" className="text-red-500 shrink-0" onClick={() => removeFeature(index)}>
                                                <Trash2 className="w-4 h-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                                <Button type="button" variant="outline" size="sm" className="mt-3" onClick={addFeature}>
                                    <Plus className="w-4 h-4 me-2" />{__('general.add_feature')}</Button>
                            </div>

                            <div className="pt-4 border-t">
                                <Label htmlFor="payment_terms">{__('general.payment_terms')}</Label>
                                <Textarea id="payment_terms" rows={3} value={data.payment_terms} onChange={e => setData('payment_terms', e.target.value)} className="mt-1" />
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t">
                                <div>
                                    <Label htmlFor="start_date">{__('general.start_date')}</Label>
                                    <Input type="date" id="start_date" value={data.start_date} onChange={e => setData('start_date', e.target.value)} />
                                </div>
                                <div>
                                    <Label htmlFor="end_date">{__('general.estimated_completion_date')}</Label>
                                    <Input type="date" id="end_date" value={data.end_date} onChange={e => setData('end_date', e.target.value)} />
                                </div>
                            </div>

                            <div className="pt-4 border-t">
                                <div className="flex justify-between items-center mb-2">
                                    <Label htmlFor="description">{__('general.contract_description_content')}</Label>
                                    <Button type="button" variant="secondary" size="sm" onClick={handleAiReview} disabled={isReviewing}>
                                        <Wand2 className="w-4 h-4 me-2" />
                                        {isReviewing ? 'Reviewing...' : 'AI Review & Fix'}
                                    </Button>
                                </div>
                                <Textarea id="description" rows={15} value={data.description} onChange={e => setData('description', e.target.value)} className="font-mono text-sm" />
                                {errors.description && <p className="text-sm text-red-500 mt-1">{errors.description}</p>}
                            </div>

                        </CardContent>
                    </Card>
                </div>

                {/* Sidebar Area */}
                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{__('general.client_status')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label>{__('general.status')}</Label>
                                <Select value={data.status} onValueChange={v => setData('status', v)}>
                                    <SelectTrigger className="mt-1"><SelectValue/></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft">{__('general.draft')}</SelectItem>
                                        <SelectItem value="sent">{__('general.sent_to_client')}</SelectItem>
                                        <SelectItem value="signed">{__('general.signed')}</SelectItem>
                                        <SelectItem value="active">{__('general.active')}</SelectItem>
                                        <SelectItem value="completed">{__('general.completed')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label>{__('general.contract_language')}</Label>
                                <Select value={data.lang} onValueChange={v => setData('lang', v)}>
                                    <SelectTrigger className="mt-1"><SelectValue/></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="ar">Arabic (RTL)</SelectItem>
                                        <SelectItem value="en">English (LTR)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label>Link to Project ID (Optional)</Label>
                                <Input type="number" value={data.project_id} onChange={e => setData('project_id', e.target.value)} className="mt-1" />
                            </div>

                            <div className="pt-4 border-t">
                                <Label className="mb-2 block">{__('general.assigned_client')}</Label>
                                
                                {data.user_id ? (
                                    <div className="flex items-center justify-between p-3 border rounded-md bg-gray-50">
                                        <span className="text-sm font-medium">{selectedClientName}</span>
                                        <Button type="button" variant="ghost" size="icon" onClick={() => { setData('user_id', null); setSelectedClientName(''); }}>
                                            <X className="w-4 h-4 text-red-500" />
                                        </Button>
                                    </div>
                                ) : (
                                    <div className="space-y-3">
                                        <div>
                                            <Label className="text-xs text-gray-500">{__('general.manual_client_name')}</Label>
                                            <Input value={data.client_name} onChange={e => setData('client_name', e.target.value)} placeholder={__('general.enter_guest_name')} className="mt-1" />
                                        </div>
                                        
                                        <div className="relative">
                                            <Search className="w-4 h-4 absolute start-3 top-3 text-gray-400" />
                                            <Input 
                                                className="ps-9" 
                                                placeholder={__('general.search_system_users')} 
                                                value={clientSearch}
                                                onChange={e => setClientSearch(e.target.value)}
                                            />
                                            {clientSearchResults.length > 0 && (
                                                <div className="absolute top-full mt-1 w-full bg-white border rounded-md shadow-lg z-10 max-h-48 overflow-y-auto">
                                                    {clientSearchResults.map((user) => (
                                                        <button
                                                            key={user.id}
                                                            type="button"
                                                            className="w-full text-start px-4 py-2 hover:bg-gray-100 text-sm border-b last:border-0"
                                                            onClick={() => selectClient(user)}
                                                        >
                                                            <div className="font-medium">{user.name}</div>
                                                            <div className="text-xs text-gray-500">{user.email}</div>
                                                        </button>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{__('general.quotation_info')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label>{__('general.quotation_reference')}</Label>
                                <Input value={data.reference} onChange={e => setData('reference', e.target.value)} className="mt-1" />
                            </div>
                            <div>
                                <Label>{__('general.prepared_by')}</Label>
                                <Input value={data.prepared_by} onChange={e => setData('prepared_by', e.target.value)} className="mt-1" />
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label>{__('general.valid_until')}</Label>
                                    <Input type="date" value={data.valid_until} onChange={e => setData('valid_until', e.target.value)} className="mt-1" />
                                </div>
                                <div>
                                    <Label>{__('general.duration')}</Label>
                                    <Input placeholder={__('general.e_g_15_days')} value={data.duration} onChange={e => setData('duration', e.target.value)} className="mt-1" />
                                </div>
                            </div>
                            
                            <div>
                                <Label>Project Description (Brief)</Label>
                                <Textarea rows={2} value={data.project_description} onChange={e => setData('project_description', e.target.value)} className="mt-1" />
                            </div>

                            <div className="space-y-3 pt-3 border-t">
                                <div className="flex items-center space-x-2">
                                    <Switch id="includes_hosting" checked={data.includes_hosting} onCheckedChange={c => setData('includes_hosting', c)} />
                                    <Label htmlFor="includes_hosting">{__('general.includes_hosting')}</Label>
                                </div>
                                {data.includes_hosting && (
                                    <Input placeholder={__('general.hosting_duration_e_g_1_year')} value={data.hosting_duration} onChange={e => setData('hosting_duration', e.target.value)} />
                                )}

                                <div className="flex items-center space-x-2">
                                    <Switch id="includes_support" checked={data.includes_support} onCheckedChange={c => setData('includes_support', c)} />
                                    <Label htmlFor="includes_support">{__('general.includes_support')}</Label>
                                </div>
                                {data.includes_support && (
                                    <Input placeholder={__('general.support_duration_e_g_3_months')} value={data.support_duration} onChange={e => setData('support_duration', e.target.value)} />
                                )}
                            </div>

                            <div className="space-y-3 pt-3 border-t">
                                <div>
                                    <Label>{__('general.extra_notes')}</Label>
                                    <Textarea rows={2} value={data.notes} onChange={e => setData('notes', e.target.value)} className="mt-1" />
                                </div>
                                <div>
                                    <Label>{__('general.special_terms')}</Label>
                                    <Textarea rows={2} value={data.terms} onChange={e => setData('terms', e.target.value)} className="mt-1" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div className="flex justify-end gap-3 sticky bottom-4 bg-white/80 backdrop-blur-md p-4 rounded-lg border shadow-sm z-10">
                <Button type="button" variant="outline" onClick={() => router.get(route('isaas.contracts.index'))}>
                    {__('general.cancel')}</Button>
                <Button type="submit" disabled={processing}>
                    {processing ? 'Saving...' : 'Save Contract'}
                </Button>
            </div>
        </form>
    );
}
