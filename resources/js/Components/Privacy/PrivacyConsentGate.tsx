import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
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

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="pdpl-react-gate-title"
            className="fixed inset-0 z-[99990] flex items-end justify-center bg-black/45 p-0 sm:p-4 overflow-x-clip"
        >
            <style>{`@keyframes pdpl-sheet-up{from{transform:translateY(100%);opacity:0}to{transform:translateY(0);opacity:1}}.pdpl-sheet-up{animation:pdpl-sheet-up .28s ease-out}@media (prefers-reduced-motion:reduce){.pdpl-sheet-up{animation:none}}`}</style>
            <div className="pdpl-sheet-up w-full max-w-3xl max-h-[85vh] overflow-y-auto rounded-t-2xl sm:rounded-2xl border border-black/15 bg-white text-[#1d1d1f] shadow-2xl p-5 sm:p-6">
                <div className="flex items-start gap-3 mb-4">
                    <div className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-black/10 bg-[#f5f5f7] text-[#0071e3]">
                        <ShieldCheck className="h-5 w-5" aria-hidden="true" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="text-[11px] font-semibold uppercase tracking-wide text-[#0066cc] mb-1">
                            {__('privacy_compliance.gate_badge')}
                        </p>
                        <h2 id="pdpl-react-gate-title" className="text-lg sm:text-xl font-bold leading-snug text-[#1d1d1f]">
                            {__('privacy_compliance.gate_title')}
                        </h2>
                    </div>
                </div>

                <p className="text-xs sm:text-sm text-[#515154] leading-relaxed mb-4">
                    {__('privacy_compliance.gate_description')}
                </p>

                <div className="rounded-xl border border-black/10 bg-[#f5f5f7]/80 p-3.5 sm:p-4 mb-4">
                    <label htmlFor="pdpl-react-essential-checkbox" className="flex items-start gap-3 cursor-pointer select-none">
                        <input
                            id="pdpl-react-essential-checkbox"
                            type="checkbox"
                            checked={essentialChecked}
                            onChange={(e) => setEssentialChecked(e.target.checked)}
                            className="mt-1 h-4 w-4 shrink-0 rounded border-black/30 text-[#0071e3] focus:ring-[#0071e3]"
                        />
                        <span className="text-xs sm:text-sm font-medium text-[#1d1d1f] leading-relaxed">
                            {__('privacy_compliance.gate_essential_checkbox')}
                        </span>
                    </label>
                    {!essentialChecked && (
                        <p className="mt-2 text-xs font-medium text-[#b45309]">
                            {__('privacy_compliance.gate_essential_required_hint')}
                        </p>
                    )}
                </div>

                <div className="rounded-xl border border-black/10 bg-white p-3.5 sm:p-4 mb-5">
                    <h3 className="text-xs sm:text-sm font-semibold text-[#1d1d1f] mb-1">
                        {__('privacy_compliance.gate_cookies_heading')}
                    </h3>
                    <p className="text-xs text-[#515154] leading-relaxed">
                        {__('privacy_compliance.gate_cookies_description')}
                    </p>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mb-3">
                    <button
                        type="button"
                        disabled={!essentialChecked || submitting}
                        onClick={() => submitGateChoice(true)}
                        className="min-h-[44px] w-full rounded-xl bg-[#0071e3] px-4 py-2.5 text-xs sm:text-sm font-semibold text-white transition hover:bg-[#0077ed] disabled:opacity-45 disabled:cursor-not-allowed cursor-pointer"
                    >
                        {__('privacy_compliance.gate_accept_all_btn')}
                    </button>
                    <button
                        type="button"
                        disabled={!essentialChecked || submitting}
                        onClick={() => submitGateChoice(false)}
                        className="min-h-[44px] w-full rounded-xl border-2 border-[#1d1d1f] bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#1d1d1f] transition hover:bg-[#f5f5f7] disabled:opacity-45 disabled:cursor-not-allowed cursor-pointer"
                    >
                        {__('privacy_compliance.gate_reject_tracking_btn')}
                    </button>
                </div>

                <button
                    type="button"
                    disabled={submitting}
                    onClick={submitDeclineEssential}
                    className="min-h-[40px] w-full rounded-xl border border-[#dc2626]/30 bg-[#fef2f2] px-4 py-2 text-xs font-semibold text-[#b91c1c] transition hover:bg-[#fee2e2] disabled:opacity-50 cursor-pointer mb-4"
                >
                    {__('privacy_compliance.gate_decline_essential_btn')}
                </button>

                <div className="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-black/10 text-xs">
                    <div className="flex flex-wrap items-center gap-4">
                        <a href="/privacy-policy" className="font-semibold text-[#0066cc] hover:underline">
                            {__('privacy_compliance.read_privacy_policy')}
                        </a>
                        <a href="/terms-of-service" className="font-semibold text-[#0066cc] hover:underline">
                            {__('privacy_compliance.read_terms')}
                        </a>
                        <a href="/cookie-policy" className="font-semibold text-[#0066cc] hover:underline">
                            {__('privacy_compliance.read_cookies')}
                        </a>
                    </div>
                    <span className="text-[11px] text-[#86868b]">
                        {compliance?.policyVersion ?? 'v2.0-2026'}
                    </span>
                </div>
            </div>
        </div>
    );
}

