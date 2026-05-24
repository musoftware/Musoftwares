import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { LoadingButton } from '@/Components/ui/LoadingButton';
import { Switch } from '@/Components/ui/switch';

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
    const { data, setData, put, processing, errors } = useForm({
        title: event.title,
        duration_minutes: event.duration_minutes,
        description: event.description || '',
        price: event.price || '',
        currency: event.currency || 'USD',
        requires_payment: !!event.requires_payment,
        is_active: !!event.is_active,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('booking.events.update', event.slug));
    };

    return (
        <WorkspaceLayout
            title="Edit Event Type"
            workspaceName="Booking Settings"
            tenantId="SYS-BOOKING"
            menuItems={[
                { id: 'dashboard', label: 'Dashboard', icon: Calendar, href: '/booking', isActive: false },
                { id: 'appointments', label: 'Appointments', icon: Clock, href: '/booking/appointments', isActive: false },
                { id: 'events', label: 'Event Types', icon: Calendar, href: '/booking/events', isActive: true },
                { id: 'providers', label: 'Providers', icon: Users, href: '/booking/providers', isActive: false },
            ]}
        >
            <Head title="Edit Event Type" />
            
            <div className="max-w-2xl mx-auto space-y-8">
                <ModulePageHeader
                    title="Edit Event Type"
                    description="Update your booking event details and settings."
                    actions={
                        <Link href={route('booking.events.index')} className="text-sm font-medium text-slate-500 hover:text-slate-900">
                            Back
                        </Link>
                    }
                />

                <form onSubmit={submit} className="mt-6 space-y-6">
                    <OperationalCard title="Basic Details">
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="title">Event Name</Label>
                                <Input 
                                    id="title" 
                                    value={data.title}
                                    onChange={e => setData('title', e.target.value)}
                                    placeholder="e.g. 30 Minute Consultation"
                                    required
                                />
                                {errors.title && <p className="text-sm text-red-500">{errors.title}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label>URL Slug (Read-only)</Label>
                                <div className="flex items-center">
                                    <span className="text-muted-foreground bg-slate-50 border border-r-0 border-input rounded-l-md px-3 h-10 flex items-center text-sm">
                                        /book/
                                    </span>
                                    <Input 
                                        value={event.slug}
                                        disabled
                                        className="rounded-l-none bg-slate-50 text-slate-500 cursor-not-allowed"
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea 
                                    id="description" 
                                    value={data.description}
                                    onChange={e => setData('description', e.target.value)}
                                    placeholder="Tell invitees what this meeting is about..."
                                    rows={4}
                                />
                                {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
                            </div>
                        </div>
                    </OperationalCard>

                    <OperationalCard title="Scheduling & Status">
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
                                    <Label>Active Status</Label>
                                    <p className="text-sm text-muted-foreground">Allow invitees to book this event type.</p>
                                </div>
                                <Switch 
                                    checked={data.is_active}
                                    onCheckedChange={checked => setData('is_active', checked)}
                                />
                            </div>
                        </div>
                    </OperationalCard>

                    <OperationalCard title="Payment Options">
                        <div className="space-y-4">
                            <div className="flex items-center justify-between p-4 border rounded-lg bg-slate-50/50">
                                <div className="space-y-0.5">
                                    <Label>Require Payment</Label>
                                    <p className="text-sm text-muted-foreground">Ask for payment when booking.</p>
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
                    </OperationalCard>

                    <div className="flex justify-end gap-3">
                        <Link 
                            href={route('booking.events.index')} 
                            className="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-slate-100 transition-colors"
                        >
                            Cancel
                        </Link>
                        <LoadingButton loading={processing} type="submit">
                            Save Changes
                        </LoadingButton>
                    </div>
                </form>
            </div>
        </WorkspaceLayout>
    );
}
