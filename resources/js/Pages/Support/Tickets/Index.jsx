import React, { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import ClientLayout from '@/Layouts/ClientLayout.tsx';
import AdminLayout from '@/Layouts/AdminLayout.tsx';
import ChatWindow from '@/Components/Chat/ChatWindow';

// Reusable UI components (assuming they exist or using simple placeholders)
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import StatusBadge from '@/Components/StatusBadge.tsx';

export default function SupportTicketsIndex({ tickets, auth, filters }) {
    const isAdmin = auth.user?.role === 'admin';
    const Layout = isAdmin ? AdminLayout : ClientLayout;

    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [selectedTicket, setSelectedTicket] = useState(null);

    // Form state for new ticket
    const [formData, setFormData] = useState({
        subject: '',
        priority: 'normal',
        description: '',
    });

    const handleCreateTicket = (e) => {
        e.preventDefault();
        router.post('/support/tickets', formData, {
            onSuccess: () => {
                setIsCreateModalOpen(false);
                setFormData({ subject: '', priority: 'normal', description: '' });
            },
        });
    };

    const handleFilterChange = (key, value) => {
        router.get('/support/tickets', { ...filters, [key]: value }, { preserveState: true });
    };

    const handleMarkResolved = (ticketId) => {
        router.put(`/support/tickets/${ticketId}`, { status: 'resolved' }, { preserveScroll: true });
    };

    return (
        <Layout user={auth.user}>
            <Head title="Support Tickets" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">

                    {/* Header Area */}
                    <div className="flex justify-between items-center mb-6">
                        <h1 className="text-2xl font-semibold text-gray-900">Support Tickets</h1>
                        {!isAdmin && (
                            <PrimaryButton onClick={() => setIsCreateModalOpen(true)}>
                                + Open New Ticket
                            </PrimaryButton>
                        )}
                    </div>

                    {/* Admin Filters */}
                    {isAdmin && (
                        <div className="mb-6 flex space-x-4 bg-white p-4 rounded-lg shadow-sm">
                            <div>
                                <select
                                    className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    value={filters?.status || ''}
                                    onChange={(e) => handleFilterChange('status', e.target.value)}
                                >
                                    <option value="">All Statuses</option>
                                    <option value="open">Open</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="resolved">Resolved</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    value={filters?.priority || ''}
                                    onChange={(e) => handleFilterChange('priority', e.target.value)}
                                >
                                    <option value="">All Priorities</option>
                                    <option value="low">Low</option>
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    )}

                    <div className="flex gap-6 h-[700px]">
                        {/* Tickets List */}
                        <div className={`bg-white shadow-sm sm:rounded-lg border border-gray-200 overflow-hidden ${selectedTicket ? 'w-1/3' : 'w-full'}`}>
                            <div className="overflow-x-auto h-full">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                            {!selectedTicket && <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>}
                                            {!selectedTicket && <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>}
                                            {!selectedTicket && <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Reply</th>}
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {tickets?.data?.map((ticket) => (
                                            <tr
                                                key={ticket.id}
                                                className={`hover:bg-gray-50 cursor-pointer ${selectedTicket?.id === ticket.id ? 'bg-indigo-50' : ''}`}
                                                onClick={() => setSelectedTicket(ticket)}
                                            >
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm font-medium text-gray-900 truncate max-w-[200px]">{ticket.subject}</div>
                                                    {selectedTicket && (
                                                        <div className="text-xs text-gray-500 mt-1">
                                                            {ticket.status} • {ticket.priority}
                                                        </div>
                                                    )}
                                                </td>
                                                {!selectedTicket && (
                                                    <>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <StatusBadge status={ticket.status} />
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                                ${ticket.priority === 'high' || ticket.priority === 'urgent' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'}`}>
                                                                {ticket.priority}
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            {new Date(ticket.updated_at).toLocaleDateString()}
                                                        </td>
                                                    </>
                                                )}
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <button
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            setSelectedTicket(ticket);
                                                        }}
                                                        className="text-indigo-600 hover:text-indigo-900"
                                                    >
                                                        View Chat
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                                {(!tickets?.data || tickets.data.length === 0) && (
                                    <div className="p-8 text-center text-gray-500">
                                        No tickets found.
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Chat Window Area */}
                        {selectedTicket && (
                            <div className="w-2/3 bg-white shadow-sm sm:rounded-lg border border-gray-200 flex flex-col relative">
                                {/* Header Actions (like Resolve) */}
                                <div className="absolute top-4 right-4 z-10 flex space-x-2">
                                    {isAdmin && selectedTicket.status !== 'resolved' && (
                                        <SecondaryButton onClick={() => handleMarkResolved(selectedTicket.id)} className="text-xs py-1 px-2">
                                            Mark Resolved
                                        </SecondaryButton>
                                    )}
                                    <button
                                        onClick={() => setSelectedTicket(null)}
                                        className="text-gray-400 hover:text-gray-600 p-1"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <div className="flex-1 overflow-hidden">
                                    <ChatWindow
                                        conversationId={selectedTicket.conversation_id}
                                        currentUserId={auth.user.id}
                                        participants={selectedTicket.participants || []}
                                        initialMessages={selectedTicket.messages || []}
                                        title={selectedTicket.subject}
                                        subtitle={`Ticket #${selectedTicket.id} • ${selectedTicket.priority}`}
                                        status={selectedTicket.status}
                                        readOnly={selectedTicket.status === 'resolved'}
                                    />
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Create Ticket Modal */}
            <Modal show={isCreateModalOpen} onClose={() => setIsCreateModalOpen(false)}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 mb-4">Open New Ticket</h2>
                    <form onSubmit={handleCreateTicket} className="space-y-4">
                        <div>
                            <InputLabel htmlFor="subject" value="Subject" />
                            <TextInput
                                id="subject"
                                type="text"
                                className="mt-1 block w-full"
                                value={formData.subject}
                                onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
                                required
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="priority" value="Priority" />
                            <select
                                id="priority"
                                className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                value={formData.priority}
                                onChange={(e) => setFormData({ ...formData, priority: e.target.value })}
                            >
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                            </select>
                        </div>

                        <div>
                            <InputLabel htmlFor="description" value="Description" />
                            <textarea
                                id="description"
                                className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                rows="4"
                                value={formData.description}
                                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                required
                            ></textarea>
                        </div>

                        <div className="mt-6 flex justify-end">
                            <SecondaryButton onClick={() => setIsCreateModalOpen(false)} className="mr-3">
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton type="submit" disabled={!formData.subject || !formData.description}>
                                Submit Ticket
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </Layout>
    );
}
