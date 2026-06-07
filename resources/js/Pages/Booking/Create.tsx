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
export default function Create() {
    const { wallet, settings } = usePage<any>().props;
    const baseCurrency = wallet?.currency || settings?.base_currency;

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        slug: '',
        duration_minutes: 30,
        description: '',
        price: '',
        currency: baseCurrency,
        requires_payment: false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('booking.events.store'));
    };

    // Auto-generate slug from title
    const handleTitleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const title = e.target.value;
        const slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
        setData({ ...data, title, slug });
    };

    return (
        <WorkspaceLayout
            title={__('general.create_event_type')}
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
            <Head title={__('general.create_event_type')} />
            
            <div className="max-w-2xl mx-auto space-y-8">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{__('general.new_event_type')}</h1>
                        <p className="text-muted-foreground">{__('general.set_up_a_new_type_of_booking_event_for_your_clients')}</p>
                    </div>
                    <div className="mt-4 sm:mt-0">
                        <Link href={route('booking.index')} className="text-sm font-medium text-slate-500 hover:text-slate-900">
                            Back
                        </Link>
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
                                    onChange={handleTitleChange}
                                    placeholder={__('general.e_g_30_minute_consultation')}
                                    required
                                />
                                {errors.title && <p className="text-sm text-red-500">{errors.title}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="slug">{__('general.url_slug')}</Label>
                                <div className="flex items-center">
                                    <span className="text-muted-foreground bg-slate-50 border border-r-0 border-input rounded-l-md px-3 h-10 flex items-center text-sm">
                                        /book/
                                    </span>
                                    <Input 
                                        id="slug" 
                                        value={data.slug}
                                        onChange={e => setData('slug', e.target.value)}
                                        className="rounded-l-none"
                                        required
                                    />
                                </div>
                                {errors.slug && <p className="text-sm text-red-500">{errors.slug}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
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
                            <CardTitle>{__('general.scheduling_settings')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                        <div className="space-y-4">
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
                                        <Label htmlFor="price">Price</Label>
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
                                        <Label htmlFor="currency">Currency</Label>
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
                            href={route('booking.index')} 
                            className="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-slate-100 transition-colors"
                        >
                            Cancel
                        </Link>
                        <LoadingButton loading={processing} type="submit">{__('general.create_event_type')}</LoadingButton>
                    </div>
                </form>
            </div>
        </WorkspaceLayout>
    );
}

