import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { __ } from '@/lib/i18n';
import { Sparkles, Loader2, FileText, Plus, Trash2, Send } from 'lucide-react';
import axios from 'axios';

export default function ContractModal({ isOpen, onClose, project, contract, currencies }) {
    const [isLoading, setIsLoading] = useState(false);
    const [isGenerating, setIsGenerating] = useState(false);
    
    const [formData, setFormData] = useState({
        description: '',
        payment_terms: '',
        terms: '',
        notes: '',
        duration: '',
        valid_until: '',
        total_amount: '',
        currency_id: project.client?.currency_id || '',
        key_features: [] as string[],
        pricing_items: [] as any[],
        status: 'draft',
    });

    const [aiPrompt, setAiPrompt] = useState('');
    const [activeTab, setActiveTab] = useState('details'); // details, scope, pricing

    useEffect(() => {
        if (contract) {
            setFormData({
                description: contract.description || '',
                payment_terms: contract.payment_terms || '',
                terms: contract.content?.terms || '',
                notes: contract.content?.notes || '',
                duration: contract.content?.duration || '',
                valid_until: contract.valid_until ? contract.valid_until.split('T')[0] : '',
                total_amount: contract.total_amount || '',
                currency_id: contract.currency_id || '',
                key_features: contract.content?.key_features || [],
                pricing_items: contract.content?.pricing_items || [],
                status: contract.status || 'draft',
            });
        } else {
            setFormData({
                description: '',
                payment_terms: '',
                terms: '',
                notes: '',
                duration: '',
                valid_until: '',
                total_amount: '',
                currency_id: project.client?.currency_id || '',
                key_features: [],
                pricing_items: [],
                status: 'draft',
            });
            setAiPrompt('');
        }
        setActiveTab('details');
    }, [contract, isOpen]);

    const handleGenerateAi = async () => {
        if (!aiPrompt) return alert('Please enter requirements for the AI to generate the contract.');
        setIsGenerating(true);
        try {
            const res = await axios.post('/contracts/ai/generate', {
                project_id: project.id,
                prompt: aiPrompt,
            });
            
            const data = res.data;
            // The AI should return structured data. Merge it into form data.
            setFormData(prev => ({
                ...prev,
                description: data.description || prev.description,
                payment_terms: data.payment_terms || prev.payment_terms,
                terms: data.terms || prev.terms,
                notes: data.notes || prev.notes,
                duration: data.duration || prev.duration,
                key_features: data.key_features || prev.key_features,
                pricing_items: data.pricing_items || prev.pricing_items,
                total_amount: data.total_amount || prev.total_amount,
            }));
            
            setActiveTab('scope');
        } catch (error) {
            console.error('AI Generation Failed:', error);
            alert('Failed to generate contract from AI. Check the console for errors.');
        } finally {
            setIsGenerating(false);
        }
    };

    const handleGenerateInvoice = () => {
        const title = prompt("Enter milestone title:");
        if (!title) return;
        const amount = prompt("Enter amount for this milestone:");
        if (!amount || isNaN(Number(amount))) return alert("Invalid amount");
        
        router.post(route('projects.contracts.invoice', { project: project.id, contract: contract.id }), {
            title,
            amount,
            description: `Milestone for ${project.project_name}`
        }, {
            onSuccess: () => {
                alert("Invoice generated successfully");
            }
        });
    };

    const onSubmit = (e) => {
        e.preventDefault();
        setIsLoading(true);
        
        const routeName = contract 
            ? 'projects.contracts.update' 
            : 'projects.contracts.store';
        
        const params = contract 
            ? { project: project.id, contract: contract.id }
            : { project: project.id };

        const action = contract ? router.put : router.post;

        action(route(routeName, params), formData, {
            onSuccess: () => {
                setIsLoading(false);
                onClose();
            },
            onError: () => {
                setIsLoading(false);
            }
        });
    };

    const addFeature = () => setFormData(p => ({ ...p, key_features: [...p.key_features, ''] }));
    const updateFeature = (i, val) => setFormData(p => {
        const nf = [...p.key_features];
        nf[i] = val;
        return { ...p, key_features: nf };
    });
    const removeFeature = (i) => setFormData(p => ({
        ...p, key_features: p.key_features.filter((_, idx) => idx !== i)
    }));

    const addPricing = () => setFormData(p => ({ 
        ...p, 
        pricing_items: [...p.pricing_items, { item: '', description: '', hours: 0, hourly_rate: 0, total: 0 }] 
    }));
    const updatePricing = (i, field, val) => setFormData(p => {
        const np = [...p.pricing_items];
        np[i][field] = val;
        if (field === 'hours' || field === 'hourly_rate') {
            np[i].total = (np[i].hours || 0) * (np[i].hourly_rate || 0);
        }
        return { ...p, pricing_items: np };
    });
    const removePricing = (i) => setFormData(p => ({
        ...p, pricing_items: p.pricing_items.filter((_, idx) => idx !== i)
    }));

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{contract ? 'Edit Contract / Proposal' : 'Create New Contract / Proposal'}</DialogTitle>
                </DialogHeader>
                
                <div className="flex border-b mb-4">
                    <button 
                        className={`px-4 py-2 font-medium text-sm ${activeTab === 'details' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-slate-500'}`}
                        onClick={() => setActiveTab('details')}
                    >
                        {__('general.general_details')}</button>
                    <button 
                        className={`px-4 py-2 font-medium text-sm ${activeTab === 'scope' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-slate-500'}`}
                        onClick={() => setActiveTab('scope')}
                    >
                        {__('general.scope_features')}</button>
                    <button 
                        className={`px-4 py-2 font-medium text-sm ${activeTab === 'pricing' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-slate-500'}`}
                        onClick={() => setActiveTab('pricing')}
                    >
                        {__('general.pricing_milestones')}</button>
                    {contract && (
                        <button 
                            className={`px-4 py-2 font-medium text-sm ${activeTab === 'ai' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-slate-500'} ms-auto flex items-center gap-1`}
                            onClick={() => setActiveTab('ai')}
                        >
                            <Sparkles className="w-3 h-3" /> {__('general.ai_assistant')}</button>
                    )}
                </div>

                {!contract && activeTab === 'details' && (
                    <div className="bg-indigo-50 p-4 rounded-lg mb-6 border border-indigo-100 flex flex-col gap-3">
                        <div className="flex items-center gap-2 text-indigo-800 font-semibold">
                            <Sparkles className="w-5 h-5" /> Generate with AI (Recommended)
                        </div>
                        <Textarea 
                            placeholder={__('general.describe_the_project_requirements_featur')} 
                            value={aiPrompt}
                            onChange={(e) => setAiPrompt(e.target.value)}
                            className="bg-white border-indigo-200"
                            rows={3}
                        />
                        <Button 
                            onClick={handleGenerateAi} 
                            disabled={isGenerating} 
                            className="self-end bg-indigo-600 hover:bg-indigo-700 text-white"
                        >
                            {isGenerating ? <><Loader2 className="w-4 h-4 me-2 animate-spin" /> {__('general.generating')}</> : 'Generate Contract'}
                        </Button>
                    </div>
                )}

                <form id="contract-form" onSubmit={onSubmit} className="space-y-6">
                    {activeTab === 'details' && (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="md:col-span-2">
                                <Label>Project Description / Objective</Label>
                                <Textarea 
                                    value={formData.description} 
                                    onChange={e => setFormData({...formData, description: e.target.value})}
                                    rows={4}
                                    placeholder={__('general.executive_summary_of_the_project')}
                                />
                            </div>
                            <div>
                                <Label>{__('general.currency')}</Label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    value={formData.currency_id}
                                    onChange={e => setFormData({...formData, currency_id: e.target.value})}
                                    required
                                >
                                    <option value="">{__('general.select_currency')}</option>
                                    {currencies.map(c => (
                                        <option key={c.id} value={c.id}>{c.currency} ({c.symbol})</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label>{__('general.total_amount')}</Label>
                                <Input 
                                    type="number" 
                                    step="0.01" 
                                    value={formData.total_amount} 
                                    onChange={e => setFormData({...formData, total_amount: e.target.value})}
                                    required
                                />
                            </div>
                            <div>
                                <Label>Duration (e.g., 3 Months)</Label>
                                <Input 
                                    value={formData.duration} 
                                    onChange={e => setFormData({...formData, duration: e.target.value})}
                                />
                            </div>
                            <div>
                                <Label>{__('general.valid_until')}</Label>
                                <Input 
                                    type="date" 
                                    value={formData.valid_until} 
                                    onChange={e => setFormData({...formData, valid_until: e.target.value})}
                                />
                            </div>
                            {contract && (
                                <div>
                                    <Label>{__('general.contract_status')}</Label>
                                    <select 
                                        className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm"
                                        value={formData.status}
                                        onChange={e => setFormData({...formData, status: e.target.value})}
                                    >
                                        <option value="draft">{__('general.draft')}</option>
                                        <option value="sent">{__('general.sent')}</option>
                                        <option value="active">{__('general.active')}</option>
                                        <option value="completed">{__('general.completed')}</option>
                                    </select>
                                </div>
                            )}
                        </div>
                    )}

                    {activeTab === 'scope' && (
                        <div className="space-y-4">
                            <div>
                                <div className="flex justify-between items-center mb-2">
                                    <Label className="text-base font-semibold">Key Features / Scope of Work</Label>
                                    <Button type="button" variant="outline" size="sm" onClick={addFeature}>
                                        <Plus className="w-4 h-4 me-1" /> {__('general.add_feature')}</Button>
                                </div>
                                <div className="space-y-2">
                                    {formData.key_features.map((feature, idx) => (
                                        <div key={idx} className="flex items-center gap-2">
                                            <Input 
                                                value={feature} 
                                                onChange={e => updateFeature(idx, e.target.value)} 
                                                placeholder={`Feature ${idx + 1}`}
                                            />
                                            <Button type="button" variant="ghost" size="icon" onClick={() => removeFeature(idx)} className="text-red-500 hover:bg-red-50 hover:text-red-700">
                                                <Trash2 className="w-4 h-4" />
                                            </Button>
                                        </div>
                                    ))}
                                    {formData.key_features.length === 0 && (
                                        <div className="text-center p-4 border border-dashed rounded text-slate-500 text-sm">
                                            No features added. Click "Add Feature" or use AI to generate.
                                        </div>
                                    )}
                                </div>
                            </div>
                            
                            <div className="pt-4 border-t">
                                <Label className="text-base font-semibold block mb-2">{__('general.general_terms_conditions')}</Label>
                                <Textarea 
                                    value={formData.terms} 
                                    onChange={e => setFormData({...formData, terms: e.target.value})}
                                    rows={6}
                                    placeholder={__('general.legal_terms_responsibilities_etc')}
                                />
                            </div>
                        </div>
                    )}

                    {activeTab === 'pricing' && (
                        <div className="space-y-4">
                            <div>
                                <div className="flex justify-between items-center mb-2">
                                    <Label className="text-base font-semibold">Pricing Items (Quotation)</Label>
                                    <Button type="button" variant="outline" size="sm" onClick={addPricing}>
                                        <Plus className="w-4 h-4 me-1" /> {__('general.add_item')}</Button>
                                </div>
                                <div className="space-y-3">
                                    {formData.pricing_items.map((item, idx) => (
                                        <div key={idx} className="flex items-start gap-2 bg-slate-50 p-3 rounded border">
                                            <div className="grid grid-cols-1 md:grid-cols-4 gap-2 flex-1">
                                                <div className="md:col-span-4">
                                                    <Input placeholder={__('general.item_title')} value={item.item} onChange={e => updatePricing(idx, 'item', e.target.value)} />
                                                </div>
                                                <div className="md:col-span-4">
                                                    <Input placeholder={__('general.description')} value={item.description} onChange={e => updatePricing(idx, 'description', e.target.value)} />
                                                </div>
                                                <div>
                                                    <Label className="text-xs">Hours/Qty</Label>
                                                    <Input type="number" placeholder={__('general.hours')} value={item.hours} onChange={e => updatePricing(idx, 'hours', e.target.value)} />
                                                </div>
                                                <div>
                                                    <Label className="text-xs">{__('general.rate')}</Label>
                                                    <Input type="number" placeholder={__('general.rate')} value={item.hourly_rate} onChange={e => updatePricing(idx, 'hourly_rate', e.target.value)} />
                                                </div>
                                                <div className="md:col-span-2">
                                                    <Label className="text-xs">{__('general.line_total')}</Label>
                                                    <Input type="number" disabled value={item.total} className="bg-slate-100" />
                                                </div>
                                            </div>
                                            <Button type="button" variant="ghost" size="icon" onClick={() => removePricing(idx)} className="text-red-500 mt-6">
                                                <Trash2 className="w-4 h-4" />
                                            </Button>
                                        </div>
                                    ))}
                                    {formData.pricing_items.length === 0 && (
                                        <div className="text-center p-4 border border-dashed rounded text-slate-500 text-sm">
                                            {__('general.no_pricing_items_added')}</div>
                                    )}
                                </div>
                            </div>
                            
                            <div className="pt-4 border-t">
                                <Label className="text-base font-semibold block mb-2">Payment Terms (Milestones)</Label>
                                <Textarea 
                                    value={formData.payment_terms} 
                                    onChange={e => setFormData({...formData, payment_terms: e.target.value})}
                                    rows={4}
                                    placeholder="e.g. 50% upfront, 25% after UI, 25% upon delivery..."
                                />
                            </div>

                            {contract && (
                                <div className="pt-4 border-t flex items-center justify-between">
                                    <div className="text-sm text-slate-600">
                                        {__('general.you_can_generate_invoices_dynamically_fr')}</div>
                                    <Button type="button" variant="outline" onClick={handleGenerateInvoice}>
                                        <Send className="w-4 h-4 me-2" /> {__('general.generate_invoice')}</Button>
                                </div>
                            )}
                        </div>
                    )}
                    
                    {activeTab === 'ai' && contract && (
                        <div className="bg-indigo-50 p-4 rounded-lg mb-6 border border-indigo-100 flex flex-col gap-3">
                            <div className="flex items-center gap-2 text-indigo-800 font-semibold">
                                <Sparkles className="w-5 h-5" /> {__('general.refine_with_ai')}</div>
                            <p className="text-sm text-indigo-700 mb-2">
                                Describe the changes you want to make to this contract (e.g., "Add a milestone for testing", "Change the duration to 6 months and adjust pricing accordingly"). The AI will create a new version.
                            </p>
                            <Textarea 
                                placeholder={__('general.instructions_for_ai')} 
                                value={aiPrompt}
                                onChange={(e) => setAiPrompt(e.target.value)}
                                className="bg-white border-indigo-200"
                                rows={3}
                            />
                            <Button 
                                onClick={handleGenerateAi} 
                                disabled={isGenerating} 
                                className="self-end bg-indigo-600 hover:bg-indigo-700 text-white"
                            >
                                {isGenerating ? <><Loader2 className="w-4 h-4 me-2 animate-spin" /> {__('general.processing')}</> : 'Refine Contract'}
                            </Button>
                        </div>
                    )}

                </form>

                <DialogFooter className="mt-6 border-t pt-4">
                    <Button variant="outline" onClick={onClose} type="button">{__('general.cancel')}</Button>
                    <Button type="submit" form="contract-form" disabled={isLoading || isGenerating}>
                        {isLoading ? <Loader2 className="w-4 h-4 animate-spin me-2" /> : null}
                        {contract ? 'Update Contract (Creates new version)' : 'Create Contract'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
