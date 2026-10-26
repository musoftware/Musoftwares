import React, { useState } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, Clock, Search, ShieldCheck, Wallet } from 'lucide-react';
import ClientPage, { Card, EmptyNote, TextAction } from '@/Components/Client/ClientPage';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface MicroServiceItem {
    id: number;
    title: string;
    description: string;
    base_price: number;
    price: number;
    formatted_price: string;
    delivery_days: number;
    is_active: boolean;
}

interface MicroServiceOrderItem {
    id: number;
    service_title: string;
    service_description: string | null;
    amount_paid: number;
    currency_symbol: string;
    formatted_amount: string;
    requirements: string;
    status: 'pending' | 'completed' | 'cancelled';
    admin_notes: string | null;
    completed_at: string | null;
    created_at: string | null;
}

interface Props {
    services: MicroServiceItem[];
    orders: MicroServiceOrderItem[];
    available_balance: number;
    formatted_balance: string;
    currency_symbol: string;
}

/** Small fixed-price jobs paid from the wallet: pick one, describe what you need, follow your orders. */
export default function MicroServicesIndex({ services = [], orders = [], available_balance = 0, formatted_balance = '0.00' }: Props) {
    const { locale } = usePage().props as any;
    const [activeTab, setActiveTab] = useState<'catalog' | 'orders'>('catalog');
    const [selectedService, setSelectedService] = useState<MicroServiceItem | null>(null);
    const [searchQuery, setSearchQuery] = useState('');
    const { data, setData, post, processing, errors, reset } = useForm({ requirements: '' });

    const openOrderModal = (service: MicroServiceItem) => { setSelectedService(service); setData('requirements', ''); };
    const closeOrderModal = () => { setSelectedService(null); reset(); };
    const handleConfirmOrder = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedService) return;
        post(route('micro-services.order', selectedService.id), {
            preserveScroll: true,
            onSuccess: () => { closeOrderModal(); setActiveTab('orders'); },
        });
    };

    const hasEnoughBalance = selectedService ? available_balance >= selectedService.price : true;
    const q = searchQuery.toLowerCase();
    const filteredServices = services.filter((s) => s.title.toLowerCase().includes(q) || s.description.toLowerCase().includes(q));
    const tab = (key: 'catalog' | 'orders', label: string) => (
        <button
            type="button"
            onClick={() => setActiveTab(key)}
            className={cn('rounded-full px-4 py-2 text-sm font-semibold transition-colors', activeTab === key ? 'bg-[#1d1d1f] text-white dark:bg-white dark:text-black' : 'text-[#1d1d1f]/70 hover:bg-black/5 dark:text-zinc-300')}
        >
            {label}
        </button>
    );

    return (
        <ClientPage title={__('client.micro_page_title')} hint={__('client.micro_subtitle')}>
            <Card className="flex flex-wrap items-center justify-between gap-3 text-sm">
                <span className="flex items-center gap-2 text-[#1d1d1f]/70 dark:text-zinc-300">
                    <Wallet className="h-4 w-4 text-emerald-600" />{__('client.micro_current_balance')}: <b dir="ltr">{formatted_balance}</b>
                </span>
                <TextAction href={route('financial.add-balance')}>{__('client.micro_top_up')}</TextAction>
            </Card>

            <div className="flex flex-wrap items-center gap-2">
                {tab('catalog', __('client.micro_catalog_tab', { count: services.length }))}
                {tab('orders', `${__('client.micro_orders_tab')}${orders.length ? ` (${orders.length})` : ''}`)}
            </div>

            {activeTab === 'catalog' ? (
                <div className="space-y-4">
                    <div className="relative max-w-md">
                        <Search className="absolute start-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#1d1d1f]/40" />
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder={__('client.micro_search_placeholder')}
                            aria-label={__('client.micro_search_placeholder')}
                            className="h-11 w-full rounded-xl border border-black/10 bg-white pe-4 ps-10 text-sm focus:outline-none focus:ring-2 focus:ring-[#0071e3]/30 dark:border-white/10 dark:bg-zinc-900"
                        />
                    </div>

                    {filteredServices.length === 0 ? (
                        <EmptyNote>{__('client.micro_no_match_desc')}</EmptyNote>
                    ) : (
                        <ul className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            {filteredServices.map((service) => (
                                <li key={service.id}>
                                    <Card className="flex h-full flex-col gap-3">
                                        <h3 className="text-lg font-bold leading-snug">{service.title}</h3>
                                        <p className="flex-1 text-sm leading-relaxed text-[#1d1d1f]/70 dark:text-zinc-300">{service.description}</p>
                                        <p className="flex items-center gap-1.5 text-sm text-[#1d1d1f]/55 dark:text-zinc-400">
                                            <Clock className="h-4 w-4" />
                                            {__(service.delivery_days === 1 ? 'client.micro_delivery_one' : 'client.micro_delivery_many', { days: service.delivery_days })}
                                        </p>
                                        <div className="flex items-center justify-between gap-3 border-t border-black/5 pt-3 dark:border-white/10">
                                            <span className="text-lg font-bold text-[#0071e3]" dir="ltr">{service.formatted_price}</span>
                                            <button type="button" onClick={() => openOrderModal(service)} className="inline-flex h-11 items-center rounded-full bg-[#0071e3] px-5 text-sm font-semibold text-white hover:bg-[#0077ed]">
                                                {__('client.micro_order_service')}
                                            </button>
                                        </div>
                                    </Card>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            ) : orders.length === 0 ? (
                <EmptyNote action={<TextAction onClick={() => setActiveTab('catalog')}>{__('client.micro_browse_services')}</TextAction>}>
                    {__('client.micro_no_orders_title')}
                </EmptyNote>
            ) : (
                <ul className="space-y-3">
                    {orders.map((order) => (
                        <li key={order.id}>
                            <Card className="space-y-3">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <h3 className="font-bold">#{order.id} · {order.service_title}</h3>
                                        <p className="text-xs text-[#1d1d1f]/55 dark:text-zinc-400">
                                            {__('client.micro_order_date', { date: order.created_at ? new Date(order.created_at).toLocaleString(locale === 'ar' ? 'ar-EG' : 'en-GB', { timeZone: 'Africa/Cairo', day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-' })}
                                        </p>
                                    </div>
                                    <div className="text-end">
                                        <p className="text-sm font-bold" dir="ltr">{order.formatted_amount}</p>
                                        {order.status === 'completed' ? (
                                            <p className="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400"><CheckCircle2 className="h-3.5 w-3.5" />{__('client.micro_status_completed')}</p>
                                        ) : (
                                            <p className="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 dark:text-amber-400"><Clock className="h-3.5 w-3.5" />{__('client.micro_status_in_progress')}</p>
                                        )}
                                    </div>
                                </div>
                                <div className="rounded-xl bg-[#f5f5f7] p-3 dark:bg-zinc-800">
                                    <p className="text-xs font-semibold text-[#1d1d1f]/60 dark:text-zinc-400">{__('client.micro_submitted_details')}</p>
                                    <p className="mt-1 whitespace-pre-wrap text-sm leading-relaxed" dir="auto">{order.requirements}</p>
                                </div>
                                {order.admin_notes && (
                                    <div className="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-950/30">
                                        <p className="flex items-center gap-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400"><ShieldCheck className="h-3.5 w-3.5" />{__('client.micro_admin_notes')}</p>
                                        <p className="mt-1 whitespace-pre-wrap text-sm leading-relaxed text-emerald-950 dark:text-emerald-100" dir="auto">{order.admin_notes}</p>
                                    </div>
                                )}
                            </Card>
                        </li>
                    ))}
                </ul>
            )}

            <Dialog open={!!selectedService} onOpenChange={(open) => !open && closeOrderModal()}>
                <DialogContent className="max-w-lg rounded-3xl p-6">
                    <DialogHeader>
                        <DialogTitle className="text-xl font-bold">{__('client.micro_order_title', { title: selectedService?.title ?? '' })}</DialogTitle>
                        <DialogDescription className="pt-1 text-sm">{selectedService?.description}</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleConfirmOrder} className="space-y-4 pt-2">
                        <div className="space-y-2 rounded-2xl bg-[#f5f5f7] p-4 text-sm dark:bg-zinc-800">
                            <div className="flex items-center justify-between">
                                <span className="text-[#1d1d1f]/60 dark:text-zinc-400">{__('client.micro_service_price')}</span>
                                <span className="font-bold text-[#0071e3]" dir="ltr">{selectedService?.formatted_price}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-[#1d1d1f]/60 dark:text-zinc-400">{__('client.micro_balance_label')}</span>
                                <span className={cn('font-bold', hasEnoughBalance ? 'text-emerald-600' : 'text-rose-600')} dir="ltr">{formatted_balance}</span>
                            </div>
                            {!hasEnoughBalance && (
                                <p className="mt-2 flex items-start gap-2 rounded-xl bg-rose-50 p-3 text-xs text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">
                                    <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                                    <span><b>{__('client.micro_insufficient')}</b> {__('client.micro_insufficient_hint')}</span>
                                </p>
                            )}
                        </div>
                        <div className="space-y-1.5">
                            <label htmlFor="micro_requirements" className="text-sm font-semibold">{__('client.micro_requirements_label')} <span className="text-rose-500">*</span></label>
                            <Textarea
                                id="micro_requirements"
                                rows={4}
                                value={data.requirements}
                                onChange={(e) => setData('requirements', e.target.value)}
                                placeholder={__('client.micro_requirements_placeholder')}
                                className="w-full rounded-xl text-sm"
                                required
                            />
                            {errors.requirements && <p className="text-sm text-rose-600">{errors.requirements}</p>}
                        </div>
                        <DialogFooter className="flex flex-col gap-2 pt-2 sm:flex-row">
                            <Button type="button" variant="outline" onClick={closeOrderModal} className="rounded-full">{__('general.cancel')}</Button>
                            {!hasEnoughBalance ? (
                                <Link href={route('financial.add-balance')} className="inline-flex h-10 items-center justify-center rounded-full bg-emerald-600 px-5 text-sm font-semibold text-white hover:bg-emerald-700">
                                    {__('client.micro_top_up_now')}
                                </Link>
                            ) : (
                                <Button type="submit" disabled={processing || !data.requirements.trim()} className="rounded-full bg-[#0071e3] text-white hover:bg-[#0077ed]">
                                    {processing ? __('client.micro_processing') : __('client.micro_confirm_order')}
                                </Button>
                            )}
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </ClientPage>
    );
}
