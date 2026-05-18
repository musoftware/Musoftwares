import React from 'react';
import { CheckCircle2 } from 'lucide-react';

interface FeatureListProps {
    features: string[];
    columns?: 1 | 2;
    showAll?: boolean;
}

export function FeatureList({ features, columns = 2, showAll = true }: FeatureListProps) {
    const list = Array.isArray(features)
        ? features
        : typeof features === 'string'
            ? (() => { try { return JSON.parse(features); } catch { return [features]; } })()
            : [];

    const items = showAll ? list : list.slice(0, 6);
    const gridClass = columns === 2 ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1';

    if (!items.length) return null;

    return (
        <div className={`grid ${gridClass} gap-2.5`}>
            {items.map((feature: string, i: number) => (
                <div key={i} className="flex items-start gap-2.5 group">
                    <div className="mt-0.5 flex-shrink-0 w-4 h-4 rounded-full bg-emerald-50 border border-emerald-200 flex items-center justify-center">
                        <CheckCircle2 className="h-3 w-3 text-emerald-600" />
                    </div>
                    <span className="text-sm text-slate-700 leading-relaxed">{feature}</span>
                </div>
            ))}
        </div>
    );
}
