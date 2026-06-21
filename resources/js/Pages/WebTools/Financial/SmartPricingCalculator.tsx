import React, { useState, useMemo } from 'react';
import { Head } from '@inertiajs/react';
import WebToolsLayout from '@/Layouts/WebToolsLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { Calculator, Target, TrendingUp, Package, Percent, DollarSign, Truck, RefreshCcw } from 'lucide-react';

export default function SmartPricingCalculator() {
    const [productCost, setProductCost] = useState(150);
    const [cpa, setCpa] = useState(100);
    const [monthlyExpenses, setMonthlyExpenses] = useState(10000);
    const [expectedOrders, setExpectedOrders] = useState(1000);
    const [confirmationRate, setConfirmationRate] = useState(80);
    const [deliveryRate, setDeliveryRate] = useState(80);
    const [shippingCost, setShippingCost] = useState(60);
    const [returnCost, setReturnCost] = useState(20);

    const results = useMemo(() => {
        const confirmedOrders = expectedOrders * (confirmationRate / 100);
        const deliveredOrders = confirmedOrders * (deliveryRate / 100);

        if (deliveredOrders <= 0) return null;

        const expensePerPiece = monthlyExpenses / deliveredOrders;
        const returnRate = 1 - (deliveryRate / 100);
        
        // Corrected logic to use the actual returnCost field if provided, or default to a loss of CPA + shipping + return processing
        const returnCostPerUnit = (cpa + shippingCost + returnCost) * returnRate;
        const totalCostPerUnit = productCost + cpa + shippingCost + returnCostPerUnit + expensePerPiece;
        
        // Old code used +10% for break even. We'll stick to that.
        const breakEvenPoint = totalCostPerUnit * 1.10;

        const suggestions = [10, 20, 30, 40].map(margin => {
            const suggestedPrice = totalCostPerUnit * (1 + margin / 100);
            const profitPerUnit = suggestedPrice - totalCostPerUnit;
            const monthlyProfit = profitPerUnit * deliveredOrders;
            return {
                margin,
                price: suggestedPrice,
                profitPerUnit,
                monthlyProfit
            };
        });

        return {
            confirmedOrders,
            deliveredOrders,
            expensePerPiece,
            totalCostPerUnit,
            breakEvenPoint,
            suggestions
        };
    }, [productCost, cpa, monthlyExpenses, expectedOrders, confirmationRate, deliveryRate, shippingCost, returnCost]);

    const formatMoney = (val: number) => val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    return (
        <WebToolsLayout title="Smart Pricing Calculator" activeNav="explore">
            <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6">
                <div className="mb-8">
                    <span className="inline-block px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-sm font-medium mb-3">
                        E-Commerce Tool
                    </span>
                    <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                        <Calculator className="w-8 h-8 text-indigo-500" />
                        Smart Pricing Calculator
                    </h1>
                    <p className="mt-2 text-lg text-slate-600">
                        Calculate optimal product pricing considering CPA, confirmation rates, and return costs.
                    </p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    {/* Inputs */}
                    <div className="lg:col-span-5 space-y-6">
                        <Card className="border-slate-200 shadow-sm">
                            <CardHeader className="bg-slate-50 border-b border-slate-100 pb-4">
                                <CardTitle className="text-lg flex items-center gap-2">
                                    <Package className="w-5 h-5 text-slate-500" />
                                    Product & Marketing
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="pt-6 space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label className="text-slate-600">Product Cost</Label>
                                        <div className="relative">
                                            <span className="absolute start-3 top-2.5 text-slate-400">$</span>
                                            <Input type="number" className="ps-7" value={productCost} onChange={e => setProductCost(Number(e.target.value))} />
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-slate-600">CPA (Cost/Acquisition)</Label>
                                        <div className="relative">
                                            <span className="absolute start-3 top-2.5 text-slate-400">$</span>
                                            <Input type="number" className="ps-7" value={cpa} onChange={e => setCpa(Number(e.target.value))} />
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="border-slate-200 shadow-sm">
                            <CardHeader className="bg-slate-50 border-b border-slate-100 pb-4">
                                <CardTitle className="text-lg flex items-center gap-2">
                                    <Target className="w-5 h-5 text-slate-500" />
                                    Operations & Logistics
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="pt-6 space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label className="text-slate-600">Expected Orders / mo</Label>
                                        <Input type="number" value={expectedOrders} onChange={e => setExpectedOrders(Number(e.target.value))} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-slate-600">Monthly Expenses</Label>
                                        <div className="relative">
                                            <span className="absolute start-3 top-2.5 text-slate-400">$</span>
                                            <Input type="number" className="ps-7" value={monthlyExpenses} onChange={e => setMonthlyExpenses(Number(e.target.value))} />
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-slate-600">Confirmation Rate</Label>
                                        <div className="relative">
                                            <Input type="number" className="pe-7" value={confirmationRate} onChange={e => setConfirmationRate(Number(e.target.value))} />
                                            <span className="absolute end-3 top-2.5 text-slate-400">%</span>
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-slate-600">Delivery Rate</Label>
                                        <div className="relative">
                                            <Input type="number" className="pe-7" value={deliveryRate} onChange={e => setDeliveryRate(Number(e.target.value))} />
                                            <span className="absolute end-3 top-2.5 text-slate-400">%</span>
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-slate-600">Shipping Cost</Label>
                                        <div className="relative">
                                            <span className="absolute start-3 top-2.5 text-slate-400">$</span>
                                            <Input type="number" className="ps-7" value={shippingCost} onChange={e => setShippingCost(Number(e.target.value))} />
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-slate-600">Return Cost</Label>
                                        <div className="relative">
                                            <span className="absolute start-3 top-2.5 text-slate-400">$</span>
                                            <Input type="number" className="ps-7" value={returnCost} onChange={e => setReturnCost(Number(e.target.value))} />
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Results */}
                    <div className="lg:col-span-7">
                        {results ? (
                            <div className="space-y-6">
                                <Card className="bg-indigo-600 text-white border-none shadow-md">
                                    <CardContent className="p-8">
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                            <div>
                                                <p className="text-indigo-100 font-medium mb-1">Break-Even Point</p>
                                                <h2 className="text-4xl font-bold">${formatMoney(results.breakEvenPoint)}</h2>
                                                <p className="text-sm text-indigo-200 mt-2">Includes 10% safety margin</p>
                                            </div>
                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <p className="text-indigo-200 text-sm mb-1">Total Unit Cost</p>
                                                    <p className="text-xl font-bold">${formatMoney(results.totalCostPerUnit)}</p>
                                                </div>
                                                <div>
                                                    <p className="text-indigo-200 text-sm mb-1">Expense Share/Unit</p>
                                                    <p className="text-xl font-bold">${formatMoney(results.expensePerPiece)}</p>
                                                </div>
                                                <div className="col-span-2">
                                                    <p className="text-indigo-200 text-sm mb-1">Projected Delivered</p>
                                                    <div className="flex items-center gap-2 mt-1">
                                                        <div className="h-2 bg-indigo-800 rounded-full overflow-hidden"><div className="h-full bg-emerald-400" style={{ width: `${(results.deliveredOrders / expectedOrders) * 100}%` }} /></div>
                                                        <span className="text-sm font-semibold">{Math.round(results.deliveredOrders)} / {expectedOrders}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <h3 className="text-xl font-bold text-slate-900 mt-8 mb-4 flex items-center gap-2">
                                    <DollarSign className="w-6 h-6 text-emerald-500" />
                                    Pricing Suggestions
                                </h3>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {results.suggestions.map((s, i) => (
                                        <Card key={i} className={`border-2 ${i === 2 ? 'border-emerald-500 shadow-emerald-100' : 'border-slate-200'} shadow-sm relative overflow-hidden`}>
                                            {i === 2 && (
                                                <div className="absolute top-0 end-0 bg-emerald-500 text-white text-[10px] font-bold px-2 py-1 rounded-bl-lg uppercase tracking-wider">
                                                    Recommended
                                                </div>
                                            )}
                                            <CardContent className="p-5">
                                                <div className="flex justify-between items-start mb-4">
                                                    <div>
                                                        <p className="text-sm font-semibold text-slate-500 uppercase tracking-wider">Target Margin</p>
                                                        <p className="text-2xl font-black text-slate-900">{s.margin}%</p>
                                                    </div>
                                                    <div className="text-end">
                                                        <p className="text-sm font-semibold text-slate-500 uppercase tracking-wider">Sell Price</p>
                                                        <p className="text-2xl font-bold text-indigo-600">${formatMoney(s.price)}</p>
                                                    </div>
                                                </div>
                                                <div className="pt-4 border-t border-slate-100 flex justify-between items-center">
                                                    <div>
                                                        <p className="text-xs text-slate-500 mb-1">Profit / Unit</p>
                                                        <p className="font-bold text-emerald-600">${formatMoney(s.profitPerUnit)}</p>
                                                    </div>
                                                    <div className="text-end">
                                                        <p className="text-xs text-slate-500 mb-1">Monthly Net Profit</p>
                                                        <p className="font-bold text-emerald-600">${formatMoney(s.monthlyProfit)}</p>
                                                    </div>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    ))}
                                </div>
                            </div>
                        ) : (
                            <Card className="border-slate-200 shadow-sm h-full flex flex-col items-center justify-center p-12 text-center text-slate-500">
                                <Target className="w-16 h-16 text-slate-300 mb-4" />
                                <h3 className="text-xl font-medium text-slate-700">Waiting for valid inputs</h3>
                                <p className="mt-2">Enter your product and marketing costs to see pricing suggestions.</p>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </WebToolsLayout>
    );
}
