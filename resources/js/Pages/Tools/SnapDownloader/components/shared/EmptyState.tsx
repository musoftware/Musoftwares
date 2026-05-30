import React from 'react';

export function EmptyState({ icon, title, sub, cta }: { icon: React.ReactNode; title: string; sub: string; cta?: React.ReactNode }) {
    return (
        <div className="flex flex-col items-center justify-center py-16 sm:py-24 space-y-4 rounded-2xl border" style={{ borderColor: 'rgba(255,255,255,0.05)', background: 'rgba(255,255,255,0.02)' }}>
            <div className="w-14 h-14 rounded-2xl flex items-center justify-center" style={{ background: 'rgba(255,255,255,0.05)' }}>
                {icon}
            </div>
            <div className="text-center px-4">
                <div className="font-semibold" style={{ color: 'rgba(255,255,255,0.4)' }}>{title}</div>
                <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.2)' }}>{sub}</p>
            </div>
            {cta}
        </div>
    );
}
