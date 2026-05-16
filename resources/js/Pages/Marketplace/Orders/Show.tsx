import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import StatusBadge from '@/Components/StatusBadge';

export default function Show({ order, conversation }: any) {
    const { auth } = usePage().props as any;
    const isBuyer = auth.user.id === order.buyer_id;
    const isSeller = auth.user.id === order.seller_id;
    const [deliveryNote, setDeliveryNote] = useState('');

    const handleDeliver = (e: React.FormEvent) => {
        e.preventDefault();
        if(confirm('Are you sure you want to submit delivery for this order?')) {
            router.post(route('marketplace.orders.deliver', order.id), { note: deliveryNote });
        }
    };

    const handleAcceptDelivery = () => {
        if(confirm('Are you sure you want to accept this delivery and complete the order?')) {
            router.post(route('marketplace.orders.complete', order.id));
        }
    };

    const handleRequestRevision = () => {
        if(confirm('Are you sure you want to request a revision?')) {
            // Ideally an endpoint for revision, falling back to dispute for now or just a specific action
            router.post(route('marketplace.orders.dispute', order.id));
        }
    };

    const steps = ['pending', 'in_progress', 'delivered', 'completed'];
    // Map existing statuses to timeline steps. 'disputed' etc might interrupt this linear flow.
    let currentStepIndex = steps.indexOf(order.status === 'active' ? 'in_progress' : order.status);
    if (currentStepIndex === -1 && order.status === 'disputed') currentStepIndex = 1; // Arbitrary fallback

    // Calculate deadline
    const deliveryDays = order.package?.delivery_days || 7;
    const orderDate = new Date(order.created_at);
    const deadlineDate = new Date(orderDate.getTime() + deliveryDays * 24 * 60 * 60 * 1000);
    const now = new Date();
    const isOverdue = now > deadlineDate && order.status !== 'completed' && order.status !== 'delivered';

    // Dummy logic for calculating commissions
    const commissionRate = 0.10; // 10%
    const sellerEarnings = order.amount * (1 - commissionRate);
    const fee = order.amount * commissionRate;

    return (
        <AuthenticatedLayout header={<h2 className="text-2xl font-bold leading-tight text-gray-800">Order #{order.id}</h2>}>
            <Head title={`Order #${order.id}`} />

            <div className="py-8 bg-gray-50 min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">

                    {/* Top Status Bar */}
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
                        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                            <div>
                                <h3 className="text-xl font-bold text-gray-900 mb-1">
                                    {order.package?.service?.title}
                                </h3>
                                <p className="text-sm text-gray-500">
                                    Order placed on {orderDate.toLocaleDateString()}
                                </p>
                            </div>
                            <div className="mt-4 sm:mt-0">
                                <StatusBadge status={order.status} />
                            </div>
                        </div>

                        {/* Progress Stepper */}
                        <div className="relative">
                            <div className="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-200">
                                <div style={{ width: `${Math.max(10, ((currentStepIndex + 1) / steps.length) * 100)}%` }} className="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-indigo-600 transition-all duration-500"></div>
                            </div>
                            <div className="flex justify-between text-xs font-medium text-gray-500 px-1">
                                <div className={`text-left ${currentStepIndex >= 0 ? 'text-indigo-600 font-bold' : ''}`}>Placed</div>
                                <div className={`text-center ${currentStepIndex >= 1 ? 'text-indigo-600 font-bold' : ''}`}>In Progress</div>
                                <div className={`text-center ${currentStepIndex >= 2 ? 'text-indigo-600 font-bold' : ''}`}>Delivered</div>
                                <div className={`text-right ${currentStepIndex >= 3 ? 'text-indigo-600 font-bold' : ''}`}>Completed</div>
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-col lg:flex-row gap-8">

                        {/* Left Column (60%) */}
                        <div className="w-full lg:w-3/5 flex flex-col gap-8">

                            {/* Requirements Note */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h4 className="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                    <svg className="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Buyer Requirements
                                </h4>
                                <div className="bg-gray-50 p-4 rounded-lg text-sm text-gray-700 border border-gray-100">
                                    <p>I need a clean and modern logo for my startup. The color scheme should be blue and white. Please include source files.</p>
                                </div>
                            </div>

                            {/* Delivery Section (Shown if delivered) */}
                            {order.status === 'delivered' && (
                                <div className="bg-white rounded-xl shadow-sm border border-indigo-200 p-6 relative overflow-hidden">
                                    <div className="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
                                    <h4 className="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                        <svg className="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>
                                        Delivery from Seller
                                    </h4>

                                    <div className="mb-6">
                                        <h5 className="text-sm font-medium text-gray-700 mb-2">Message:</h5>
                                        <p className="text-sm text-gray-600 bg-gray-50 p-4 rounded-lg border border-gray-100">
                                            Here is the final delivery. I've included all the requested files. Let me know if you need any revisions!
                                        </p>
                                    </div>

                                    <div className="mb-6">
                                        <h5 className="text-sm font-medium text-gray-700 mb-2">Attached Files:</h5>
                                        <div className="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition cursor-pointer">
                                            <svg className="w-8 h-8 text-indigo-500 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clipRule="evenodd"></path></svg>
                                            <div className="flex-1">
                                                <p className="text-sm font-medium text-gray-900">final_delivery_files.zip</p>
                                                <p className="text-xs text-gray-500">12.4 MB</p>
                                            </div>
                                            <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        </div>
                                    </div>

                                    {isBuyer && (
                                        <div className="flex gap-4 border-t border-gray-100 pt-6">
                                            <button onClick={handleAcceptDelivery} className="flex-1 bg-green-600 text-white font-bold px-4 py-2.5 rounded-lg hover:bg-green-700 transition flex justify-center items-center gap-2">
                                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>
                                                Accept & Complete
                                            </button>
                                            <button onClick={handleRequestRevision} className="flex-1 bg-white border-2 border-indigo-600 text-indigo-600 font-bold px-4 py-2.5 rounded-lg hover:bg-indigo-50 transition flex justify-center items-center gap-2">
                                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                Request Revision
                                            </button>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Chat Interface via Reverb */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col h-[600px]">
                                <div className="p-4 border-b border-gray-200 bg-gray-50 rounded-t-xl flex items-center justify-between">
                                    <h4 className="font-bold text-gray-900 flex items-center gap-2">
                                        <svg className="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                        Order Messages
                                    </h4>
                                    <span className="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full font-medium flex items-center gap-1">
                                        <span className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                        Real-time active
                                    </span>
                                </div>

                                <div className="p-4 flex-1 overflow-y-auto bg-gray-50 flex flex-col gap-4">
                                    {conversation?.messages && conversation.messages.length > 0 ? (
                                        conversation.messages.map((msg: any) => {
                                            const isMe = msg.sender_id === auth.user.id;
                                            return (
                                                <div key={msg.id} className={`flex ${isMe ? 'justify-end' : 'justify-start'}`}>
                                                    <div className={`max-w-[75%] flex flex-col ${isMe ? 'items-end' : 'items-start'}`}>
                                                        <span className="text-xs text-gray-500 mb-1 ml-1">{isMe ? 'You' : msg.sender?.name}</span>
                                                        <div className={`px-4 py-2.5 rounded-2xl ${isMe ? 'bg-indigo-600 text-white rounded-tr-none' : 'bg-white border border-gray-200 text-gray-900 rounded-tl-none shadow-sm'}`}>
                                                            <p className="text-sm whitespace-pre-wrap">{msg.body}</p>
                                                        </div>
                                                        <span className="text-[10px] text-gray-400 mt-1">{new Date(msg.created_at || new Date()).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                                                    </div>
                                                </div>
                                            );
                                        })
                                    ) : (
                                        <div className="flex-1 flex flex-col items-center justify-center text-gray-500">
                                            <svg className="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                            <p className="text-sm">No messages yet. Say hello!</p>
                                        </div>
                                    )}
                                </div>

                                <div className="p-4 border-t border-gray-200 bg-white rounded-b-xl">
                                    <form
                                        onSubmit={(e) => {
                                            e.preventDefault();
                                            const form = e.target as HTMLFormElement;
                                            const input = form.elements.namedItem('body') as HTMLInputElement;
                                            if(!input.value.trim()) return;

                                            router.post(route('marketplace.orders.messages.store', order.id), {
                                                body: input.value
                                            }, {
                                                preserveScroll: true,
                                                onSuccess: () => {
                                                    input.value = '';
                                                }
                                            });
                                        }}
                                        className="flex gap-3"
                                    >
                                        <button type="button" className="text-gray-400 hover:text-indigo-600 transition">
                                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                        </button>
                                        <input
                                            type="text"
                                            name="body"
                                            placeholder="Type your message..."
                                            autoComplete="off"
                                            className="flex-1 rounded-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50 px-4"
                                        />
                                        <button type="submit" className="bg-indigo-600 text-white p-2.5 rounded-full hover:bg-indigo-700 transition flex items-center justify-center">
                                            <svg className="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {/* Right Column (40%) - Sticky Summary */}
                        <div className="w-full lg:w-2/5">
                            <div className="sticky top-6 flex flex-col gap-6">

                                {/* Order Info Card */}
                                <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                    <div className="bg-gray-50 border-b border-gray-200 p-4 flex justify-between items-center">
                                        <h4 className="font-bold text-gray-900">Order Details</h4>
                                        <span className="font-mono text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded">#{order.id.toString().padStart(6, '0')}</span>
                                    </div>
                                    <div className="p-6">
                                        <div className="flex justify-between items-center mb-4">
                                            <span className="text-gray-500 text-sm">Package</span>
                                            <span className="font-bold text-gray-900 bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded text-sm">{order.package?.name}</span>
                                        </div>

                                        <div className="space-y-3 mb-6">
                                            <div className="flex items-start text-sm text-gray-600">
                                                <svg className="w-5 h-5 text-green-500 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>
                                                Source files included
                                            </div>
                                            <div className="flex items-start text-sm text-gray-600">
                                                <svg className="w-5 h-5 text-green-500 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>
                                                3 Revisions
                                            </div>
                                            <div className="flex items-start text-sm text-gray-600">
                                                <svg className="w-5 h-5 text-green-500 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>
                                                Commercial use
                                            </div>
                                        </div>

                                        <div className="border-t border-gray-100 pt-4 mb-6">
                                            {isSeller ? (
                                                <div className="space-y-2 text-sm">
                                                    <div className="flex justify-between text-gray-500">
                                                        <span>Price</span>
                                                        <span>${order.amount.toFixed(2)}</span>
                                                    </div>
                                                    <div className="flex justify-between text-red-500">
                                                        <span>Platform Fee (10%)</span>
                                                        <span>-${fee.toFixed(2)}</span>
                                                    </div>
                                                    <div className="flex justify-between font-bold text-gray-900 pt-2 border-t border-gray-100">
                                                        <span>Your Earnings</span>
                                                        <span>${sellerEarnings.toFixed(2)}</span>
                                                    </div>
                                                </div>
                                            ) : (
                                                <div className="flex justify-between font-bold text-gray-900 text-lg">
                                                    <span>Total Paid</span>
                                                    <span>${order.amount.toFixed(2)} {order.currency_code}</span>
                                                </div>
                                            )}
                                        </div>

                                        {/* Deadline Countdown */}
                                        {(order.status === 'pending' || order.status === 'in_progress') && (
                                            <div className={`p-4 rounded-lg border flex items-center justify-between ${isOverdue ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200'}`}>
                                                <div>
                                                    <h5 className={`text-sm font-bold ${isOverdue ? 'text-red-700' : 'text-amber-800'}`}>
                                                        {isOverdue ? 'Delivery Overdue' : 'Time Left to Deliver'}
                                                    </h5>
                                                    <p className={`text-xs ${isOverdue ? 'text-red-600' : 'text-amber-700'}`}>
                                                        Deadline: {deadlineDate.toLocaleDateString()}
                                                    </p>
                                                </div>
                                                <div className={`font-mono text-xl font-bold ${isOverdue ? 'text-red-600' : 'text-amber-600'}`}>
                                                    {isOverdue ? 'LATE' : '3d 12h'}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Seller Delivery Action Box */}
                                {isSeller && (order.status === 'pending' || order.status === 'in_progress') && (
                                    <div className="bg-white rounded-xl shadow-sm border border-indigo-500 overflow-hidden">
                                        <div className="bg-indigo-50 p-4 border-b border-indigo-100 flex items-center gap-2">
                                            <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                            <h4 className="font-bold text-indigo-900">Submit Delivery</h4>
                                        </div>
                                        <div className="p-6">
                                            <form onSubmit={handleDeliver}>
                                                <div className="mb-4">
                                                    <label className="block text-sm font-medium text-gray-700 mb-2">Delivery Note</label>
                                                    <textarea
                                                        className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                                        rows={4}
                                                        placeholder="Describe what you are delivering..."
                                                        value={deliveryNote}
                                                        onChange={(e) => setDeliveryNote(e.target.value)}
                                                        required
                                                    ></textarea>
                                                </div>
                                                <div className="mb-6">
                                                    <label className="block text-sm font-medium text-gray-700 mb-2">Upload Files</label>
                                                    <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition cursor-pointer">
                                                        <svg className="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                                        <p className="text-sm text-gray-600">Click to browse or drag & drop files here</p>
                                                        <p className="text-xs text-gray-500 mt-1">Max 100MB per file</p>
                                                    </div>
                                                </div>
                                                <button type="submit" className="w-full bg-indigo-600 text-white font-bold px-4 py-3 rounded-lg hover:bg-indigo-700 transition flex justify-center items-center gap-2">
                                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                                    Send Delivery
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                )}

                                {/* Admin / Dispute Options */}
                                <div className="text-center mt-2">
                                    <button onClick={() => router.post(route('marketplace.orders.dispute', order.id))} className="text-xs text-gray-500 hover:text-red-600 transition underline">
                                        Having issues? Dispute Order
                                    </button>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
