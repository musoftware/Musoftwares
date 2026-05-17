import React from 'react';
import { Head } from '@inertiajs/react';
import { Copy, Mail, MessageCircle } from 'lucide-react';

export default function Show({ client, wallets }) {
    const handleLoginAsClient = () => {
        // Implement impersonation logic later, mock for now
        alert(`Impersonating client: ${client.name}`);
    };

    const formatCurrency = (amount, currency = 'USD') => {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(amount || 0);
    };

    const referralCode = client.referral_code || `${client.name.toUpperCase().replace(/\s+/g, '').substring(0, 5)}2024`;
    const referralLink = `${window.location.origin}/ref/${referralCode}`;

    const copyToClipboard = (text) => {
        navigator.clipboard.writeText(text);
        alert('Copied to clipboard!');
    };

    return (
        <div className="p-6 max-w-5xl mx-auto">
            <Head title={`Client Profile: ${client.name}`} />

            <div className="flex justify-between items-center mb-6">
                <h1 className="text-3xl font-bold font-sora">Client Profile</h1>
                <button
                    onClick={handleLoginAsClient}
                    className="bg-indigo-600 text-white px-4 py-2 rounded-[8px] hover:bg-indigo-700 transition shadow-sm"
                >
                    Login as client
                </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                {/* Profile Details */}
                <div className="col-span-1 space-y-6">
                    <div className="bg-white p-6 rounded-[12px] shadow-sm border border-gray-100">
                        <h2 className="text-xl font-bold font-sora mb-4 border-b pb-2">Details</h2>
                        <div className="space-y-3 text-sm">
                            <div>
                                <span className="text-gray-500 block mb-1">Name</span>
                                <span className="font-medium text-gray-900">{client.name}</span>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">Email</span>
                                <span className="font-medium text-gray-900">{client.email}</span>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">Phone</span>
                                <span className="font-medium text-gray-900">{client.phone || 'N/A'}</span>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">Joined</span>
                                <span className="font-medium text-gray-900">{new Date(client.created_at).toLocaleDateString()}</span>
                            </div>
                        </div>
                    </div>

                    {/* Referral Section */}
                    <div className="bg-white p-6 rounded-[12px] shadow-sm border border-gray-100">
                        <h2 className="text-xl font-bold font-sora mb-4 border-b pb-2">Referral Program</h2>

                        <div className="space-y-4">
                            <div>
                                <span className="text-sm text-gray-500 block mb-1">Referral Code:</span>
                                <div className="flex items-center space-x-2">
                                    <span className="font-jetbrains text-indigo-600 bg-indigo-50 px-2 py-1 rounded font-bold tracking-wider">{referralCode}</span>
                                    <button onClick={() => copyToClipboard(referralCode)} className="text-gray-400 hover:text-indigo-600">
                                        <Copy size={16} />
                                    </button>
                                </div>
                            </div>

                            <div>
                                <span className="text-sm text-gray-500 block mb-1">Shareable Link:</span>
                                <div className="flex items-center space-x-2">
                                    <input
                                        type="text"
                                        readOnly
                                        value={referralLink}
                                        className="text-xs border-gray-300 rounded-[4px] w-full bg-gray-50"
                                    />
                                    <button onClick={() => copyToClipboard(referralLink)} className="text-gray-400 hover:text-indigo-600">
                                        <Copy size={16} />
                                    </button>
                                </div>
                            </div>

                            <div className="flex space-x-2 pt-2">
                                <button onClick={() => copyToClipboard(referralLink)} className="flex-1 flex items-center justify-center space-x-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-1.5 rounded-[4px] text-xs transition">
                                    <Copy size={14} /> <span>Copy</span>
                                </button>
                                <a href={`mailto:?subject=Join me&body=Use my referral link: ${referralLink}`} className="flex-1 flex items-center justify-center space-x-1 bg-blue-50 hover:bg-blue-100 text-blue-600 py-1.5 rounded-[4px] text-xs transition">
                                    <Mail size={14} /> <span>Email</span>
                                </a>
                                <a href={`https://wa.me/?text=Join me using my referral link: ${referralLink}`} target="_blank" rel="noopener noreferrer" className="flex-1 flex items-center justify-center space-x-1 bg-green-50 hover:bg-green-100 text-green-600 py-1.5 rounded-[4px] text-xs transition">
                                    <MessageCircle size={14} /> <span>WhatsApp</span>
                                </a>
                            </div>

                            <div className="pt-4 border-t mt-4">
                                <div className="flex justify-between text-sm mb-1">
                                    <span className="text-gray-500">Referred Clients:</span>
                                    <span className="font-bold">3</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-500">Total Earned:</span>
                                    <span className="font-bold text-green-600 font-jetbrains">$75.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Wallets and History */}
                <div className="col-span-2 space-y-6">

                    {/* Support Tickets */}
                    <div className="bg-white p-6 rounded-[12px] shadow-sm border border-gray-100">
                        <h2 className="text-xl font-bold font-sora mb-4 border-b pb-2">Support Tickets</h2>
                        {client.support_tickets && client.support_tickets.length > 0 ? (
                            <ul className="space-y-3">
                                {client.support_tickets.map(ticket => (
                                    <li key={ticket.id} className="border-b pb-2 flex justify-between">
                                        <span>{ticket.subject}</span>
                                        <span className={`px-2 py-1 text-xs rounded-[4px] ${ticket.status === 'open' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
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
                        <div key={wallet.id} className="bg-white p-6 rounded-[12px] shadow-sm border border-gray-100">
                            <div className="flex justify-between items-end mb-4 border-b pb-2">
                                <h2 className="text-xl font-bold font-sora">Wallet ({wallet.context})</h2>
                                <span className="text-3xl font-bold text-green-600 font-jetbrains">
                                    {formatCurrency(wallet.balance, wallet.currency)}
                                </span>
                            </div>

                            <h3 className="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Transaction History</h3>
                            {wallet.transactions && wallet.transactions.length > 0 ? (
                                <table className="w-full text-sm text-left">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="p-2 font-medium">Date</th>
                                            <th className="p-2 font-medium">Description</th>
                                            <th className="p-2 text-right font-medium">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {wallet.transactions.map(tx => (
                                            <tr key={tx.id} className="border-t">
                                                <td className="p-2 text-gray-500">{new Date(tx.created_at).toLocaleString()}</td>
                                                <td className="p-2">{tx.description}</td>
                                                <td className={`p-2 text-right font-jetbrains font-medium ${tx.type === 'credit' ? 'text-green-600' : 'text-red-600'}`}>
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
