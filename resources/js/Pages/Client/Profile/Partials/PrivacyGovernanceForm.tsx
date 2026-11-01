import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Download, FileText, ShieldCheck, Trash2 } from 'lucide-react';
import { Switch } from '@/Components/ui/switch';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { __ } from '@/lib/i18n';

export interface ConsentStateEntry {
    granted: boolean;
    policy_version: string | null;
    updated_at: string | null;
}

export interface DataSubjectRequestRow {
    id: number;
    request_type: string;
    status: string;
    requested_at: string | null;
    due_at: string | null;
    completed_at: string | null;
    admin_notes: string | null;
}

interface Props {
    consents?: Record<string, ConsentStateEntry>;
    requests?: DataSubjectRequestRow[];
    className?: string;
}

const CONSENT_ITEMS = [
    {
        key: 'direct_marketing_email',
        titleKey: 'privacy_compliance.consent_marketing_email_title',
        descKey: 'privacy_compliance.consent_marketing_email_desc',
    },
    {
        key: 'direct_marketing_sms',
        titleKey: 'privacy_compliance.consent_marketing_sms_title',
        descKey: 'privacy_compliance.consent_marketing_sms_desc',
    },
    {
        key: 'cookies_tracking',
        titleKey: 'privacy_compliance.consent_cookies_title',
        descKey: 'privacy_compliance.consent_cookies_desc',
    },
    {
        key: 'cross_border_transfer',
        titleKey: 'privacy_compliance.consent_cross_border_title',
        descKey: 'privacy_compliance.consent_cross_border_desc',
    },
] as const;

const DSR_TYPE_LABELS: Record<string, string> = {
    access_data: 'privacy_compliance.dsr_type_access_data',
    update_data: 'privacy_compliance.dsr_type_update_data',
    delete_account: 'privacy_compliance.dsr_type_delete_account',
    restrict_processing: 'privacy_compliance.dsr_type_restrict_processing',
};

const DSR_STATUS_LABELS: Record<string, string> = {
    pending: 'privacy_compliance.dsr_status_pending',
    in_progress: 'privacy_compliance.dsr_status_in_progress',
    completed: 'privacy_compliance.dsr_status_completed',
    rejected_with_reason: 'privacy_compliance.dsr_status_rejected_with_reason',
};

