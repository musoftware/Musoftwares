import React, { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import PageHeader from '@/Components/PageHeader';
import CurrencyDisplay from '@/Components/CurrencyDisplay';
import {
    addDays,
    addWeeks,
    addMonths,
    addYears,
    format,
    parseISO,
    isValid,
    setDay,
    setDate,
    setMonth
} from 'date-fns';
import {
    TrendingUp,
    TrendingDown,
    Info,
    Calendar,
    Clock,
    DollarSign,
    ChevronLeft
} from 'lucide-react';

const getOrdinalSuffix = (day) => {
    const d = parseInt(day);
    if (d > 3 && d < 21) return 'th';
    switch (d % 10) {
        case 1:  return "st";
        case 2:  return "nd";
        case 3:  return "rd";
        default: return "th";
    }
};

export default function Create({ recurring = null, currencies = [], exchangeRates = [] }) {
    const isEdit = !!recurring;

    const { data, setData, post, put, processing, errors } = useForm({
        type: recurring?.type || 'income',
        title: recurring?.title || '',
        description: recurring?.description || '',
        amount: recurring?.amount || '',
        amount_currency: recurring?.amount_currency || 'USD',
        frequency: recurring?.frequency || 'monthly',
        frequency_day: recurring?.frequency_day || 1,
        frequency_month: recurring?.frequency_month || 1,
        starts_at: recurring?.starts_at || format(new Date(), 'yyyy-MM-dd'),
        ends_at: recurring?.ends_at || '',
    });

    const [exchangeRate, setExchangeRate] = useState(1.0);
    const [previewDates, setPreviewDates] = useState([]);

    useEffect(() => {
        if (data.amount_currency === 'USD') {
            setExchangeRate(1.0);
        } else {
            const rateObj = exchangeRates.find(r => r.from_currency === data.amount_currency && r.to_currency === 'USD');
            setExchangeRate(rateObj ? parseFloat(rateObj.rate) : 1.0);
        }
    }, [data.amount_currency, exchangeRates]);

    useEffect(() => {
        calculatePreview();
    }, [data.frequency, data.frequency_day, data.frequency_month, data.starts_at]);

    const calculatePreview = () => {
        if (!data.starts_at || !isValid(parseISO(data.starts_at))) return;

        let dates = [];
        let current = parseISO(data.starts_at);

        for (let i = 0; i < 3; i++) {
            let nextDate = new Date(current);

            if (data.frequency === 'daily') {
                // Do nothing to nextDate, it's already current
            } else if (data.frequency === 'weekly') {
                nextDate = setDay(nextDate, parseInt(data.frequency_day || 0));
                if (nextDate < current) {
                    nextDate = addWeeks(nextDate, 1);
                }
            } else if (data.frequency === 'monthly') {
                nextDate = setDate(nextDate, parseInt(data.frequency_day || 1));
                if (nextDate < current) {
                    nextDate = addMonths(nextDate, 1);
                }
            } else if (data.frequency === 'yearly') {
                nextDate = setMonth(nextDate, parseInt(data.frequency_month || 1) - 1);
                nextDate = setDate(nextDate, parseInt(data.frequency_day || 1));
                if (nextDate < current) {
                    nextDate = addYears(nextDate, 1);
                }
            }

            dates.push(nextDate);

            // Prepare 'current' for the next iteration (must be after the date we just found)
            if (data.frequency === 'daily') current = addDays(nextDate, 1);
            else if (data.frequency === 'weekly') current = addWeeks(nextDate, 1);
            else if (data.frequency === 'monthly') current = addMonths(nextDate, 1);
            else if (data.frequency === 'yearly') current = addYears(nextDate, 1);
        }
        setPreviewDates(dates);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route('erp.recurring.update', recurring.id));
        } else {
            post(route('erp.recurring.store'));
        }
    };

    const daysOfWeek = [
        { label: 'Sun', value: 0 },
        { label: 'Mon', value: 1 },
        { label: 'Tue', value: 2 },
        { label: 'Wed', value: 3 },
        { label: 'Thu', value: 4 },
        { label: 'Fri', value: 5 },
        { label: 'Sat', value: 6 },
    ];

    const monthsList = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    return (
        <AuthenticatedLayout>
            <Head title={isEdit ? 'Edit Recurring Entry' : 'Create Recurring Entry'} />

            <div className="p-4 sm:p-8">
                <div className="max-w-2xl mx-auto">
                    <div className="mb-6">
                        <Link
                            href={route('erp.recurring.index')}
                            className="text-sm text-gray-500 hover:text-gray-700 flex items-center"
                        >
                            <ChevronLeft className="w-4 h-4 mr-1" />
                            Back to Recurring Entries
                        </Link>
                    </div>

                    <PageHeader
                        title={isEdit ? 'Edit Recurring Entry' : 'Create Recurring Entry'}
                        description="Set up a new automatic income or expense entry"
                    />

                    <form onSubmit={handleSubmit} className="space-y-8 mt-8">
                        {/* Section 1 — Basic Info */}
                        <section className="bg-white p-6 rounded-lg border shadow-sm space-y-4">
                            <div className="flex items-center mb-2">
                                <Info className="w-5 h-5 text-indigo-500 mr-2" />
                                <h3 className="text-lg font-medium">Basic Info</h3>
                            </div>

                            <div>
                                <InputLabel value="Entry Type" />
                                <div className="grid grid-cols-2 gap-4 mt-2">
                                    <button
                                        type="button"
                                        onClick={() => setData('type', 'income')}
                                        className={`flex items-center justify-center px-4 py-3 border rounded-md transition-all ${
                                            data.type === 'income'
                                            ? 'border-green-500 bg-green-50 text-green-700 ring-1 ring-green-500'
                                            : 'border-gray-200 hover:border-gray-300'
                                        }`}
                                    >
                                        <TrendingUp className="w-4 h-4 mr-2" />
                                        📈 Income
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setData('type', 'expense')}
                                        className={`flex items-center justify-center px-4 py-3 border rounded-md transition-all ${
                                            data.type === 'expense'
                                            ? 'border-red-500 bg-red-50 text-red-700 ring-1 ring-red-500'
                                            : 'border-gray-200 hover:border-gray-300'
                                        }`}
                                    >
                                        <TrendingDown className="w-4 h-4 mr-2" />
                                        📉 Expense
                                    </button>
                                </div>
                                <InputError message={errors.type} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="title" value="Title" />
                                <TextInput
                                    id="title"
                                    className="mt-1 block w-full"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    placeholder="e.g. Monthly Rent, Client Retainer"
                                    required
                                />
                                <InputError message={errors.title} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="description" value="Description (Optional)" />
                                <textarea
                                    id="description"
                                    className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    rows="3"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Add more details about this entry..."
                                />
                                <InputError message={errors.description} className="mt-2" />
                            </div>
                        </section>

                        {/* Section 2 — Amount */}
                        <section className="bg-white p-6 rounded-lg border shadow-sm space-y-4">
                            <div className="flex items-center mb-2">
                                <DollarSign className="w-5 h-5 text-indigo-500 mr-2" />
                                <h3 className="text-lg font-medium">Amount</h3>
                            </div>

                            <div className="flex gap-4">
                                <div className="flex-1">
                                    <InputLabel htmlFor="amount" value="Amount" />
                                    <TextInput
                                        id="amount"
                                        type="number"
                                        step="0.01"
                                        className="mt-1 block w-full"
                                        value={data.amount}
                                        onChange={(e) => setData('amount', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.amount} className="mt-2" />
                                </div>
                                <div className="w-1/3">
                                    <InputLabel htmlFor="amount_currency" value="Currency" />
                                    <select
                                        id="amount_currency"
                                        className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        value={data.amount_currency}
                                        onChange={(e) => setData('amount_currency', e.target.value)}
                                    >
                                        {currencies.length > 0 ? (
                                            currencies.map(c => (
                                                <option key={c.code} value={c.code}>{c.code}</option>
                                            ))
                                        ) : (
                                            <>
                                                <option value="USD">USD</option>
                                                <option value="EUR">EUR</option>
                                                <option value="GBP">GBP</option>
                                                <option value="IDR">IDR</option>
                                            </>
                                        )}
                                    </select>
                                    <InputError message={errors.amount_currency} className="mt-2" />
                                </div>
                            </div>

                            {data.amount && (
                                <div className="text-sm text-gray-500 bg-gray-50 p-3 rounded-md border border-dashed">
                                    = <CurrencyDisplay amount={data.amount * exchangeRate} currency="USD" /> at today's rate
                                </div>
                            )}
                        </section>

                        {/* Section 3 — Schedule */}
                        <section className="bg-white p-6 rounded-lg border shadow-sm space-y-6">
                            <div className="flex items-center mb-2">
                                <Clock className="w-5 h-5 text-indigo-500 mr-2" />
                                <h3 className="text-lg font-medium">Schedule</h3>
                            </div>

                            <div>
                                <InputLabel value="Frequency" />
                                <div className="flex flex-wrap gap-2 mt-2">
                                    {['daily', 'weekly', 'monthly', 'yearly'].map((freq) => (
                                        <button
                                            key={freq}
                                            type="button"
                                            onClick={() => setData('frequency', freq)}
                                            className={`px-4 py-2 rounded-full text-sm font-medium border transition-all ${
                                                data.frequency === freq
                                                ? 'bg-indigo-600 text-white border-indigo-600'
                                                : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'
                                            }`}
                                        >
                                            {freq.charAt(0).toUpperCase() + freq.slice(1)}
                                        </button>
                                    ))}
                                </div>
                                <InputError message={errors.frequency} className="mt-2" />
                            </div>

                            {/* Conditional Frequency Fields */}
                            <div className="pt-2">
                                {data.frequency === 'weekly' && (
                                    <div className="space-y-3">
                                        <InputLabel value="Run on which day?" />
                                        <div className="flex gap-2">
                                            {daysOfWeek.map((day) => (
                                                <button
                                                    key={day.value}
                                                    type="button"
                                                    onClick={() => setData('frequency_day', day.value)}
                                                    className={`flex-1 py-2 text-xs font-medium border rounded-md transition-all ${
                                                        parseInt(data.frequency_day) === day.value
                                                        ? 'bg-indigo-50 border-indigo-500 text-indigo-700'
                                                        : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'
                                                    }`}
                                                >
                                                    {day.label}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {data.frequency === 'monthly' && (
                                    <div className="max-w-xs">
                                        <InputLabel htmlFor="day_of_month" value="Day of month" />
                                        <TextInput
                                            id="day_of_month"
                                            type="number"
                                            min="1"
                                            max="31"
                                            className="mt-1 block w-full"
                                            value={data.frequency_day}
                                            onChange={(e) => setData('frequency_day', e.target.value)}
                                        />
                                        <p className="mt-2 text-xs text-gray-500">
                                            Runs on the {data.frequency_day || 1}{getOrdinalSuffix(data.frequency_day || 1)} of every month
                                        </p>
                                    </div>
                                )}

                                {data.frequency === 'yearly' && (
                                    <div className="flex gap-4">
                                        <div className="flex-1">
                                            <InputLabel htmlFor="month" value="Month" />
                                            <select
                                                id="month"
                                                className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                                value={data.frequency_month}
                                                onChange={(e) => setData('frequency_month', e.target.value)}
                                            >
                                                {monthsList.map((month, idx) => (
                                                    <option key={idx} value={idx + 1}>{month}</option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="w-1/3">
                                            <InputLabel htmlFor="day" value="Day" />
                                            <TextInput
                                                id="day"
                                                type="number"
                                                min="1"
                                                max="31"
                                                className="mt-1 block w-full"
                                                value={data.frequency_day}
                                                onChange={(e) => setData('frequency_day', e.target.value)}
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel htmlFor="starts_at" value="Start Date" />
                                    <TextInput
                                        id="starts_at"
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.starts_at}
                                        onChange={(e) => setData('starts_at', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.starts_at} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="ends_at" value="End Date (Optional)" />
                                    <TextInput
                                        id="ends_at"
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.ends_at}
                                        onChange={(e) => setData('ends_at', e.target.value)}
                                    />
                                    <InputError message={errors.ends_at} className="mt-2" />
                                </div>
                            </div>
                        </section>

                        {/* Section 4 — Preview */}
                        <section className="bg-gray-50 p-6 rounded-lg border border-dashed space-y-4">
                            <div className="flex items-center">
                                <Calendar className="w-5 h-5 text-gray-500 mr-2" />
                                <h3 className="text-lg font-medium text-gray-700">Preview</h3>
                            </div>

                            <div>
                                <p className="text-sm text-gray-600 mb-3">Next 3 occurrences:</p>
                                <div className="space-y-2">
                                    {previewDates.map((date, idx) => (
                                        <div key={idx} className="flex items-center text-sm font-medium text-gray-900 bg-white p-2 rounded border">
                                            <span className="w-8 text-gray-400">→</span>
                                            {format(date, 'MMMM d, yyyy')}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </section>

                        <div className="flex items-center justify-end gap-4 pt-4 border-t">
                            <Link
                                href={route('erp.recurring.index')}
                                className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton disabled={processing}>
                                {isEdit ? 'Update Recurring Entry' : 'Save Recurring Entry'}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
