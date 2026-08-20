import React from 'react';
import { Link, router } from '@inertiajs/react';

/**
 * Prefixes and paths of Blade (Server-Rendered HTML) routes that require full browser page loads.
 */
const BLADE_PREFIXES = [
    '/',
    '/about',
    '/portfolio',
    '/blog',
    '/compare',
    '/platforms',
    '/solutions',
    '/start-project',
    '/legal',
    '/contact',
    '/pricing',
    '/cost',
    '/tools/cost',
    '/tools/website-cost',
    '/dashboard',
    '/sso',
];

/**
 * Specific paths that are known Inertia SPA routes (exempted from Blade full-page redirects).
 */
const INERTIA_PREFIXES = [
    '/estimator',
    '/admin',
    '/workspace',
    '/app',
    '/login',
    '/register',
];

/**
 * Determines whether a given URL is a Blade (non-Inertia) or external route.
 */
export function isExternalRoute(href?: string): boolean {
    if (!href) return false;

    // External protocols
    if (
        href.startsWith('http://') ||
        href.startsWith('https://') ||
        href.startsWith('//') ||
        href.startsWith('mailto:') ||
        href.startsWith('tel:') ||
        href.startsWith('#') ||
        href.includes('/sso/redirect') ||
        href.includes('/sso/')
    ) {
        return true;
    }

    // Normalize path by stripping origin and query/hash
    let path = href;
    try {
        if (href.startsWith('/')) {
            path = href.split('?')[0].split('#')[0];
        } else {
            const parsed = new URL(href, window.location.origin);
            if (parsed.origin !== window.location.origin) {
                return true;
            }
            path = parsed.pathname;
        }
    } catch {
        path = href;
    }

    // Check if path is an explicit Inertia route
    const isInertia = INERTIA_PREFIXES.some(prefix => path === prefix || path.startsWith(prefix + '/'));
    if (isInertia) {
        return false;
    }

    // Check exact root or Blade prefixes
    if (path === '/') return true;

    return BLADE_PREFIXES.some(prefix => prefix !== '/' && (path === prefix || path.startsWith(prefix + '/')));
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
