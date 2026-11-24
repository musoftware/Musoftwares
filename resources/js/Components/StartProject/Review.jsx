import { CheckCircle2, MessageSquare, Send } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { archetypeOptions, budgetOptions, industryOptions, moduleGroups, platformOptions, solutionOptions, timelineOptions } from './options';

const titleOf = (options, id) => options.find((option) => option.id === id)?.title ?? '';

/** Every answer as [label, value, step] in the page language. Used by the review list and the WhatsApp text. */
export function summaryRows(brief) {
    const budget = budgetOptions().find((option) => option.id === brief.budgetTier);
    const timeline = timelineOptions().find((option) => option.id === brief.timeline);
    const modules = moduleGroups().flatMap((group) => group.items);
    const tech = [
        titleOf(archetypeOptions(), brief.archetype),
        ...brief.platforms.map((id) => titleOf(platformOptions(), id)),
        ...brief.modules.map((id) => titleOf(modules, id)),
        brief.customFeatures.trim(),
    ].filter(Boolean).join(document.documentElement.lang === 'ar' ? '، ' : ', ');
    const contact = [brief.contactName, brief.contactPhone, brief.companyName, brief.contactEmail, brief.docUrl].map((v) => v.trim()).filter(Boolean).join(' / ');

    return [
        [__('start_project.summary_solution'), titleOf(solutionOptions(), brief.solution), 1],
        [__('start_project.summary_business'), industryOptions().find((option) => option.value === brief.industry)?.label ?? '', 1],
        [__('start_project.summary_goal'), brief.goal.trim(), 1],
        [__('start_project.summary_existing'), brief.existingSystem.trim(), 1],
        [__('start_project.summary_timeline'), timeline ? `${timeline.title} (${timeline.time})` : '', 2],
        [__('start_project.summary_budget'), budget ? `${budget.title}: ${brief.currency === 'USD' ? budget.usd : budget.egp}` : '', 2],
        [__('start_project.summary_tech'), tech, 2],
        [__('start_project.summary_contact'), contact, 3],
    ];
}

function SummaryList({ rows, onEdit }) {
    return (
        <dl className="divide-y divide-black/5 rounded-2xl bg-[#f5f5f7] px-4 sm:px-6">
            {rows.map(([label, value, step]) => (
                <div key={label} className="flex flex-col gap-1 py-3.5 sm:flex-row sm:items-start sm:gap-4">
                    <dt className="text-[13px] font-semibold text-[#1d1d1f]/60 sm:w-40 sm:shrink-0">{label}</dt>
                    <dd className="min-w-0 flex-1 break-words text-[15px] text-[#1d1d1f]">{value ? <bdi>{value}</bdi> : <span className="text-[#1d1d1f]/45">{__('start_project.not_set')}</span>}</dd>
                    <dd className="shrink-0">
                        <button type="button" onClick={() => onEdit(step)} aria-label={`${__('start_project.edit')}: ${label}`} className="min-h-[44px] text-[14px] font-semibold text-[#0071e3] hover:underline sm:min-h-0">
                            {__('start_project.edit')}
                        </button>
                    </dd>
                </div>
            ))}
        </dl>
    );
}

function SentNotice({ code, whatsAppUrl }) {
    return (
        <div role="status" className="space-y-4 rounded-2xl border border-green-600/30 bg-white p-6 text-center">
            <CheckCircle2 className="mx-auto h-10 w-10 text-green-600" aria-hidden="true" />
            <h3 className="text-xl font-semibold text-[#1d1d1f]">{__('start_project.sent_title')}</h3>
            <p className="text-[15px] text-[#1d1d1f]/75">{__('start_project.sent_desc', { code })}</p>
            <WhatsAppButton url={whatsAppUrl} />
        </div>
    );
}

function WhatsAppButton({ url }) {
    return (
        <a href={url} target="_blank" rel="noopener noreferrer" className="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-full bg-[#25D366] px-6 text-[15px] font-semibold text-white hover:bg-[#1DA851]">
            <MessageSquare className="h-4 w-4" aria-hidden="true" />
            {__('start_project.whatsapp_send')}
        </a>
    );
}

/** Step 4: summary, then one send button. After sending, the button is gone so the request cannot go twice. */
export function StepReview({ brief, status, code, whatsAppUrl, onEdit, onSubmit }) {
    if (status === 'sent') return <SentNotice code={code} whatsAppUrl={whatsAppUrl} />;

    return (
        <div className="space-y-6">
            <p className="text-[14px] text-[#1d1d1f]/70">{__('start_project.review_desc')}</p>
            <SummaryList rows={summaryRows(brief)} onEdit={onEdit} />
            {status === 'failed' && <p role="alert" className="rounded-xl bg-[#d70015]/5 p-4 text-[14px] text-[#d70015]">{__('start_project.send_failed')}</p>}
            <div className="flex flex-col gap-3 sm:flex-row">
                <button type="button" onClick={onSubmit} disabled={status === 'sending'} className="inline-flex min-h-[48px] flex-1 items-center justify-center gap-2 rounded-full bg-[#0071e3] px-6 text-[15px] font-semibold text-white hover:bg-[#0077ed] disabled:opacity-60">
                    <Send className="h-4 w-4" aria-hidden="true" />
                    {status === 'sending' ? __('start_project.sending') : __('start_project.submit')}
                </button>
                {status === 'failed' && <WhatsAppButton url={whatsAppUrl} />}
            </div>
        </div>
    );
}
