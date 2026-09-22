import React, { useState } from 'react';
import { 
    FileText, 
    FileArchive, 
    Music, 
    Video, 
    Download, 
    ExternalLink, 
    Check, 
    CheckCheck, 
    Image as ImageIcon,
    File,
    X,
    Maximize2
} from 'lucide-react';

export function formatAttachmentUrl(path) {
    if (!path) return '';
    if (path.startsWith('blob:') || path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }
    if (path.startsWith('/storage/') || path.startsWith('/uploads/')) {
        return path;
    }
    if (path.startsWith('storage/')) {
        return `/${path}`;
    }
    if (path.startsWith('uploads/')) {
        return `/${path}`;
    }
    return `/storage/${path}`;
}

const IMAGE_REGEX = /\.(jpeg|jpg|gif|png|svg|webp|jfif|avif|bmp)$/i;
const AUDIO_REGEX = /\.(mp3|wav|ogg|m4a|webm|aac)$/i;
const VIDEO_REGEX = /\.(mp4|webm|mov|m4v|ogv)$/i;
const ARCHIVE_REGEX = /\.(zip|rar|7z|tar|gz)$/i;
const DOCUMENT_REGEX = /\.(pdf|doc|docx|xls|xlsx|ppt|pptx|txt|csv)$/i;

