import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Cookie } from 'lucide-react';
import { __ } from '@/lib/i18n';

export interface TrackingConfig {
    ga_id?: string | null;
    gtm_id?: string | null;
    meta_pixel_id?: string | null;
    custom_head_scripts?: string | null;
    custom_body_scripts?: string | null;
}

export interface PrivacyComplianceShared {
    policyVersion: string;
    hasEssentialConsent: boolean;
    isBlocked: boolean;
    cookiesTracking: 'granted' | 'withdrawn' | 'unset' | string;
    trackingConfig?: TrackingConfig;
}

declare global {
    interface Window {
        __musoftwareLoadTrackers?: (config?: TrackingConfig) => void;
        __musoftwareRevokeTrackers?: () => void;
    }
}

const EXEMPT_PREFIXES = [
    '/privacy-policy',
    '/terms-of-service',
    '/cookie-policy',
    '/privacy',
    '/terms',
    '/cookies',
    '/legal/',
    '/unsubscribe/',
    '/winback/unsubscribe/',
];

function isExemptPath(pathname: string): boolean {
    const normalized = pathname.toLowerCase();
    return EXEMPT_PREFIXES.some((prefix) => normalized === prefix || normalized.startsWith(prefix));
}

function readCsrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    return meta?.content ?? '';
}

