import React, { useState, useEffect, useCallback } from 'react';
import { X, ChevronLeft, ChevronRight, ZoomIn } from 'lucide-react';

interface Screenshot {
    id: number;
    url: string;
    caption: string | null;
}

interface ScreenshotGalleryProps {
    screenshots: Screenshot[];
}

export function ScreenshotGallery({ screenshots }: ScreenshotGalleryProps) {
    const [active, setActive] = useState(0);
    const [lightboxOpen, setLightboxOpen] = useState(false);
    const [lightboxIndex, setLightboxIndex] = useState(0);

    const openLightbox = (i: number) => {
        setLightboxIndex(i);
        setLightboxOpen(true);
    };

    const closeLightbox = () => setLightboxOpen(false);

    const prevLight = useCallback(() => setLightboxIndex(i => (i - 1 + screenshots.length) % screenshots.length), [screenshots.length]);
    const nextLight = useCallback(() => setLightboxIndex(i => (i + 1) % screenshots.length), [screenshots.length]);

    useEffect(() => {
        if (!lightboxOpen) return;
        const handler = (e: KeyboardEvent) => {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') prevLight();
            if (e.key === 'ArrowRight') nextLight();
        };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [lightboxOpen, prevLight, nextLight]);

    if (!screenshots || screenshots.length === 0) return null;

    return (
        <>
            <div className="space-y-3">
                {/* Main preview */}
                <div
                    className="relative group rounded-xl overflow-hidden bg-slate-900 aspect-video cursor-zoom-in border border-slate-200"
                    onClick={() => openLightbox(active)}
                >
                    <img
                        src={screenshots[active]?.url}
                        alt={screenshots[active]?.caption ?? ''}
                        className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-[1.01]"
                    />
                    <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-200 flex items-center justify-center">
                        <div className="opacity-0 group-hover:opacity-100 transition-opacity bg-white/90 backdrop-blur-sm rounded-full p-2">
                            <ZoomIn className="h-5 w-5 text-slate-700" />
                        </div>
                    </div>
                    {screenshots[active]?.caption && (
                        <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4">
                            <p className="text-white text-sm font-medium">{screenshots[active].caption}</p>
                        </div>
                    )}
                </div>

                {/* Thumbnails */}
                {screenshots.length > 1 && (
                    <div className="flex gap-2 overflow-x-auto pb-1">
                        {screenshots.map((s, i) => (
                            <button
                                key={s.id}
                                onClick={() => setActive(i)}
                                className={`flex-shrink-0 w-20 h-14 rounded-lg overflow-hidden border-2 transition-all duration-150 ${
                                    active === i
                                        ? 'border-slate-900 shadow-sm'
                                        : 'border-transparent opacity-50 hover:opacity-80 hover:border-slate-300'
                                }`}
                            >
                                <img src={s.url} alt={s.caption ?? ''} className="w-full h-full object-cover" />
                            </button>
                        ))}
                    </div>
                )}
            </div>

            {/* Lightbox */}
            {lightboxOpen && (
                <div
                    className="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4"
                    onClick={closeLightbox}
                >
                    <button
                        className="absolute top-4 right-4 text-white/70 hover:text-white transition-colors p-2"
                        onClick={closeLightbox}
                    >
                        <X className="h-6 w-6" />
                    </button>

                    {screenshots.length > 1 && (
                        <>
                            <button
                                className="absolute left-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white transition-colors p-2 bg-white/10 rounded-full"
                                onClick={e => { e.stopPropagation(); prevLight(); }}
                            >
                                <ChevronLeft className="h-6 w-6" />
                            </button>
                            <button
                                className="absolute right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white transition-colors p-2 bg-white/10 rounded-full"
                                onClick={e => { e.stopPropagation(); nextLight(); }}
                            >
                                <ChevronRight className="h-6 w-6" />
                            </button>
                        </>
                    )}

                    <div className="max-w-5xl max-h-full" onClick={e => e.stopPropagation()}>
                        <img
                            src={screenshots[lightboxIndex]?.url}
                            alt={screenshots[lightboxIndex]?.caption ?? ''}
                            className="max-w-full max-h-[85vh] object-contain rounded-lg"
                        />
                        {screenshots[lightboxIndex]?.caption && (
                            <p className="text-white/70 text-sm text-center mt-3">{screenshots[lightboxIndex].caption}</p>
                        )}
                        <p className="text-white/40 text-xs text-center mt-1">{lightboxIndex + 1} / {screenshots.length}</p>
                    </div>
                </div>
            )}
        </>
    );
}
