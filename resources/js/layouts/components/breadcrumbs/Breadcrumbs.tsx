import { Link } from '@inertiajs/react';
import { ChevronRight, Home } from 'lucide-react';
import React from 'react';

interface BreadcrumbItem {
    name: string;
    href?: string;
}

export function Breadcrumbs({ items }: { items: BreadcrumbItem[] }) {
    return (
        <nav className="mb-4 flex items-center space-x-1 text-sm text-muted-foreground">
            <Link
                href="/dashboard"
                className="flex items-center hover:text-foreground"
            >
                <Home size={14} />
            </Link>

            {items.map((item, index) => (
                <React.Fragment key={index}>
                    <ChevronRight size={14} className="mx-1" />
                    {item.href ? (
                        <Link
                            href={item.href}
                            className="max-w-[150px] truncate hover:text-foreground"
                        >
                            {item.name}
                        </Link>
                    ) : (
                        <span className="max-w-[150px] truncate font-medium text-foreground">
                            {item.name}
                        </span>
                    )}
                </React.Fragment>
            ))}
        </nav>
    );
}
