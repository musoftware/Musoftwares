import React, { useState, useEffect } from 'react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import { 
    DropdownMenu, 
    DropdownMenuContent, 
    DropdownMenuItem, 
    DropdownMenuLabel, 
    DropdownMenuSeparator, 
    DropdownMenuTrigger 
} from '@/Components/ui/dropdown-menu';
import { 
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import { EmptyState } from '@/Components/ui/EmptyState';
import { format } from 'date-fns';
import { Calendar, Clock, CreditCard, ExternalLink, MoreVertical, Search, UserCircle2, Briefcase, FileText, CheckCircle, XCircle, Users, LayoutList, CalendarDays, CalendarOff } from 'lucide-react';
import { SimpleCalendar } from '@/Components/Booking/SimpleCalendar';
import { __ } from '@/lib/i18n';

export default function Appointments({ bookings, providers, filters }: any) {
    const [search, setSearch] = useState(filters?.search || '');
    const [providerId, setProviderId] = useState(filters?.provider_id || '');
    const [viewMode, setViewMode] = useState<'list' | 'calendar'>('list');
    const [selectedBooking, setSelectedBooking] = useState<any>(null);
    const [isNotesModalOpen, setIsNotesModalOpen] = useState(false);
    
    useEffect(() => {
        const debounce = setTimeout(() => {
            if (search !== (filters?.search || '') || providerId !== (filters?.provider_id || '')) {
                const params: any = {};
                if (search) params.search = search;
                if (providerId) params.provider_id = providerId;
                
                router.get(
                    route('booking.appointments'),
                    params,
                    { preserveState: true, replace: true }
                );
            }
        }, 300);
        return () => clearTimeout(debounce);
    }, [search, providerId, filters?.search, filters?.provider_id]);
    
    const { data: notesData, setData: setNotesData, post: postNotes, processing: processingNotes } = useForm({
        internal_notes: ''
    });

    const openNotesModal = (booking: any) => {
        setSelectedBooking(booking);
        setNotesData('internal_notes', booking.internal_notes || '');
        setIsNotesModalOpen(true);
    };

    const handleSaveNotes = (e: React.FormEvent) => {
        e.preventDefault();
        postNotes(route('booking.appointments.notes', selectedBooking.id), {
            onSuccess: () => setIsNotesModalOpen(false)
        });
    };

    const handleUpdateStatus = (id: number, status: string) => {
        router.post(route('booking.appointments.status', id), { status }, {
            preserveScroll: true
        });
    };

    const handleCreateProject = (id: number) => {
        router.post(route('booking.appointments.create-project', id), {}, {
            preserveScroll: true
        });
    };
    
    const handleCreateInvoice = (id: number) => {
        router.post(route('booking.appointments.create-invoice', id), {}, {
            preserveScroll: true
        });
    };

    const getStatusBadge = (status: string) => {
        const variants: Record<string, string> = {
            pending: 'bg-amber-100 text-amber-800 hover:bg-amber-100',
            confirmed: 'bg-blue-100 text-blue-800 hover:bg-blue-100',
            completed: 'bg-emerald-100 text-emerald-800 hover:bg-emerald-100',
            cancelled: 'bg-slate-100 text-slate-800 hover:bg-slate-100',
        };
        return <Badge className={`font-medium ${variants[status] || 'bg-slate-100'}`}>{status.charAt(0).toUpperCase() + status.slice(1)}</Badge>;
    };

    const getPaymentBadge = (status: string, method?: string) => {
        if (status === 'free') return <Badge variant="outline" className="text-slate-500">{__('general.free')}</Badge>;
        if (status === 'pending') return <Badge variant="outline" className="text-amber-600 border-amber-200">{__('general.payment_pending')}</Badge>;
        if (status === 'paid') return <Badge variant="outline" className="text-emerald-600 border-emerald-200 bg-emerald-50">Paid via {method}</Badge>;
        return null;
    };

    return (
        <WorkspaceLayout
            title={__('general.appointments')}
            workspaceName="Booking Settings"
            tenantId="SYS-BOOKING"
            menuItems={[
                { id: 'dashboard', label: 'Dashboard', icon: Calendar, href: '/booking', isActive: false },
                { id: 'appointments', label: 'Appointments', icon: Clock, href: '/booking/appointments', isActive: true },
                { id: 'events', label: 'Event Types', icon: Calendar, href: '/booking/events', isActive: false },
                { id: 'providers', label: 'Providers', icon: Users, href: '/booking/providers', isActive: false },
                { id: 'exceptions', label: 'Exceptions', icon: CalendarOff, href: '/booking/exceptions', isActive: false },
            ]}
        >
            <Head title={__('general.appointments')} />

            <div className="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{__('general.appointments')}</h1>
                        <p className="text-muted-foreground">{__('general.manage_your_bookings_consultations_and_operational_pipeline')}</p>
                    </div>
                    
                    <div className="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <select
                            className="w-full sm:w-48 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            value={providerId}
                            onChange={(e) => setProviderId(e.target.value)}
                        >
                            <option value="">{__('general.all_providers')}</option>
                            {providers.map((p: any) => (
                                <option key={p.id} value={p.id}>{p.name}</option>
                            ))}
                        </select>

                        <div className="relative w-full sm:w-64">
                            <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <Input 
                                placeholder={__('general.search_guest_name_or_email')} 
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                                className="ps-9 bg-white"
                            />
                        </div>

                        <div className="flex bg-white rounded-md border border-slate-200 p-0.5">
                            <button
                                onClick={() => setViewMode('list')}
                                className={`px-3 py-1.5 rounded-sm text-sm font-medium flex items-center transition-colors ${viewMode === 'list' ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:text-slate-700'}`}
                            >
                                <LayoutList className="w-4 h-4 me-1.5" /> {__('general.list')}</button>
                            <button
                                onClick={() => setViewMode('calendar')}
                                className={`px-3 py-1.5 rounded-sm text-sm font-medium flex items-center transition-colors ${viewMode === 'calendar' ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:text-slate-700'}`}
                            >
                                <CalendarDays className="w-4 h-4 me-1.5" /> {__('general.calendar')}</button>
                        </div>
                    </div>
                </div>

                {(bookings.data as any).length === 0 ? (
                    <EmptyState 
                        icon={Calendar}
                        title={__('general.no_appointments_yet')}
                        description={__('general.share_your_booking_links_to_start_receiving_appointments_from_clients')}
                        action={{
                            label: "View Event Types",
                            href: route('booking.index')
                        }}
                    />
                ) : viewMode === 'calendar' ? (
                    <SimpleCalendar 
                        bookings={bookings.data}
                        onBookingClick={(booking: any) => {
                            // You could open a detailed modal here, for now we just log or we can open notes
                            console.log('Clicked booking', booking);
                            openNotesModal(booking);
                        }}
                    />
                ) : (
                    <div className="space-y-4">
                        {(bookings.data as any).map((booking: any) => (
                            <Card key={booking.id} className="overflow-hidden border-slate-200/60 shadow-sm transition-shadow hover:shadow-md">
                                <div className="flex flex-col md:flex-row">
                                    {/* Left Status Bar */}
                                    <div className="w-full md:w-48 bg-slate-50 p-6 flex flex-col justify-center border-b md:border-b-0 md:border-e border-slate-100">
                                        <div className="text-sm text-slate-500 font-medium mb-1">
                                            {format(new Date(booking.starts_at), 'MMM d, yyyy')}
                                        </div>
                                        <div className="text-lg font-semibold text-slate-900 mb-4">
                                            {format(new Date(booking.starts_at), 'h:mm a')}
                                        </div>
                                        <div className="flex flex-col items-start gap-2">
                                            {getStatusBadge(booking.status)}
                                            {getPaymentBadge(booking.payment_status, booking.payment_method)}
                                        </div>
                                    </div>
                                    
                                    {/* Center Content */}
                                    <div className="flex-1 p-6">
                                        <div className="flex justify-between items-start">
                                            <div>
                                                <h3 className="text-lg font-medium text-slate-900 mb-1">{booking.eventType.title}</h3>
                                                
                                                <div className="flex items-center text-slate-600 mb-4">
                                                    <UserCircle2 className="h-4 w-4 me-2 text-slate-400" />
                                                    <span className="font-medium me-2">{booking.guest_name}</span>
                                                    <span className="text-sm text-slate-500">({booking.guest_email})</span>
                                                </div>
                                                
                                                {booking.provider && (
                                                    <div className="flex items-center text-sm text-slate-500 mb-2">
                                                        <Users className="h-4 w-4 me-2 text-slate-400" />
                                                        Provider: <span className="font-medium ms-1 text-slate-700">{booking.provider.name}</span>
                                                    </div>
                                                )}
                                                
                                                {booking.notes && (
                                                    <div className="bg-slate-50 rounded-lg p-3 text-sm text-slate-600 border border-slate-100 mt-2">
                                                        <span className="font-medium block text-slate-700 mb-1">Guest Notes:</span>
                                                        {booking.notes}
                                                    </div>
                                                )}
                                                
                                                {booking.internal_notes && (
                                                    <div className="bg-amber-50 rounded-lg p-3 text-sm text-amber-800 border border-amber-100 mt-2">
                                                        <span className="font-medium block mb-1">Internal Notes:</span>
                                                        {booking.internal_notes}
                                                    </div>
                                                )}
                                            </div>
                                            
                                            <div className="flex flex-col items-end gap-2 text-sm text-slate-500">
                                                <div className="flex items-center">
                                                    <Clock className="h-4 w-4 me-1.5" /> {booking.eventType.duration_minutes}m
                                                </div>
                                                {booking.price && (
                                                    <div className="flex items-center">
                                                        <CreditCard className="h-4 w-4 me-1.5" /> {booking.price} {booking.currency}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {/* Right Actions */}
                                    <div className="p-4 md:p-6 bg-white border-t md:border-t-0 md:border-s border-slate-100 flex md:flex-col items-center justify-end gap-2 md:w-16">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 hover:bg-slate-100 hover:text-slate-900 h-8 w-8">
                                                <MoreVertical className="h-4 w-4 text-slate-500" />
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" className="w-56">
                                                <DropdownMenuLabel>{__('general.manage_appointment')}</DropdownMenuLabel>
                                                <DropdownMenuSeparator />
                                                
                                                {/* Status Actions */}
                                                {booking.status === 'pending' && (
                                                    <DropdownMenuItem onClick={() => handleUpdateStatus(booking.id, 'confirmed')}>
                                                        <CheckCircle className="h-4 w-4 me-2 text-slate-500" />{__('general.confirm_booking')}</DropdownMenuItem>
                                                )}
                                                {(booking.status === 'confirmed' || booking.status === 'pending') && (
                                                    <DropdownMenuItem onClick={() => handleUpdateStatus(booking.id, 'cancelled')}>
                                                        <XCircle className="h-4 w-4 me-2 text-slate-500" />{__('general.cancel_booking')}</DropdownMenuItem>
                                                )}
                                                {booking.status === 'confirmed' && (
                                                    <DropdownMenuItem onClick={() => handleUpdateStatus(booking.id, 'completed')}>
                                                        <CheckCircle className="h-4 w-4 me-2 text-slate-500" />{__('general.mark_completed')}</DropdownMenuItem>
                                                )}

                                                <DropdownMenuSeparator />
                                                
                                                <DropdownMenuItem onClick={() => openNotesModal(booking)}>
                                                    <FileText className="h-4 w-4 me-2 text-slate-500" />{__('general.internal_notes_1')}</DropdownMenuItem>

                                                <DropdownMenuSeparator />
                                                <DropdownMenuLabel className="text-xs text-slate-500">{__('general.pipeline_workflow')}</DropdownMenuLabel>
                                                
                                                <DropdownMenuItem onClick={() => handleCreateProject(booking.id)}>
                                                    <Briefcase className="h-4 w-4 me-2 text-slate-500" />{__('general.convert_to_project')}</DropdownMenuItem>
                                                <DropdownMenuItem onClick={() => handleCreateInvoice(booking.id)}>
                                                    <CreditCard className="h-4 w-4 me-2 text-slate-500" />{__('general.generate_invoice')}</DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            {/* Notes Modal */}
            <Dialog open={isNotesModalOpen} onOpenChange={setIsNotesModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{__('general.internal_notes_1')}</DialogTitle>
                        <DialogDescription>{__('general.add_private_notes_for_this_appointment_the_guest_will_not_see_this')}</DialogDescription>
                    </DialogHeader>
                    
                    <form onSubmit={handleSaveNotes} className="space-y-4 py-4">
                        <Textarea 
                            value={notesData.internal_notes}
                            onChange={e => setNotesData('internal_notes', e.target.value)}
                            placeholder={__('general.add_your_notes_here')}
                            rows={5}
                        />
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsNotesModalOpen(false)}>
                                {__('general.cancel')}</Button>
                            <Button type="submit" disabled={processingNotes}>{__('general.save_notes')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </WorkspaceLayout>
    );
}