export default function PrivacyGovernanceForm({
    consents = {},
    requests = [],
    className = '',
}: Props) {
    const [pendingConsent, setPendingConsent] = useState<string | null>(null);
    const [details, setDetails] = useState('');
    const [submittingType, setSubmittingType] = useState<string | null>(null);

    const handleToggleConsent = (consentType: string, granted: boolean) => {
        setPendingConsent(consentType);
        router.patch(
            route('profile.privacy.consents'),
            { consent_type: consentType, granted },
            {
                preserveScroll: true,
                onFinish: () => {
                    setPendingConsent(null);
                    if (consentType === 'cookies_tracking' && !granted) {
                        window.__musoftwareRevokeTrackers?.();
                    }
                },
            },
        );
    };

    const handleSubmitDsr = (requestType: 'restrict_processing' | 'update_data' | 'delete_account') => {
        setSubmittingType(requestType);
        router.post(
            route('profile.privacy.requests.store'),
            { request_type: requestType, details },
            {
                preserveScroll: true,
                onSuccess: () => setDetails(''),
                onFinish: () => setSubmittingType(null),
            },
        );
    };

    return (
        <section className={`space-y-6 overflow-x-clip ${className}`}>
            <header className="flex items-start gap-3">
                <div className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-black/10 bg-[#f5f5f7] text-[#0071e3] dark:border-white/10 dark:bg-zinc-800">
                    <ShieldCheck className="h-5 w-5" aria-hidden="true" />
                </div>
                <div className="min-w-0 flex-1">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                        {__('privacy_compliance.profile_section_title')}
                    </h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-zinc-400">
                        {__('privacy_compliance.profile_section_subtitle')}
                    </p>
                </div>
            </header>

            <div className="space-y-3">
                <h3 className="text-sm font-semibold text-gray-900 dark:text-zinc-200">
                    {__('privacy_compliance.consents_heading')}
                </h3>
                <div className="divide-y divide-black/5 rounded-xl border border-black/10 bg-[#f5f5f7]/40 dark:divide-white/10 dark:border-white/10 dark:bg-zinc-900/60">
                    {CONSENT_ITEMS.map((item) => {
                        const isGranted = Boolean(consents[item.key]?.granted);
                        const isBusy = pendingConsent === item.key;

                        return (
                            <div key={item.key} className="flex items-start justify-between gap-4 p-4">
                                <div className="min-w-0 flex-1">
                                    <label
                                        htmlFor={`consent-switch-${item.key}`}
                                        className="block text-sm font-medium text-gray-900 dark:text-white cursor-pointer"
                                    >
                                        {__(item.titleKey)}
                                    </label>
                                    <p className="mt-0.5 text-xs text-gray-600 dark:text-zinc-400">
                                        {__(item.descKey)}
                                    </p>
                                    <p className="mt-1 text-[11px] font-medium text-gray-500 dark:text-zinc-500">
                                        {isGranted
                                            ? __('privacy_compliance.consent_status_granted')
                                            : __('privacy_compliance.consent_status_withdrawn')}
                                    </p>
                                </div>
                                <Switch
                                    id={`consent-switch-${item.key}`}
                                    checked={isGranted}
                                    disabled={isBusy}
                                    onCheckedChange={(checked) => handleToggleConsent(item.key, checked)}
                                />
                            </div>
                        );
                    })}
                </div>
            </div>

            <div className="space-y-3 pt-2 border-t border-black/5 dark:border-white/10">
                <h3 className="text-sm font-semibold text-gray-900 dark:text-zinc-200">
                    {__('privacy_compliance.export_heading')}
                </h3>
                <p className="text-xs text-gray-600 dark:text-zinc-400">
                    {__('privacy_compliance.export_description')}
                </p>
                <div className="flex flex-wrap gap-2.5">
                    <a
                        href={route('profile.privacy.export', { format: 'json' })}
                        className="inline-flex min-h-[40px] items-center gap-2 rounded-lg border border-black/15 bg-white px-3.5 py-2 text-xs font-semibold text-gray-900 transition hover:bg-[#f5f5f7] dark:border-white/15 dark:bg-zinc-900 dark:text-white dark:hover:bg-zinc-800"
                    >
                        <Download className="h-4 w-4 text-[#0071e3]" aria-hidden="true" />
                        <span>{__('privacy_compliance.export_json_btn')}</span>
                    </a>
                    <a
                        href={route('profile.privacy.export', { format: 'pdf' })}
                        className="inline-flex min-h-[40px] items-center gap-2 rounded-lg border border-black/15 bg-white px-3.5 py-2 text-xs font-semibold text-gray-900 transition hover:bg-[#f5f5f7] dark:border-white/15 dark:bg-zinc-900 dark:text-white dark:hover:bg-zinc-800"
                    >
                        <FileText className="h-4 w-4 text-[#0071e3]" aria-hidden="true" />
                        <span>{__('privacy_compliance.export_pdf_btn')}</span>
                    </a>
                </div>
            </div>

            <div className="space-y-3 pt-2 border-t border-black/5 dark:border-white/10">
                <h3 className="text-sm font-semibold text-gray-900 dark:text-zinc-200">
                    {__('privacy_compliance.dsr_heading')}
                </h3>
                <Textarea
                    rows={2}
                    value={details}
                    onChange={(e) => setDetails(e.target.value)}
                    placeholder={__('privacy_compliance.dsr_notes_placeholder')}
                    className="text-xs rounded-xl"
                />
                <div className="flex flex-wrap gap-2.5">
                    <Button
                        type="button"
                        variant="outline"
                        disabled={submittingType !== null}
                        onClick={() => handleSubmitDsr('restrict_processing')}
                        className="min-h-[40px] text-xs rounded-lg"
                    >
                        {__('privacy_compliance.dsr_restrict_btn')}
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        disabled={submittingType !== null}
                        onClick={() => handleSubmitDsr('update_data')}
                        className="min-h-[40px] text-xs rounded-lg"
                    >
                        {__('privacy_compliance.dsr_update_btn')}
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        disabled={submittingType !== null}
                        onClick={() => handleSubmitDsr('delete_account')}
                        className="min-h-[40px] text-xs rounded-lg gap-1.5"
                    >
                        <Trash2 className="h-3.5 w-3.5" aria-hidden="true" />
                        <span>{__('privacy_compliance.dsr_delete_btn')}</span>
                    </Button>
                </div>
            </div>

            <div className="space-y-2.5 pt-2 border-t border-black/5 dark:border-white/10">
                <h3 className="text-sm font-semibold text-gray-900 dark:text-zinc-200">
                    {__('privacy_compliance.dsr_history_heading')}
                </h3>
                {requests.length === 0 ? (
                    <p className="text-xs text-gray-500 dark:text-zinc-500">
                        {__('privacy_compliance.dsr_history_empty')}
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-black/10 dark:border-white/10">
                        <table className="w-full min-w-[520px] text-start text-xs">
                            <thead className="bg-[#f5f5f7] text-gray-700 dark:bg-zinc-800 dark:text-zinc-300">
                                <tr>
                                    <th className="px-3 py-2.5 text-start font-semibold">{__('privacy_compliance.dsr_col_type')}</th>
                                    <th className="px-3 py-2.5 text-start font-semibold">{__('privacy_compliance.dsr_col_status')}</th>
                                    <th className="px-3 py-2.5 text-start font-semibold">{__('privacy_compliance.dsr_col_requested_at')}</th>
                                    <th className="px-3 py-2.5 text-start font-semibold">{__('privacy_compliance.dsr_col_due_at')}</th>
                                    <th className="px-3 py-2.5 text-start font-semibold">{__('privacy_compliance.dsr_col_notes')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-black/5 dark:divide-white/10">
                                {requests.map((row) => (
                                    <tr key={row.id}>
                                        <td className="px-3 py-2.5 font-medium">
                                            {__(DSR_TYPE_LABELS[row.request_type] ?? row.request_type)}
                                        </td>
                                        <td className="px-3 py-2.5">
                                            {__(DSR_STATUS_LABELS[row.status] ?? row.status)}
                                        </td>
                                        <td className="px-3 py-2.5 whitespace-nowrap" dir="ltr">
                                            {row.requested_at ?? '-'}
                                        </td>
                                        <td className="px-3 py-2.5 whitespace-nowrap" dir="ltr">
                                            {row.due_at ?? '-'}
                                        </td>
                                        <td className="px-3 py-2.5 text-gray-600 dark:text-zinc-400">
                                            {row.admin_notes || '-'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </section>
    );
}
