import React from 'react';
import { Head, usePage } from '@inertiajs/react';

interface SeoHeadProps {
    title: string;
    description?: string;
    image?: string;
    url?: string;
    type?: string;
    canonicalUrl?: string;
    arUrl?: string;
    enUrl?: string;
    jsonLd?: Record<string, any>;
}

export function SeoHead({
    title,
    description,
    image = '/images/og-default.jpg',
    url,
    type = 'website',
    canonicalUrl,
    arUrl,
    enUrl,
    jsonLd
}: SeoHeadProps) {
    const { props } = usePage();
    const appName = (props.app as any)?.name || 'Musoftware';
    
    // Safely get the current URL if not provided, avoiding SSR issues
    const currentUrl = url || (typeof window !== 'undefined' ? window.location.href : '');
    const canonical = canonicalUrl || (typeof window !== 'undefined' ? window.location.origin + window.location.pathname : '');
    
    const arHref = arUrl || (canonical ? `${canonical}?lang=ar` : '');
    const enHref = enUrl || (canonical ? `${canonical}?lang=en` : '');

    // If description is empty, try to get a default one, otherwise just use a generic one
    const safeDescription = description || `Welcome to ${appName}`;

    return (
        <Head>
            <title>{title}</title>
            <meta name="description" content={safeDescription} />

            {/* Open Graph / Facebook */}
            <meta property="og:type" content={type} />
            <meta property="og:url" content={currentUrl} />
            <meta property="og:title" content={title} />
            <meta property="og:description" content={safeDescription} />
            {image && <meta property="og:image" content={image} />}

            {/* Twitter */}
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:url" content={currentUrl} />
            <meta name="twitter:title" content={title} />
            <meta name="twitter:description" content={safeDescription} />
            {image && <meta name="twitter:image" content={image} />}

            {/* Canonical & hreflang URLs */}
            {canonical && <link rel="canonical" href={canonical} />}
            {arHref && <link rel="alternate" hrefLang="ar" href={arHref} />}
            {enHref && <link rel="alternate" hrefLang="en" href={enHref} />}
            {canonical && <link rel="alternate" hrefLang="x-default" href={canonical} />}

            {/* JSON-LD Schema */}
            {jsonLd && (
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }} />
            )}
        </Head>
    );
}
