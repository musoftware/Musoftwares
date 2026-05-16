import React from 'react';
import { Head } from '@inertiajs/react';

export default function Show({ client, wallets }) {
    const handleLoginAsClient = () => {
        // Implement impersonation logic later, mock for now
        alert(`Impersonating client: ${client.name}`);
    };

    const formatCurrency = (amount, currency = 'USD') => {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(amount || 0);
    };

    return (
        <div className="p-6 max-w-5xl mx-auto">
            <Head title={`Client Profile: ${client.name}`} />

            <div className="flex justify-between items-center mb-6">
                <h1 className="text-3xl font-bold">Client Profile</h1>
                <button
                    onClick={handleLoginAsClient}
                    className="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition"
                >
                    Login as client
                </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                {/* Profile Details */}
                <div className="col-span-1 bg-white p-6 rounded shadow">
                    <h2 className="text-xl font-bold mb-4 border-b pb-2">Details</h2>
                    <div className="space-y-3">
                        <div>
                            <span className="text-gray-500 text-sm block">Name</span>
                            <span className="font-medium">{client.name}</span>
                        </div>
                        <div>
                            <span className="text-gray-500 text-sm block">Email</span>
                            <span className="font-medium">{client.email}</span>
                        </div>
                        <div>
                            <span className="text-gray-500 text-sm block">Phone</span>
                            <span className="font-medium">{client.phone || 'N/A'}</span>
                        </div>
                        <div>
                            <span className="text-gray-500 text-sm block">Joined</span>
                            <span className="font-medium">{new Date(client.created_at).toLocaleDateString()}</span>
                        </div>
                    </div>
                </div>

                {/* Wallets and History */}
                <div className="col-span-2 space-y-6">

                    {/* Support Tickets */}
                    <div className="bg-white p-6 rounded shadow">
                        <h2 className="text-xl font-bold mb-4 border-b pb-2">Support Tickets</h2>
                        {client.support_tickets && client.support_tickets.length > 0 ? (
                            <ul className="space-y-3">
                                {client.support_tickets.map(ticket => (
                                    <li key={ticket.id} className="border-b pb-2 flex justify-between">
                                        <span>{ticket.subject}</span>
                                        <span className={`px-2 py-1 text-xs rounded ${ticket.status === 'open' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                            {ticket.status}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-gray-500 text-sm">No support tickets found.</p>
                        )}
                    </div>

                    {/* Wallets & Transactions */}
                    {wallets.map((wallet) => (
                        <div key={wallet.id} className="bg-white p-6 rounded shadow">
                            <div className="flex justify-between items-end mb-4 border-b pb-2">
                                <h2 className="text-xl font-bold">Wallet ({wallet.context})</h2>
                                <span className="text-2xl font-bold text-green-600">
                                    {formatCurrency(wallet.balance, wallet.currency)}
                                </span>
                            </div>

                            <h3 className="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Transaction History</h3>
                            {wallet.transactions && wallet.transactions.length > 0 ? (
                                <table className="w-full text-sm text-left">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="p-2">Date</th>
                                            <th className="p-2">Description</th>
                                            <th className="p-2 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {wallet.transactions.map(tx => (
                                            <tr key={tx.id} className="border-t">
                                                <td className="p-2">{new Date(tx.created_at).toLocaleString()}</td>
                                                <td className="p-2">{tx.description}</td>
                                                <td className={`p-2 text-right font-medium ${tx.type === 'credit' ? 'text-green-600' : 'text-red-600'}`}>
                                                    {tx.type === 'credit' ? '+' : '-'}{formatCurrency(tx.amount, wallet.currency)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            ) : (
                                <p className="text-gray-500 text-sm">No transactions found.</p>
                            )}
                        </div>
                    ))}

                </div>
            </div>
        </div>
    );
}
