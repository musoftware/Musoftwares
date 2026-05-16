import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        type: 'income',
        amount: '',
        currency: 'USD',
        frequency: 'monthly',
        start_date: new Date().toISOString().split('T')[0],
        end_date: '',
        day_of_week: '',
        day_of_month: '1',
        month_of_year: ''
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('erp.recurring.store'));
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Create Recurring Entry</h2>}
        >
            <Head title="Create Recurring Entry" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <form onSubmit={handleSubmit} className="space-y-6 max-w-2xl">
                            <div>
                                <InputLabel htmlFor="type" value="Type" />
                                <select
                                    id="type"
                                    className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    value={data.type}
                                    onChange={e => setData('type', e.target.value)}
                                >
                                    <option value="income">Income</option>
                                    <option value="expense">Expense</option>
                                </select>
                                {errors.type && <div className="text-red-600 mt-1 text-sm">{errors.type}</div>}
                            </div>

                            <div className="flex space-x-4">
                                <div className="flex-1">
                                    <InputLabel htmlFor="amount" value="Amount" />
                                    <TextInput
                                        id="amount"
                                        type="number"
                                        step="0.01"
                                        className="mt-1 block w-full"
                                        value={data.amount}
                                        onChange={e => setData('amount', e.target.value)}
                                    />
                                    {errors.amount && <div className="text-red-600 mt-1 text-sm">{errors.amount}</div>}
                                </div>
                                <div className="w-1/3">
                                    <InputLabel htmlFor="currency" value="Currency" />
                                    <TextInput
                                        id="currency"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.currency}
                                        onChange={e => setData('currency', e.target.value)}
                                    />
                                    {errors.currency && <div className="text-red-600 mt-1 text-sm">{errors.currency}</div>}
                                </div>
                            </div>

                            <div>
                                <InputLabel htmlFor="frequency" value="Frequency" />
                                <select
                                    id="frequency"
                                    className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    value={data.frequency}
                                    onChange={e => setData('frequency', e.target.value)}
                                >
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                                {errors.frequency && <div className="text-red-600 mt-1 text-sm">{errors.frequency}</div>}
                            </div>

                            {data.frequency === 'weekly' && (
                                <div>
                                    <InputLabel htmlFor="day_of_week" value="Day of Week (0 = Sunday, 6 = Saturday)" />
                                    <TextInput
                                        id="day_of_week"
                                        type="number"
                                        min="0"
                                        max="6"
                                        className="mt-1 block w-full"
                                        value={data.day_of_week}
                                        onChange={e => setData('day_of_week', e.target.value)}
                                    />
                                </div>
                            )}

                            {data.frequency === 'monthly' && (
                                <div>
                                    <InputLabel htmlFor="day_of_month" value="Day of Month (1-31)" />
                                    <TextInput
                                        id="day_of_month"
                                        type="number"
                                        min="1"
                                        max="31"
                                        className="mt-1 block w-full"
                                        value={data.day_of_month}
                                        onChange={e => setData('day_of_month', e.target.value)}
                                    />
                                </div>
                            )}

                            {data.frequency === 'yearly' && (
                                <div className="flex space-x-4">
                                    <div className="flex-1">
                                        <InputLabel htmlFor="month_of_year" value="Month (1-12)" />
                                        <TextInput
                                            id="month_of_year"
                                            type="number"
                                            min="1"
                                            max="12"
                                            className="mt-1 block w-full"
                                            value={data.month_of_year}
                                            onChange={e => setData('month_of_year', e.target.value)}
                                        />
                                    </div>
                                    <div className="flex-1">
                                        <InputLabel htmlFor="day_of_month_yearly" value="Day of Month" />
                                        <TextInput
                                            id="day_of_month_yearly"
                                            type="number"
                                            min="1"
                                            max="31"
                                            className="mt-1 block w-full"
                                            value={data.day_of_month}
                                            onChange={e => setData('day_of_month', e.target.value)}
                                        />
                                    </div>
                                </div>
                            )}

                            <div className="flex space-x-4">
                                <div className="flex-1">
                                    <InputLabel htmlFor="start_date" value="Start Date" />
                                    <TextInput
                                        id="start_date"
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.start_date}
                                        onChange={e => setData('start_date', e.target.value)}
                                    />
                                    {errors.start_date && <div className="text-red-600 mt-1 text-sm">{errors.start_date}</div>}
                                </div>
                                <div className="flex-1">
                                    <InputLabel htmlFor="end_date" value="End Date (Optional)" />
                                    <TextInput
                                        id="end_date"
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.end_date}
                                        onChange={e => setData('end_date', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="flex items-center justify-end">
                                <PrimaryButton disabled={processing}>Create Entry</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
