import React, { useRef, useState, useEffect } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface MobileTouchSliderProps {
    children: React.ReactNode[];
    className?: string;
    itemClassName?: string;
    showArrows?: boolean;
    showDots?: boolean;
    ariaLabel?: string;
}

export default function MobileTouchSlider({
    children,
    className = '',
    itemClassName = 'w-[85%] sm:w-[48%] md:w-[32%] flex-shrink-0',
    showArrows = true,
    showDots = true,
    ariaLabel = 'Carousel',
}: MobileTouchSliderProps) {
    const scrollContainerRef = useRef<HTMLDivElement>(null);
    const [activeIndex, setActiveIndex] = useState(0);
    const [canScrollLeft, setCanScrollLeft] = useState(false);
    const [canScrollRight, setCanScrollRight] = useState(true);

    const updateScrollState = () => {
        const container = scrollContainerRef.current;
        if (!container) return;

        const { scrollLeft, scrollWidth, clientWidth } = container;
        const maxScroll = scrollWidth - clientWidth;
        const currentScroll = Math.abs(scrollLeft);

        setCanScrollLeft(currentScroll > 10);
        setCanScrollRight(currentScroll < maxScroll - 10);

        // Approximate active index
        const childWidth = container.firstElementChild ? (container.firstElementChild as HTMLElement).offsetWidth + 16 : clientWidth;
        const index = Math.round(currentScroll / childWidth);
        setActiveIndex(Math.min(Math.max(0, index), children.length - 1));
    };

    useEffect(() => {
        const container = scrollContainerRef.current;
        if (!container) return;

        container.addEventListener('scroll', updateScrollState, { passive: true });
        updateScrollState();

        return () => container.removeEventListener('scroll', updateScrollState);
    }, [children.length]);

    const scrollToIndex = (index: number) => {
        const container = scrollContainerRef.current;
        if (!container) return;

        const child = container.children[index] as HTMLElement;
        if (child) {
            child.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    };

    const scrollByStep = (direction: 'next' | 'prev') => {
        const container = scrollContainerRef.current;
        if (!container) return;

        const amount = container.clientWidth * 0.75;
        container.scrollBy({
            left: direction === 'next' ? amount : -amount,
            behavior: 'smooth',
        });
    };

    return (
        <div className={`relative w-full ${className}`} aria-label={ariaLabel}>
            {/* Scrollable Container */}
            <div
                ref={scrollContainerRef}
                className="flex items-stretch gap-4 sm:gap-6 overflow-x-auto snap-x snap-mandatory scrollbar-none py-2 px-1 focus:outline-none -mx-4 px-4 sm:mx-0 sm:px-0"
                style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
            >
                {React.Children.map(children, (child, idx) => (
                    <div key={idx} className={`snap-center sm:snap-start ${itemClassName}`}>
                        {child}
                    </div>
                ))}
            </div>

            {/* Desktop / Tablet Navigation Arrows */}
            {showArrows && children.length > 1 && (
                <div className="hidden sm:flex items-center justify-end gap-2 mt-4">
                    <button
                        type="button"
                        onClick={() => scrollByStep('prev')}
                        disabled={!canScrollLeft}
                        className="p-2 rounded-full border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-100 disabled:opacity-30 disabled:pointer-events-none transition shadow-sm cursor-pointer"
                        aria-label="Previous"
                    >
                        <ChevronLeft className="w-4 h-4 rtl:rotate-180" />
                    </button>
                    <button
                        type="button"
                        onClick={() => scrollByStep('next')}
                        disabled={!canScrollRight}
                        className="p-2 rounded-full border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-100 disabled:opacity-30 disabled:pointer-events-none transition shadow-sm cursor-pointer"
                        aria-label="Next"
                    >
                        <ChevronRight className="w-4 h-4 rtl:rotate-180" />
                    </button>
                </div>
            )}

            {/* Mobile Pagination Dots */}
            {showDots && children.length > 1 && (
                <div className="flex sm:hidden justify-center items-center gap-1.5 mt-4">
                    {children.map((_, idx) => (
                        <button
                            key={idx}
                            type="button"
                            onClick={() => scrollToIndex(idx)}
                            className={`h-1.5 rounded-full transition-all duration-300 ${
                                activeIndex === idx ? 'w-6 bg-zinc-900' : 'w-1.5 bg-zinc-300 hover:bg-zinc-400'
                            }`}
                            aria-label={`Go to slide ${idx + 1}`}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
