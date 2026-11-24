import { ChevronDown } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { ChoiceGroup, Field, inputClass } from './Fields';
import { archetypeOptions, budgetOptions, industryOptions, moduleGroups, platformOptions, solutionOptions, timelineOptions } from './options';
import { toggleInList } from './brief';

/** Step 1: plain questions only. */
export function StepProject({ brief, errors, update }) {
    return (
        <div className="space-y-8">
            <ChoiceGroup
                legend={__('start_project.solution_label')}
                hint={__('start_project.solution_hint')}
                error={errors.solution}
                name="solution"
                options={solutionOptions()}
                isChecked={(id) => brief.solution === id}
                onToggle={(id) => update({ solution: id })}
                columns="sm:grid-cols-2 lg:grid-cols-3"
            />
            <Field id="sp-industry" label={__('start_project.business_label')}>
                {(props) => (
                    <select {...props} value={brief.industry} onChange={(e) => update({ industry: e.target.value })} className={inputClass}>
                        <option value="">{__('start_project.business_placeholder')}</option>
                        {industryOptions().map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                    </select>
                )}
            </Field>
            <Field id="sp-goal" label={__('start_project.goal_label')}>
                {(props) => (
                    <textarea {...props} rows={4} value={brief.goal} onChange={(e) => update({ goal: e.target.value })} placeholder={__('start_project.goal_placeholder')} className={inputClass} />
                )}
            </Field>
            {brief.solution === 'existing' && (
                <Field id="sp-existing" label={__('start_project.existing_label')}>
                    {(props) => <input {...props} type="text" value={brief.existingSystem} onChange={(e) => update({ existingSystem: e.target.value })} className={inputClass} />}
                </Field>
            )}
        </div>
    );
}

function BudgetChoice({ brief, update }) {
    const currencies = [{ id: 'EGP', title: 'EGP' }, { id: 'USD', title: 'USD' }];
    const options = budgetOptions().map((option) => ({ id: option.id, title: option.title, desc: brief.currency === 'USD' ? option.usd : option.egp, descLtr: option.id !== 'tier_custom' }));

    return (
        <div className="space-y-3">
            <fieldset className="flex flex-wrap items-center gap-3">
                <legend className="sr-only">{__('start_project.currency_label')}</legend>
                <span aria-hidden="true" className="text-[13px] text-[#1d1d1f]/65">{__('start_project.currency_label')}</span>
                {currencies.map((currency) => (
                    <label key={currency.id} className="inline-flex min-h-[44px] cursor-pointer items-center gap-2 text-[14px] font-semibold">
                        <input type="radio" name="currency" value={currency.id} checked={brief.currency === currency.id} onChange={() => update({ currency: currency.id })} className="h-4 w-4 accent-[#0071e3]" />
                        {currency.title}
                    </label>
                ))}
            </fieldset>
            <ChoiceGroup legend={__('start_project.budget_label')} hint={__('start_project.budget_note')} name="budget" options={options} isChecked={(id) => brief.budgetTier === id} onToggle={(id) => update({ budgetTier: id })} />
        </div>
    );
}

function Glossary() {
    return (
        <div className="rounded-xl bg-white p-4 text-[13px] leading-relaxed text-[#1d1d1f]/75 ring-1 ring-black/5">
            <p className="font-semibold text-[#1d1d1f]">{__('start_project.glossary_title')}</p>
            <ul className="mt-1 space-y-1">
                {['term_erp', 'term_saas', 'term_api', 'term_webhook'].map((key) => <li key={key}>{__(`start_project.${key}`)}</li>)}
            </ul>
        </div>
    );
}

function TechnicalDetails({ brief, update }) {
    return (
        <details className="group rounded-2xl border border-black/10 bg-[#f5f5f7] p-4 sm:p-5" open={Boolean(brief.archetype || brief.platforms.length || brief.modules.length || brief.customFeatures)}>
            <summary className="flex min-h-[44px] cursor-pointer list-none items-center justify-between gap-3 text-[15px] font-semibold text-[#1d1d1f] [&::-webkit-details-marker]:hidden">
                {__('start_project.tech_toggle')}
                <ChevronDown aria-hidden="true" className="h-4 w-4 shrink-0 text-[#1d1d1f]/50 transition-transform group-open:rotate-180" />
            </summary>
            <div className="mt-4 space-y-8">
                <p className="text-[13px] text-[#1d1d1f]/70">{__('start_project.tech_intro')}</p>
                <Glossary />
                <ChoiceGroup legend={__('start_project.tech_system_type')} name="archetype" options={archetypeOptions()} isChecked={(id) => brief.archetype === id} onToggle={(id) => update({ archetype: id })} />
                <ChoiceGroup legend={__('start_project.tech_platforms')} name="platforms" type="checkbox" options={platformOptions()} isChecked={(id) => brief.platforms.includes(id)} onToggle={(id) => update({ platforms: toggleInList(brief.platforms, id) })} />
                <fieldset className="space-y-5">
                    <legend className="text-[15px] font-semibold text-[#1d1d1f]">{__('start_project.tech_modules')}</legend>
                    {moduleGroups().map((group) => (
                        <ChoiceGroup key={group.category} legend={group.category} name={`modules-${group.category}`} type="checkbox" options={group.items} isChecked={(id) => brief.modules.includes(id)} onToggle={(id) => update({ modules: toggleInList(brief.modules, id) })} />
                    ))}
                </fieldset>
                <Field id="sp-custom" label={__('start_project.tech_custom')}>
                    {(props) => <textarea {...props} rows={3} value={brief.customFeatures} onChange={(e) => update({ customFeatures: e.target.value })} placeholder={__('frontend.wizard_custom_features_placeholder')} className={inputClass} />}
                </Field>
            </div>
        </details>
    );
}

/** Step 2: everything optional. Technical words live in a closed section with short meanings. */
export function StepDetails({ brief, update }) {
    return (
        <div className="space-y-8">
            <p className="text-[14px] text-[#1d1d1f]/70">{__('start_project.details_desc')}</p>
            <ChoiceGroup legend={__('start_project.timeline_label')} name="timeline" options={timelineOptions()} isChecked={(id) => brief.timeline === id} onToggle={(id) => update({ timeline: id })} columns="sm:grid-cols-3" />
            <BudgetChoice brief={brief} update={update} />
            <TechnicalDetails brief={brief} update={update} />
        </div>
    );
}

const CONTACT_FIELDS = [
    { key: 'contactName', id: 'sp-name', label: 'start_project.name_label', type: 'text', autoComplete: 'name' },
    { key: 'contactPhone', id: 'sp-phone', label: 'start_project.phone_label', type: 'tel', autoComplete: 'tel', dir: 'ltr', placeholder: '+20 10X XXX XXXX' },
    { key: 'companyName', id: 'sp-company', label: 'start_project.company_label', type: 'text', autoComplete: 'organization' },
    { key: 'contactEmail', id: 'sp-email', label: 'start_project.email_label', type: 'email', autoComplete: 'email', dir: 'ltr' },
    { key: 'docUrl', id: 'sp-doc', label: 'start_project.doc_label', type: 'url', dir: 'ltr', placeholder: 'https://' },
];

/** Step 3: only name and phone are required. */
export function StepContact({ brief, errors, update }) {
    return (
        <div className="space-y-6">
            <p className="text-[14px] text-[#1d1d1f]/70">{__('start_project.contact_desc')}</p>
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                {CONTACT_FIELDS.map((field) => (
                    <Field key={field.key} id={field.id} label={__(field.label)} error={errors[field.key]}>
                        {(props) => (
                            <input
                                {...props}
                                type={field.type}
                                dir={field.dir}
                                autoComplete={field.autoComplete}
                                placeholder={field.placeholder}
                                value={brief[field.key]}
                                onChange={(e) => update({ [field.key]: e.target.value })}
                                className={`${inputClass} ${field.dir === 'ltr' ? 'text-start' : ''}`}
                            />
                        )}
                    </Field>
                ))}
            </div>
        </div>
    );
}