export function PrivacyConsentGate({ initialCompliance }: { initialCompliance?: PrivacyComplianceShared }) {
    const [compliance, setCompliance] = useState<PrivacyComplianceShared | undefined>(initialCompliance);
    const [manualOpen, setManualOpen] = useState(false);
    const [essentialChecked, setEssentialChecked] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [showRequiredHint, setShowRequiredHint] = useState(false);
    const [pathname, setPathname] = useState(() => (typeof window !== 'undefined' ? window.location.pathname : '/'));

    useEffect(() => {
        if (compliance?.cookiesTracking === 'granted' && compliance.trackingConfig) {
            window.__musoftwareLoadTrackers?.(compliance.trackingConfig);
        }
    }, [compliance?.cookiesTracking, compliance?.trackingConfig]);

    useEffect(() => {
        const removeNavigate = router.on('navigate', (event) => {
            const nextProps = (event.detail.page?.props ?? {}) as { privacyCompliance?: PrivacyComplianceShared };
            if (nextProps.privacyCompliance) {
                setCompliance(nextProps.privacyCompliance);
            }
            setPathname(window.location.pathname);
            setManualOpen(false);
        });

        const handleOpenPreferences = () => {
            setEssentialChecked(false);
            setShowRequiredHint(false);
            setManualOpen(true);
        };

        window.addEventListener('open-privacy-consent-gate', handleOpenPreferences);
        return () => {
            removeNavigate();
            window.removeEventListener('open-privacy-consent-gate', handleOpenPreferences);
        };
    }, []);

    const needsAutoGate = Boolean(compliance && !compliance.hasEssentialConsent && !isExemptPath(pathname));
    const isVisible = manualOpen || needsAutoGate;

    if (!isVisible) {
        return null;
    }

    const submitGateChoice = async (enableTracking: boolean) => {
        if (!essentialChecked || submitting) return;
        setSubmitting(true);

        try {
            const response = await fetch('/privacy/consent/gate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': readCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    essential_consent: true,
                    cookies_tracking: enableTracking,
                }),
            });

            if (!response.ok) {
                setSubmitting(false);
                return;
            }

            if (enableTracking) {
                window.__musoftwareLoadTrackers?.(compliance?.trackingConfig);
            } else {
                window.__musoftwareRevokeTrackers?.();
            }

            setCompliance((prev) =>
                prev
                    ? {
                          ...prev,
                          hasEssentialConsent: true,
                          isBlocked: false,
                          cookiesTracking: enableTracking ? 'granted' : 'withdrawn',
                      }
                    : prev,
            );
            setManualOpen(false);
        } finally {
            setSubmitting(false);
        }
    };

    const submitDeclineEssential = async () => {
        if (submitting) return;
        setSubmitting(true);

        try {
            await fetch('/privacy/consent/decline', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': readCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            window.__musoftwareRevokeTrackers?.();
            window.location.reload();
        } catch {
            setSubmitting(false);
        }
    };

    const tryChoice = (enableTracking: boolean) => {
        if (!essentialChecked) {
            setShowRequiredHint(true);
            document.getElementById('pdpl-react-essential-checkbox')?.focus();
            return;
        }
        submitGateChoice(enableTracking);
    };

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="pdpl-react-gate-title"
            aria-describedby="pdpl-react-gate-description"
            className="fixed inset-0 z-[99990] flex items-end sm:items-center justify-center bg-black/40 backdrop-blur-[2px] p-0 sm:p-6 overflow-x-clip"
        >
            <style>{`@keyframes pdpl-sheet-up{from{transform:translateY(24px);opacity:0}to{transform:translateY(0);opacity:1}}.pdpl-sheet-up{animation:pdpl-sheet-up .25s ease-out}@media (prefers-reduced-motion:reduce){.pdpl-sheet-up{animation:none}}`}</style>
            <div className="pdpl-sheet-up w-full sm:max-w-[440px] max-h-[90vh] overflow-y-auto rounded-t-2xl sm:rounded-2xl bg-white text-[#1d1d1f] shadow-[0_24px_64px_-16px_rgba(0,0,0,0.35)] ring-1 ring-black/5 p-6">
                <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-[#f5f5f7] text-[#1d1d1f]">
                    <Cookie className="h-5 w-5" aria-hidden="true" />
                </div>

                <h2 id="pdpl-react-gate-title" className="text-[17px] font-semibold leading-snug">
                    {__('privacy_compliance.gate_title')}
                </h2>
                <p id="pdpl-react-gate-description" className="mt-2 text-sm leading-relaxed text-[#6e6e73]">
                    {__('privacy_compliance.gate_description')}
                </p>

                <label
                    htmlFor="pdpl-react-essential-checkbox"
                    className={`mt-5 flex cursor-pointer select-none items-start gap-3 rounded-xl bg-[#f5f5f7] p-3.5 ring-1 transition ${showRequiredHint && !essentialChecked ? 'ring-[#d70015]' : 'ring-transparent'}`}
                >
                    <input
                        id="pdpl-react-essential-checkbox"
                        type="checkbox"
                        checked={essentialChecked}
                        aria-describedby="pdpl-react-essential-hint"
                        onChange={(e) => setEssentialChecked(e.target.checked)}
                        className="mt-0.5 h-[18px] w-[18px] shrink-0 cursor-pointer rounded border-black/25 text-[#1d1d1f] focus:ring-[#1d1d1f]"
                    />
                    <span className="text-[13px] leading-relaxed text-[#1d1d1f]">
                        {__('privacy_compliance.gate_essential_checkbox')}
                    </span>
                </label>
                <p
                    id="pdpl-react-essential-hint"
                    role="alert"
                    className={`mt-2 text-xs text-[#d70015] ${showRequiredHint && !essentialChecked ? '' : 'hidden'}`}
                >
                    {__('privacy_compliance.gate_essential_required_hint')}
                </p>

                <div className="mt-5 grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        disabled={submitting}
                        onClick={() => tryChoice(false)}
                        className="min-h-[44px] rounded-full bg-[#f5f5f7] px-4 text-sm font-semibold text-[#1d1d1f] transition hover:bg-[#e8e8ed] disabled:opacity-50 cursor-pointer"
                    >
                        {__('privacy_compliance.gate_reject_tracking_btn')}
                    </button>
                    <button
                        type="button"
                        disabled={submitting}
                        onClick={() => tryChoice(true)}
                        className="min-h-[44px] rounded-full bg-[#1d1d1f] px-4 text-sm font-semibold text-white transition hover:bg-black disabled:opacity-50 cursor-pointer"
                    >
                        {__('privacy_compliance.gate_accept_all_btn')}
                    </button>
                </div>

                <button
                    type="button"
                    disabled={submitting}
                    onClick={submitDeclineEssential}
                    className="mt-3 w-full min-h-[36px] text-[13px] text-[#6e6e73] underline-offset-4 transition hover:text-[#1d1d1f] hover:underline disabled:opacity-50 cursor-pointer"
                >
                    {__('privacy_compliance.gate_decline_essential_btn')}
                </button>

                <div className="mt-4 border-t border-black/[0.06] pt-4 text-xs text-[#6e6e73]">
                    <div className="flex flex-wrap gap-x-4 gap-y-1">
                        <a href="/privacy-policy" className="hover:text-[#1d1d1f] hover:underline">
                            {__('privacy_compliance.read_privacy_policy')}
                        </a>
                        <a href="/terms-of-service" className="hover:text-[#1d1d1f] hover:underline">
                            {__('privacy_compliance.read_terms')}
                        </a>
                        <a href="/cookie-policy" className="hover:text-[#1d1d1f] hover:underline">
                            {__('privacy_compliance.read_cookies')}
                        </a>
                    </div>
                    <p className="mt-2 text-[11px] text-[#86868b]">{__('privacy_compliance.gate_badge')}</p>
                </div>
            </div>
        </div>
    );
}
