import React from 'react';
import { Link, InertiaLinkProps, router } from '@inertiajs/react';

/**
 * List of non-Inertia (Blade) routes in the application that require full page loads.
 */
const BLADE_ROUTES = ['/dashboard', '/dashboard/directory'];

/**
 * Determines whether a given URL is a Blade (non-Inertia) or external route.
 */
export function isExternalRoute(href?: string): boolean {
    if (!href) return false;
    if (
        href.startsWith('http://') ||
        href.startsWith('https://') ||
        href.startsWith('//') ||
        href.startsWith('mailto:') ||
        href.startsWith('tel:')
    ) {
        return true;
    }
    return BLADE_ROUTES.some(
        (route) => href === route || href.startsWith(route + '?') || href.startsWith(route + '#')
    );
}

/**
 * Programmatically navigate to a URL.
 * Uses window.location.href for Blade/external routes to avoid iframe rendering issues,
 * and Inertia router.visit for SPA routes.
 */
export function visitUrl(href: string) {
    if (isExternalRoute(href)) {
        window.location.href = href;
    } else {
        router.visit(href);
    }
}

export interface SafeLinkProps extends React.AnchorHTMLAttributes<HTMLAnchorElement> {
    href: string;
    external?: boolean;
    children?: React.ReactNode;
}

/**
 * A drop-in replacement for Inertia's <Link> component.
 * Automatically renders a standard HTML <a> tag for Blade and external routes,
 * ensuring full page browser navigation and preventing Inertia srcdoc iframe issues.
 */
export default function SafeLink({ href, external, children, className, ...props }: SafeLinkProps) {
    if (external || isExternalRoute(href)) {
        return (
            <a href={href} className={className} {...props}>
                {children}
            </a>
        );
    }

    return (
        <Link href={href} className={className} {...(props as any)}>
            {children}
        </Link>
    );
}
