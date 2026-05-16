import { useForm } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { format, addDays, addWeeks, addMonths, addYears, nextDay, parseISO, set } from 'date-fns';
import { useState, useEffect } from 'react';
import { TrendingUp, TrendingDown, Calendar as CalendarIcon } from 'lucide-react';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';

export default function RecurringForm({ entry = null, business_currency = 'USD' }) {
    const isEditing = !!entry;

    const { data, setData, post, put, processing, errors } = useForm({
        type: entry?.type || 'income',
        title: entry?.title || '',
        description: entry?.description || '',
        amount: entry?.amount || '',
        amount_currency: entry?.amount_currency || business_currency,
        frequency: entry?.frequency || 'monthly',
        frequency_day: entry?.frequency_day || 1,
        frequency_month: entry?.frequency_month || 1,
        starts_at: entry?.starts_at ? format(new Date(entry.starts_at), 'yyyy-MM-dd') : format(new Date(), 'yyyy-MM-dd'),
        ends_at: entry?.ends_at ? format(new Date(entry.ends_at), 'yyyy-MM-dd') : '',
        status: entry?.status || 'active',
        no_end_date: !entry?.ends_at,
    });

    const [occurrences, setOccurrences] = useState([]);

    useEffect(() => {
        calculateNextOccurrences();
    }, [data.frequency, data.frequency_day, data.frequency_month, data.starts_at]);

    const calculateNextOccurrences = () => {
        try {
            const start = parseISO(data.starts_at);
            let next = new Date(start);
            const results = [];

            for (let i = 0; i < 3; i++) {
                if (data.frequency === 'daily') {
                    if (i > 0) next = addDays(next, 1);
                } else if (data.frequency === 'weekly') {
                    const targetDay = parseInt(data.frequency_day);
                    if (i === 0) {
                        const currentDay = next.getDay();
                        if (currentDay !== targetDay) {
                            next = addDays(next, (targetDay + 7 - currentDay) % 7);
                        }
                    } else {
                        next = addWeeks(next, 1);
                    }
                } else if (data.frequency === 'monthly') {
                    const targetDay = parseInt(data.frequency_day);
                    if (i === 0) {
                        next.setDate(targetDay);
                        if (next < start) {
                            next = addMonths(next, 1);
                        }
                    } else {
                        next = addMonths(next, 1);
                    }
                } else if (data.frequency === 'yearly') {
                    const targetMonth = parseInt(data.frequency_month) - 1;
                    const targetDay = parseInt(data.frequency_day);
                    if (i === 0) {
                        next.setMonth(targetMonth);
                        next.setDate(targetDay);
                        if (next < start) {
                            next = addYears(next, 1);
                        }
                    } else {
                        next = addYears(next, 1);
                    }
                }
                results.push(new Date(next));
            }
            setOccurrences(results);
        } catch (e) {
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const submitData = { ...data };
        if (data.no_end_date) {
            submitData.ends_at = null;
        }

        if (isEditing) {
            put(route('erp.recurring.update', entry.id));
        } else {
            post(route('erp.recurring.store'));
        }
    };

    const frequencies = [
        { id: 'daily', label: 'Daily' },
        { id: 'weekly', label: 'Weekly' },
        { id: 'monthly', label: 'Monthly' },
        { id: 'yearly', label: 'Yearly' },
    ];

    const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    return (
        <form onSubmit={handleSubmit} className="max-w-2xl mx-auto space-y-8">
            <div className="bg-white p-6 shadow sm:rounded-lg">
                <h3 className="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Section 1 — Basic Info</h3>
                <div className="space-y-4">
                    <div>
                        <InputLabel value="Type" />
                        <div className="flex mt-1 space-x-4">
                            <button
                                type="button"
                                onClick={() => setData('type', 'income')}
                                className={`flex-1 py-2 px-4 border rounded-md flex items-center justify-center ${data.type === 'income' ? 'bg-indigo-50 border-indigo-500 text-indigo-700' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'}`}
                            >
                                <TrendingUp className="w-4 h-4 mr-2" /> Income
                            </button>
                            <button
                                type="button"
                                onClick={() => setData('type', 'expense')}
                                className={`flex-1 py-2 px-4 border rounded-md flex items-center justify-center ${data.type === 'expense' ? 'bg-indigo-50 border-indigo-500 text-indigo-700' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'}`}
                            >
                                <TrendingDown className="w-4 h-4 mr-2" /> Expense
                            </button>
                        </div>
                        <InputError message={errors.type} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="title" value="Title" />
                        <TextInput
                            id="title"
                            type="text"
                            className="mt-1 block w-full"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            placeholder="e.g. Monthly Rent, Client Subscription"
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
                        />
                        <InputError message={errors.description} className="mt-2" />
                    </div>
                </div>
            </div>

            <div className="bg-white p-6 shadow sm:rounded-lg">
                <h3 className="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Section 2 — Amount</h3>
                <div className="grid grid-cols-3 gap-4">
                    <div className="col-span-2">
                        <InputLabel htmlFor="amount" value="Amount" />
                        <TextInput
                            id="amount"
                            type="number"
                            step="0.01"
                            className="mt-1 block w-full"
                            value={data.amount}
                            onChange={(e) => setData('amount', e.target.value)}
                        />
                        <InputError message={errors.amount} className="mt-2" />
                    </div>
                    <div>
                        <InputLabel htmlFor="amount_currency" value="Currency" />
                        <TextInput
                            id="amount_currency"
                            type="text"
                            className="mt-1 block w-full"
                            value={data.amount_currency}
                            onChange={(e) => setData('amount_currency', e.target.value.toUpperCase())}
                        />
                        <InputError message={errors.amount_currency} className="mt-2" />
                    </div>
                </div>
                {data.amount && data.amount_currency !== business_currency && (
                    <div className="mt-4 text-sm text-gray-500 flex items-center">
                        ≈ <span className="mx-1 font-medium"><CurrencyDisplay amount={data.amount} currency={data.amount_currency} /></span> at today's rate
                    </div>
                )}
            </div>

            <div className="bg-white p-6 shadow sm:rounded-lg">
                <h3 className="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Section 3 — Schedule</h3>
                <div className="space-y-6">
                    <div>
                        <InputLabel value="Frequency" />
                        <div className="flex mt-1 space-x-2">
                            {frequencies.map((freq) => (
                                <button
                                    key={freq.id}
                                    type="button"
                                    onClick={() => setData('frequency', freq.id)}
                                    className={`flex-1 py-2 px-3 border rounded-full text-sm font-medium ${data.frequency === freq.id ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'}`}
                                >
                                    {freq.label}
                                </button>
                            ))}
                        </div>
                        <InputError message={errors.frequency} className="mt-2" />
                    </div>

                    {data.frequency === 'weekly' && (
                        <div>
                            <InputLabel value="Day of week" />
                            <div className="flex mt-1 space-x-2">
                                {weekDays.map((day, idx) => (
                                    <button
                                        key={day}
                                        type="button"
                                        onClick={() => setData('frequency_day', idx)}
                                        className={`flex-1 py-2 border rounded-md text-xs font-medium ${parseInt(data.frequency_day) === idx ? 'bg-indigo-100 border-indigo-500 text-indigo-700' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'}`}
                                    >
                                        {day}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    {data.frequency === 'monthly' && (
                        <div>
                            <InputLabel htmlFor="frequency_day_monthly" value="Day of month" />
                            <TextInput
                                id="frequency_day_monthly"
                                type="number"
                                min="1"
                                max="31"
                                className="mt-1 block w-full"
                                value={data.frequency_day}
                                onChange={(e) => setData('frequency_day', e.target.value)}
                            />
                            <p className="mt-1 text-xs text-gray-500">Runs on the {data.frequency_day || 'X'}{getOrdinal(data.frequency_day)} of every month</p>
                        </div>
                    )}

                    {data.frequency === 'yearly' && (
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel htmlFor="frequency_month" value="Month" />
                                <select
                                    id="frequency_month"
                                    className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    value={data.frequency_month}
                                    onChange={(e) => setData('frequency_month', e.target.value)}
                                >
                                    {months.map((m, idx) => (
                                        <option key={m} value={idx + 1}>{m}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <InputLabel htmlFor="frequency_day_yearly" value="Day" />
                                <TextInput
                                    id="frequency_day_yearly"
                                    type="number"
                                    min="1"
                                    max="31"
                                    className="mt-1 block w-full"
                                    value={data.frequency_day}
                                    onChange={(e) => setData('frequency_day', e.target.value)}
                                />
                            </div>
                            <p className="col-span-2 mt-1 text-xs text-gray-500">
                                Runs on {months[data.frequency_month - 1]} {data.frequency_day} every year
                            </p>
                        </div>
                    )}

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel htmlFor="starts_at" value="Start Date" />
                            <TextInput
                                id="starts_at"
                                type="date"
                                className="mt-1 block w-full"
                                value={data.starts_at}
                                onChange={(e) => setData('starts_at', e.target.value)}
                            />
                            <InputError message={errors.starts_at} className="mt-2" />
                        </div>
                        <div>
                            <div className="flex items-center justify-between">
                                <InputLabel htmlFor="ends_at" value="End Date" />
                                <label className="flex items-center text-xs text-gray-500 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 mr-1"
                                        checked={data.no_end_date}
                                        onChange={(e) => setData('no_end_date', e.target.checked)}
                                    />
                                    No end date
                                </label>
                            </div>
                            <TextInput
                                id="ends_at"
                                type="date"
                                className={`mt-1 block w-full ${data.no_end_date ? 'bg-gray-50 text-gray-400' : ''}`}
                                value={data.ends_at}
                                onChange={(e) => setData('ends_at', e.target.value)}
                                disabled={data.no_end_date}
                            />
                            <InputError message={errors.ends_at} className="mt-2" />
                        </div>
                    </div>
                </div>
            </div>

            <div className="bg-gray-50 p-6 border-2 border-dashed border-gray-300 rounded-lg">
                <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                    <CalendarIcon className="w-5 h-5 mr-2 text-indigo-500" />
                    Section 4 — Preview
                </h3>
                <div className="space-y-2">
                    <p className="text-sm font-medium text-gray-700">Next 3 occurrences:</p>
                    <ul className="space-y-1">
                        {occurrences.map((date, idx) => (
                            <li key={idx} className="text-sm text-gray-600 flex items-center">
                                <span className="text-indigo-400 mr-2">→</span>
                                {format(date, 'MMMM d, yyyy')}
                            </li>
                        ))}
                    </ul>
                </div>
            </div>

            <div className="flex items-center justify-between pt-4">
                <SecondaryButton onClick={() => window.history.back()}>
                    Cancel
                </SecondaryButton>
                <PrimaryButton disabled={processing}>
                    {isEditing ? 'Update Recurring Entry' : 'Save Recurring Entry'}
                </PrimaryButton>
            </div>
        </form>
    );
}

const getOrdinal = (n) => {
    const s = ["th", "st", "nd", "rd"];
    const v = n % 100;
    return s[(v - 20) % 10] || s[v] || s[0];
};
