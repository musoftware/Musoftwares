import { __ } from '@/lib/i18n';

export function formatBytes(b: number) {
    if (b < 1024) return `${b} B`;
    if (b < 1024 * 1024) return `${(b / 1024).toFixed(1)} KB`;
    if (b < 1024 * 1024 * 1024) return `${(b / 1024 / 1024).toFixed(1)} MB`;
    return `${(b / 1024 / 1024 / 1024).toFixed(2)} GB`;
}

export function timeAgo(iso: string) {
    const diff = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    if (diff < 60) return `${diff} ${__('s ago')}`;
    if (diff < 3600) return `${Math.floor(diff / 60)} ${__('m ago')}`;
    return `${Math.floor(diff / 3600)} ${__('h ago')}`;
}
