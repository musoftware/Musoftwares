import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';

export default function Show({ order, conversation }: any) {
    const { auth } = usePage().props as any;
    const isBuyer = auth.user.id === order.buyer_id;
    const isSeller = auth.user.id === order.seller_id;

    const handleDeliver = () => {
        if(confirm('Are you sure you want to mark this order as delivered?')) {
            router.post(route('marketplace.orders.deliver', order.id));
        }
    };

    const handleComplete = () => {
        if(confirm('Are you sure you want to mark this order as complete?')) {
            router.post(route('marketplace.orders.complete', order.id));
        }
    };

    const handleDispute = () => {
        if(confirm('Are you sure you want to dispute this order?')) {
            router.post(route('marketplace.orders.dispute', order.id));
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Order #{order.id}</h2>}>
            <Head title={`Order #${order.id}`} />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 flex flex-col md:flex-row gap-6">

                    {/* Left Column: Details & Actions */}
                    <div className="w-full md:w-1/3 flex flex-col gap-6">

                        {/* Order Details */}
                        <div className="bg-white shadow-sm sm:rounded-lg p-6 border border-gray-200">
                            <h3 className="text-lg font-bold mb-4 border-b pb-2">Order Summary</h3>
                            <div className="mb-4">
                                <p className="text-sm text-gray-500">Service</p>
                                <p className="font-medium text-gray-900">{order.package?.service?.title}</p>
                            </div>
                            <div className="mb-4">
                                <p className="text-sm text-gray-500">Package</p>
                                <p className="font-medium text-gray-900">{order.package?.name}</p>
                            </div>
                            <div className="mb-4">
                                <p className="text-sm text-gray-500">Amount</p>
                                <p className="font-medium text-gray-900">${order.amount} {order.currency_code}</p>
                            </div>
                            <div className="mb-4">
                                <p className="text-sm text-gray-500">Status</p>
                                <p className="font-medium capitalize text-indigo-600">{order.status}</p>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="bg-white shadow-sm sm:rounded-lg p-6 border border-gray-200">
                            <h3 className="text-lg font-bold mb-4 border-b pb-2">Actions</h3>

                            {isSeller && order.status === 'pending' && (
                                <button onClick={handleDeliver} className="w-full mb-2 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                                    Deliver Order
                                </button>
                            )}

                            {isBuyer && order.status === 'delivered' && (
                                <button onClick={handleComplete} className="w-full mb-2 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">
                                    Mark as Completed
                                </button>
                            )}

                            {(order.status === 'pending' || order.status === 'delivered') && (
                                <button onClick={handleDispute} className="w-full bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition">
                                    Dispute Order
                                </button>
                            )}

                            {order.status === 'completed' && (
                                <p className="text-green-600 font-semibold text-center">This order is complete.</p>
                            )}
                        </div>

                        {/* Timeline */}
                        <div className="bg-white shadow-sm sm:rounded-lg p-6 border border-gray-200">
                            <h3 className="text-lg font-bold mb-4 border-b pb-2">Timeline</h3>
                            <ul className="space-y-4">
                                <li>
                                    <p className="text-sm font-medium text-gray-900">Order Placed</p>
                                    <p className="text-xs text-gray-500">{new Date(order.created_at).toLocaleString()}</p>
                                </li>
                                {order.delivered_at && (
                                    <li>
                                        <p className="text-sm font-medium text-gray-900">Order Delivered</p>
                                        <p className="text-xs text-gray-500">{new Date(order.delivered_at).toLocaleString()}</p>
                                    </li>
                                )}
                                {order.completed_at && (
                                    <li>
                                        <p className="text-sm font-medium text-gray-900">Order Completed</p>
                                        <p className="text-xs text-gray-500">{new Date(order.completed_at).toLocaleString()}</p>
                                    </li>
                                )}
                            </ul>
                        </div>
                    </div>

                    {/* Right Column: Chat */}
                    <div className="w-full md:w-2/3">
                        <div className="bg-white shadow-sm sm:rounded-lg border border-gray-200 h-full flex flex-col min-h-[500px]">
                            <div className="p-4 border-b bg-gray-50 rounded-t-lg">
                                <h3 className="font-bold">Order Conversation</h3>
                            </div>

                            {/* Chat Messages Placeholder */}
                            <div className="p-4 flex-1 overflow-y-auto bg-gray-50">
                                {conversation?.messages && conversation.messages.length > 0 ? (
                                    conversation.messages.map((msg: any) => (
                                        <div key={msg.id} className={`mb-4 flex ${msg.sender_id === auth.user.id ? 'justify-end' : 'justify-start'}`}>
                                            <div className={`px-4 py-2 rounded-lg max-w-[70%] ${msg.sender_id === auth.user.id ? 'bg-indigo-600 text-white' : 'bg-white border'}`}>
                                                <p className="text-xs opacity-75 mb-1">{msg.sender?.name}</p>
                                                <p>{msg.body}</p>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="text-center text-gray-500 h-full flex items-center justify-center">
                                        No messages yet. Send a message to get started!
                                    </div>
                                )}
                            </div>

                            {/* Chat Input */}
                            <div className="p-4 border-t bg-white rounded-b-lg">
                                <form
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        const form = e.target as HTMLFormElement;
                                        const input = form.elements.namedItem('body') as HTMLInputElement;
                                        router.post(route('marketplace.orders.messages.store', order.id), {
                                            body: input.value
                                        }, {
                                            onSuccess: () => {
                                                input.value = '';
                                            }
                                        });
                                    }}
                                    className="flex gap-2"
                                >
                                    <input
                                        type="text"
                                        name="body"
                                        placeholder="Type your message..."
                                        required
                                        className="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <button type="submit" className="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Send</button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
