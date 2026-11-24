import { useCallback, useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { X } from 'lucide-react';
import { __ } from '@/lib/i18n';

export interface TrackingConfig {
    ga_id?: string | null;
    gtm_id?: string | null;
    meta_pixel_id?: string | null;
    custom_head_scripts?: string | null;
    custom_body_scripts?: string | null;
}

export type CookieChoice = 'granted' | 'withdrawn' | 'unset';

export interface PrivacyComplianceShared {
    policyVersion: string;
    cookiesTracking: CookieChoice | string;
    trackingConfig?: TrackingConfig;
}

declare global {
    interface Window {
        __musoftwareLoadTrackers?: (config?: TrackingConfig) => void;
        __musoftwareRevokeTrackers?: () => void;
        __musoftwareTrackersLoaded?: boolean;
    }
}

const CHOICE_BUTTON =
    'min-h-[44px] rounded-full border border-black/15 bg-white px-4 text-sm font-semibold text-[#1d1d1f] transition hover:bg-[#f5f5f7] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0071e3] disabled:opacity-50 cursor-pointer';

async function saveCookieChoice(allow: boolean): Promise<boolean> {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
    try {
        const response = await fetch('/privacy/consent/gate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ cookies_tracking: allow }),
        });
        return response.ok;
    } catch {
        return false;
    }
}

/** Starts or stops the optional scripts. Scripts that already ran cannot be unloaded, so stopping them reloads the page. */
function applyTrackers(allow: boolean, config?: TrackingConfig): void {
    if (allow) {
        window.__musoftwareLoadTrackers?.(config);
        return;
    }
    const wasLoaded = Boolean(window.__musoftwareTrackersLoaded);
    window.__musoftwareRevokeTrackers?.();
    if (wasLoaded) window.location.reload();
}

function CookiePanel({ tracking, onTrackingChange, onSave, busy }: { tracking: boolean; onTrackingChange: (value: boolean) => void; onSave: () => void; busy: boolean }) {
    return (
        <div id="react-cookie-panel" className="mt-4 space-y-2">
            <div className="rounded-xl bg-[#f5f5f7] p-3.5">
                <div className="flex items-center justify-between gap-3">
                    <span className="text-sm font-semibold">{__('privacy_compliance.gate_necessary_title')}</span>
                    <span className="text-xs font-medium text-[#6e6e73]">{__('privacy_compliance.gate_always_on')}</span>
                </div>
                <p className="mt-1 text-[13px] leading-relaxed text-[#6e6e73]">{__('privacy_compliance.gate_necessary_desc')}</p>
            </div>
            <label htmlFor="react-cookie-tracking" className="flex cursor-pointer items-start gap-3 rounded-xl bg-[#f5f5f7] p-3.5">
                <input id="react-cookie-tracking" type="checkbox" autoFocus checked={tracking} onChange={(e) => onTrackingChange(e.target.checked)}
                    className="mt-0.5 h-5 w-5 shrink-0 cursor-pointer rounded border-black/25 text-[#1d1d1f] focus:ring-[#0071e3]" />
                <span>
                    <span className="block text-sm font-semibold">{__('privacy_compliance.gate_tracking_title')}</span>
                    <span className="mt-1 block text-[13px] leading-relaxed text-[#6e6e73]">{__('privacy_compliance.gate_tracking_desc')}</span>
                </span>
            </label>
            <button type="button" disabled={busy} onClick={onSave} className={`w-full ${CHOICE_BUTTON}`}>{__('privacy_compliance.gate_save_btn')}</button>
        </div>
    );
}

/**
 * Cookie banner for Inertia pages; the Blade twin is resources/views/layouts/cookie-banner.blade.php.
 * It only asks about optional analytics and ad cookies. It is not a modal: nothing is dimmed or locked.
 */
