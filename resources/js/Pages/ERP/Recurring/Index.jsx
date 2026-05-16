import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import PageHeader from '@/Components/PageHeader';
import DataTable from '@/Components/DataTable';
import StatusBadge from '@/Components/StatusBadge';
import CurrencyDisplay from '@/Components/CurrencyDisplay';
import Dropdown from '@/Components/Dropdown';
import { format, formatDistanceToNow, parseISO } from 'date-fns';
import {
    Plus,
    MoreVertical,
    History,
    Pause,
    Play,
    Edit,
    Trash2,
    TrendingUp,
    TrendingDown,
    Calendar,
    Circle
} from 'lucide-react';

const getOrdinalSuffix = (day) => {
    if (day > 3 && day < 21) return 'th';
    switch (day % 10) {
        case 1:  return "st";
        case 2:  return "nd";
        case 3:  return "rd";
        default: return "th";
    }
};

export default function Index({ income, expenses, stats }) {
    const [activeTab, setActiveTab] = useState('income');
    const { post, delete: destroy } = useForm();

    const handlePauseResume = (entry) => {
        const action = entry.status === 'active' ? 'pause' : 'resume';
        post(route(`erp.recurring.${action}`, entry.id), {
            preserveScroll: true
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this recurring entry?')) {
            destroy(route('erp.recurring.destroy', id));
        }
    };

    const columns = [
        {
            header: 'Title',
            cell: (row) => (
                <div>
                    <div className="font-medium text-gray-900">{row.title}</div>
                    {row.description && (
                        <div className="text-xs text-gray-500 truncate max-w-xs">
                            {row.description}
                        </div>
                    )}
                </div>
            )
        },
        {
            header: 'Frequency',
            cell: (row) => {
                let label = row.frequency.charAt(0).toUpperCase() + row.frequency.slice(1);
                let color = 'blue';

                if (row.frequency === 'weekly') {
                    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    label = `Every ${days[row.frequency_day] || 'Monday'}`;
                    color = 'purple';
                } else if (row.frequency === 'monthly') {
                    const day = row.frequency_day || 1;
                    label = `${day}${getOrdinalSuffix(day)} of month`;
                    color = 'indigo';
                } else if (row.frequency === 'yearly') {
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    label = `${months[(row.frequency_month || 1) - 1]} ${row.frequency_day || 1}`;
                    color = 'cyan';
                }

                return <StatusBadge status={label} variant={color} />;
            }
        },
        {
            header: 'Next Run',
            cell: (row) => (
                <div className="text-sm">
                    <div className="text-gray-900">{format(parseISO(row.next_run_at), 'MMM d, yyyy')}</div>
                    <div className="text-xs text-gray-500">
                        in {formatDistanceToNow(parseISO(row.next_run_at))}
                    </div>
                </div>
            )
        },
        {
            header: 'Amount',
            cell: (row) => (
                <CurrencyDisplay
                    amount={row.amount}
                    currency={row.amount_currency}
                    className="font-semibold"
                />
            )
        },
        {
            header: 'Status',
            cell: (row) => (
                <button
                    onClick={() => handlePauseResume(row)}
                    className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-medium transition-colors ${
                        row.status === 'active'
                        ? 'bg-green-100 text-green-800 hover:bg-green-200'
                        : 'bg-gray-100 text-gray-800 hover:bg-gray-200'
                    }`}
                >
                    <Circle className={`w-2 h-2 mr-2 fill-current ${row.status === 'active' ? 'text-green-500' : 'text-gray-400'}`} />
                    {row.status === 'active' ? 'Active' : 'Paused'}
                </button>
            )
        },
        {
            header: 'Actions',
            cell: (row) => (
                <div className="flex items-center space-x-2">
                    <Link
                        href={route('erp.recurring.edit', row.id)}
                        className="p-1 text-gray-400 hover:text-indigo-600 transition-colors"
                        title="Edit"
                    >
                        <Edit className="w-4 h-4" />
                    </Link>
                    <Link
                        href={route('erp.recurring.logs', row.id)}
                        className="p-1 text-gray-400 hover:text-blue-600 transition-colors"
                        title="Execution Logs"
                    >
                        <History className="w-4 h-4" />
                    </Link>
                    <button
                        onClick={() => handleDelete(row.id)}
                        className="p-1 text-gray-400 hover:text-red-600 transition-colors"
                        title="Delete"
                    >
                        <Trash2 className="w-4 h-4" />
                    </button>
                </div>
            )
        }
    ];

    const currentStats = stats[activeTab];

    return (
        <AuthenticatedLayout>
            <Head title="Recurring Entries" />

            <div className="p-4 sm:p-8">
                <PageHeader
                    title="Recurring Entries"
                    description="Manage your scheduled income and expenses"
                >
                    <Link
                        href={route('erp.recurring.create')}
                        className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        <Plus className="w-4 h-4 mr-2" />
                        Create Recurring Entry
                    </Link>
                </PageHeader>

                {/* Tab Bar */}
                <div className="flex border-b border-gray-200 mb-6">
                    <button
                        onClick={() => setActiveTab('income')}
                        className={`py-2 px-4 text-sm font-medium border-b-2 transition-colors ${
                            activeTab === 'income'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        }`}
                    >
                        Income
                    </button>
                    <button
                        onClick={() => setActiveTab('expense')}
                        className={`py-2 px-4 text-sm font-medium border-b-2 transition-colors ${
                            activeTab === 'expense'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        }`}
                    >
                        Expenses
                    </button>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                    <div className="bg-white p-6 rounded-lg border shadow-sm flex items-center">
                        <div className={`p-3 rounded-full mr-4 ${activeTab === 'income' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'}`}>
                            {activeTab === 'income' ? <TrendingUp /> : <TrendingDown />}
                        </div>
                        <div>
                            <p className="text-sm text-gray-500 font-medium">
                                Total monthly recurring {activeTab === 'income' ? 'income' : 'expenses'}
                            </p>
                            <p className="text-2xl font-bold">
                                <CurrencyDisplay amount={currentStats.totalMonthly} />
                            </p>
                        </div>
                    </div>
                    <div className="bg-white p-6 rounded-lg border shadow-sm flex items-center">
                        <div className="p-3 rounded-full mr-4 bg-blue-100 text-blue-600">
                            <Calendar />
                        </div>
                        <div>
                            <p className="text-sm text-gray-500 font-medium">Next 7 days scheduled</p>
                            <p className="text-2xl font-bold">{currentStats.next7Days} entries</p>
                        </div>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    data={activeTab === 'income' ? income : expenses}
                />
            </div>
        </AuthenticatedLayout>
    );
}
