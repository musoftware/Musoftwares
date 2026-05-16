import React from 'react';

export default function Message({ message, isOwnMessage }) {
    const formattedTime = new Date(message.created_at).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    });

    return (
        <div className={`flex flex-col mb-4 ${isOwnMessage ? 'items-end' : 'items-start'}`}>
            <div className="flex gap-2 items-end">
                {!isOwnMessage && (
                    <div className="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-xs font-bold text-gray-600 flex-shrink-0">
                        {message.sender?.name?.charAt(0) || '?'}
                    </div>
                )}

                <div className={`flex flex-col max-w-[75%] rounded-lg px-4 py-2 ${isOwnMessage ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-900'}`}>
                    {!isOwnMessage && (
                        <span className="text-xs font-semibold mb-1 opacity-75">
                            {message.sender?.name}
                        </span>
                    )}

                    {message.attachments && message.attachments.length > 0 && message.attachments.map(attachment => (
                        attachment.type === 'image' && (
                            <img
                                key={attachment.id}
                                src={attachment.isTempUrl ? attachment.path : `/storage/${attachment.path}`}
                                alt={attachment.original_name || 'attachment'}
                                className="rounded-lg mb-2 max-w-full h-auto"
                            />
                        )
                    ))}

                    {message.body && (
                        <span className="text-sm whitespace-pre-wrap">{message.body}</span>
                    )}

                    <div className={`flex justify-end items-center gap-1 mt-1 text-[10px] ${isOwnMessage ? 'text-blue-100' : 'text-gray-500'}`}>
                        <span>{formattedTime}</span>
                        {isOwnMessage && (
                            <span className="ml-1">
                                {message.read ? (
                                    <svg className="w-3 h-3 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7m-9 6l4 4m4-11l-8 8" /></svg>
                                ) : (
                                    <svg className="w-3 h-3 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" /></svg>
                                )}
                            </span>
                        )}
                    </div>
                </div>

                {isOwnMessage && (
                    <div className="w-8 h-8 rounded-full bg-blue-300 flex items-center justify-center text-xs font-bold text-blue-800 flex-shrink-0">
                        {message.sender?.name?.charAt(0) || 'Me'}
                    </div>
                )}
            </div>
        </div>
    );
}
