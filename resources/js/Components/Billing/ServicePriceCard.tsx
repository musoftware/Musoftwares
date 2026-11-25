import { router } from '@inertiajs/react';
import { Mail, Star } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { toast } from 'sonner';

export interface ServiceQuote {
    market_minutes: number | null;
    market_value: number;
    response_minutes: number | null;
    start_bonus_percent: number;
    client_average_rating: number | null;
    client_ratings_count: number;
    quality_bonus_percent: number;
    service_value: number;
    minimum: number | null;
    floor: number;
    sessions_total: number;
    price: number;
}

interface Currency {
    id: number;
    currency: string;
    symbol: string | null;
}

interface RatingInfo {
    id: number;
    client_rating?: number | null;
    client_rating_note?: string | null;
    rating_requested_at?: string | null;
    client_has_email?: boolean;
}

interface Props {
    quote: ServiceQuote;
    currency: Currency | null;
    hourRate: number | null;
    item: RatingInfo;
    requestedAt: string;
    marketHours: string;
    onRequestedAtChange: (value: string) => void;
    onMarketHoursChange: (value: string) => void;
    isDirty: boolean;
    disabled: boolean;
}

/** Prices a timed task as a service: market time, fast start, and this client's ratings. The server does the math. */
export default function ServicePriceCard(props: Props) {
    const { quote, currency, disabled } = props;

    return (
        <section className="border border-gray-200 rounded-xl p-4 mb-6" aria-labelledby="service-price-title">
            <h2 id="service-price-title" className="text-sm font-bold text-gray-900 mb-3">{__('service_pricing.section_title')}</h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label htmlFor="requested_at" className="text-xs font-semibold text-gray-600 mb-1 block">{__('service_pricing.requested_at')}</label>
                    <Input id="requested_at" type="datetime-local" value={props.requestedAt} onChange={(e) => props.onRequestedAtChange(e.target.value)} disabled={disabled} />
                </div>
                <div>
                    <label htmlFor="market_hours" className="text-xs font-semibold text-gray-600 mb-1 block">{__('service_pricing.market_time')}</label>
                    <Input id="market_hours" type="number" min="0" step="0.25" placeholder="0" value={props.marketHours} onChange={(e) => props.onMarketHoursChange(e.target.value)} disabled={disabled} />
                    <p className="text-xs text-gray-500 mt-1">{__('service_pricing.market_time_help')}</p>
                </div>
            </div>

            <dl className="divide-y divide-gray-100 text-sm">
                <BreakdownRow label={__('service_pricing.market_value')} detail={marketDetail(quote, props.hourRate, currency)} value={formatMoney(quote.market_value, currency)} />
                <BreakdownRow label={__('service_pricing.start_bonus')} detail={startDetail(quote)} value={`+${quote.start_bonus_percent}%`} />
                <BreakdownRow label={__('service_pricing.quality_bonus')} detail={qualityDetail(quote)} value={`+${quote.quality_bonus_percent}%`} />
                {quote.minimum !== null && <BreakdownRow label={__('service_pricing.minimum')} value={formatMoney(quote.minimum, currency)} />}
                <BreakdownRow label={__('service_pricing.service_price')} value={formatMoney(quote.floor, currency)} strong />
            </dl>
            {props.isDirty && <p className="text-xs text-amber-700 mt-2">{__('service_pricing.save_to_update')}</p>}

            <RatingRow item={props.item} />
        </section>
    );
}

function BreakdownRow({ label, detail, value, strong = false }: { label: string; detail?: string; value: string; strong?: boolean }) {
    return (
        <div className="flex items-start justify-between gap-3 py-2">
            <dt className="min-w-0">
                <span className={strong ? 'font-bold text-gray-900' : 'font-medium text-gray-700'}>{label}</span>
                {detail && <span className="block text-xs text-gray-500">{detail}</span>}
            </dt>
            <dd className={`shrink-0 font-mono ${strong ? 'font-bold text-gray-900' : 'text-gray-700'}`} dir="ltr">{value}</dd>
        </div>
    );
}

function RatingRow({ item }: { item: RatingInfo }) {
    const askRating = () => router.post(route('admin.invoices.timer-details.ask-rating', item.id), {}, {
        preserveScroll: true,
        onSuccess: (page) => showFlash(page.props.flash as Flash | undefined),
        onError: () => toast.error(__('general.error_occurred')),
    });
    const askLabel = item.rating_requested_at ? __('service_pricing.ask_rating_again') : __('service_pricing.ask_rating');

    return (
        <div className="flex flex-wrap items-center gap-3 mt-4 pt-4 border-t border-gray-100">
            {item.client_rating ? (
                <span className="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-900">
                    <Star className="w-4 h-4 fill-amber-400 text-amber-400" aria-hidden="true" />
                    {__('service_pricing.client_rated', { rating: item.client_rating })}
                </span>
            ) : item.rating_requested_at && (
                <span className="text-xs text-gray-500">{__('service_pricing.rating_asked_on', { date: item.rating_requested_at })}</span>
            )}
            {item.client_rating_note && <p className="basis-full text-sm text-gray-600">{item.client_rating_note}</p>}
            <Button type="button" variant="outline" onClick={askRating} disabled={!item.client_has_email} className="ms-auto">
                <Mail className="w-4 h-4 me-2" /> {askLabel}
            </Button>
        </div>
    );
}

type Flash = { success?: string; error?: string };

/** Admin pages have no global flash toast, so the result is shown here. */
function showFlash(flash: Flash | undefined): void {
    if (flash?.error) {
        toast.error(flash.error);
        return;
    }
    if (flash?.success) toast.success(flash.success);
}

function marketDetail(quote: ServiceQuote, hourRate: number | null, currency: Currency | null): string {
    if (!quote.market_minutes) return __('service_pricing.market_value_empty');
    const hours = +(quote.market_minutes / 60).toFixed(2);
    return __('service_pricing.market_value_detail', { hours, rate: formatMoney(hourRate ?? 0, currency) });
}

function startDetail(quote: ServiceQuote): string {
    if (quote.response_minutes === null) return __('service_pricing.start_bonus_empty');
    return __('service_pricing.start_bonus_detail', { minutes: quote.response_minutes });
}

function qualityDetail(quote: ServiceQuote): string {
    if (quote.client_average_rating === null) return __('service_pricing.quality_bonus_empty');
    return __('service_pricing.quality_bonus_detail', { rating: quote.client_average_rating, count: quote.client_ratings_count });
}
