/**
 * Pure helpers for the start-project form: the empty form, step checks, the payload for
 * POST /tools/lead-capture, and the WhatsApp text. No React and no network here.
 */

export const STEP_COUNT = 4;

export const emptyBrief = Object.freeze({
    solution: '',
    industry: '',
    goal: '',
    existingSystem: '',
    timeline: '',
    budgetTier: '',
    currency: 'EGP',
    archetype: '',
    platforms: [],
    modules: [],
    customFeatures: '',
    contactName: '',
    contactPhone: '',
    companyName: '',
    contactEmail: '',
    docUrl: '',
});

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const URL_PATTERN = /^https?:\/\/\S+$/i;

function phoneDigits(phone) {
    return phone.replace(/\D/g, '');
}

function isValidPhone(phone) {
    const digits = phoneDigits(phone);
    return digits.length >= 7 && digits.length <= 15;
}

const STEP_CHECKS = {
    1: (brief) => (brief.solution ? {} : { solution: 'start_project.solution_required' }),
    2: () => ({}),
    3: (brief) => ({
        ...(brief.contactName.trim() ? {} : { contactName: 'start_project.name_required' }),
        ...(isValidPhone(brief.contactPhone) ? {} : { contactPhone: 'start_project.phone_invalid' }),
        ...(!brief.contactEmail.trim() || EMAIL_PATTERN.test(brief.contactEmail.trim()) ? {} : { contactEmail: 'start_project.email_invalid' }),
        ...(!brief.docUrl.trim() || URL_PATTERN.test(brief.docUrl.trim()) ? {} : { docUrl: 'start_project.url_invalid' }),
    }),
    4: () => ({}),
};

/** @returns {Record<string, string>} field name to translation key; empty when the step is fine. */
export function validateStep(step, brief) {
    return (STEP_CHECKS[step] ?? (() => ({})))(brief);
}

/** The first step that still has an error, so the review can send the visitor back to it. */
export function firstInvalidStep(brief) {
    return [1, 2, 3].find((step) => Object.keys(validateStep(step, brief)).length > 0) ?? null;
}

export function toggleInList(list, id) {
    return list.includes(id) ? list.filter((item) => item !== id) : [...list, id];
}

/** Same keys the endpoint already reads (name, email, mobile, business, scope); new answers go inside scope. */
export function buildLeadPayload(brief, code) {
    return {
        name: brief.contactName.trim(),
        email: brief.contactEmail.trim(),
        mobile: brief.contactPhone.trim(),
        business: brief.companyName.trim(),
        tool_name: 'Start project',
        scope: {
            brief_code: code,
            solution: brief.solution,
            industry: brief.industry,
            goal: brief.goal.trim(),
            existing_system: brief.existingSystem.trim(),
            timeline: brief.timeline,
            budget: brief.budgetTier,
            currency: brief.currency,
            archetype: brief.archetype,
            platforms: brief.platforms,
            modules: brief.modules,
            custom_features: brief.customFeatures.trim(),
            notes: brief.goal.trim(),
            figma_url: brief.docUrl.trim(),
        },
    };
}

/** Maps Laravel validation field names back to form fields and the message shown under them. */
const SERVER_FIELD_MAP = {
    name: ['contactName', 'start_project.name_required'],
    mobile: ['contactPhone', 'start_project.phone_invalid'],
    email: ['contactEmail', 'start_project.email_invalid'],
};

/** @returns {Record<string, string>} form field errors from a 422 response body; empty when none apply. */
export function serverFieldErrors(responseErrors) {
    return Object.fromEntries(Object.keys(responseErrors ?? {}).filter((key) => SERVER_FIELD_MAP[key]).map((key) => SERVER_FIELD_MAP[key]));
}

/**
 * WhatsApp text in the visitor's own voice. `rows` are [label, value] pairs already in the page language.
 */
export function buildWhatsAppText(intro, rows) {
    const lines = rows.filter(([, value]) => value).map(([label, value]) => `${label}: ${value}`);
    return [intro, '', ...lines].join('\n');
}

/** Link to the /ai assistant with the answers ready in its message box. Contact details are left out on purpose. */
export function buildAssistantUrl(intro, rows) {
    return `/ai?brief=${encodeURIComponent(buildWhatsAppText(intro, rows))}`;
}

export function makeBriefCode() {
    return `MS-SYS-${Math.floor(1000 + Math.random() * 9000)}`;
}
