import React from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function JournalEntriesIndex({ entries }: { entries: any[] }) {
    
    const postEntry = (id: string) => {
        if (confirm('Are you sure you want to post this journal entry to the ledger?')) {
            router.post(route('erp.accounting.journal-entries.post', id));
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Journal Entries" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex justify-between items-center mb-6">
                            <h2 className="text-2xl font-semibold">Journal Entries</h2>
                            <button className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                New Manual Entry
                            </button>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entry #</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lines (Debit/Credit)</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {entries.map(entry => (
                                        <tr key={entry.id}>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{entry.entry_number}</td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{entry.entry_date}</td>
                                            <td className="px-6 py-4 text-sm text-gray-900">{entry.description}</td>
                                            <td className="px-6 py-4 text-sm text-gray-500">
                                                <ul className="list-disc pl-4">
                                                    {entry.lines.map((line: any) => (
                                                        <li key={line.id}>
                                                            {line.chart_of_account?.name} : 
                                                            <span className="text-green-600 ml-1">Dr {line.debit > 0 ? line.debit : ''}</span>
                                                            <span className="text-red-600 ml-1">Cr {line.credit > 0 ? line.credit : ''}</span>
                                                        </li>
                                                    ))}
                                                </ul>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${entry.status === 'posted' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}>
                                                    {entry.status.toUpperCase()}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                {entry.status === 'draft' && (
                                                    <button onClick={() => postEntry(entry.id)} className="text-blue-600 hover:text-blue-900 font-bold">
                                                        Post to Ledger
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                    {entries.length === 0 && (
                                        <tr>
                                            <td colSpan={6} className="px-6 py-4 text-center text-sm text-gray-500">
                                                No journal entries found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
