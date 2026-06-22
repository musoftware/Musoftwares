import React, { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Bot, Calculator, Clock, Loader2 } from 'lucide-react';
import { __ } from '@/lib/i18n';
import axios from 'axios';
import { formatMoney } from '@/lib/utils';

interface Currency {
    id: number;
    currency: string;
    symbol?: string;
}

interface Props {
    expected_monthly_income: number;
    work_days_per_month: number;
    hours_per_day: number;
    currency: Currency | null;
}

export default function AiEstimator({ expected_monthly_income, work_days_per_month, hours_per_day, currency }: Props) {
    const [income, setIncome] = useState(expected_monthly_income || 0);
    const [days, setDays] = useState(work_days_per_month || 0);
    const [hours, setHours] = useState(hours_per_day || 0);
    const [description, setDescription] = useState('');
    const [loading, setLoading] = useState(false);
    const [estimatedHours, setEstimatedHours] = useState<number | null>(null);
    const [error, setError] = useState<string | null>(null);

    const handleEstimate = async () => {
        if (!description || description.length < 10) {
            setError('Please provide a detailed task description (min 10 characters).');
            return;
        }

        setLoading(true);
        setError(null);
        setEstimatedHours(null);

        try {
            const response = await axios.post(route('admin.tools.ai-estimator.estimate'), {
                task_description: description
            });

            if (response.data && response.data.estimated_hours !== undefined) {
                setEstimatedHours(response.data.estimated_hours);
            } else {
                setError('Failed to get a valid response from AI.');
            }
        } catch (err: any) {
            setError(err.response?.data?.error || 'An error occurred while estimating.');
        } finally {
            setLoading(false);
        }
    };

    const computedRate = React.useMemo(() => {
        if (income > 0 && days > 0 && hours > 0) {
            return income / (days * hours);
        }
        return 0;
    }, [income, days, hours]);

    const totalPrice = estimatedHours !== null ? estimatedHours * computedRate : 0;

    return (
        <AdminSidebarLayout title={__('admin.ai_estimator')} header={__('admin.ai_estimator')}>
            <Head title={__('admin.ai_estimator')} />

            <div className="max-w-7xl mx-auto space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Bot className="h-5 w-5" />
                            {__('admin.ai_estimator')}
                        </CardTitle>
                        <CardDescription>
                            {__('general.describe_a_project_or_task_and_let_the_a')}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div className="space-y-2">
                                <Label htmlFor="income">{__('admin.expected_monthly_income')}</Label>
                                <input
                                    id="income"
                                    type="number"
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    value={income || ''}
                                    onChange={(e) => setIncome(parseFloat(e.target.value) || 0)}
                                    placeholder="e.g. 5000"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="days">{__('admin.work_days_per_month')}</Label>
                                <input
                                    id="days"
                                    type="number"
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    value={days || ''}
                                    onChange={(e) => setDays(parseFloat(e.target.value) || 0)}
                                    placeholder="e.g. 20"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="hours">{__('admin.hours_per_day')}</Label>
                                <input
                                    id="hours"
                                    type="number"
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    value={hours || ''}
                                    onChange={(e) => setHours(parseFloat(e.target.value) || 0)}
                                    placeholder="e.g. 8"
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">{__('admin.task_description')}</Label>
                            <Textarea
                                id="description"
                                placeholder={__('general.describe_the_task_or_project_requirement')}
                                value={description}
                                onChange={(e) => setDescription(e.target.value)}
                                className="min-h-[150px]"
                            />
                            {error && <p className="text-sm text-red-600">{error}</p>}
                        </div>

                        <Button onClick={handleEstimate} disabled={loading} className="w-full sm:w-auto gap-2">
                            {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Calculator className="h-4 w-4" />}
                            {__('admin.estimate_hours')}
                        </Button>
                    </CardContent>
                </Card>

                {estimatedHours !== null && (
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <Card className="bg-slate-50 border-slate-50">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-slate-900 flex items-center gap-2">
                                    <Clock className="h-4 w-4" />
                                    {__('admin.estimated_hours')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-slate-900">{estimatedHours}h</div>
                            </CardContent>
                        </Card>

                        <Card className="bg-gray-50 border-gray-200">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700">
                                    {__('admin.your_hourly_rate')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-xl font-semibold text-gray-900">
                                    {currency ? formatMoney(computedRate, currency) : computedRate.toFixed(2)}
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="bg-green-50 border-green-100">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-green-800">
                                    {__('admin.total_price')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-green-900">
                                    {currency ? formatMoney(totalPrice, currency) : totalPrice}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </AdminSidebarLayout>
    );
}
