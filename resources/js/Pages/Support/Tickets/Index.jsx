import ChatWindow from '@/Components/Chat/ChatWindow';
import DataTable from '@/Components/DataTable';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import StatusBadge from '@/Components/StatusBadge';
import TextInput from '@/Components/TextInput';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function TicketsIndex({ tickets, isAdmin }) {
    const { auth, errors } = usePage().props;
    const [isCreating, setIsCreating] = useState(false);
    const [selectedTicket, setSelectedTicket] = useState(null);
    const [filterStatus, setFilterStatus] = useState('');
    const [filterPriority, setFilterPriority] = useState('');

    const [form, setForm] = useState({
        subject: '',
        priority: 'Medium',
        description: '',
    });

    const submitTicket = (e) => {
        e.preventDefault();
        router.post(route('tickets.store'), form, {
            onSuccess: () => {
                setIsCreating(false);
                setForm({ subject: '', priority: 'Medium', description: '' });
            },
        });
    };

    const markResolved = (id) => {
        router.post(
            route('tickets.resolve', id),
            {},
            {
                preserveScroll: true,
                onSuccess: () =>
                    setSelectedTicket((prev) =>
                        prev?.id === id
                            ? { ...prev, status: 'Resolved' }
                            : prev,
                    ),
            },
        );
    };

    // Table columns configuration
    const columns = [
        { header: 'ID', accessor: (row) => `#${row.id}` },
        { header: 'Subject', accessor: 'subject' },
        ...(isAdmin
            ? [{ header: 'Client', accessor: (row) => row.user?.name }]
            : []),
        {
            header: 'Status',
            accessor: (row) => <StatusBadge status={row.status} />,
        },
        {
            header: 'Priority',
            accessor: (row) => (
                <span
                    className={`rounded px-2 py-1 text-xs font-medium ${
                        row.priority === 'High'
                            ? 'bg-red-100 text-red-800'
                            : row.priority === 'Medium'
                              ? 'bg-yellow-100 text-yellow-800'
                              : 'bg-green-100 text-green-800'
                    }`}
                >
                    {row.priority}
                </span>
            ),
        },
        {
            header: 'Last Reply',
            accessor: (row) => new Date(row.updated_at).toLocaleDateString(),
        },
        {
            header: 'Action',
            accessor: (row) => (
                <SecondaryButton onClick={() => setSelectedTicket(row)}>
                    {isAdmin ? 'View / Respond' : 'View Ticket'}
                </SecondaryButton>
            ),
        },
    ];

    // Filter tickets for admin view
    const filteredTickets =
        tickets?.data?.filter((ticket) => {
            if (filterStatus && ticket.status !== filterStatus) return false;
            if (filterPriority && ticket.priority !== filterPriority)
                return false;
            return true;
        }) || [];

    return (
        <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <Head title="Support Tickets" />

            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900">
                        Support Tickets
                    </h1>
                    <p className="mt-1 text-sm text-gray-500">
                        {isAdmin
                            ? 'Manage client support requests.'
                            : 'View and manage your support tickets.'}
                    </p>
                </div>
                {!isAdmin && (
                    <PrimaryButton onClick={() => setIsCreating(true)}>
                        + Open New Ticket
                    </PrimaryButton>
                )}
            </div>

            {isAdmin && (
                <div className="mb-6 flex gap-4 rounded-lg border bg-white p-4 shadow-sm">
                    <div>
                        <InputLabel value="Filter Status" />
                        <select
                            value={filterStatus}
                            onChange={(e) => setFilterStatus(e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">All</option>
                            <option value="Open">Open</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Resolved">Resolved</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Filter Priority" />
                        <select
                            value={filterPriority}
                            onChange={(e) => setFilterPriority(e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">All</option>
                            <option value="High">High</option>
                            <option value="Medium">Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                </div>
            )}

            <div className="overflow-hidden border bg-white shadow-sm sm:rounded-lg">
                <DataTable
                    columns={columns}
                    data={isAdmin ? filteredTickets : tickets?.data || []}
                    emptyMessage="No tickets found."
                />
            </div>

            {/* Create Ticket Modal */}
            <Modal show={isCreating} onClose={() => setIsCreating(false)}>
                <div className="p-6">
                    <h2 className="mb-4 text-lg font-medium text-gray-900">
                        Open New Ticket
                    </h2>
                    <form onSubmit={submitTicket} className="space-y-4">
                        <div>
                            <InputLabel htmlFor="subject" value="Subject" />
                            <TextInput
                                id="subject"
                                type="text"
                                className="mt-1 block w-full"
                                value={form.subject}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        subject: e.target.value,
                                    })
                                }
                                required
                            />
                            <InputError
                                message={errors.subject}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="priority" value="Priority" />
                            <select
                                id="priority"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={form.priority}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        priority: e.target.value,
                                    })
                                }
                            >
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                            <InputError
                                message={errors.priority}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="description"
                                value="Description"
                            />
                            <textarea
                                id="description"
                                rows="4"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={form.description}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        description: e.target.value,
                                    })
                                }
                                required
                            />
                            <InputError
                                message={errors.description}
                                className="mt-2"
                            />
                        </div>

                        <div className="mt-6 flex justify-end gap-3">
                            <SecondaryButton
                                onClick={() => setIsCreating(false)}
                            >
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton type="submit">
                                Submit Ticket
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            {/* View Ticket Chat Modal */}
            <Modal
                show={!!selectedTicket}
                onClose={() => setSelectedTicket(null)}
                maxWidth="4xl"
            >
                {selectedTicket && (
                    <div className="flex h-[80vh] flex-col">
                        <div className="flex items-center justify-between border-b bg-gray-50 p-4">
                            <div>
                                <h2 className="flex items-center gap-2 text-lg font-semibold">
                                    #{selectedTicket.id} -{' '}
                                    {selectedTicket.subject}
                                    <StatusBadge
                                        status={selectedTicket.status}
                                    />
                                </h2>
                                {isAdmin && selectedTicket.user && (
                                    <p className="mt-1 text-sm text-gray-500">
                                        Client: {selectedTicket.user.name}
                                    </p>
                                )}
                            </div>
                            <div className="flex gap-2">
                                {isAdmin &&
                                    selectedTicket.status !== 'Resolved' && (
                                        <PrimaryButton
                                            onClick={() =>
                                                markResolved(selectedTicket.id)
                                            }
                                        >
                                            Mark Resolved
                                        </PrimaryButton>
                                    )}
                                <SecondaryButton
                                    onClick={() => setSelectedTicket(null)}
                                >
                                    Close
                                </SecondaryButton>
                            </div>
                        </div>

                        <div className="flex-1 overflow-hidden bg-white p-0">
                            {/* Assuming conversation ID is tied to the ticket, or passed as ticket.conversation_id */}
                            <ChatWindow
                                conversationId={
                                    selectedTicket.conversation_id ||
                                    selectedTicket.id
                                }
                                participants={[
                                    {
                                        id: selectedTicket.user_id,
                                        name: selectedTicket.user?.name,
                                    },
                                ]}
                                readOnly={selectedTicket.status === 'Resolved'}
                            />
                        </div>
                    </div>
                )}
            </Modal>
        </div>
    );
}
