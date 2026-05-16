import StatusBadge from '@/Components/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Show({ order, conversation }: any) {
    const { auth } = usePage().props as any;
    const isBuyer = auth.user.id === order.buyer_id;
    const isSeller = auth.user.id === order.seller_id;
    const [deliveryNote, setDeliveryNote] = useState('');

    const handleDeliver = (e: React.FormEvent) => {
        e.preventDefault();
        if (
            confirm('Are you sure you want to submit delivery for this order?')
        ) {
            router.post(route('marketplace.orders.deliver', order.id), {
                note: deliveryNote,
            });
        }
    };

    const handleAcceptDelivery = () => {
        if (
            confirm(
                'Are you sure you want to accept this delivery and complete the order?',
            )
        ) {
            router.post(route('marketplace.orders.complete', order.id));
        }
    };

    const handleRequestRevision = () => {
        if (confirm('Are you sure you want to request a revision?')) {
            // Ideally an endpoint for revision, falling back to dispute for now or just a specific action
            router.post(route('marketplace.orders.dispute', order.id));
        }
    };

    const steps = ['pending', 'in_progress', 'delivered', 'completed'];
    // Map existing statuses to timeline steps. 'disputed' etc might interrupt this linear flow.
    let currentStepIndex = steps.indexOf(
        order.status === 'active' ? 'in_progress' : order.status,
    );
    if (currentStepIndex === -1 && order.status === 'disputed')
        currentStepIndex = 1; // Arbitrary fallback

    // Calculate deadline
    const deliveryDays = order.package?.delivery_days || 7;
    const orderDate = new Date(order.created_at);
    const deadlineDate = new Date(
        orderDate.getTime() + deliveryDays * 24 * 60 * 60 * 1000,
    );
    const now = new Date();
    const isOverdue =
        now > deadlineDate &&
        order.status !== 'completed' &&
        order.status !== 'delivered';

    // Dummy logic for calculating commissions
    const commissionRate = 0.1; // 10%
    const sellerEarnings = order.amount * (1 - commissionRate);
    const fee = order.amount * commissionRate;

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-2xl leading-tight font-bold text-gray-800">
                    Order #{order.id}
                </h2>
            }
        >
            <Head title={`Order #${order.id}`} />

            <div className="min-h-screen bg-gray-50 py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Top Status Bar */}
                    <div className="mb-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div className="mb-6 flex flex-col items-start justify-between sm:flex-row sm:items-center">
                            <div>
                                <h3 className="mb-1 text-xl font-bold text-gray-900">
                                    {order.package?.service?.title}
                                </h3>
                                <p className="text-sm text-gray-500">
                                    Order placed on{' '}
                                    {orderDate.toLocaleDateString()}
                                </p>
                            </div>
                            <div className="mt-4 sm:mt-0">
                                <StatusBadge status={order.status} />
                            </div>
                        </div>

                        {/* Progress Stepper */}
                        <div className="relative">
                            <div className="mb-4 flex h-2 overflow-hidden rounded bg-gray-200 text-xs">
                                <div
                                    style={{
                                        width: `${Math.max(10, ((currentStepIndex + 1) / steps.length) * 100)}%`,
                                    }}
                                    className="flex flex-col justify-center bg-indigo-600 text-center whitespace-nowrap text-white shadow-none transition-all duration-500"
                                ></div>
                            </div>
                            <div className="flex justify-between px-1 text-xs font-medium text-gray-500">
                                <div
                                    className={`text-left ${currentStepIndex >= 0 ? 'font-bold text-indigo-600' : ''}`}
                                >
                                    Placed
                                </div>
                                <div
                                    className={`text-center ${currentStepIndex >= 1 ? 'font-bold text-indigo-600' : ''}`}
                                >
                                    In Progress
                                </div>
                                <div
                                    className={`text-center ${currentStepIndex >= 2 ? 'font-bold text-indigo-600' : ''}`}
                                >
                                    Delivered
                                </div>
                                <div
                                    className={`text-right ${currentStepIndex >= 3 ? 'font-bold text-indigo-600' : ''}`}
                                >
                                    Completed
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-col gap-8 lg:flex-row">
                        {/* Left Column (60%) */}
                        <div className="flex w-full flex-col gap-8 lg:w-3/5">
                            {/* Requirements Note */}
                            <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                                <h4 className="mb-4 flex items-center gap-2 text-lg font-bold text-gray-900">
                                    <svg
                                        className="h-5 w-5 text-indigo-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        ></path>
                                    </svg>
                                    Buyer Requirements
                                </h4>
                                <div className="rounded-lg border border-gray-100 bg-gray-50 p-4 text-sm text-gray-700">
                                    <p>
                                        I need a clean and modern logo for my
                                        startup. The color scheme should be blue
                                        and white. Please include source files.
                                    </p>
                                </div>
                            </div>

                            {/* Delivery Section (Shown if delivered) */}
                            {order.status === 'delivered' && (
                                <div className="relative overflow-hidden rounded-xl border border-indigo-200 bg-white p-6 shadow-sm">
                                    <div className="absolute top-0 left-0 h-full w-1 bg-indigo-500"></div>
                                    <h4 className="mb-4 flex items-center gap-2 text-lg font-bold text-gray-900">
                                        <svg
                                            className="h-5 w-5 text-indigo-500"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth="2"
                                                d="M5 13l4 4L19 7"
                                            ></path>
                                        </svg>
                                        Delivery from Seller
                                    </h4>

                                    <div className="mb-6">
                                        <h5 className="mb-2 text-sm font-medium text-gray-700">
                                            Message:
                                        </h5>
                                        <p className="rounded-lg border border-gray-100 bg-gray-50 p-4 text-sm text-gray-600">
                                            Here is the final delivery. I've
                                            included all the requested files.
                                            Let me know if you need any
                                            revisions!
                                        </p>
                                    </div>

                                    <div className="mb-6">
                                        <h5 className="mb-2 text-sm font-medium text-gray-700">
                                            Attached Files:
                                        </h5>
                                        <div className="flex cursor-pointer items-center rounded-lg border border-gray-200 p-3 transition hover:bg-gray-50">
                                            <svg
                                                className="mr-3 h-8 w-8 text-indigo-500"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fillRule="evenodd"
                                                    d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                                    clipRule="evenodd"
                                                ></path>
                                            </svg>
                                            <div className="flex-1">
                                                <p className="text-sm font-medium text-gray-900">
                                                    final_delivery_files.zip
                                                </p>
                                                <p className="text-xs text-gray-500">
                                                    12.4 MB
                                                </p>
                                            </div>
                                            <svg
                                                className="h-5 w-5 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth="2"
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                                ></path>
                                            </svg>
                                        </div>
                                    </div>

                                    {isBuyer && (
                                        <div className="flex gap-4 border-t border-gray-100 pt-6">
                                            <button
                                                onClick={handleAcceptDelivery}
                                                className="flex flex-1 items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 font-bold text-white transition hover:bg-green-700"
                                            >
                                                <svg
                                                    className="h-5 w-5"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        strokeWidth="2"
                                                        d="M5 13l4 4L19 7"
                                                    ></path>
                                                </svg>
                                                Accept & Complete
                                            </button>
                                            <button
                                                onClick={handleRequestRevision}
                                                className="flex flex-1 items-center justify-center gap-2 rounded-lg border-2 border-indigo-600 bg-white px-4 py-2.5 font-bold text-indigo-600 transition hover:bg-indigo-50"
                                            >
                                                <svg
                                                    className="h-5 w-5"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        strokeWidth="2"
                                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                                    ></path>
                                                </svg>
                                                Request Revision
                                            </button>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Chat Interface via Reverb */}
                            <div className="flex h-[600px] flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
                                <div className="flex items-center justify-between rounded-t-xl border-b border-gray-200 bg-gray-50 p-4">
                                    <h4 className="flex items-center gap-2 font-bold text-gray-900">
                                        <svg
                                            className="h-5 w-5 text-indigo-500"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth="2"
                                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                                            ></path>
                                        </svg>
                                        Order Messages
                                    </h4>
                                    <span className="flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
                                        <span className="h-2 w-2 animate-pulse rounded-full bg-green-500"></span>
                                        Real-time active
                                    </span>
                                </div>

                                <div className="flex flex-1 flex-col gap-4 overflow-y-auto bg-gray-50 p-4">
                                    {conversation?.messages &&
                                    conversation.messages.length > 0 ? (
                                        conversation.messages.map(
                                            (msg: any) => {
                                                const isMe =
                                                    msg.sender_id ===
                                                    auth.user.id;
                                                return (
                                                    <div
                                                        key={msg.id}
                                                        className={`flex ${isMe ? 'justify-end' : 'justify-start'}`}
                                                    >
                                                        <div
                                                            className={`flex max-w-[75%] flex-col ${isMe ? 'items-end' : 'items-start'}`}
                                                        >
                                                            <span className="mb-1 ml-1 text-xs text-gray-500">
                                                                {isMe
                                                                    ? 'You'
                                                                    : msg.sender
                                                                          ?.name}
                                                            </span>
                                                            <div
                                                                className={`rounded-2xl px-4 py-2.5 ${isMe ? 'rounded-tr-none bg-indigo-600 text-white' : 'rounded-tl-none border border-gray-200 bg-white text-gray-900 shadow-sm'}`}
                                                            >
                                                                <p className="text-sm whitespace-pre-wrap">
                                                                    {msg.body}
                                                                </p>
                                                            </div>
                                                            <span className="mt-1 text-[10px] text-gray-400">
                                                                {new Date(
                                                                    msg.created_at ||
                                                                        new Date(),
                                                                ).toLocaleTimeString(
                                                                    [],
                                                                    {
                                                                        hour: '2-digit',
                                                                        minute: '2-digit',
                                                                    },
                                                                )}
                                                            </span>
                                                        </div>
                                                    </div>
                                                );
                                            },
                                        )
                                    ) : (
                                        <div className="flex flex-1 flex-col items-center justify-center text-gray-500">
                                            <svg
                                                className="mb-3 h-12 w-12 text-gray-300"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth="2"
                                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                                                ></path>
                                            </svg>
                                            <p className="text-sm">
                                                No messages yet. Say hello!
                                            </p>
                                        </div>
                                    )}
                                </div>

                                <div className="rounded-b-xl border-t border-gray-200 bg-white p-4">
                                    <form
                                        onSubmit={(e) => {
                                            e.preventDefault();
                                            const form =
                                                e.target as HTMLFormElement;
                                            const input =
                                                form.elements.namedItem(
                                                    'body',
                                                ) as HTMLInputElement;
                                            if (!input.value.trim()) return;

                                            router.post(
                                                route(
                                                    'marketplace.orders.messages.store',
                                                    order.id,
                                                ),
                                                {
                                                    body: input.value,
                                                },
                                                {
                                                    preserveScroll: true,
                                                    onSuccess: () => {
                                                        input.value = '';
                                                    },
                                                },
                                            );
                                        }}
                                        className="flex gap-3"
                                    >
                                        <button
                                            type="button"
                                            className="text-gray-400 transition hover:text-indigo-600"
                                        >
                                            <svg
                                                className="h-6 w-6"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth="2"
                                                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                                                ></path>
                                            </svg>
                                        </button>
                                        <input
                                            type="text"
                                            name="body"
                                            placeholder="Type your message..."
                                            autoComplete="off"
                                            className="flex-1 rounded-full border-gray-300 bg-gray-50 px-4 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <button
                                            type="submit"
                                            className="flex items-center justify-center rounded-full bg-indigo-600 p-2.5 text-white transition hover:bg-indigo-700"
                                        >
                                            <svg
                                                className="ml-1 h-5 w-5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth="2"
                                                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                                                ></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {/* Right Column (40%) - Sticky Summary */}
                        <div className="w-full lg:w-2/5">
                            <div className="sticky top-6 flex flex-col gap-6">
                                {/* Order Info Card */}
                                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                    <div className="flex items-center justify-between border-b border-gray-200 bg-gray-50 p-4">
                                        <h4 className="font-bold text-gray-900">
                                            Order Details
                                        </h4>
                                        <span className="rounded bg-gray-200 px-2 py-1 font-mono text-xs text-gray-500">
                                            #
                                            {order.id
                                                .toString()
                                                .padStart(6, '0')}
                                        </span>
                                    </div>
                                    <div className="p-6">
                                        <div className="mb-4 flex items-center justify-between">
                                            <span className="text-sm text-gray-500">
                                                Package
                                            </span>
                                            <span className="rounded bg-indigo-50 px-2 py-0.5 text-sm font-bold text-gray-900 text-indigo-700">
                                                {order.package?.name}
                                            </span>
                                        </div>

                                        <div className="mb-6 space-y-3">
                                            <div className="flex items-start text-sm text-gray-600">
                                                <svg
                                                    className="mr-2 h-5 w-5 shrink-0 text-green-500"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        strokeWidth="2"
                                                        d="M5 13l4 4L19 7"
                                                    ></path>
                                                </svg>
                                                Source files included
                                            </div>
                                            <div className="flex items-start text-sm text-gray-600">
                                                <svg
                                                    className="mr-2 h-5 w-5 shrink-0 text-green-500"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        strokeWidth="2"
                                                        d="M5 13l4 4L19 7"
                                                    ></path>
                                                </svg>
                                                3 Revisions
                                            </div>
                                            <div className="flex items-start text-sm text-gray-600">
                                                <svg
                                                    className="mr-2 h-5 w-5 shrink-0 text-green-500"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        strokeWidth="2"
                                                        d="M5 13l4 4L19 7"
                                                    ></path>
                                                </svg>
                                                Commercial use
                                            </div>
                                        </div>

                                        <div className="mb-6 border-t border-gray-100 pt-4">
                                            {isSeller ? (
                                                <div className="space-y-2 text-sm">
                                                    <div className="flex justify-between text-gray-500">
                                                        <span>Price</span>
                                                        <span>
                                                            $
                                                            {order.amount.toFixed(
                                                                2,
                                                            )}
                                                        </span>
                                                    </div>
                                                    <div className="flex justify-between text-red-500">
                                                        <span>
                                                            Platform Fee (10%)
                                                        </span>
                                                        <span>
                                                            -${fee.toFixed(2)}
                                                        </span>
                                                    </div>
                                                    <div className="flex justify-between border-t border-gray-100 pt-2 font-bold text-gray-900">
                                                        <span>
                                                            Your Earnings
                                                        </span>
                                                        <span>
                                                            $
                                                            {sellerEarnings.toFixed(
                                                                2,
                                                            )}
                                                        </span>
                                                    </div>
                                                </div>
                                            ) : (
                                                <div className="flex justify-between text-lg font-bold text-gray-900">
                                                    <span>Total Paid</span>
                                                    <span>
                                                        $
                                                        {order.amount.toFixed(
                                                            2,
                                                        )}{' '}
                                                        {order.currency_code}
                                                    </span>
                                                </div>
                                            )}
                                        </div>

                                        {/* Deadline Countdown */}
                                        {(order.status === 'pending' ||
                                            order.status === 'in_progress') && (
                                            <div
                                                className={`flex items-center justify-between rounded-lg border p-4 ${isOverdue ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50'}`}
                                            >
                                                <div>
                                                    <h5
                                                        className={`text-sm font-bold ${isOverdue ? 'text-red-700' : 'text-amber-800'}`}
                                                    >
                                                        {isOverdue
                                                            ? 'Delivery Overdue'
                                                            : 'Time Left to Deliver'}
                                                    </h5>
                                                    <p
                                                        className={`text-xs ${isOverdue ? 'text-red-600' : 'text-amber-700'}`}
                                                    >
                                                        Deadline:{' '}
                                                        {deadlineDate.toLocaleDateString()}
                                                    </p>
                                                </div>
                                                <div
                                                    className={`font-mono text-xl font-bold ${isOverdue ? 'text-red-600' : 'text-amber-600'}`}
                                                >
                                                    {isOverdue
                                                        ? 'LATE'
                                                        : '3d 12h'}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Seller Delivery Action Box */}
                                {isSeller &&
                                    (order.status === 'pending' ||
                                        order.status === 'in_progress') && (
                                        <div className="overflow-hidden rounded-xl border border-indigo-500 bg-white shadow-sm">
                                            <div className="flex items-center gap-2 border-b border-indigo-100 bg-indigo-50 p-4">
                                                <svg
                                                    className="h-5 w-5 text-indigo-600"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        strokeWidth="2"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                                                    ></path>
                                                </svg>
                                                <h4 className="font-bold text-indigo-900">
                                                    Submit Delivery
                                                </h4>
                                            </div>
                                            <div className="p-6">
                                                <form onSubmit={handleDeliver}>
                                                    <div className="mb-4">
                                                        <label className="mb-2 block text-sm font-medium text-gray-700">
                                                            Delivery Note
                                                        </label>
                                                        <textarea
                                                            className="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                            rows={4}
                                                            placeholder="Describe what you are delivering..."
                                                            value={deliveryNote}
                                                            onChange={(e) =>
                                                                setDeliveryNote(
                                                                    e.target
                                                                        .value,
                                                                )
                                                            }
                                                            required
                                                        ></textarea>
                                                    </div>
                                                    <div className="mb-6">
                                                        <label className="mb-2 block text-sm font-medium text-gray-700">
                                                            Upload Files
                                                        </label>
                                                        <div className="cursor-pointer rounded-lg border-2 border-dashed border-gray-300 p-6 text-center transition hover:bg-gray-50">
                                                            <svg
                                                                className="mx-auto mb-2 h-8 w-8 text-gray-400"
                                                                fill="none"
                                                                stroke="currentColor"
                                                                viewBox="0 0 24 24"
                                                            >
                                                                <path
                                                                    strokeLinecap="round"
                                                                    strokeLinejoin="round"
                                                                    strokeWidth="2"
                                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                                                ></path>
                                                            </svg>
                                                            <p className="text-sm text-gray-600">
                                                                Click to browse
                                                                or drag & drop
                                                                files here
                                                            </p>
                                                            <p className="mt-1 text-xs text-gray-500">
                                                                Max 100MB per
                                                                file
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <button
                                                        type="submit"
                                                        className="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-3 font-bold text-white transition hover:bg-indigo-700"
                                                    >
                                                        <svg
                                                            className="h-5 w-5"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                strokeLinecap="round"
                                                                strokeLinejoin="round"
                                                                strokeWidth="2"
                                                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                                                            ></path>
                                                        </svg>
                                                        Send Delivery
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    )}

                                {/* Admin / Dispute Options */}
                                <div className="mt-2 text-center">
                                    <button
                                        onClick={() =>
                                            router.post(
                                                route(
                                                    'marketplace.orders.dispute',
                                                    order.id,
                                                ),
                                            )
                                        }
                                        className="text-xs text-gray-500 underline transition hover:text-red-600"
                                    >
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
