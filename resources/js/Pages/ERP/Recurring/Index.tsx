import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Index({ income, expense }: { income: any[], expense: any[] }) {
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
            <h3 className="text-lg font-medium text-gray-900 mb-4">{title}</h3>
            <div className="overflow-x-auto border rounded-lg">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Frequency</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Next Run</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {entries.map((entry) => (
                            <tr key={entry.id}>
                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{entry.amount} {entry.currency}</td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {entry.frequency}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{entry.next_run_date}</td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${entry.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}>
                                        {entry.status}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    {entry.status === 'active' ? (
                                        <button onClick={() => handlePause(entry.id)} className="text-yellow-600 hover:text-yellow-900">Pause</button>
                                    ) : (
                                        <button onClick={() => handleResume(entry.id)} className="text-green-600 hover:text-green-900">Resume</button>
                                    )}
                                    <Link href={route('erp.recurring.edit', entry.id)} className="text-indigo-600 hover:text-indigo-900">Edit</Link>
                                    <Link href={route('erp.recurring.logs', entry.id)} className="text-gray-600 hover:text-gray-900">Logs</Link>
                                    <button onClick={() => handleDelete(entry.id)} className="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        ))}
                        {entries.length === 0 && (
                            <tr>
                                <td colSpan={5} className="px-6 py-4 text-center text-sm text-gray-500">No entries found.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Recurring Entries</h2>}
        >
            <Head title="Recurring Entries" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex justify-end mb-6">
                            <Link
                                href={route('erp.recurring.create')}
                                className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
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
