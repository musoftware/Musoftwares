import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageHeader } from '@/Components/ui/PageHeader';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import {
    Calendar,
    MoreHorizontal,
    Pause,
    Play,
    History,
    Edit2,
    Trash2,
    TrendingUp,
    TrendingDown,
    Clock
} from 'lucide-react';
import { useState } from 'react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger
} from '@/Components/ui/dropdown-menu.tsx';
import { format, formatDistanceToNow, isAfter, isBefore, addDays } from 'date-fns';

export default function Index({ income, expense, stats }) {
    const [activeTab, setActiveTab] = useState('income');
    const { post, delete: destroy } = useForm();

    const entries = activeTab === 'income' ? income : expense;
    const tabStats = activeTab === 'income' ? stats.income : stats.expense;

    const handlePauseResume = (entry) => {
        const action = entry.status === 'active' ? 'pause' : 'resume';
        post(route(`erp.recurring.${action}`, entry.id), {
            preserveScroll: true,
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this recurring entry?')) {
            destroy(route('erp.recurring.destroy', id));
        }
    };

    const getFrequencyLabel = (entry) => {
        switch (entry.frequency) {
            case 'daily':
                return { label: 'Daily', className: 'bg-blue-100 text-blue-800' };
            case 'weekly':
                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                return { label: `Every ${days[entry.frequency_day]}`, className: 'bg-purple-100 text-purple-800' };
            case 'monthly':
                return { label: `${entry.frequency_day}${getOrdinal(entry.frequency_day)} of month`, className: 'bg-indigo-100 text-indigo-800' };
            case 'yearly':
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                return { label: `${months[entry.frequency_month - 1]} ${entry.frequency_day}`, className: 'bg-cyan-100 text-cyan-800' };
            default:
                return { label: entry.frequency, className: 'bg-gray-100 text-gray-800' };
        }
    };

    const getOrdinal = (n) => {
        const s = ["th", "st", "nd", "rd"];
        const v = n % 100;
        return s[(v - 20) % 10] || s[v] || s[0];
    };

    const businessCurrency = stats.business_currency || 'USD';
    const { menuItems, workspaceName, tenantId } = useERPMenu('invoices');

    return (
        <ERPLayout title="Recurring Entries" workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems}>

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <PageHeader
                        title="Recurring Entries"
                        subtitle="Manage your automated income and expenses"
                    >
                        <Link
                            href={route('erp.recurring.create')}
                            className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150"
                        >
                            Create Entry
                        </Link>
                    </PageHeader>

                    {/* Tab Bar */}
                    <div className="mb-6 border-b border-gray-200">
                        <nav className="-mb-px flex space-x-8" aria-label="Tabs">
                            <button
                                onClick={() => setActiveTab('income')}
                                className={`
                                    whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center
                                    ${activeTab === 'income'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'}
                                `}
                            >
                                <TrendingUp className="w-4 h-4 mr-2" />
                                Income
                            </button>
                            <button
                                onClick={() => setActiveTab('expense')}
                                className={`
                                    whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center
                                    ${activeTab === 'expense'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'}
                                `}
                            >
                                <TrendingDown className="w-4 h-4 mr-2" />
                                Expenses
                            </button>
                        </nav>
                    </div>

                    {/* Stats */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 mb-8">
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <div className={`p-3 rounded-md ${activeTab === 'income' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'}`}>
                                            {activeTab === 'income' ? <TrendingUp /> : <TrendingDown />}
                                        </div>
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total monthly recurring {activeTab}
                                            </dt>
                                            <dd className="flex items-baseline">
                                                <div className="text-2xl font-semibold text-gray-900">
                                                    <CurrencyDisplay amount={tabStats.total_monthly} currency={businessCurrency} />
                                                </div>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <div className="p-3 rounded-md bg-indigo-100 text-indigo-600">
                                            <Clock />
                                        </div>
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Next 7 days scheduled
                                            </dt>
                                            <dd className="flex items-baseline">
                                                <div className="text-2xl font-semibold text-gray-900">
                                                    {tabStats.next_7_days} entries
                                                </div>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Table */}
                    <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frequency</th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Run</th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" className="relative px-6 py-3">
                                        <span className="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {entries.map((entry) => {
                                    const freq = getFrequencyLabel(entry);
                                    const nextRun = new Date(entry.next_run_at);

                                    return (
                                        <tr key={entry.id}>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm font-medium text-gray-900">{entry.title}</div>
                                                {entry.description && (
                                                    <div className="text-sm text-gray-500 truncate max-w-xs">{entry.description}</div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${freq.className}`}>
                                                    {freq.label}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div>{format(nextRun, 'MMM d, yyyy')}</div>
                                                <div className="text-xs text-gray-400">
                                                    {isBefore(nextRun, new Date()) ? 'Overdue' : `in ${formatDistanceToNow(nextRun)}`}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <CurrencyDisplay amount={entry.amount} currency={entry.amount_currency} />
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <button onClick={() => handlePauseResume(entry)}>
                                                    <StatusBadge status={entry.status === 'active' ? 'Active' : 'Paused'} />
                                                </button>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger className="text-gray-400 hover:text-gray-600">
                                                        <MoreHorizontal className="w-5 h-5" />
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem asChild>
                                                            <Link href={route('erp.recurring.edit', entry.id)} className="flex items-center w-full">
                                                                <Edit2 className="w-4 h-4 mr-2" /> Edit
                                                            </Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem onClick={() => handlePauseResume(entry)} className="flex items-center w-full cursor-pointer">
                                                            {entry.status === 'active' ? (
                                                                <><Pause className="w-4 h-4 mr-2" /> Pause</>
                                                            ) : (
                                                                <><Play className="w-4 h-4 mr-2" /> Resume</>
                                                            )}
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem asChild>
                                                            <Link href={route('erp.recurring.logs', entry.id)} className="flex items-center w-full">
                                                                <History className="w-4 h-4 mr-2" /> Logs
                                                            </Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            className="flex items-center w-full text-red-600 focus:text-red-600 cursor-pointer"
                                                            onClick={() => handleDelete(entry.id)}
                                                        >
                                                            <Trash2 className="w-4 h-4 mr-2" /> Delete
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </td>
                                        </tr>
                                    );
                                })}
                                {entries.length === 0 && (
                                    <tr>
                                        <td colSpan="6" className="px-6 py-12 text-center text-sm text-gray-500">
                                            No recurring {activeTab} entries found.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
