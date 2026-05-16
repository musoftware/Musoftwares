import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Index({
    income,
    expense,
}: {
    income: any[];
    expense: any[];
}) {
    const { post, delete: destroy } = useForm();

    const handlePause = (id: number) => {
        post(route('erp.recurring.pause', id));
    };

    const handleResume = (id: number) => {
        post(route('erp.recurring.resume', id));
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this recurring entry?')) {
            destroy(route('erp.recurring.destroy', id));
        }
    };

    const renderTable = (entries: any[], title: string) => (
        <div className="mb-8">
            <h3 className="mb-4 text-lg font-medium text-gray-900">{title}</h3>
            <div className="overflow-x-auto rounded-lg border">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                Amount
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                Frequency
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                Next Run
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                Status
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {entries.map((entry) => (
                            <tr key={entry.id}>
                                <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900">
                                    {entry.amount} {entry.currency}
                                </td>
                                <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                    <span className="inline-flex rounded-full bg-blue-100 px-2 text-xs leading-5 font-semibold text-blue-800">
                                        {entry.frequency}
                                    </span>
                                </td>
                                <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                    {entry.next_run_date}
                                </td>
                                <td className="px-6 py-4 text-sm whitespace-nowrap">
                                    <span
                                        className={`inline-flex rounded-full px-2 text-xs leading-5 font-semibold ${entry.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}
                                    >
                                        {entry.status}
                                    </span>
                                </td>
                                <td className="space-x-2 px-6 py-4 text-sm font-medium whitespace-nowrap">
                                    {entry.status === 'active' ? (
                                        <button
                                            onClick={() =>
                                                handlePause(entry.id)
                                            }
                                            className="text-yellow-600 hover:text-yellow-900"
                                        >
                                            Pause
                                        </button>
                                    ) : (
                                        <button
                                            onClick={() =>
                                                handleResume(entry.id)
                                            }
                                            className="text-green-600 hover:text-green-900"
                                        >
                                            Resume
                                        </button>
                                    )}
                                    <Link
                                        href={route(
                                            'erp.recurring.edit',
                                            entry.id,
                                        )}
                                        className="text-indigo-600 hover:text-indigo-900"
                                    >
                                        Edit
                                    </Link>
                                    <Link
                                        href={route(
                                            'erp.recurring.logs',
                                            entry.id,
                                        )}
                                        className="text-gray-600 hover:text-gray-900"
                                    >
                                        Logs
                                    </Link>
                                    <button
                                        onClick={() => handleDelete(entry.id)}
                                        className="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                        {entries.length === 0 && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="px-6 py-4 text-center text-sm text-gray-500"
                                >
                                    No entries found.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl leading-tight font-semibold text-gray-800">
                    Recurring Entries
                </h2>
            }
        >
            <Head title="Recurring Entries" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <div className="mb-6 flex justify-end">
                            <Link
                                href={route('erp.recurring.create')}
                                className="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase hover:bg-indigo-700"
                            >
                                Create Recurring Entry
                            </Link>
                        </div>

                        {renderTable(income, 'Income')}
                        {renderTable(expense, 'Expense')}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
