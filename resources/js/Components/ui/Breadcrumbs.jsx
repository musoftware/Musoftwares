import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';

export function Breadcrumbs({ items = [] }) {
    if (!items || items.length === 0) return null;

    return (
        <nav className="flex flex-wrap items-center space-x-2 font-sans text-[13px]">
            {items.map((item, index) => {
                const isLast = index === items.length - 1;

                return (
                    <div key={index} className="flex items-center space-x-2">
                        {isLast ? (
                            <span className="text-text-primary font-medium">
                                {item.label}
                            </span>
                        ) : (
                            <>
                                {item.href ? (
                                    <Link
                                        href={item.href}
                                        className="text-text-muted hover:text-text-primary transition-colors"
                                    >
                                        {item.label}
                                    </Link>
                                ) : (
                                    <span className="text-text-muted">
                                        {item.label}
                                    </span>
                                )}
                                <ChevronRight className="text-text-muted h-[14px] w-[14px]" />
                            </>
                        )}
                    </div>
                );
            })}
        </nav>
    );
}
