import React from 'react';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { cn } from '@/lib/utils';
import { Trash2, Plus, Info } from 'lucide-react';
import { emptyPackage } from '../Create';

const CURRENCIES = [
    { id: 1, code: 'USD' },
    { id: 2, code: 'EGP' },
    { id: 3, code: 'EUR' },
    { id: 4, code: 'GBP' },
    { id: 5, code: 'AED' },
    { id: 6, code: 'SAR' },
];
const PKG_LABELS = ['Basic', 'Standard', 'Premium'];

export default function PricingStep({ data, setData, errors }: any) {

    const setPackageField = (idx: number, field: string, value: any) => {
        const pkgs = [...data.packages];
        pkgs[idx] = { ...pkgs[idx], [field]: value };
        setData('packages', pkgs);
    };

    const addPackage = () => {
        if (data.packages.length < 3) setData('packages', [...data.packages, emptyPackage()]);
    };

    const removePackage = (idx: number) => {
        if (data.packages.length > 1) setData('packages', data.packages.filter((_: any, i: number) => i !== idx));
    };

    return (
        <div className="space-y-8">
            <div>
                <h2 className="text-2xl font-bold text-slate-900 mb-1">Scope & Pricing</h2>
                <p className="text-sm text-slate-500">Offer up to 3 packages to give buyers more choices and increase your earnings.</p>
            </div>

            <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
                {data.packages.map((pkg: any, idx: number) => (
                    <div key={idx} className={cn(
                        'bg-white rounded-2xl border shadow-sm overflow-hidden flex flex-col',
                        idx === 0 ? 'border-slate-200' :
                        idx === 1 ? 'border-indigo-200 shadow-indigo-100' : 'border-violet-200 shadow-violet-100'
                    )}>
                        <div className={cn(
                            'px-5 py-4 flex items-center justify-between border-b',
                            idx === 0 ? 'bg-slate-50/80 border-slate-100' :
                            idx === 1 ? 'bg-indigo-50/50 border-indigo-100' : 'bg-violet-50/50 border-violet-100'
                        )}>
                            <span className={cn(
                                'text-sm font-bold uppercase tracking-widest',
                                idx === 0 ? 'text-slate-600' :
                                idx === 1 ? 'text-indigo-700' : 'text-violet-700'
                            )}>
                                {PKG_LABELS[idx]}
                            </span>
                            {idx > 0 && (
                                <button type="button" onClick={() => removePackage(idx)} className="text-slate-400 hover:text-red-500 transition-colors">
                                    <Trash2 className="w-4 h-4" />
                                </button>
                            )}
                        </div>

                        <div className="p-5 space-y-5 flex-1 flex flex-col">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-bold text-slate-600 uppercase tracking-wider">Name your package</Label>
                                <Input
                                    value={pkg.name}
                                    onChange={e => setPackageField(idx, 'name', e.target.value)}
                                    placeholder={`e.g. ${PKG_LABELS[idx]} Design`}
                                    maxLength={80}
                                    className="h-10 text-sm font-medium"
                                />
                            </div>

                            <div className="space-y-1.5 flex-1">
                                <Label className="text-xs font-bold text-slate-600 uppercase tracking-wider">Description</Label>
                                <textarea
                                    value={pkg.description}
                                    onChange={e => setPackageField(idx, 'description', e.target.value)}
                                    placeholder="Briefly describe what's included..."
                                    rows={4}
                                    maxLength={500}
                                    className="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all h-full min-h-[100px]"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1">Delivery Time <Info className="w-3 h-3 text-slate-400" /></Label>
                                    <div className="relative">
                                        <Input
                                            type="number"
                                            min="1" max="365"
                                            value={pkg.delivery_days}
                                            onChange={e => setPackageField(idx, 'delivery_days', Number(e.target.value))}
                                            className="h-10 text-sm font-medium pr-12"
                                        />
                                        <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Days</span>
                                    </div>
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-bold text-slate-600 uppercase tracking-wider">Revisions</Label>
                                    <select
                                        value={pkg.revisions}
                                        onChange={e => setPackageField(idx, 'revisions', Number(e.target.value))}
                                        className="w-full h-10 rounded-xl border border-input bg-background px-3 text-sm font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none"
                                    >
                                        <option value="0">None</option>
                                        {[1,2,3,4,5,6,7,8,9].map(n => <option key={n} value={n}>{n}</option>)}
                                        <option value="-1">Unlimited</option>
                                    </select>
                                </div>
                            </div>

                            <div className="pt-4 border-t border-slate-100">
                                <Label className="text-xs font-bold text-slate-600 uppercase tracking-wider mb-3 block">Price</Label>
                                <div className="relative flex items-center shadow-sm rounded-xl overflow-hidden border focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/20 transition-all">
                                    <select
                                        value={pkg.currency_id}
                                        onChange={e => setPackageField(idx, 'currency_id', Number(e.target.value))}
                                        className="h-12 pl-4 pr-2 bg-slate-50 text-sm font-bold text-slate-600 border-none outline-none appearance-none cursor-pointer"
                                    >
                                        {CURRENCIES.map(c => <option key={c.id} value={c.id}>{c.code}</option>)}
                                    </select>
                                    <div className="w-[1px] h-6 bg-slate-200 mx-1"></div>
                                    <input
                                        type="number"
                                        min="1"
                                        value={pkg.price}
                                        onChange={e => setPackageField(idx, 'price', e.target.value)}
                                        placeholder="0.00"
                                        className="flex-1 h-12 bg-transparent text-lg font-bold px-3 border-none outline-none text-slate-900"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                ))}

                {data.packages.length < 3 && (
                    <button
                        type="button"
                        onClick={addPackage}
                        className="border-2 border-dashed border-slate-200 bg-slate-50/50 rounded-2xl flex flex-col items-center justify-center gap-3 py-12 text-slate-400 hover:border-indigo-300 hover:text-indigo-600 hover:bg-indigo-50/30 transition-all group min-h-[400px]"
                    >
                        <div className="w-12 h-12 rounded-full bg-white border shadow-sm flex items-center justify-center group-hover:scale-110 transition-transform">
                            <Plus className="w-5 h-5" />
                        </div>
                        <span className="text-sm font-bold tracking-wide">Add {PKG_LABELS[data.packages.length]} Package</span>
                    </button>
                )}
            </div>

            {errors.packages && <p className="text-sm text-red-500 font-medium">{errors.packages}</p>}
        </div>
    );
}