export default function Message({ message, isOwnMessage }) {
    const [imageError, setImageError] = useState(false);
    const [lightboxOpen, setLightboxOpen] = useState(false);
    const [activeLightboxSrc, setActiveLightboxSrc] = useState('');

    const formattedTime = new Date(message.created_at).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    });

    const formattedDate = new Date(message.created_at).toLocaleDateString([], {
        month: 'short',
        day: 'numeric',
    });

    const openLightbox = (src) => {
        setActiveLightboxSrc(src);
        setLightboxOpen(true);
    };

    const fileNameFromPath = (path) => {
        if (!path) return 'file';
        return path.split('/').pop()?.split('?')[0] || 'file';
    };

    // Filter out legacy automated strings like "Sent an image" when attachment is present
    const isLegacyAutoBody = 
        message.body === 'Sent an image' || 
        message.body === 'Sent a file' || 
        message.body === 'Sent a voice message';
    const showBody = message.body && (!isLegacyAutoBody || (!message.attachment && (!message.attachments || message.attachments.length === 0)));

    const senderInitial = message.sender?.name?.charAt(0)?.toUpperCase() || (isOwnMessage ? 'U' : '?');

    return (
        <div className={`mb-4 flex flex-col ${isOwnMessage ? 'items-end' : 'items-start'} group transition-all`}>
            <div className={`flex items-start gap-2.5 max-w-[85%] sm:max-w-[75%] ${isOwnMessage ? 'flex-row-reverse' : 'flex-row'}`}>
                
                {/* Sender Avatar */}
                <div 
                    className={`h-8 w-8 rounded-full flex items-center justify-center shrink-0 text-xs font-bold shadow-2xs select-none mt-0.5 ${
                        isOwnMessage 
                            ? 'bg-[#0071e3] text-white' 
                            : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 border border-black/5 dark:border-white/10'
                    }`}
                    title={message.sender?.name || (isOwnMessage ? 'You' : 'Participant')}
                >
                    {senderInitial}
                </div>

                {/* Bubble Container */}
                <div className="flex flex-col min-w-0">
                    {/* Sender Name (for incoming messages) */}
                    {!isOwnMessage && (
                        <div className="flex items-center gap-1.5 mb-1 px-1">
                            <span className="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                {message.sender?.name || 'Support'}
                            </span>
                            {message.is_internal && (
                                <span className="text-[10px] px-1.5 py-0.2 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 font-medium">
                                    Internal
                                </span>
                            )}
                        </div>
                    )}

                    {/* Chat Bubble Body */}
                    <div
                        className={`rounded-2xl px-4 py-2.5 transition-all text-sm shadow-2xs ${
                            isOwnMessage
                                ? 'bg-[#0071e3] text-white rounded-br-xs'
                                : 'bg-white dark:bg-zinc-800 text-[#1d1d1f] dark:text-zinc-100 border border-black/5 dark:border-white/10 rounded-bl-xs'
                        }`}
                        title={`${formattedDate} · ${formattedTime}`}
                    >
                        {/* Attachments rendering (unified to prevent duplicate rendering) */}
                        {(() => {
                            const rawList = (message.attachments && message.attachments.length > 0)
                                ? message.attachments
                                : message.attachment
                                  ? [{ id: 'single', path: message.attachment, name: fileNameFromPath(message.attachment) }]
                                  : [];

                            if (rawList.length === 0) return null;

                            return (
                                <div className="space-y-2 mb-2">
                                    {rawList.map((att, idx) => {
                                        const rawPath = att.path || att;
                                        const src = att.isTempUrl ? rawPath : formatAttachmentUrl(rawPath);
                                        const isImg = !imageError && (att.type === 'image' || IMAGE_REGEX.test(rawPath));
                                        const isAudio = att.type === 'audio' || AUDIO_REGEX.test(rawPath);
                                        const isVideo = att.type === 'video' || VIDEO_REGEX.test(rawPath);
                                        const isArchive = ARCHIVE_REGEX.test(rawPath);
                                        const isDoc = DOCUMENT_REGEX.test(rawPath);
                                        const displayName = att.original_name || att.name || fileNameFromPath(rawPath);

                                        if (isImg) {
                                            return (
                                                <div key={att.id || idx} className="relative group/img overflow-hidden rounded-xl bg-black/5 dark:bg-white/5 border border-black/5">
                                                    <img
                                                        src={src}
                                                        alt={displayName}
                                                        className="max-h-72 max-w-full rounded-xl object-contain cursor-pointer hover:opacity-95 transition-opacity"
                                                        onClick={() => openLightbox(src)}
                                                        onError={() => setImageError(true)}
                                                        loading="lazy"
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={() => openLightbox(src)}
                                                        className="absolute bottom-2 end-2 p-1.5 rounded-lg bg-black/60 text-white opacity-0 group-hover/img:opacity-100 transition-opacity backdrop-blur-xs cursor-pointer"
                                                        title="Expand Image"
                                                    >
                                                        <Maximize2 className="w-3.5 h-3.5" />
                                                    </button>
                                                </div>
                                            );
                                        }

                                        if (isAudio) {
                                            return (
                                                <div key={att.id || idx} className="p-2.5 rounded-xl bg-black/5 dark:bg-white/10 my-1">
                                                    <audio controls src={src} className="w-full max-w-[260px] h-9" />
                                                </div>
                                            );
                                        }

                                        if (isVideo) {
                                            return (
                                                <div key={att.id || idx} className="my-1 rounded-xl overflow-hidden border border-black/10">
                                                    <video controls playsInline src={src} className="rounded-xl max-h-60 max-w-full" />
                                                </div>
                                            );
                                        }

                                        return (
                                            <a
                                                key={att.id || idx}
                                                href={src}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                download
                                                className={`flex items-center gap-2.5 p-2.5 rounded-xl transition-colors border text-xs font-medium ${
                                                    isOwnMessage
                                                        ? 'bg-white/10 border-white/20 hover:bg-white/20 text-white'
                                                        : 'bg-zinc-50 dark:bg-zinc-700/50 border-black/5 dark:border-white/10 hover:bg-zinc-100 text-zinc-900 dark:text-zinc-100'
                                                }`}
                                            >
                                                <div className={`p-2 rounded-lg ${isOwnMessage ? 'bg-white/20' : 'bg-zinc-200 dark:bg-zinc-600'}`}>
                                                    {isArchive ? (
                                                        <FileArchive className="w-4 h-4" />
                                                    ) : isDoc ? (
                                                        <FileText className="w-4 h-4" />
                                                    ) : (
                                                        <File className="w-4 h-4" />
                                                    )}
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <p className="truncate font-semibold text-xs">{displayName}</p>
                                                    <p className="text-[10px] opacity-75">Click to download</p>
                                                </div>
                                                <Download className="w-4 h-4 shrink-0 opacity-70" />
                                            </a>
                                        );
                                    })}
                                </div>
                            );
                        })()}

                        {/* Text Body */}
                        {showBody && (
                            <p className="whitespace-pre-wrap leading-relaxed break-words dir-auto text-[13px] sm:text-sm font-sans" dir="auto">
                                {message.body}
                            </p>
                        )}

                        {/* Footer: Timestamp & Read Status */}
                        <div className={`mt-1.5 flex items-center justify-end gap-1.5 text-[10px] font-sans select-none ${
                            isOwnMessage ? 'text-white/80' : 'text-zinc-400 dark:text-zinc-500'
                        }`}>
                            <span>{formattedTime}</span>
                            {isOwnMessage && (
                                <span title={message.read ? 'Read' : 'Sent'}>
                                    {message.read ? (
                                        <CheckCheck className="w-3.5 h-3.5 text-white stroke-[2.5]" />
                                    ) : (
                                        <Check className="w-3.5 h-3.5 text-white/80 stroke-[2]" />
                                    )}
                                </span>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Lightbox Modal */}
            {lightboxOpen && (
                <div 
                    className="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4"
                    onClick={() => setLightboxOpen(false)}
                >
                    <div 
                        className="relative max-w-4xl max-h-[90vh] flex flex-col items-center justify-center"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <img 
                            src={activeLightboxSrc} 
                            alt="Preview" 
                            className="max-h-[80vh] max-w-full rounded-xl object-contain shadow-2xl" 
                        />
                        <div className="flex items-center gap-3 mt-4">
                            <a
                                href={activeLightboxSrc}
                                target="_blank"
                                rel="noopener noreferrer"
                                download
                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/20 hover:bg-white/30 text-white text-xs font-semibold backdrop-blur-md transition-colors"
                            >
                                <Download className="w-3.5 h-3.5" />
                                Download Full Image
                            </a>
                            <button
                                type="button"
                                onClick={() => setLightboxOpen(false)}
                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/20 text-white text-xs font-semibold backdrop-blur-md transition-colors cursor-pointer"
                            >
                                <X className="w-3.5 h-3.5" />
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
