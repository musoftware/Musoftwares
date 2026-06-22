import React from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { LoadingButton } from '@/Components/ui/LoadingButton';
import { Switch } from '@/Components/ui/switch';
import { __ } from '@/lib/i18n';
import { Calendar, Clock, Users, CalendarOff } from 'lucide-react';
interface BookingEvent {
    id: number;
    title: string;
    slug: string;
    duration_minutes: number;
    description: string | null;
    price: string | null;
    currency: string | null;
    requires_payment: boolean;
    is_active: boolean;
}

interface Props {
    event: BookingEvent;
}

export default function Edit({ event }: Props) {
    const { wallet, settings } = usePage<any>().props;
    const baseCurrency = wallet?.currency || settings?.base_currency;

    const { data, setData, put, processing, errors } = useForm({
        title: event.title,
        duration_minutes: event.duration_minutes,
        description: event.description || '',
        price: event.price || '',
        currency: event.currency || baseCurrency,
        requires_payment: !!event.requires_payment,
        is_active: !!event.is_active,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('booking.events.update', event.slug));
    };

    return (
        <WorkspaceLayout
            title={__('general.edit_event_type')}
            workspaceName="Booking Settings"
            tenantId="SYS-BOOKING"
            menuItems={[
                { id: 'dashboard', label: 'Dashboard', icon: Calendar, href: '/booking', isActive: false },
                { id: 'appointments', label: 'Appointments', icon: Clock, href: '/booking/appointments', isActive: false },
                { id: 'events', label: 'Event Types', icon: Calendar, href: '/booking/events', isActive: true },
                { id: 'providers', label: 'Providers', icon: Users, href: '/booking/providers', isActive: false },
                { id: 'exceptions', label: 'Exceptions', icon: CalendarOff, href: '/booking/exceptions', isActive: false },
            ]}
        >
            <Head title={__('general.edit_event_type')} />
            
            <div className="max-w-2xl mx-auto space-y-8">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{__('general.edit_event_type')}</h1>
                        <p className="text-muted-foreground">{__('general.update_your_booking_event_details_and_settings')}</p>
                    </div>
                    <div className="mt-4 sm:mt-0">
                        <Link href={route('booking.events.index')} className="text-sm font-medium text-slate-500 hover:text-slate-900">
                            {__('general.back')}</Link>
                    </div>
                </div>

                <form onSubmit={submit} className="mt-6 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{__('general.basic_details')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="title">{__('general.event_name')}</Label>
                                <Input 
                                    id="title" 
                                    value={data.title}
                                    onChange={e => setData('title', e.target.value)}
                                    placeholder={__('general.e_g_30_minute_consultation')}
                                    required
                                />
                                {errors.title && <p className="text-sm text-red-500">{errors.title}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label>URL Slug (Read-only)</Label>
                                <div className="flex items-center">
                                    <span className="text-muted-foreground bg-slate-50 border border-e-0 border-input rounded-s-md px-3 h-10 flex items-center text-sm">
                                        /book/
                                    </span>
                                    <Input 
                                        value={event.slug}
                                        disabled
                                        className="rounded-s-none bg-slate-50 text-slate-500 cursor-not-allowed"
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">{__('general.description')}</Label>
                                <Textarea 
                                    id="description" 
                                    value={data.description}
                                    onChange={e => setData('description', e.target.value)}
                                    placeholder={__('general.tell_invitees_what_this_meeting_is_about')}
                                    rows={4}
                                />
                                {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
                            </div>
                        </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{__('general.scheduling_status')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                        <div className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="duration_minutes">Duration (Minutes)</Label>
                                <Input 
                                    id="duration_minutes" 
                                    type="number"
                                    min="5"
                                    step="5"
                                    value={data.duration_minutes}
                                    onChange={e => setData('duration_minutes', parseInt(e.target.value))}
                                    required
                                    className="w-32"
                                />
                                {errors.duration_minutes && <p className="text-sm text-red-500">{errors.duration_minutes}</p>}
                            </div>

                            <div className="flex items-center justify-between p-4 border rounded-lg bg-slate-50/50">
                                <div className="space-y-0.5">
                                    <Label>{__('general.active_status')}</Label>
                                    <p className="text-sm text-muted-foreground">{__('general.allow_invitees_to_book_this_event_type')}</p>
                                </div>
                                <Switch 
                                    checked={data.is_active}
                                    onCheckedChange={checked => setData('is_active', checked)}
                                />
                            </div>
                        </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{__('general.payment_options')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                        <div className="space-y-4">
                            <div className="flex items-center justify-between p-4 border rounded-lg bg-slate-50/50">
                                <div className="space-y-0.5">
                                    <Label>{__('general.require_payment')}</Label>
                                    <p className="text-sm text-muted-foreground">{__('general.ask_for_payment_when_booking')}</p>
                                </div>
                                <Switch 
                                    checked={data.requires_payment}
                                    onCheckedChange={checked => setData('requires_payment', checked)}
                                />
                            </div>

                            {data.requires_payment && (
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="price">{__('general.price')}</Label>
                                        <Input 
                                            id="price" 
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={data.price}
                                            onChange={e => setData('price', e.target.value)}
                                            required={data.requires_payment}
                                        />
                                        {errors.price && <p className="text-sm text-red-500">{errors.price}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="currency">{__('general.currency')}</Label>
                                        <Input 
                                            id="currency" 
                                            value={data.currency}
                                            onChange={e => setData('currency', e.target.value)}
                                            maxLength={3}
                                            required={data.requires_payment}
                                        />
                                        {errors.currency && <p className="text-sm text-red-500">{errors.currency}</p>}
                                    </div>
                                </div>
                            )}
                        </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-3">
                        <Link 
                            href={route('booking.events.index')} 
                            className="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-slate-100 transition-colors"
                        >
                            {__('general.cancel')}</Link>
                        <LoadingButton loading={processing} type="submit">{__('general.save_changes')}</LoadingButton>
                    </div>
                </form>
            </div>
        </WorkspaceLayout>
    );
}

