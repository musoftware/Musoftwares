import React from 'react';
import { router } from '@inertiajs/react';
import { t } from '@/Components/Admin/SoftwareStore/t';

export interface ImportState {
    github_repo: string | null;
    release_mode: 'each' | 'batch' | 'manual';
    release_after_changes: number;
    release_quiet_minutes: number;
    deploy_enabled: boolean;
    deploy_command: string | null;
    imported_at: string | null;
    released_at: string | null;
    changes_waiting: number;
    pull_request_url: string | null;
    can_release: boolean;
}

function ruleText(state: ImportState): string {
    if (state.release_mode === 'each') return t('After every change', 'بعد كل تعديل');
    if (state.release_mode === 'manual') return t('Only when you release it', 'لما ترفعها بنفسك بس');
    return t(`When ${state.release_after_changes} changes wait, or the customer is quiet for ${state.release_quiet_minutes} minutes`,
        `لما يتجمع ${state.release_after_changes} تعديلات، أو العميل يسكت ${state.release_quiet_minutes} دقيقة`);
}

/** An imported project: where its changes go, what is waiting, the last pull request, and a button to release now. */
export default function ReleasePanel({ uuid, state }: { uuid: string; state: ImportState }) {
    const release = () => router.post(`/admin/builder/${uuid}/release`, {}, { preserveScroll: true });

    return (
        <section className="space-y-2 rounded-xl border bg-white p-4 text-sm">
            <h2 className="font-semibold">{t('Imported project', 'مشروع مرفوع')}</h2>
            {state.github_repo && <p dir="ltr"><a className="font-semibold text-blue-700" href={`https://github.com/${state.github_repo}`} target="_blank" rel="noopener">github.com/{state.github_repo}</a></p>}
            <p>{t('Changes go out:', 'التعديلات بتترفع:')} {ruleText(state)}</p>
            <p>{state.deploy_enabled
                ? <>{t('Deploys to the server', 'بيرفع على السيرفر')}{state.deploy_command && <> · <code dir="ltr">{state.deploy_command}</code></>}</>
                : t('GitHub pull request only, no deploy', 'pull request على GitHub بس، من غير رفع على السيرفر')}</p>
            <p>{t(`Changes waiting: ${state.changes_waiting}`, `تعديلات مستنية: ${state.changes_waiting}`)}{state.released_at && <> · {t('last release', 'آخر رفع')} <span dir="ltr">{state.released_at}</span></>}</p>
            {state.pull_request_url && <p dir="ltr"><a className="font-semibold text-blue-700 wrap-anywhere" href={state.pull_request_url} target="_blank" rel="noopener">{state.pull_request_url}</a></p>}
            {state.can_release && (
                <button type="button" onClick={release} className="rounded-lg bg-slate-900 px-4 py-2 font-semibold text-white">{t('Release now', 'ارفع دلوقتي')}</button>
            )}
        </section>
    );
}
