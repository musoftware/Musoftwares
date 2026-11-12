import { useEffect, useRef, useState } from 'react';
import axios from 'axios';
import { Send } from 'lucide-react';
import { __ } from '@/lib/i18n';

type State = { configured: boolean; linked: boolean; bot: string | null };

const BASE = '/settings/telegram';
const CHECK_EVERY_MS = 3000;
const CHECK_FOR_MS = 120000;

/**
 * The account in the site's Telegram bot (invoices, projects, the AI assistant; for admins also the approvals): the
 * bot opens with a one-time code, the user presses Start, and this card waits until the bot has linked it.
 */
export default function TelegramAlerts() {
    const [state, setState] = useState<State | null>(null);
    const [waiting, setWaiting] = useState(false);
    const [note, setNote] = useState('');
    const timer = useRef<number | null>(null);

    useEffect(() => {
        axios.get<State>(BASE).then(({ data }) => setState(data)).catch(() => setState(null));
        return () => { if (timer.current) window.clearInterval(timer.current); };
    }, []);

    const stopWaiting = () => {
        if (timer.current) window.clearInterval(timer.current);
        timer.current = null;
        setWaiting(false);
    };

    const waitForStart = () => {
        const until = Date.now() + CHECK_FOR_MS;
        setWaiting(true);
        timer.current = window.setInterval(async () => {
            // the bot links the account the moment Start arrives; the page only reads the result
            const { data } = await axios.get<State>(BASE).catch(() => ({ data: null }));
            if (data?.linked) { setState(data); stopWaiting(); setNote(__('client_home.settings.telegram_linked_now')); }
            if (Date.now() > until) { stopWaiting(); setNote(__('client_home.settings.telegram_not_seen')); }
        }, CHECK_EVERY_MS);
    };

    const link = async () => {
        setNote('');
        // opened before the request, so the phone does not block it as a pop-up
        const tab = window.open('', '_blank');
        try {
            const { data } = await axios.post<{ url: string }>(`${BASE}/link`);
            if (tab) tab.location.href = data.url; else window.location.href = data.url;
            waitForStart();
        } catch {
            tab?.close();
            setNote(__('client_home.settings.telegram_failed'));
        }
    };

    const test = () => axios.post(`${BASE}/test`)
        .then(() => setNote(__('client_home.settings.telegram_test_sent')))
        .catch(() => setNote(__('client_home.settings.telegram_failed')));

    const unlink = () => axios.delete<State>(BASE).then(({ data }) => { setState(data); setNote(''); });

    if (!state?.configured) return null;
    const button = 'min-h-11 rounded-full px-4 text-[15px] font-semibold';

    return (
        <section className="mt-6">
            <h2 className="mb-2 flex items-center gap-2 px-1 text-sm font-semibold text-[#1d1d1f]/60 dark:text-zinc-400">
                <Send className="h-4 w-4" />{__('client_home.settings.telegram_title')}
            </h2>
            <div className="space-y-3 rounded-[20px] bg-white p-4 shadow-sm dark:bg-zinc-900">
                <p className="text-[15px]">
                    {state.linked ? __('client_home.settings.telegram_on') : __('client_home.settings.telegram_off')}
                    {state.bot && <span className="text-[#1d1d1f]/50 dark:text-zinc-500" dir="ltr"> @{state.bot}</span>}
                </p>
                <div className="flex flex-wrap gap-2">
                    {!state.linked && (
                        <button type="button" onClick={link} disabled={waiting} className={`${button} bg-[#0071e3] text-white disabled:opacity-60`}>
                            {waiting ? __('client_home.settings.telegram_waiting') : __('client_home.settings.telegram_link')}
                        </button>
                    )}
                    {state.linked && (
                        <>
                            <button type="button" onClick={test} className={`${button} bg-[#0071e3] text-white`}>{__('client_home.settings.telegram_test')}</button>
                            <button type="button" onClick={unlink} className={`${button} text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30`}>{__('client_home.settings.telegram_unlink')}</button>
                        </>
                    )}
                </div>
                {note && <p role="status" className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{note}</p>}
            </div>
        </section>
    );
}
