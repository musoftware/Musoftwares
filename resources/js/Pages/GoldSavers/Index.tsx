import React, { useState, useMemo } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Plus, Trash2, TrendingUp, Coins, AlertCircle, Scale, ShieldCheck, Info, Calculator, Percent, Edit2, X } from 'lucide-react';
import { AppPage } from '@/Components/ui/AppPage';
import { PageHeader } from '@/Components/ui/PageHeader';
import { Alert, AlertTitle, AlertDescription } from '@/Components/ui/alert';

interface GoldSaverRecord {
    id: number;
    carat: string;
    gram_price: number;
    additional_price: number;
    grams: number;
    tax: number;
    bought_date: string;
    zakat: boolean;
    buyer_price: number;
    current_value: number;
    profit: number;
    profit_percentage: number;
}

interface GoldPrice {
    price_24k: number;
    price_22k: number;
    price_21k: number;
    price_18k: number;
    price_14k: number;
    price_10k: number;
    price_date: string;
}

export default function Index({ records, latestPrice }: { records: GoldSaverRecord[], latestPrice: GoldPrice | null }) {
    const [editingId, setEditingId] = useState<number | null>(null);
    const { data, setData, post, put, processing, reset, errors } = useForm({
        carat: '21',
        gram_price: '',
        additional_price: '',
        grams: '',
        tax: '',
        bought_date: new Date().toISOString().split('T')[0],
        zakat: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingId) {
            put(route('isaas.gold-savers.update', editingId), {
                onSuccess: () => {
                    reset();
                    setEditingId(null);
                },
            });
        } else {
            post(route('isaas.gold-savers.store'), {
                onSuccess: () => reset(),
            });
        }
    };

    const handleEdit = (record: GoldSaverRecord) => {
        setEditingId(record.id);
        setData({
            carat: record.carat.toString(),
            gram_price: record.gram_price.toString(),
            additional_price: record.additional_price.toString(),
            grams: record.grams.toString(),
            tax: record.tax.toString(),
            bought_date: new Date(record.bought_date).toISOString().split('T')[0],
            zakat: record.zakat,
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const handleCancelEdit = () => {
        setEditingId(null);
        reset();
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this record?')) {
            router.delete(route('isaas.gold-savers.destroy', id));
        }
    };

    // Summaries
    const totalGrams = useMemo(() => records.reduce((sum, r) => sum + Number(r.grams), 0), [records]);
    const totalInvestment = useMemo(() => records.reduce((sum, r) => sum + Number(r.buyer_price), 0), [records]);
    const totalCurrentValue = useMemo(() => records.reduce((sum, r) => sum + Number(r.current_value), 0), [records]);
    const totalProfit = totalCurrentValue - totalInvestment;
    const totalProfitPercentage = totalInvestment > 0 ? (totalProfit / totalInvestment) * 100 : 0;

    // Zakat Calculation (24k equivalent)
    const zakatable24kGrams = useMemo(() => {
        return records.reduce((sum, r) => {
            if (r.zakat) {
                return sum + (Number(r.grams) * (Number(r.carat) / 24));
            }
            return sum;
        }, 0);
    }, [records]);
    
    const zakatableCurrentValue = useMemo(() => {
        return records.reduce((sum, r) => {
            if (r.zakat) {
                return sum + Number(r.current_value);
            }
            return sum;
        }, 0);
    }, [records]);

    const nisabThreshold = 85.0; // 85 grams of 24k gold
    const isZakatDue = zakatable24kGrams >= nisabThreshold;
    const zakatEstimate = isZakatDue ? zakatableCurrentValue * 0.025 : 0;

    return (
        <AuthenticatedLayout header="Gold Savers">
            <Head title="Gold Savers - iSAAS" />
            <AppPage>
                <PageHeader 
                    title="Gold Portfolio Tracker" 
                    subtitle="Track your physical gold investments, analyze buy/sell spreads, and monitor Zakat eligibility all in one place."
                    icon={Coins}
                />

                {/* Info / Zakat Alert */}
                {zakatable24kGrams > 0 && (
                    <Alert className={`mb-8 border-l-4 ${isZakatDue ? 'border-l-amber-500 bg-amber-50/50' : 'border-l-emerald-500 bg-emerald-50/50'}`}>
                        {isZakatDue ? <AlertCircle className="h-5 w-5 text-amber-600" /> : <ShieldCheck className="h-5 w-5 text-emerald-600" />}
                        <AlertTitle className={`font-semibold ${isZakatDue ? 'text-amber-800' : 'text-emerald-800'}`}>
                            {isZakatDue ? 'Zakat is Due on your Gold' : 'Below Zakat Threshold (Nisab)'}
                        </AlertTitle>
                        <AlertDescription className="mt-2 text-slate-700 text-sm leading-relaxed">
                            Your zakatable gold is equivalent to <strong>{zakatable24kGrams.toFixed(2)}g of 24k gold</strong>. 
                            {isZakatDue ? (
                                <span> This exceeds the Nisab threshold (85g). Therefore, Zakat (2.5%) is obligatory if a full Hijri year has passed since acquiring it. 
                                <br/><span className="inline-block mt-2 font-medium px-2 py-1 bg-amber-100 text-amber-800 rounded">Estimated Zakat Amount: {zakatEstimate.toLocaleString(undefined, {maximumFractionDigits: 2})} EGP</span>
                                </span>
                            ) : (
                                <span> You are short of the Nisab threshold (85g) by <strong>{(nisabThreshold - zakatable24kGrams).toFixed(2)}g</strong>. Zakat is not currently obligatory on this gold.</span>
                            )}
                        </AlertDescription>
                    </Alert>
                )}

                {/* Today's Prices */}
                {latestPrice && (
                    <div className="mb-8">
                        <div className="flex items-center gap-2 mb-4">
                            <div className="p-2 bg-yellow-50 text-yellow-600 rounded-lg">
                                <Coins className="w-5 h-5" />
                            </div>
                            <h3 className="text-lg font-bold text-slate-900">Today's Gold Price (EGP)</h3>
                            <span className="text-xs text-slate-500 ml-2">Last updated: {new Date(latestPrice.price_date).toLocaleDateString()}</span>
                        </div>
                        <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                            {[
                                { label: '24K', value: latestPrice.price_24k },
                                { label: '22K', value: latestPrice.price_22k },
                                { label: '21K', value: latestPrice.price_21k },
                                { label: '18K', value: latestPrice.price_18k },
                                { label: '14K', value: latestPrice.price_14k },
                            ].map((item) => (
                                <Card key={item.label} className="border-0 shadow-sm ring-1 ring-slate-200/50 bg-white">
                                    <CardContent className="p-4 text-center">
                                        <p className="text-sm font-medium text-slate-500 mb-1">{item.label}</p>
                                        <p className="text-xl font-bold text-slate-900">{Number(item.value).toLocaleString()}</p>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                )}

                {/* Stat Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <Card className="border-0 shadow-sm ring-1 ring-slate-200/50 bg-white hover:shadow-md transition-shadow">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Investment</p>
                                <div className="p-2 bg-blue-50 text-blue-600 rounded-lg">
                                    <TrendingUp className="w-5 h-5" />
                                </div>
                            </div>
                            <div className="mt-4">
                                <h3 className="text-3xl font-bold text-slate-900">{totalInvestment.toLocaleString()} <span className="text-sm font-normal text-slate-500">EGP</span></h3>
                            </div>
                            <p className="text-xs text-slate-400 mt-2">Total amount spent including tax & making charges.</p>
                        </CardContent>
                    </Card>

                    <Card className="border-0 shadow-sm ring-1 ring-slate-200/50 bg-white hover:shadow-md transition-shadow">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-slate-500 uppercase tracking-wider">Current Value</p>
                                <div className="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                                    <Coins className="w-5 h-5" />
                                </div>
                            </div>
                            <div className="mt-4">
                                <h3 className="text-3xl font-bold text-slate-900">{totalCurrentValue.toLocaleString()} <span className="text-sm font-normal text-slate-500">EGP</span></h3>
                            </div>
                            <p className="text-xs text-slate-400 mt-2">Estimated market value of your portfolio today.</p>
                        </CardContent>
                    </Card>

                    <Card className="border-0 shadow-sm ring-1 ring-slate-200/50 bg-white hover:shadow-md transition-shadow">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Profit/Loss</p>
                                <div className={`p-2 rounded-lg ${totalProfit >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'}`}>
                                    <Percent className="w-5 h-5" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-baseline gap-2">
                                <h3 className={`text-3xl font-bold ${totalProfit >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                                    {totalProfit >= 0 ? '+' : ''}{totalProfit.toLocaleString()} <span className="text-sm font-normal opacity-80">EGP</span>
                                </h3>
                                <span className={`text-sm font-bold ${totalProfit >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                                    ({totalProfit >= 0 ? '+' : ''}{totalProfitPercentage.toFixed(2)}%)
                                </span>
                            </div>
                            <p className="text-xs text-slate-400 mt-2">Net change since purchase.</p>
                        </CardContent>
                    </Card>

                    <Card className="border-0 shadow-sm ring-1 ring-slate-200/50 bg-white hover:shadow-md transition-shadow">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-slate-500 uppercase tracking-wider">Portfolio Items</p>
                                <div className="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                                    <Coins className="w-5 h-5" />
                                </div>
                            </div>
                            <div className="mt-4">
                                <h3 className="text-3xl font-bold text-slate-900">{records.length} <span className="text-sm font-normal text-slate-500">pieces</span></h3>
                            </div>
                            <p className="text-xs text-slate-400 mt-2">Number of registered gold purchases.</p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    {/* Add New Record Form */}
                    <div className="lg:col-span-4">
                        <Card className="border-0 shadow-sm ring-1 ring-slate-200/50 sticky top-24">
                            <CardHeader className="bg-slate-50/50 border-b border-slate-100 pb-4">
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <Calculator className="w-5 h-5 text-slate-500" />
                                    {editingId ? 'Edit Purchase' : 'Record New Purchase'}
                                </CardTitle>
                                <CardDescription>{editingId ? 'Update the details for this gold item.' : 'Enter exactly what is written on your gold invoice.'}</CardDescription>
                            </CardHeader>
                            <form onSubmit={handleSubmit}>
                                <CardContent className="space-y-5 pt-6">
                                    
                                    <div className="space-y-2">
                                        <Label htmlFor="carat" className="text-slate-700">Gold Purity (Carat)</Label>
                                        <Select 
                                            value={data.carat} 
                                            onValueChange={(v) => setData('carat', v)}
                                        >
                                            <SelectTrigger id="carat" className="h-11">
                                                <SelectValue placeholder="Select carat" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="14">14k</SelectItem>
                                                <SelectItem value="18">18k</SelectItem>
                                                <SelectItem value="21">21k</SelectItem>
                                                <SelectItem value="22">22k</SelectItem>
                                                <SelectItem value="24">24k (Bullion)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.carat && <p className="text-xs text-red-500">{errors.carat}</p>}
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="grams" className="text-slate-700">Weight (Grams)</Label>
                                            <Input id="grams" type="number" step="0.001" className="h-11" value={data.grams} onChange={e => setData('grams', e.target.value)} placeholder="e.g. 10.50" required />
                                            {errors.grams && <p className="text-xs text-red-500">{errors.grams}</p>}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="gram_price" className="text-slate-700">Base Price / Gram</Label>
                                            <Input id="gram_price" type="number" step="0.001" className="h-11" value={data.gram_price} onChange={e => setData('gram_price', e.target.value)} placeholder="e.g. 3500" required />
                                            {errors.gram_price && <p className="text-xs text-red-500">{errors.gram_price}</p>}
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="additional_price" className="text-slate-700 text-xs sm:text-sm">Making Charge (Add.)</Label>
                                            <Input id="additional_price" type="number" step="0.001" className="h-11" value={data.additional_price} onChange={e => setData('additional_price', e.target.value)} placeholder="e.g. 150" required />
                                            {errors.additional_price && <p className="text-xs text-red-500">{errors.additional_price}</p>}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="tax" className="text-slate-700">Tax / Stamp</Label>
                                            <Input id="tax" type="number" step="0.001" className="h-11" value={data.tax} onChange={e => setData('tax', e.target.value)} placeholder="e.g. 50" required />
                                            {errors.tax && <p className="text-xs text-red-500">{errors.tax}</p>}
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="bought_date" className="text-slate-700">Purchase Date</Label>
                                        <Input id="bought_date" type="date" className="h-11" value={data.bought_date} onChange={e => setData('bought_date', e.target.value)} required />
                                        {errors.bought_date && <p className="text-xs text-red-500">{errors.bought_date}</p>}
                                    </div>

                                    <div className="flex items-start space-x-3 pt-2 p-3 bg-slate-50 rounded-lg border border-slate-100">
                                        <input 
                                            type="checkbox"
                                            id="zakat" 
                                            className="w-5 h-5 mt-0.5 rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                                            checked={data.zakat} 
                                            onChange={(e) => setData('zakat', e.target.checked)} 
                                        />
                                        <div>
                                            <Label htmlFor="zakat" className="font-semibold cursor-pointer text-slate-800">Include in Zakat</Label>
                                            <p className="text-xs text-slate-500 mt-1 leading-relaxed">Check this if the item is kept for saving/investment purposes. Do not check if it is jewelry used for personal wear.</p>
                                        </div>
                                    </div>

                                </CardContent>
                                <CardFooter className="bg-slate-50/50 border-t border-slate-100 pt-4 flex gap-2">
                                    <Button type="submit" size="lg" className="flex-1 bg-slate-900 hover:bg-slate-800" disabled={processing}>
                                        <Plus className="w-4 h-4 mr-2" />
                                        {editingId ? 'Update Portfolio' : 'Save to Portfolio'}
                                    </Button>
                                    {editingId && (
                                        <Button type="button" variant="outline" size="lg" onClick={handleCancelEdit} disabled={processing}>
                                            <X className="w-4 h-4" />
                                        </Button>
                                    )}
                                </CardFooter>
                            </form>
                        </Card>
                    </div>

                    {/* Records Table */}
                    <div className="lg:col-span-8">
                        <Card className="border-0 shadow-sm ring-1 ring-slate-200/50 h-full flex flex-col">
                            <CardHeader className="border-b border-slate-100 pb-4">
                                <CardTitle className="text-lg">Detailed Log</CardTitle>
                                <CardDescription>A complete log of your physical gold purchases and their calculated losses.</CardDescription>
                            </CardHeader>
                            <CardContent className="flex-1 p-0">
                                {records.length === 0 ? (
                                    <div className="text-center py-20 text-slate-500 flex flex-col items-center justify-center">
                                        <div className="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                            <TrendingUp className="w-8 h-8 text-slate-300" />
                                        </div>
                                        <h3 className="text-lg font-medium text-slate-900">Your portfolio is empty</h3>
                                        <p className="mt-1 text-sm text-slate-500 max-w-sm">Start building your gold portfolio by adding your first purchase record from the form on the left.</p>
                                    </div>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader className="bg-slate-50/80">
                                                <TableRow>
                                                    <TableHead className="w-[120px]">Purchase Date</TableHead>
                                                    <TableHead>Item Details</TableHead>
                                                    <TableHead className="text-right">Breakdown</TableHead>
                                                    <TableHead className="text-right">Total Paid</TableHead>
                                                    <TableHead className="text-right">Current Val</TableHead>
                                                    <TableHead className="text-center">Profit/Loss</TableHead>
                                                    <TableHead className="w-[90px]"></TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {records.map((record) => (
                                                    <TableRow key={record.id} className="hover:bg-slate-50/50">
                                                        <TableCell className="font-medium whitespace-nowrap text-slate-600">
                                                            {new Date(record.bought_date).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })}
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="text-sm font-semibold text-slate-900">{Number(record.grams).toLocaleString(undefined, { maximumFractionDigits: 3 })}g of {record.carat}k</div>
                                                            <div className="flex items-center gap-1 mt-1">
                                                                {record.zakat ? (
                                                                    <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-800">
                                                                        Zakat
                                                                    </span>
                                                                ) : (
                                                                    <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-500">
                                                                        Personal
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            <div className="text-xs text-slate-500 font-medium">Base: <span className="text-slate-900">{Number(record.gram_price).toLocaleString(undefined, { maximumFractionDigits: 2 })}</span></div>
                                                            <div className="text-xs text-slate-500">Making: +{Number(record.additional_price).toLocaleString(undefined, { maximumFractionDigits: 2 })}</div>
                                                            <div className="text-xs text-slate-500">Tax/Stamp: +{Number(record.tax).toLocaleString(undefined, { maximumFractionDigits: 2 })}</div>
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            <span className="font-bold text-slate-900">{record.buyer_price.toLocaleString()}</span>
                                                            <span className="text-xs text-slate-500 ml-1">EGP</span>
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            <span className="font-bold text-indigo-600">{record.current_value.toLocaleString()}</span>
                                                            <span className="text-xs text-slate-500 ml-1">EGP</span>
                                                        </TableCell>
                                                        <TableCell className="text-center">
                                                            <div className="flex flex-col items-center">
                                                                <span className={`inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold ${
                                                                    record.profit >= 0 ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20' : 
                                                                    'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
                                                                }`}>
                                                                    {record.profit >= 0 ? '+' : ''}{record.profit.toLocaleString()}
                                                                </span>
                                                                <span className={`text-[10px] font-medium mt-1 ${record.profit >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                                                                    {record.profit >= 0 ? '+' : ''}{Number(record.profit_percentage).toLocaleString(undefined, { maximumFractionDigits: 2 })}%
                                                                </span>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            <div className="flex justify-end gap-1">
                                                                <Button 
                                                                    variant="ghost" 
                                                                    size="icon" 
                                                                    className="text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 h-8 w-8"
                                                                    onClick={() => handleEdit(record)}
                                                                    title="Edit record"
                                                                >
                                                                    <Edit2 className="w-4 h-4" />
                                                                </Button>
                                                                <Button 
                                                                    variant="ghost" 
                                                                    size="icon" 
                                                                    className="text-slate-400 hover:text-red-600 hover:bg-red-50 h-8 w-8"
                                                                    onClick={() => handleDelete(record.id)}
                                                                    title="Delete record"
                                                                >
                                                                    <Trash2 className="w-4 h-4" />
                                                                </Button>
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>

            </AppPage>
        </AuthenticatedLayout>
    );
}
