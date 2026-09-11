/**
 * Universal Mouse Proximity & Drag Horizontal Scroll Handler
 * Works on any element with `[data-mouse-scroll]` attribute or `.mouse-scroll-container` class.
 * Supports:
 * 1. Edge-proximity auto-scrolling (moves left/right as mouse nears boundary)
 * 2. Click-and-drag scrolling on desktop
 * 3. Mouse wheel vertical to horizontal translation
 * 4. Child focus/hover auto-reveal (scrollIntoView if partially obscured)
 * 5. Dynamic edge fade indicators (RTL and LTR aware)
 * 6. Native smooth touch swipe on mobile devices
 */

interface MouseScrollOptions {
    proximityRatio?: number;
    maxSpeed?: number;
    enableDrag?: boolean;
    enableWheel?: boolean;
    autoRevealChild?: boolean;
}

export function initMouseScroll(container: HTMLElement, options: MouseScrollOptions = {}): () => void {
    if (!container || (container as any).__mouseScrollInitialized) {
        return () => {};
    }
    (container as any).__mouseScrollInitialized = true;

    const proximityRatio = options.proximityRatio ?? 0.2;
    const maxSpeed = options.maxSpeed ?? 16;
    const enableDrag = options.enableDrag !== false;
    const enableWheel = options.enableWheel !== false;
    const autoRevealChild = options.autoRevealChild !== false;

    // Apply baseline styles to container
    container.style.overflowX = 'auto';
    container.style.scrollbarWidth = 'none';
    (container.style as any)['-ms-overflow-style'] = 'none';

    // Create wrapper & fade masks if parent is not already a wrapper
    const wrapper = container.parentElement;
    let leftFade: HTMLElement | null = null;
    let rightFade: HTMLElement | null = null;

    if (wrapper && wrapper.classList.contains('mouse-scroll-wrapper')) {
        leftFade = wrapper.querySelector('.mouse-scroll-fade-left');
        rightFade = wrapper.querySelector('.mouse-scroll-fade-right');
    }

    let animFrame: number | null = null;
    let scrollVelocity = 0;

    // Drag states
    let isMouseDown = false;
    let startX = 0;
    let scrollLeftStart = 0;
    let hasDragged = false;

    const checkBoundaries = () => {
        const isRtl = getComputedStyle(container).direction === 'rtl';
        const scrollWidth = container.scrollWidth;
        const clientWidth = container.clientWidth;
        const maxScroll = scrollWidth - clientWidth;

        if (maxScroll <= 2) {
            if (leftFade) leftFade.style.opacity = '0';
            if (rightFade) rightFade.style.opacity = '0';
            return;
        }

        let canScrollLeft = false;
        let canScrollRight = false;

        if (isRtl) {
            const absScroll = Math.abs(container.scrollLeft);
            canScrollLeft = absScroll < maxScroll - 2;
            canScrollRight = absScroll > 2;
        } else {
            canScrollLeft = container.scrollLeft > 2;
            canScrollRight = container.scrollLeft < maxScroll - 2;
        }

        if (leftFade) leftFade.style.opacity = canScrollLeft ? '1' : '0';
        if (rightFade) rightFade.style.opacity = canScrollRight ? '1' : '0';
    };

    const startScrollLoop = () => {
        if (animFrame !== null) return;
        const loop = () => {
            if (scrollVelocity !== 0) {
                container.scrollLeft += scrollVelocity;
                checkBoundaries();
                animFrame = requestAnimationFrame(loop);
            } else {
                animFrame = null;
            }
        };
        animFrame = requestAnimationFrame(loop);
    };

    const stopScrollLoop = () => {
        scrollVelocity = 0;
        if (animFrame !== null) {
            cancelAnimationFrame(animFrame);
            animFrame = null;
        }
    };

    const onMouseMove = (e: MouseEvent) => {
        if (isMouseDown && enableDrag) {
            const dx = e.pageX - startX;
            if (Math.abs(dx) > 5) {
                hasDragged = true;
            }
            container.scrollLeft = scrollLeftStart - dx;
            checkBoundaries();
            return;
        }

        const rect = container.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const width = rect.width;

        if (width <= 0) return;

        const isRtl = getComputedStyle(container).direction === 'rtl';
        const zoneWidth = Math.max(40, width * proximityRatio);

        if (mouseX < zoneWidth) {
            // Near Left Edge
            const intensity = 1 - Math.max(0, mouseX) / zoneWidth;
            const dir = isRtl ? 1 : -1;
            scrollVelocity = dir * maxSpeed * intensity;
            startScrollLoop();
        } else if (mouseX > width - zoneWidth) {
            // Near Right Edge
            const intensity = 1 - Math.max(0, width - mouseX) / zoneWidth;
            const dir = isRtl ? -1 : 1;
            scrollVelocity = dir * maxSpeed * intensity;
            startScrollLoop();
        } else {
            stopScrollLoop();
        }
    };

    const onMouseLeave = () => {
        stopScrollLoop();
        isMouseDown = false;
    };

    const onMouseDown = (e: MouseEvent) => {
        if (!enableDrag) return;
        isMouseDown = true;
        hasDragged = false;
        startX = e.pageX;
        scrollLeftStart = container.scrollLeft;
    };

    const onMouseUp = () => {
        isMouseDown = false;
    };

    const onClickCapture = (e: MouseEvent) => {
        if (hasDragged) {
            e.preventDefault();
            e.stopPropagation();
            hasDragged = false;
        }
    };

    const onWheel = (e: WheelEvent) => {
        if (!enableWheel) return;
        if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
            container.scrollLeft += e.deltaY;
            checkBoundaries();
            e.preventDefault();
        }
    };

    const attachChildListeners = () => {
        if (!autoRevealChild) return;
        const children = container.querySelectorAll('button, a, .scroll-item, [role="tab"]');
        children.forEach((child) => {
            if ((child as any).__mouseScrollChildAttached) return;
            (child as any).__mouseScrollChildAttached = true;

            const reveal = () => {
                const childEl = child as HTMLElement;
                const containerRect = container.getBoundingClientRect();
                const childRect = childEl.getBoundingClientRect();

                const isPartiallyHidden =
                    childRect.left < containerRect.left ||
                    childRect.right > containerRect.right;

                if (isPartiallyHidden) {
                    childEl.scrollIntoView({
                        behavior: 'smooth',
                        inline: 'nearest',
                        block: 'nearest',
                    });
                    setTimeout(checkBoundaries, 250);
                }
            };

            child.addEventListener('mouseenter', reveal);
            child.addEventListener('focus', reveal);
        });
    };

    container.addEventListener('mousemove', onMouseMove);
    container.addEventListener('mouseleave', onMouseLeave);
    container.addEventListener('mousedown', onMouseDown);
    window.addEventListener('mouseup', onMouseUp);
    container.addEventListener('click', onClickCapture, true);
    container.addEventListener('wheel', onWheel, { passive: false });
    container.addEventListener('scroll', checkBoundaries, { passive: true });
    window.addEventListener('resize', checkBoundaries);

    attachChildListeners();
    checkBoundaries();

    // Re-check boundaries after layout settles
    setTimeout(checkBoundaries, 150);

    return () => {
        stopScrollLoop();
        container.removeEventListener('mousemove', onMouseMove);
        container.removeEventListener('mouseleave', onMouseLeave);
        container.removeEventListener('mousedown', onMouseDown);
        window.removeEventListener('mouseup', onMouseUp);
        container.removeEventListener('click', onClickCapture, true);
        container.removeEventListener('wheel', onWheel);
        container.removeEventListener('scroll', checkBoundaries);
        window.removeEventListener('resize', checkBoundaries);
        (container as any).__mouseScrollInitialized = false;
    };
}

export function initAllMouseScrollContainers(): void {
    if (typeof document === 'undefined') return;
    const elements = document.querySelectorAll<HTMLElement>('[data-mouse-scroll], .mouse-scroll-container');
    elements.forEach((el) => {
        initMouseScroll(el);
    });
}

// Global hook for browsers
if (typeof window !== 'undefined') {
    (window as any).initAllMouseScrollContainers = initAllMouseScrollContainers;
}
