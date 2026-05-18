import React from 'react';

const OS_CONFIG: Record<string, { label: string; icon: string; color: string }> = {
    windows: { label: 'Windows', icon: '⊞', color: 'bg-blue-50 text-blue-700 border-blue-200' },
    macos:   { label: 'macOS',   icon: '',  color: 'bg-slate-50 text-slate-700 border-slate-200' },
    linux:   { label: 'Linux',   icon: '🐧', color: 'bg-orange-50 text-orange-700 border-orange-200' },
};

interface PlatformBadgeProps {
    os: string;
    size?: 'sm' | 'md';
}

export function PlatformBadge({ os, size = 'sm' }: PlatformBadgeProps) {
    const config = OS_CONFIG[os.toLowerCase()] ?? { label: os, icon: '📦', color: 'bg-slate-50 text-slate-600 border-slate-200' };
    const px = size === 'md' ? 'px-2.5 py-1 text-xs' : 'px-2 py-0.5 text-[11px]';

    return (
        <span className={`inline-flex items-center gap-1 rounded-md border font-medium capitalize ${px} ${config.color}`}>
            <span className="text-[10px]">{config.icon}</span>
            {config.label}
        </span>
    );
}

interface PlatformBadgesProps {
    platforms: string[];
    size?: 'sm' | 'md';
}

export function PlatformBadges({ platforms, size = 'sm' }: PlatformBadgesProps) {
    const list = Array.isArray(platforms)
        ? platforms
        : typeof platforms === 'string'
            ? (() => { try { return JSON.parse(platforms); } catch { return [platforms]; } })()
            : [];

    return (
        <div className="flex flex-wrap gap-1">
            {list.map((os: string) => (
                <PlatformBadge key={os} os={os} size={size} />
            ))}
        </div>
    );
}
