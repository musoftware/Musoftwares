import { __ } from '@/lib/i18n';

const CAMPAIGN_STATUS_KEYS: Record<string, string> = {
    draft: 'admin.notifications_status_draft',
    scheduled: 'admin.notifications_status_scheduled',
    sending: 'admin.notifications_status_sending',
    paused: 'admin.notifications_status_paused',
    completed: 'admin.notifications_status_completed',
};

export const campaignStatusLabel = (status: string): string => {
    const key = CAMPAIGN_STATUS_KEYS[status];
    return key ? __(key) : status;
};

export const campaignAudienceLabel = (audienceType: string): string =>
    audienceType === 'personal'
        ? __('admin.notifications_audience_personal')
        : __('admin.notifications_audience_global');
