import React from 'react';

export interface PageTransitionProps {
    children: React.ReactNode;
    durationMs?: number;
    className?: string;
}

export function PageTransition({ children, durationMs = 200, className }: PageTransitionProps) {
    const [mounted, setMounted] = React.useState(false);

    React.useEffect(() => {
        const id = window.requestAnimationFrame(() => setMounted(true));
        return () => window.cancelAnimationFrame(id);
    }, []);

    return (
        <div
            className={className}
            style={{
                opacity: mounted ? 1 : 0,
                transform: mounted ? 'translateY(0)' : 'translateY(4px)',
                transition: `opacity ${durationMs}ms ease-out, transform ${durationMs}ms ease-out`,
            }}
        >
            {children}
        </div>
    );
}

export default PageTransition;
