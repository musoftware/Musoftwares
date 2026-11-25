import { FormEvent } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { Star } from 'lucide-react';
import GuestFrame from '@/Components/GuestFrame';
import { Card } from '@/Components/Client/ClientPage';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface Task {
    title: string;
    invoice_number: string | number | null;
    client_name: string | null;
    rating: number | null;
    note: string | null;
}

const STARS = [1, 2, 3, 4, 5];

/** The client rates one task from the emailed link, no sign-in. */
export default function TaskRating({ task, submit_url }: { task: Task; submit_url: string }) {
    const flash = usePage<any>().props.flash as { success?: string } | undefined;
    const { data, setData, post, processing, errors } = useForm({ rating: task.rating ?? 0, note: task.note ?? '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(submit_url, { preserveScroll: true });
    };

    const hint = task.invoice_number ? __('service_pricing.invoice_ref', { number: task.invoice_number }) : undefined;

    return (
        <GuestFrame title={__('service_pricing.page_title')} hint={hint}>
            <Card className="space-y-6">
                <div>
                    <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('service_pricing.heading')}</p>
                    <p className="mt-1 text-lg font-semibold">{task.title}</p>
                </div>
                {flash?.success && <p role="status" className="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{flash.success}</p>}
                {!flash?.success && task.rating && <p className="text-sm text-[#1d1d1f]/70 dark:text-zinc-400">{__('service_pricing.already_rated', { rating: task.rating })}</p>}
                <form onSubmit={submit} className="space-y-5">
                    <StarPicker value={data.rating} onChange={(v) => setData('rating', v)} />
                    {errors.rating && <p className="text-xs text-rose-600">{errors.rating}</p>}
                    <div className="space-y-1.5">
                        <label htmlFor="note" className="block text-sm font-semibold">{__('service_pricing.note_label')}</label>
                        <textarea id="note" rows={3} maxLength={1000} value={data.note} onChange={(e) => setData('note', e.target.value)}
                            className="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-base focus:border-[#0071e3] focus:outline-none focus:ring-2 focus:ring-[#0071e3]/20 dark:border-white/10 dark:bg-zinc-900" />
                    </div>
                    <button type="submit" disabled={processing || data.rating < 1}
                        className="inline-flex h-12 w-full items-center justify-center rounded-full bg-[#0071e3] text-sm font-semibold text-white hover:bg-[#0077ed] disabled:opacity-50">
                        {processing ? __('general.processing') : __('service_pricing.submit')}
                    </button>
                </form>
            </Card>
        </GuestFrame>
    );
}

function StarPicker({ value, onChange }: { value: number; onChange: (v: number) => void }) {
    return (
        <div role="radiogroup" aria-label={__('service_pricing.heading')} className="flex justify-center gap-2" dir="ltr">
            {STARS.map((star) => (
                <button key={star} type="button" role="radio" aria-checked={value === star} aria-label={__('service_pricing.star_label', { count: star })}
                    onClick={() => onChange(star)}
                    className="flex h-12 w-12 items-center justify-center rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0071e3]">
                    <Star className={cn('h-9 w-9 transition-colors', star <= value ? 'fill-amber-400 text-amber-400' : 'text-gray-300 dark:text-zinc-600')} aria-hidden="true" />
                </button>
            ))}
        </div>
    );
}
