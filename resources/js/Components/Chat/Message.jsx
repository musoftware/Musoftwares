import { useState } from 'react';

function formatAttachmentUrl(path) {
    if (!path) return '';
    if (path.startsWith('blob:') || path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }
    if (path.startsWith('/storage/')) {
        return path;
    }
    if (path.startsWith('storage/')) {
        return `/${path}`;
    }
    return `/storage/${path}`;
}

export default function Message({ message, isOwnMessage }) {
    const [isImageExpanded, setIsImageExpanded] = useState(false);

    const formattedTime = new Date(message.created_at).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    });

    const formattedDate = new Date(message.created_at).toLocaleDateString([], {
        month: 'short',
        day: 'numeric',
    });

    return (
        <div
            className={`mb-4 flex flex-col ${isOwnMessage ? 'items-end' : 'items-start'} group`}
        >
            <div className="flex items-end gap-2">
                {!isOwnMessage && (
                    <div className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full border bg-gray-200 text-xs font-bold text-gray-600">
                        {message.sender?.name?.charAt(0) || '?'}
                    </div>
                )}

                <div className="relative flex flex-col">
                    {!isOwnMessage && (
                        <span className="mb-1 ms-1 text-xs font-medium text-gray-500">
                            {message.sender?.name}
                        </span>
                    )}

                    <div
                        className={`flex max-w-[280px] flex-col rounded-2xl px-4 py-2 sm:max-w-md lg:max-w-lg ${
                            isOwnMessage
                                ? 'rounded-be-sm bg-indigo-600 text-white'
                                : 'rounded-bs-sm border bg-white text-gray-900 shadow-sm'
                        }`}
                        title={`${formattedDate}, ${formattedTime}`}
                    >
                        {/* Handle single file path attachment */}
                        {message.attachment && (
                            <div className="mb-2">
                                {/\.(jpeg|jpg|gif|png|svg|webp)$/i.test(message.attachment) ? (
                                    <img
                                        src={formatAttachmentUrl(message.attachment)}
                                        alt="attachment"
                                        className={`cursor-pointer rounded-lg transition-all ${isImageExpanded ? 'h-auto max-w-full' : 'h-32 w-48 object-cover'}`}
                                        onClick={() => setIsImageExpanded(!isImageExpanded)}
                                    />
                                ) : (
                                    <a
                                        href={formatAttachmentUrl(message.attachment)}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className={`inline-flex items-center gap-1.5 text-xs font-semibold underline p-2 rounded-lg ${isOwnMessage ? 'bg-indigo-700/50 text-white' : 'bg-slate-100 text-indigo-600 hover:bg-slate-200'}`}
                                    >
                                        📎 {message.attachment.split('/').pop() || 'Attachment'}
                                    </a>
                                )}
                            </div>
                        )}

                        {/* Handle attachments array */}
                        {message.attachments &&
                            message.attachments.length > 0 &&
                            message.attachments.map(
                                (attachment) =>
                                    attachment.type === 'image' && (
                                        <div
                                            key={attachment.id}
                                            className="mb-2"
                                        >
                                            <img
                                                src={
                                                    attachment.isTempUrl
                                                        ? attachment.path
                                                        : formatAttachmentUrl(attachment.path)
                                                }
                                                alt={
                                                    attachment.original_name ||
                                                    'attachment'
                                                }
                                                className={`cursor-pointer rounded-lg transition-all ${isImageExpanded ? 'h-auto max-w-full' : 'h-32 w-48 object-cover'}`}
                                                onClick={() =>
                                                    setIsImageExpanded(
                                                        !isImageExpanded,
                                                    )
                                                }
                                            />
                                            {attachment.original_name &&
                                                !isImageExpanded && (
                                                    <p className="mt-1 truncate text-[10px] opacity-70">
                                                        {
                                                            attachment.original_name
                                                        }
                                                    </p>
                                                )}
                                        </div>
                                    ),
                            )}

                        {message.body && (
                            <span className="text-sm whitespace-pre-wrap">
                                {message.body}
                            </span>
                        )}

                        <div
                            className={`mt-1 flex items-center justify-end gap-1 text-[10px] ${isOwnMessage ? 'text-indigo-200' : 'text-gray-400'}`}
                        >
                            <span className="opacity-0 transition-opacity group-hover:opacity-100">
                                {formattedTime}
                            </span>
                            {isOwnMessage && (
                                <span
                                    className="ms-1"
                                    title={message.read ? 'Read' : 'Delivered'}
                                >
                                    {message.read ? (
                                        // Double checkmark for read
                                        <svg
                                            className="h-4 w-4 text-white"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth="2"
                                                d="M5 13l4 4L19 7"
                                            />
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth="2"
                                                d="M11 13l4 4L21 7"
                                                className="text-indigo-300"
                                            />
                                        </svg>
                                    ) : (
                                        // Single checkmark for delivered
                                        <svg
                                            className="h-3.5 w-3.5 opacity-70"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth="2"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    )}
                                </span>
                            )}
                        </div>
                    </div>
                </div>

                {isOwnMessage && (
                    <div className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full border border-indigo-300 bg-indigo-200 text-xs font-bold text-indigo-800">
                        {message.sender?.name?.charAt(0) || 'Me'}
                    </div>
                )}
            </div>
        </div>
    );
}
