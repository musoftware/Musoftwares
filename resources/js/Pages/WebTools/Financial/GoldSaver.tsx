import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import WebToolsLayout from '@/Layouts/WebToolsLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Checkbox } from '@/Components/ui/checkbox';
import { Coins, Wallet, TrendingUp, TrendingDown, Plus, List, LayoutGrid, Trash2, Edit2, CheckCircle } from 'lucide-react';
import { useToast } from '@/Components/ui/use-toast';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { __ } from '@/lib/i18n';

interface GoldItem {
    id: number;
    carat: string;
    date: string;
    gram_price: number;
    mfg_price: number;
    grams: number;
    tax: number;
    zakat: boolean;
}

export default function GoldSaver() {
    const { toast } = useToast();
    
    // Mock prices
    const [prices, setPrices] = useState({
        '10K': 1500,
        '14K': 2100,
        '18K': 2700,
        '21K': 3150,
        '24K': 3600
    });

    // Mock items
    const [items, setItems] = useState<GoldItem[]>([
        { id: 1, carat: '21K', date: '2023-01-15', gram_price: 2500, mfg_price: 150, grams: 20, tax: 0, zakat: false },
        { id: 2, carat: '24K', date: '2023-06-20', gram_price: 2900, mfg_price: 100, grams: 31.1, tax: 50, zakat: true },
    ]);

    // Form state
    const [newCarat, setNewCarat] = useState('');
    const [newDate, setNewDate] = useState('');
    const [newGramPrice, setNewGramPrice] = useState('');
    const [newMfgPrice, setNewMfgPrice] = useState('');
    const [newGrams, setNewGrams] = useState('');
    const [newTax, setNewTax] = useState('');

    const formatMoney = (val: number) => val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const handleAdd = (e: React.FormEvent) => {
        e.preventDefault();
        const item: GoldItem = {
            id: Date.now(),
            carat: newCarat + 'K',
            date: newDate,
            gram_price: parseFloat(newGramPrice) || 0,
            mfg_price: parseFloat(newMfgPrice) || 0,
            grams: parseFloat(newGrams) || 0,
            tax: parseFloat(newTax) || 0,
            zakat: false
        };
        setItems([...items, item]);
        toast({ title: 'Success', description: 'Investment record added successfully.' });
        // reset
        setNewCarat(''); setNewDate(''); setNewGramPrice(''); setNewMfgPrice(''); setNewGrams(''); setNewTax('');
    };

    const handleDelete = (id: number) => {
        setItems(items.filter(i => i.id !== id));
        toast({ title: 'Deleted', description: 'Record removed.' });
    };

    const toggleZakat = (id: number) => {
        setItems(items.map(i => i.id === id ? { ...i, zakat: !i.zakat } : i));
    };

    // Derived stats
    const totalInvestment = items.reduce((sum, item) => sum + ((item.gram_price + item.mfg_price) * item.grams + item.tax), 0);
    const currentValue = items.reduce((sum, item) => {
        const currentPrice = prices[item.carat as keyof typeof prices] || 0;
        return sum + (currentPrice * item.grams);
    }, 0);
    const totalProfit = currentValue - totalInvestment;
    const profitPercent = totalInvestment > 0 ? (totalProfit / totalInvestment) * 100 : 0;
    const totalZakat = items.filter(i => i.zakat).reduce((sum, item) => {
        const currentPrice = prices[item.carat as keyof typeof prices] || 0;
        return sum + (currentPrice * item.grams * 0.025); // 2.5% zakat
    }, 0);

    // Mock chart data
    const chartData = [
        { name: 'Jan', profit: 1200 },
        { name: 'Feb', profit: 2100 },
        { name: 'Mar', profit: 1800 },
        { name: 'Apr', profit: 3200 },
        { name: 'May', profit: 4500 },
        { name: 'Jun', profit: totalProfit },
    ];

    return (
        <WebToolsLayout title={__('general.gold_saver')} activeNav="explore">
            <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6">
                <div className="mb-8">
                    <span className="inline-block px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-sm font-medium mb-3">
                        {__('general.financial_tools')}</span>
                    <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                        <Wallet className="w-8 h-8 text-amber-500" />
                        {__('general.gold_saver')}</h1>
                    <p className="mt-2 text-lg text-slate-600">
                        {__('general.track_your_gold_investments_and_monitor')}</p>
                </div>

                {/* Today's Prices */}
                <Card className="mb-6 border-slate-200 shadow-sm">
                    <CardContent className="p-6">
                        <div className="flex items-center gap-2 mb-4">
                            <div className="bg-amber-100 p-2 rounded-lg text-amber-700">
                                <Coins className="w-5 h-5" />
                            </div>
                            <h3 className="font-semibold text-slate-900">Today's Gold Price (EGP)</h3>
                        </div>
                        <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                            {Object.entries(prices).map(([carat, price]) => (
                                <div key={carat} className="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                    <Label className="text-xs text-slate-500 mb-1 block">{carat}</Label>
                                    <Input 
                                        type="number" 
                                        value={price} 
                                        onChange={e => setPrices({...prices, [carat]: parseFloat(e.target.value) || 0})}
                                        className="font-bold text-slate-900 bg-white"
                                    />
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {/* Main Stats */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <Card className="border-slate-200 shadow-sm">
                        <CardContent className="p-6 flex items-center gap-4">
                            <div className="p-4 rounded-xl bg-blue-100 text-blue-600">
                                <Wallet className="w-6 h-6" />
                            </div>
                            <div>
                                <p className="text-sm font-medium text-slate-500">{__('general.total_investment')}</p>
                                <h4 className="text-2xl font-bold text-slate-900">{formatMoney(totalInvestment)}</h4>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-slate-200 shadow-sm">
                        <CardContent className="p-6 flex items-center gap-4">
                            <div className="p-4 rounded-xl bg-amber-100 text-amber-600">
                                <Coins className="w-6 h-6" />
                            </div>
                            <div>
                                <p className="text-sm font-medium text-slate-500">{__('general.current_value')}</p>
                                <h4 className="text-2xl font-bold text-slate-900">{formatMoney(currentValue)}</h4>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-slate-200 shadow-sm">
                        <CardContent className="p-6 flex items-center gap-4">
                            <div className={`p-4 rounded-xl ${totalProfit >= 0 ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600'}`}>
                                {totalProfit >= 0 ? <TrendingUp className="w-6 h-6" /> : <TrendingDown className="w-6 h-6" />}
                            </div>
                            <div>
                                <p className="text-sm font-medium text-slate-500">Total Profit / Loss</p>
                                <div className="flex items-baseline gap-2">
                                    <h4 className={`text-2xl font-bold ${totalProfit >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                                        {totalProfit >= 0 ? '+' : ''}{formatMoney(totalProfit)}
                                    </h4>
                                    <span className={`text-sm font-bold ${totalProfit >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                                        ({totalProfit >= 0 ? '+' : ''}{profitPercent.toFixed(2)}%)
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Tabs defaultValue="overview" className="w-full">
                    <TabsList className="mb-6 bg-slate-100/50 p-1">
                        <TabsTrigger value="overview" className="gap-2"><LayoutGrid className="w-4 h-4" /> {__('general.overview')}</TabsTrigger>
                        <TabsTrigger value="new" className="gap-2"><Plus className="w-4 h-4" /> {__('general.new_saving')}</TabsTrigger>
                        <TabsTrigger value="history" className="gap-2"><List className="w-4 h-4" /> {__('general.items_history')}</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview">
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                            {/* Breakdown */}
                            {['18K', '21K', '24K'].map(carat => {
                                const caratItems = items.filter(i => i.carat === carat);
                                const grams = caratItems.reduce((s, i) => s + i.grams, 0);
                                const cost = caratItems.reduce((s, i) => s + ((i.gram_price + i.mfg_price) * i.grams + i.tax), 0);
                                const currentPrice = prices[carat as keyof typeof prices] || 0;
                                const val = currentPrice * grams;
                                const prof = val - cost;
                                const profPer = cost > 0 ? (prof/cost)*100 : 0;
                                const isProf = prof >= 0;

                                return (
                                    <Card key={carat} className="border-slate-200 shadow-sm">
                                        <CardContent className="p-6">
                                            <div className="flex justify-between items-start mb-4">
                                                <h4 className="font-bold text-slate-800">{carat} Gold</h4>
                                                <div className={`px-2 py-1 rounded-md text-xs font-bold ${isProf ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'}`}>
                                                    {isProf ? '+' : ''}{profPer.toFixed(2)}%
                                                </div>
                                            </div>
                                            <div className="mb-4">
                                                <span className="text-sm text-slate-500 block">{__('general.total_grams')}</span>
                                                <span className="text-xl font-bold">{grams.toFixed(2)} g</span>
                                            </div>
                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <span className="text-xs text-slate-500 block">{__('general.current_val')}</span>
                                                    <span className="font-semibold">{formatMoney(val)}</span>
                                                </div>
                                                <div>
                                                    <span className="text-xs text-slate-500 block">Profit/Loss</span>
                                                    <span className={`font-semibold ${isProf ? 'text-emerald-600' : 'text-red-600'}`}>
                                                        {isProf ? '+' : ''}{formatMoney(prof)}
                                                    </span>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                )
                            })}
                        </div>

                        <Card className="border-slate-200 shadow-sm mb-6">
                            <CardHeader>
                                <CardTitle className="text-base">Profit / Loss History</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="h-[300px] w-full">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <LineChart data={chartData}>
                                            <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                            <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} dy={10} />
                                            <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                                            <Tooltip formatter={(val: any) => [formatMoney(val) + ' EGP', 'Profit']} />
                                            <Line type="monotone" dataKey="profit" stroke="#10b981" strokeWidth={3} fill="#10b981" />
                                        </LineChart>
                                    </ResponsiveContainer>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="bg-indigo-50 border-indigo-100 shadow-sm">
                            <CardContent className="p-6 flex items-center justify-between">
                                <div className="flex items-center gap-4">
                                    <div className="p-3 bg-indigo-600 text-white rounded-xl">
                                        <Wallet className="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h4 className="font-bold text-slate-900">{__('general.zakat_requirement')}</h4>
                                        <p className="text-sm text-slate-600">Calculated based on items marked for Zakat in your history (2.5%).</p>
                                    </div>
                                </div>
                                <div className="text-end">
                                    <h3 className="text-3xl font-bold text-indigo-600">{formatMoney(totalZakat)} <span className="text-lg">EGP</span></h3>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="new">
                        <Card className="border-slate-200 shadow-sm max-w-7xl mx-auto">
                            <CardHeader>
                                <CardTitle>{__('general.record_investment')}</CardTitle>
                                <CardDescription>{__('general.add_a_new_gold_purchase_to_your_portfoli')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleAdd} className="space-y-6">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div className="space-y-2">
                                            <Label>{__('general.carat')}</Label>
                                            <Select value={newCarat} onValueChange={(val) => setNewCarat(val || '')} required>
                                                <SelectTrigger><SelectValue placeholder={__('general.select_carat')} /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="10">10K</SelectItem>
                                                    <SelectItem value="14">14K</SelectItem>
                                                    <SelectItem value="18">18K</SelectItem>
                                                    <SelectItem value="21">21K</SelectItem>
                                                    <SelectItem value="22">22K</SelectItem>
                                                    <SelectItem value="24">24K</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <Label>{__('general.bought_date')}</Label>
                                            <Input type="date" value={newDate} onChange={e => setNewDate(e.target.value)} required />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Gram Price (Gold Only)</Label>
                                            <Input type="number" step="0.01" value={newGramPrice} onChange={e => setNewGramPrice(e.target.value)} required placeholder="0.00" />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Manufacturing / Gram</Label>
                                            <Input type="number" step="0.01" value={newMfgPrice} onChange={e => setNewMfgPrice(e.target.value)} required placeholder="0.00" />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>{__('general.total_grams')}</Label>
                                            <Input type="number" step="0.001" value={newGrams} onChange={e => setNewGrams(e.target.value)} required placeholder="0.000" />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Tax for Total (Optional)</Label>
                                            <Input type="number" step="0.01" value={newTax} onChange={e => setNewTax(e.target.value)} placeholder="0.00" />
                                        </div>
                                    </div>
                                    <div className="text-end pt-4">
                                        <Button type="submit" size="lg" className="w-full md:w-auto">
                                            <Plus className="w-4 h-4 me-2" /> {__('general.record_investment')}</Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="history">
                        <Card className="border-slate-200 shadow-sm">
                            <CardHeader>
                                <CardTitle>{__('general.items_history')}</CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="bg-slate-50">
                                            <TableHead className="w-16 text-center">{__('general.zakat')}</TableHead>
                                            <TableHead>{__('general.carat')}</TableHead>
                                            <TableHead>{__('general.grams')}</TableHead>
                                            <TableHead>{__('general.unit_cost')}</TableHead>
                                            <TableHead>{__('general.total_cost')}</TableHead>
                                            <TableHead>{__('general.current_val')}</TableHead>
                                            <TableHead>Profit/Loss</TableHead>
                                            <TableHead>{__('general.date')}</TableHead>
                                            <TableHead className="text-end">{__('general.actions')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {items.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={9} className="text-center py-8 text-slate-500">{__('general.no_items_found')}</TableCell>
                                            </TableRow>
                                        ) : (
                                            items.map(item => {
                                                const unitCost = item.gram_price + item.mfg_price;
                                                const totalCost = (unitCost * item.grams) + item.tax;
                                                const currentPrice = prices[item.carat as keyof typeof prices] || 0;
                                                const currentVal = currentPrice * item.grams;
                                                const prof = currentVal - totalCost;
                                                const isProf = prof >= 0;
                                                const profPer = totalCost > 0 ? (prof/totalCost)*100 : 0;

                                                return (
                                                    <TableRow key={item.id}>
                                                        <TableCell className="text-center">
                                                            <Checkbox checked={item.zakat} onCheckedChange={() => toggleZakat(item.id)} />
                                                        </TableCell>
                                                        <TableCell><span className="px-2 py-1 bg-slate-100 rounded-md font-semibold text-xs border border-slate-200">{item.carat}</span></TableCell>
                                                        <TableCell className="font-semibold">{item.grams.toFixed(2)}</TableCell>
                                                        <TableCell>{formatMoney(unitCost)}</TableCell>
                                                        <TableCell className="font-semibold">{formatMoney(totalCost)}</TableCell>
                                                        <TableCell className="font-bold text-indigo-600">{formatMoney(currentVal)}</TableCell>
                                                        <TableCell>
                                                            <div className={`font-bold ${isProf ? 'text-emerald-600' : 'text-red-600'}`}>
                                                                {isProf ? '+' : ''}{formatMoney(prof)}
                                                            </div>
                                                            <div className={`text-xs ${isProf ? 'text-emerald-600' : 'text-red-600'}`}>
                                                                {isProf ? '+' : ''}{profPer.toFixed(1)}%
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="text-sm text-slate-500">{item.date}</TableCell>
                                                        <TableCell className="text-end">
                                                            <Button variant="ghost" size="icon" className="text-red-500 hover:text-red-700 hover:bg-red-50" onClick={() => handleDelete(item.id)}>
                                                                <Trash2 className="w-4 h-4" />
                                                            </Button>
                                                        </TableCell>
                                                    </TableRow>
                                                )
                                            })
                                        )}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </WebToolsLayout>
    );
}
