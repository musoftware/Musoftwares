import React, { useState } from 'react';

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
        <div className={`flex flex-col mb-4 ${isOwnMessage ? 'items-end' : 'items-start'} group`}>
            <div className="flex gap-2 items-end">
                {!isOwnMessage && (
                    <div className="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600 flex-shrink-0 border">
                        {message.sender?.name?.charAt(0) || '?'}
                    </div>
                )}

                <div className="flex flex-col relative">
                    {!isOwnMessage && (
                        <span className="text-xs font-medium mb-1 text-gray-500 ml-1">
                            {message.sender?.name}
                        </span>
                    )}

                    <div
                        className={`flex flex-col max-w-[280px] sm:max-w-md lg:max-w-lg rounded-2xl px-4 py-2 ${
                            isOwnMessage
                                ? 'bg-indigo-600 text-white rounded-br-sm'
                                : 'bg-white border text-gray-900 rounded-bl-sm shadow-sm'
                        }`}
                        title={`${formattedDate}, ${formattedTime}`}
                    >
                        {message.attachments && message.attachments.length > 0 && message.attachments.map(attachment => (
                            attachment.type === 'image' && (
                                <div key={attachment.id} className="mb-2">
                                    <img
                                        src={attachment.isTempUrl ? attachment.path : `/storage/${attachment.path}`}
                                        alt={attachment.original_name || 'attachment'}
                                        className={`rounded-lg cursor-pointer transition-all ${isImageExpanded ? 'max-w-full h-auto' : 'w-48 h-32 object-cover'}`}
                                        onClick={() => setIsImageExpanded(!isImageExpanded)}
                                    />
                                    {attachment.original_name && !isImageExpanded && (
                                        <p className="text-[10px] mt-1 opacity-70 truncate">{attachment.original_name}</p>
                                    )}
                                </div>
                            )
                        ))}

                        {message.body && (
                            <span className="text-sm whitespace-pre-wrap">{message.body}</span>
                        )}

                        <div className={`flex justify-end items-center gap-1 mt-1 text-[10px] ${isOwnMessage ? 'text-indigo-200' : 'text-gray-400'}`}>
                            <span className="opacity-0 group-hover:opacity-100 transition-opacity">
                                {formattedTime}
                            </span>
                            {isOwnMessage && (
                                <span className="ml-1" title={message.read ? "Read" : "Delivered"}>
                                    {message.read ? (
                                        // Double checkmark for read
                                        <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 13l4 4L21 7" className="text-indigo-300" />
                                        </svg>
                                    ) : (
                                        // Single checkmark for delivered
                                        <svg className="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    )}
                                </span>
                            )}
                        </div>
                    </div>
                </div>

                {isOwnMessage && (
                    <div className="w-8 h-8 rounded-full bg-indigo-200 flex items-center justify-center text-xs font-bold text-indigo-800 flex-shrink-0 border border-indigo-300">
                        {message.sender?.name?.charAt(0) || 'Me'}
                    </div>
                )}
            </div>
        </div>
    );
}