export function CookieBanner({ initialCompliance }: { initialCompliance?: PrivacyComplianceShared }) {
    const [choice, setChoice] = useState<string>(initialCompliance?.cookiesTracking ?? 'unset');
    const [config, setConfig] = useState<TrackingConfig | undefined>(initialCompliance?.trackingConfig);
    const [manualOpen, setManualOpen] = useState(false);
    const [panelOpen, setPanelOpen] = useState(false);
    const [tracking, setTracking] = useState(false);
    const [busy, setBusy] = useState(false);
    const [failed, setFailed] = useState(false);
    const bannerRef = useRef<HTMLElement>(null);
    const openerRef = useRef<HTMLElement | null>(null);

    useEffect(() => {
        if (choice === 'granted') window.__musoftwareLoadTrackers?.(config);
    }, [choice, config]);

    useEffect(() => {
        const removeNavigate = router.on('navigate', (event) => {
            const next = (event.detail.page?.props as { privacyCompliance?: PrivacyComplianceShared })?.privacyCompliance;
            if (next) {
                setChoice(next.cookiesTracking);
                setConfig(next.trackingConfig);
            }
        });
        const openSettings = () => {
            openerRef.current = document.activeElement as HTMLElement | null;
            setTracking(choice === 'granted');
            setFailed(false);
            setManualOpen(true);
        };
        window.addEventListener('open-privacy-consent-gate', openSettings);
        return () => {
            removeNavigate();
            window.removeEventListener('open-privacy-consent-gate', openSettings);
        };
    }, [choice]);

    useEffect(() => {
        if (manualOpen) bannerRef.current?.focus();
    }, [manualOpen]);

    const close = useCallback(() => {
        setManualOpen(false);
        setPanelOpen(false);
        openerRef.current?.focus();
        openerRef.current = null;
    }, []);

    const save = async (allow: boolean) => {
        setBusy(true);
        setFailed(false);
        const saved = await saveCookieChoice(allow);
        setBusy(false);
        if (!saved) {
            setFailed(true);
            return;
        }
        setChoice(allow ? 'granted' : 'withdrawn');
        applyTrackers(allow, config);
        close();
    };

    if (!manualOpen && choice !== 'unset') return null;

    return (
        <section
            ref={bannerRef}
            tabIndex={-1}
            aria-labelledby="react-cookie-title"
            aria-describedby="react-cookie-description"
            onKeyDown={(e) => { if (e.key === 'Escape' && manualOpen) close(); }}
            className="fixed inset-x-3 bottom-3 z-[99990] mx-auto max-h-[85vh] max-w-[560px] overflow-y-auto rounded-2xl bg-white p-5 text-[#1d1d1f] shadow-[0_16px_48px_-12px_rgba(0,0,0,0.35)] ring-1 ring-black/10 focus:outline-none sm:bottom-6"
        >
            <div className="flex items-start justify-between gap-3">
                <h2 id="react-cookie-title" className="text-[17px] font-semibold leading-snug">{__('privacy_compliance.gate_title')}</h2>
                {manualOpen && (
                    <button type="button" onClick={close} aria-label={__('privacy_compliance.gate_close_btn')}
                        className="-m-2 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-[#6e6e73] hover:bg-[#f5f5f7] hover:text-[#1d1d1f] cursor-pointer">
                        <X className="h-5 w-5" aria-hidden="true" />
                    </button>
                )}
            </div>
            <p id="react-cookie-description" className="mt-2 text-sm leading-relaxed text-[#6e6e73]">{__('privacy_compliance.gate_description')}</p>

            {panelOpen && <CookiePanel tracking={tracking} onTrackingChange={setTracking} onSave={() => save(tracking)} busy={busy} />}

            {failed && <p role="status" className="mt-3 text-sm text-[#d70015]">{__('privacy_compliance.gate_save_failed')}</p>}

            <div className="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
                <button type="button" disabled={busy} onClick={() => save(false)} className={CHOICE_BUTTON}>{__('privacy_compliance.gate_reject_tracking_btn')}</button>
                <button type="button" disabled={busy} onClick={() => save(true)} className={CHOICE_BUTTON}>{__('privacy_compliance.gate_accept_all_btn')}</button>
                <button type="button" disabled={busy} onClick={() => setPanelOpen((open) => !open)} aria-expanded={panelOpen} aria-controls="react-cookie-panel" className={CHOICE_BUTTON}>
                    {__('privacy_compliance.gate_customize_btn')}
                </button>
            </div>

            <div className="mt-4 border-t border-black/[0.06] pt-3 text-xs text-[#6e6e73]">
                <div className="flex flex-wrap gap-x-4 gap-y-1">
                    <a href="/cookie-policy" className="py-1 hover:text-[#1d1d1f] hover:underline">{__('privacy_compliance.read_cookies')}</a>
                    <a href="/privacy-policy" className="py-1 hover:text-[#1d1d1f] hover:underline">{__('privacy_compliance.read_privacy_policy')}</a>
                </div>
                <p className="mt-1 text-[11px] text-[#86868b]">{__('privacy_compliance.gate_badge')}</p>
            </div>
        </section>
    );
}
