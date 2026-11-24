import { useEffect, useMemo, useRef, useState } from 'react';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { ArrowLeft, ArrowRight, Check } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';
import StudioHeader from '@/Components/Studio/StudioHeader';
import { __ } from '@/lib/i18n';
import { STUDIO_PHONE, whatsAppLink } from '@/lib/whatsapp';
import { STEP_COUNT, buildLeadPayload, buildWhatsAppText, emptyBrief, firstInvalidStep, makeBriefCode, serverFieldErrors, validateStep } from '@/Components/StartProject/brief';
import { StepContact, StepDetails, StepProject } from '@/Components/StartProject/Steps';
import { StepReview, summaryRows } from '@/Components/StartProject/Review';

const STEPS = [
    { step: 1, label: 'start_project.step_project', title: 'start_project.project_title' },
    { step: 2, label: 'start_project.step_details', title: 'start_project.details_title' },
    { step: 3, label: 'start_project.step_contact', title: 'start_project.contact_title' },
    { step: 4, label: 'start_project.step_review', title: 'start_project.review_title' },
];

/** Input ids for each field, so an error can move focus to the field it belongs to. */
const FIELD_FOCUS = { solution: 'input[name="solution"]', contactName: '#sp-name', contactPhone: '#sp-phone', contactEmail: '#sp-email', docUrl: '#sp-doc' };

/** Runs after React has drawn the step, so the field exists. Only called when the visitor presses Next or Send. */
function focusFirstError(errors) {
    const first = Object.keys(errors)[0];
    if (first) requestAnimationFrame(() => document.querySelector(FIELD_FOCUS[first])?.focus());
}

function Progress({ current, onJump }) {
    return (
        <ol aria-label={__('start_project.progress_label')} className="mx-auto mb-8 grid max-w-2xl grid-cols-4 gap-2">
            {STEPS.map(({ step, label }) => {
                const done = step < current;
                const active = step === current;
                return (
                    <li key={step} aria-current={active ? 'step' : undefined}>
                        <button type="button" onClick={() => done && onJump(step)} disabled={!done} className="flex w-full flex-col items-center gap-1.5 disabled:cursor-default">
                            <span className={`flex h-9 w-9 items-center justify-center rounded-full text-[13px] font-semibold ${active ? 'bg-[#0071e3] text-white' : done ? 'border-2 border-[#0071e3] bg-white text-[#0071e3]' : 'bg-[#f5f5f7] text-[#1d1d1f]/50'}`}>
                                {done ? <Check className="h-4 w-4" strokeWidth={3} aria-hidden="true" /> : step}
                            </span>
                            <span className={`text-center text-[12px] leading-tight sm:text-[13px] ${active ? 'font-semibold text-[#1d1d1f]' : 'text-[#1d1d1f]/60'}`}>{__(label)}</span>
                        </button>
                    </li>
                );
            })}
        </ol>
    );
}

function StepNav({ step, onBack, onNext }) {
    return (
        <div className="mt-10 flex items-center justify-between gap-3 border-t border-black/5 pt-6">
            {step > 1 ? (
                <button type="button" onClick={onBack} className="inline-flex min-h-[44px] items-center gap-2 rounded-full border border-black/15 bg-white px-5 text-[14px] font-semibold text-[#1d1d1f] hover:border-black/30">
                    <ArrowLeft className="h-4 w-4 rtl:rotate-180" aria-hidden="true" />
                    {__('start_project.back')}
                </button>
            ) : <span />}
            {step < STEP_COUNT && (
                <button type="button" onClick={onNext} className="inline-flex min-h-[44px] items-center gap-2 rounded-full bg-[#0071e3] px-6 text-[14px] font-semibold text-white hover:bg-[#0077ed]">
                    {__('start_project.next')}
                    <ArrowRight className="h-4 w-4 rtl:rotate-180" aria-hidden="true" />
                </button>
            )}
        </div>
    );
}

