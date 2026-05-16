import { Head } from '@inertiajs/react';

export default function Show({ client, wallets }) {
    const handleLoginAsClient = () => {
        // Implement impersonation logic later, mock for now
        alert(`Impersonating client: ${client.name}`);
    };

    const formatCurrency = (amount, currency = 'USD') => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency,
        }).format(amount || 0);
    };

    return (
        <div className="mx-auto max-w-5xl p-6">
            <Head title={`Client Profile: ${client.name}`} />

            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-3xl font-bold">Client Profile</h1>
                <button
                    onClick={handleLoginAsClient}
                    className="rounded bg-purple-600 px-4 py-2 text-white transition hover:bg-purple-700"
                >
                    Login as client
                </button>
            </div>

            <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                {/* Profile Details */}
                <div className="col-span-1 rounded bg-white p-6 shadow">
                    <h2 className="mb-4 border-b pb-2 text-xl font-bold">
                        Details
                    </h2>
                    <div className="space-y-3">
                        <div>
                            <span className="block text-sm text-gray-500">
                                Name
                            </span>
                            <span className="font-medium">{client.name}</span>
                        </div>
                        <div>
                            <span className="block text-sm text-gray-500">
                                Email
                            </span>
                            <span className="font-medium">{client.email}</span>
                        </div>
                        <div>
                            <span className="block text-sm text-gray-500">
                                Phone
                            </span>
                            <span className="font-medium">
                                {client.phone || 'N/A'}
                            </span>
                        </div>
                        <div>
                            <span className="block text-sm text-gray-500">
                                Joined
                            </span>
                            <span className="font-medium">
                                {new Date(
                                    client.created_at,
                                ).toLocaleDateString()}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Wallets and History */}
                <div className="col-span-2 space-y-6">
                    {/* Support Tickets */}
                    <div className="rounded bg-white p-6 shadow">
                        <h2 className="mb-4 border-b pb-2 text-xl font-bold">
                            Support Tickets
                        </h2>
                        {client.support_tickets &&
                        client.support_tickets.length > 0 ? (
                            <ul className="space-y-3">
                                {client.support_tickets.map((ticket) => (
                                    <li
                                        key={ticket.id}
                                        className="flex justify-between border-b pb-2"
                                    >
                                        <span>{ticket.subject}</span>
                                        <span
                                            className={`rounded px-2 py-1 text-xs ${ticket.status === 'open' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}
                                        >
                                            {ticket.status}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-sm text-gray-500">
                                No support tickets found.
                            </p>
                        )}
                    </div>

                    {/* Wallets & Transactions */}
                    {wallets.map((wallet) => (
                        <div
                            key={wallet.id}
                            className="rounded bg-white p-6 shadow"
                        >
                            <div className="mb-4 flex items-end justify-between border-b pb-2">
                                <h2 className="text-xl font-bold">
                                    Wallet ({wallet.context})
                                </h2>
                                <span className="text-2xl font-bold text-green-600">
                                    {formatCurrency(
                                        wallet.balance,
                                        wallet.currency,
                                    )}
                                </span>
                            </div>

                            <h3 className="mb-3 text-sm font-bold tracking-wider text-gray-500 uppercase">
                                Transaction History
                            </h3>
                            {wallet.transactions &&
                            wallet.transactions.length > 0 ? (
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="p-2">Date</th>
                                            <th className="p-2">Description</th>
                                            <th className="p-2 text-right">
                                                Amount
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {wallet.transactions.map((tx) => (
                                            <tr
                                                key={tx.id}
                                                className="border-t"
                                            >
                                                <td className="p-2">
                                                    {new Date(
                                                        tx.created_at,
                                                    ).toLocaleString()}
                                                </td>
                                                <td className="p-2">
                                                    {tx.description}
                                                </td>
                                                <td
                                                    className={`p-2 text-right font-medium ${tx.type === 'credit' ? 'text-green-600' : 'text-red-600'}`}
                                                >
                                                    {tx.type === 'credit'
                                                        ? '+'
                                                        : '-'}
                                                    {formatCurrency(
                                                        tx.amount,
                                                        wallet.currency,
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            ) : (
                                <p className="text-sm text-gray-500">
                                    No transactions found.
                                </p>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
