import React, { useState } from 'react';
import FreelanceLayout from '../Layout';
import { useFreelanceMode } from '@/Components/Freelance/FreelanceModeContext';
import { Head, router } from '@inertiajs/react';

export default function ShowContract({ auth, contract: initialContract }) {
    const { mode } = useFreelanceMode();
    const isClient = mode === 'client';

    // Mock contract data if not provided
    const contract = initialContract || {
        id: 1,
        title: 'Full Stack Developer for SaaS',
        status: 'active', // active, completed, disputed
        agreed_price: '1200.00',
        currency_code: '$',
        deadline: new Date(Date.now() + 5 * 24 * 60 * 60 * 1000).toISOString(), // 5 days from now
        client: { name: 'Acme Corp', avatar: '🏢' },
        freelancer: { name: 'Alice Smith', avatar: '👩‍💻' },
        chat_messages: [
            { id: 1, sender_id: 999, sender_name: 'Acme Corp', text: 'Hi Alice, excited to start working with you!', time: '10:00 AM' },
            { id: 2, sender_id: auth.user.id, sender_name: 'Alice Smith', text: 'Thanks! I will get started on the initial setup today.', time: '10:05 AM' }
        ]
    };

    const [messageInput, setMessageInput] = useState('');
    const [deliveryDescription, setDeliveryDescription] = useState('');

    const daysRemaining = Math.ceil((new Date(contract.deadline) - new Date()) / (1000 * 60 * 60 * 24));

    const handleSendMessage = (e) => {
        e.preventDefault();
        if (!messageInput.trim()) return;
        // In a real app, send via Reverb/axios
        console.log('Sending message:', messageInput);
        setMessageInput('');
    };

    const handleMarkCompleted = () => {
        if(confirm('Are you sure you want to mark this contract as completed and release funds?')) {
            router.post(route('freelance.contracts.complete', contract.id));
        }
    };

    const handleRaiseDispute = () => {
        if(confirm('Are you sure you want to raise a dispute? This will freeze the contract.')) {
            router.post(route('freelance.contracts.dispute', contract.id));
        }
    };

    const handleSubmitDelivery = (e) => {
        e.preventDefault();
        router.post(route('freelance.contracts.deliver', contract.id), {
            description: deliveryDescription
        });
    };

    return (
        <FreelanceLayout auth={auth}>
            <Head title={`Contract: ${contract.title}`} />

            <div className="max-w-6xl mx-auto space-y-6">

                {/* Status Banner */}
                <div className={`p-4 rounded-xl shadow-sm flex justify-between items-center ${
                    contract.status === 'active' ? 'bg-blue-50 border border-blue-200' :
                    contract.status === 'completed' ? 'bg-green-50 border border-green-200' :
                    'bg-red-50 border border-red-200'
                }`}>
                    <div>
                        <h2 className="text-xl font-bold flex items-center gap-2 text-gray-900">
                            {contract.status === 'active' && <span className="w-3 h-3 rounded-full bg-blue-500 animate-pulse"></span>}
                            {contract.status === 'completed' && <span className="text-green-500">✅</span>}
                            {contract.status === 'disputed' && <span className="text-red-500">⚠️</span>}
                            Contract: {contract.title}
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">Status: <span className="uppercase font-semibold">{contract.status}</span></p>
                    </div>
                </div>

                <div className="flex flex-col md:flex-row gap-6">

                    {/* Left side: Chat & Details (60%) */}
                    <div className="w-full md:w-[60%] space-y-6">

                        {/* Parties & Terms */}
                        <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-wrap justify-between items-center gap-4">
                            <div className="flex items-center gap-4">
                                <div className="text-center">
                                    <div className="text-3xl bg-gray-100 rounded-full w-12 h-12 flex items-center justify-center mb-1">{contract.client.avatar}</div>
                                    <div className="text-xs font-bold text-gray-700">Client</div>
                                </div>
                                <div className="text-gray-400 font-bold text-xl">&harr;</div>
                                <div className="text-center">
                                    <div className="text-3xl bg-indigo-100 rounded-full w-12 h-12 flex items-center justify-center mb-1">{contract.freelancer.avatar}</div>
                                    <div className="text-xs font-bold text-gray-700">Freelancer</div>
                                </div>
                            </div>

                            <div className="text-right">
                                <div className="text-sm text-gray-500">Agreed Price</div>
                                <div className="text-2xl font-bold text-gray-900">{contract.currency_code}{contract.agreed_price}</div>
                            </div>

                            <div className="text-right border-l pl-4 border-gray-200">
                                <div className="text-sm text-gray-500">Deadline</div>
                                <div className={`text-lg font-bold ${daysRemaining <= 2 ? 'text-red-600' : 'text-gray-900'}`}>
                                    Due in {daysRemaining} days
                                </div>
                            </div>
                        </div>

                        {/* Real-time Chat Embedded */}
                        <div className="bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col h-[500px]">
                            <div className="p-4 border-b border-gray-100 bg-gray-50 rounded-t-xl">
                                <h3 className="font-bold text-gray-800 flex items-center gap-2">
                                    <span className="text-green-500">●</span> Project Workspace Chat
                                </h3>
                            </div>

                            {/* Messages Area */}
                            <div className="flex-1 p-4 overflow-y-auto space-y-4 bg-gray-50/50">
                                {contract.chat_messages.map(msg => {
                                    const isMe = msg.sender_id === auth.user.id || (isClient && msg.sender_name === contract.client.name) || (!isClient && msg.sender_name === contract.freelancer.name);

                                    return (
                                        <div key={msg.id} className={`flex flex-col ${isMe ? 'items-end' : 'items-start'}`}>
                                            <span className="text-xs text-gray-500 mb-1 mx-1">{msg.sender_name} • {msg.time}</span>
                                            <div className={`px-4 py-2 rounded-2xl max-w-[80%] ${
                                                isMe ? 'bg-indigo-600 text-white rounded-tr-none' : 'bg-white border border-gray-200 text-gray-800 rounded-tl-none shadow-sm'
                                            }`}>
                                                {msg.text}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            {/* Input Area */}
                            {contract.status === 'active' && (
                                <div className="p-4 border-t border-gray-200 bg-white rounded-b-xl">
                                    <form onSubmit={handleSendMessage} className="flex gap-2">
                                        <button type="button" className="p-2 text-gray-400 hover:text-gray-600 bg-gray-100 rounded-full transition" title="Upload Image/File">
                                            📎
                                        </button>
                                        <input
                                            type="text"
                                            value={messageInput}
                                            onChange={(e) => setMessageInput(e.target.value)}
                                            placeholder="Type a message..."
                                            className="flex-1 border-gray-300 rounded-full shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-4"
                                        />
                                        <button type="submit" className="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-full font-bold transition">
                                            Send
                                        </button>
                                    </form>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Right side: Actions (40%) */}
                    <div className="w-full md:w-[40%] space-y-6">
                        {contract.status === 'active' && (
                            <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm sticky top-6">
                                <h3 className="text-lg font-bold mb-4 border-b pb-2">Contract Actions</h3>

                                {isClient ? (
                                    <div className="space-y-4">
                                        <div className="bg-blue-50 p-4 rounded-lg text-sm text-blue-800 border border-blue-100 mb-4">
                                            Review the freelancer's work in the chat. Once satisfied, mark as completed to release funds from escrow.
                                        </div>
                                        <button
                                            onClick={handleMarkCompleted}
                                            className="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg shadow-sm transition flex justify-center items-center gap-2"
                                        >
                                            ✅ Mark as Completed
                                        </button>
                                        <button
                                            onClick={handleRaiseDispute}
                                            className="w-full bg-white border-2 border-red-200 text-red-600 hover:bg-red-50 font-bold py-3 px-4 rounded-lg transition flex justify-center items-center gap-2"
                                        >
                                            ⚠️ Raise Dispute
                                        </button>
                                    </div>
                                ) : (
                                    <form onSubmit={handleSubmitDelivery} className="space-y-4">
                                        <div className="bg-indigo-50 p-4 rounded-lg text-sm text-indigo-800 border border-indigo-100 mb-4">
                                            Ready to submit your work? Add a description and attach final files below.
                                        </div>

                                        <div>
                                            <label className="block text-sm font-bold text-gray-700 mb-1">Delivery Notes</label>
                                            <textarea
                                                value={deliveryDescription}
                                                onChange={(e) => setDeliveryDescription(e.target.value)}
                                                className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 min-h-[100px]"
                                                placeholder="Describe what you've completed..."
                                                required
                                            ></textarea>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-bold text-gray-700 mb-1">Attach Files</label>
                                            <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 cursor-pointer transition">
                                                <div className="text-gray-400 text-2xl mb-2">📤</div>
                                                <p className="text-sm text-gray-500">Click or drag files here to upload final delivery</p>
                                            </div>
                                        </div>

                                        <button
                                            type="submit"
                                            className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg shadow-sm transition"
                                        >
                                            Submit Delivery
                                        </button>
                                    </form>
                                )}
                            </div>
                        )}

                        {contract.status === 'completed' && (
                            <div className="bg-green-50 p-6 rounded-xl border border-green-200 shadow-sm text-center">
                                <div className="text-5xl mb-4">🎉</div>
                                <h3 className="text-xl font-bold text-green-900 mb-2">Contract Completed!</h3>
                                <p className="text-green-800">Funds have been released from escrow.</p>
                                {isClient && (
                                    <button className="mt-4 bg-white border border-green-300 text-green-700 font-bold py-2 px-6 rounded-lg hover:bg-green-100 transition">
                                        Leave Feedback
                                    </button>
                                )}
                            </div>
                        )}
                    </div>

                </div>
            </div>
        </FreelanceLayout>
    );
}
