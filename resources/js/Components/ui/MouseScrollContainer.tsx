import React, { useRef, useState, useEffect, useCallback } from 'react';

interface MouseScrollContainerProps {
    children: React.ReactNode;
    className?: string;
    contentClassName?: string;
    showFadeMasks?: boolean;
    proximityZoneRatio?: number; // ratio of edge width to trigger auto-scroll (e.g. 0.18 = 18%)
    maxScrollSpeed?: number;     // max pixels per frame
    enableDrag?: boolean;
    enableWheel?: boolean;
    autoScrollOnHoverChild?: boolean;
}

export default function MouseScrollContainer({
    children,
    className = '',
    contentClassName = '',
    showFadeMasks = true,
    proximityZoneRatio = 0.2,
    maxScrollSpeed = 16,
    enableDrag = true,
    enableWheel = true,
    autoScrollOnHoverChild = true,
}: MouseScrollContainerProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const animFrameRef = useRef<number | null>(null);
    const scrollVelocityRef = useRef<number>(0);

    // Fade mask states
    const [canScrollLeft, setCanScrollLeft] = useState(false);
    const [canScrollRight, setCanScrollRight] = useState(false);

    // Mouse drag states
    const isMouseDownRef = useRef(false);
    const startXRef = useRef(0);
    const scrollLeftStartRef = useRef(0);
    const hasDraggedRef = useRef(false);

    // Update fade indicators based on scroll boundaries
    const checkScrollBoundaries = useCallback(() => {
        const el = containerRef.current;
        if (!el) return;

        const isRtl = getComputedStyle(el).direction === 'rtl';
        const scrollWidth = el.scrollWidth;
        const clientWidth = el.clientWidth;
        const maxScroll = scrollWidth - clientWidth;

        if (maxScroll <= 2) {
            setCanScrollLeft(false);
            setCanScrollRight(false);
            return;
        }

        if (isRtl) {
            // In RTL browsers, scrollLeft is either negative or 0 to -maxScroll
            const absScroll = Math.abs(el.scrollLeft);
            setCanScrollLeft(absScroll < maxScroll - 2);
            setCanScrollRight(absScroll > 2);
        } else {
            setCanScrollLeft(el.scrollLeft > 2);
            setCanScrollRight(el.scrollLeft < maxScroll - 2);
        }
    }, []);

    // Continuous scroll animation loop when hovering near edges
    const startScrollLoop = useCallback(() => {
        if (animFrameRef.current !== null) return;

        const loop = () => {
            const el = containerRef.current;
            if (el && scrollVelocityRef.current !== 0) {
                el.scrollLeft += scrollVelocityRef.current;
                checkScrollBoundaries();
                animFrameRef.current = requestAnimationFrame(loop);
            } else {
                animFrameRef.current = null;
            }
        };

        animFrameRef.current = requestAnimationFrame(loop);
    }, [checkScrollBoundaries]);

    const stopScrollLoop = useCallback(() => {
        scrollVelocityRef.current = 0;
        if (animFrameRef.current !== null) {
            cancelAnimationFrame(animFrameRef.current);
            animFrameRef.current = null;
        }
    }, []);

    // Handle mouse movement across container to trigger edge auto-scrolling
    const handleMouseMove = (e: React.MouseEvent<HTMLDivElement>) => {
        if (isMouseDownRef.current && enableDrag) {
            // Dragging mode
            const dx = e.pageX - startXRef.current;
            if (Math.abs(dx) > 5) {
                hasDraggedRef.current = true;
            }
            if (containerRef.current) {
                containerRef.current.scrollLeft = scrollLeftStartRef.current - dx;
                checkScrollBoundaries();
            }
            return;
        }

        const el = containerRef.current;
        if (!el) return;

        const rect = el.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const width = rect.width;

        if (width <= 0) return;

        const isRtl = getComputedStyle(el).direction === 'rtl';
        const zoneWidth = Math.max(40, width * proximityZoneRatio);

        if (mouseX < zoneWidth) {
            // Near Left Edge
            const intensity = 1 - Math.max(0, mouseX) / zoneWidth;
            const dir = isRtl ? 1 : -1;
            scrollVelocityRef.current = dir * maxScrollSpeed * intensity;
            startScrollLoop();
        } else if (mouseX > width - zoneWidth) {
            // Near Right Edge
            const intensity = 1 - Math.max(0, width - mouseX) / zoneWidth;
            const dir = isRtl ? -1 : 1;
            scrollVelocityRef.current = dir * maxScrollSpeed * intensity;
            startScrollLoop();
        } else {
            // In center dead-zone
            stopScrollLoop();
        }
    };

    const handleMouseLeave = () => {
        stopScrollLoop();
        isMouseDownRef.current = false;
    };

    // Drag-to-scroll listeners
    const handleMouseDown = (e: React.MouseEvent<HTMLDivElement>) => {
        if (!enableDrag) return;
        // Only react to primary mouse button
        if (e.button !== 0) return;

        isMouseDownRef.current = true;
        hasDraggedRef.current = false;
        startXRef.current = e.pageX;
        if (containerRef.current) {
            scrollLeftStartRef.current = containerRef.current.scrollLeft;
        }
    };

    const handleMouseUp = () => {
        isMouseDownRef.current = false;
    };

    // Prevent accidental click if user was dragging
    const handleClickCapture = (e: React.MouseEvent) => {
        if (hasDraggedRef.current) {
            e.preventDefault();
            e.stopPropagation();
            hasDraggedRef.current = false;
        }
    };

    // Convert vertical mouse wheel into horizontal scroll
    useEffect(() => {
        const el = containerRef.current;
        if (!el || !enableWheel) return;

        const handleWheel = (e: WheelEvent) => {
            // If deltaX is already horizontal, let browser handle it
            if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) return;

            // Only hijack if container has overflow to scroll
            const maxScroll = el.scrollWidth - el.clientWidth;
            if (maxScroll <= 0) return;

            e.preventDefault();
            const isRtl = getComputedStyle(el).direction === 'rtl';
            const multiplier = isRtl ? -1 : 1;
            el.scrollLeft += e.deltaY * multiplier;
            checkScrollBoundaries();
        };

        el.addEventListener('wheel', handleWheel, { passive: false });
        return () => {
            el.removeEventListener('wheel', handleWheel);
        };
    }, [enableWheel, checkScrollBoundaries]);

    // Check boundaries on mount, resize, and scroll
    useEffect(() => {
        const el = containerRef.current;
        if (!el) return;

        checkScrollBoundaries();

        const observer = new ResizeObserver(() => {
            checkScrollBoundaries();
        });
        observer.observe(el);

        return () => {
            observer.disconnect();
            stopScrollLoop();
        };
    }, [checkScrollBoundaries, stopScrollLoop]);

    // Auto-scroll hovered child into view if partially hidden
    const handleChildMouseEnter = (e: React.MouseEvent<HTMLDivElement>) => {
        if (!autoScrollOnHoverChild || !containerRef.current) return;
        
        // Find nearest interactive or child element inside container
        const target = (e.target as HTMLElement).closest('button, a, [data-scroll-item]') as HTMLElement;
        if (!target || !containerRef.current.contains(target)) return;

        const containerRect = containerRef.current.getBoundingClientRect();
        const targetRect = target.getBoundingClientRect();

        // If target is cut off on left or right, smoothly scroll into view
        const padding = 16;
        if (targetRect.left < containerRect.left + padding || targetRect.right > containerRect.right - padding) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
                inline: 'nearest'
            });
            // Update boundaries after smooth scroll settles
            setTimeout(checkScrollBoundaries, 350);
        }
    };

    return (
        <div className={`relative overflow-hidden group/mousescroll select-none ${className}`}>
            {/* Left Fade Gradient Mask */}
            {showFadeMasks && canScrollLeft && (
                <div 
                    aria-hidden="true"
                    className="absolute start-0 top-0 bottom-0 w-8 sm:w-12 bg-gradient-to-r rtl:bg-gradient-to-l from-white/95 to-transparent pointer-events-none z-10 transition-opacity duration-300" 
                />
            )}

            {/* Scrollable Viewport */}
            <div
                ref={containerRef}
                onMouseMove={handleMouseMove}
                onMouseLeave={handleMouseLeave}
                onMouseDown={handleMouseDown}
                onMouseUp={handleMouseUp}
                onClickCapture={handleClickCapture}
                onScroll={checkScrollBoundaries}
                onMouseOver={handleChildMouseEnter}
                className={`overflow-x-auto scrollbar-none touch-pan-x cursor-grab active:cursor-grabbing flex items-center ${contentClassName}`}
                style={{
                    WebkitOverflowScrolling: 'touch',
                    scrollbarWidth: 'none',
                    msOverflowStyle: 'none'
                }}
            >
                {children}
            </div>

            {/* Right Fade Gradient Mask */}
            {showFadeMasks && canScrollRight && (
                <div 
                    aria-hidden="true"
                    className="absolute end-0 top-0 bottom-0 w-8 sm:w-12 bg-gradient-to-l rtl:bg-gradient-to-r from-white/95 to-transparent pointer-events-none z-10 transition-opacity duration-300" 
                />
            )}
        </div>
    );
}