/** Sends the request once. Validation errors from the server go back to the contact step, next to their fields. */
function useSubmit(brief, code, goTo, setErrors) {
    const [status, setStatus] = useState('idle');

    const submit = async () => {
        if (status === 'sending' || status === 'sent') return;
        const invalidStep = firstInvalidStep(brief);
        if (invalidStep) {
            const stepErrors = validateStep(invalidStep, brief);
            setErrors(stepErrors);
            goTo(invalidStep);
            focusFirstError(stepErrors);
            return;
        }
        setStatus('sending');
        try {
            await axios.post('/tools/lead-capture', buildLeadPayload(brief, code));
            setStatus('sent');
        } catch (error) {
            const fieldErrors = serverFieldErrors(error.response?.data?.errors);
            setStatus(Object.keys(fieldErrors).length ? 'idle' : 'failed');
            if (Object.keys(fieldErrors).length) {
                setErrors(fieldErrors);
                goTo(3);
                focusFirstError(fieldErrors);
            }
        }
    };

    return { status, submit };
}

export default function SystemBuilder() {
    const [brief, setBrief] = useState(emptyBrief);
    const [step, setStep] = useState(1);
    const [errors, setErrors] = useState({});
    const code = useRef(makeBriefCode()).current;
    const headingRef = useRef(null);
    const firstRender = useRef(true);

    const goTo = (next) => setStep(next);
    const { status, submit } = useSubmit(brief, code, goTo, setErrors);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        headingRef.current?.scrollIntoView({ block: 'start', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        headingRef.current?.focus({ preventScroll: true });
    }, [step]);

    const update = (changes) => {
        setBrief((prev) => ({ ...prev, ...changes }));
        setErrors((prev) => Object.fromEntries(Object.entries(prev).filter(([key]) => !(key in changes))));
    };

    const next = () => {
        const stepErrors = validateStep(step, brief);
        setErrors(stepErrors);
        if (Object.keys(stepErrors).length === 0) goTo(Math.min(STEP_COUNT, step + 1));
        focusFirstError(stepErrors);
    };

    const whatsAppUrl = useMemo(() => {
        const rows = summaryRows(brief).map(([label, value]) => [label, value]);
        return whatsAppLink(buildWhatsAppText(__('start_project.wa_intro'), [[__('start_project.wa_request'), code], ...rows]), STUDIO_PHONE);
    }, [brief, code]);

    const current = STEPS[step - 1];

    return (
        <PublicLayout>
            <Head>
                <title>{`${__('frontend.wizard_meta_title')} | Musoftwares`}</title>
                <meta name="description" content={__('frontend.wizard_meta_desc')} />
            </Head>

            <div className="w-full bg-white pb-24 pt-8 text-[#1d1d1f] sm:pb-32 sm:pt-14">
                <StudioHeader
                    badge={__('start_project.badge')}
                    title={<>{__('start_project.hero_title')} <span className="text-[#0071e3]">{__('start_project.hero_highlight')}</span></>}
                    subtitle={__('start_project.subtitle')}
                />

                <div className="mx-auto max-w-[960px] px-4 sm:px-8">
                    <Progress current={step} onJump={goTo} />

                    <div className="rounded-[28px] border border-black/5 bg-white p-5 shadow-[0_20px_60px_rgba(0,0,0,0.04)] sm:p-10">
                        <div className="mb-6 space-y-1">
                            <p className="text-[13px] font-semibold text-[#0071e3]">{__('start_project.step_counter', { current: step, total: STEP_COUNT })}</p>
                            <h2 ref={headingRef} tabIndex={-1} className="scroll-mt-24 text-2xl font-semibold tracking-tight focus:outline-none sm:text-3xl">{__(current.title)}</h2>
                        </div>

                        {step === 1 && <StepProject brief={brief} errors={errors} update={update} />}
                        {step === 2 && <StepDetails brief={brief} update={update} />}
                        {step === 3 && <StepContact brief={brief} errors={errors} update={update} />}
                        {step === 4 && <StepReview brief={brief} status={status} code={code} whatsAppUrl={whatsAppUrl} onEdit={goTo} onSubmit={submit} />}

                        {status !== 'sent' && <StepNav step={step} onBack={() => goTo(Math.max(1, step - 1))} onNext={next} />}
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
