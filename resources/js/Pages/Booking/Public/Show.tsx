import React, { useState, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent } from '@/Components/ui/card';
import { CalendarIcon, Clock, CreditCard, ChevronRight, ChevronLeft, Globe, Users, Stethoscope, CheckCircle2, AlertCircle } from 'lucide-react';
import { format, addDays, startOfToday } from 'date-fns';
import { __ } from '@/lib/i18n';

export default function Show({ host, eventType }: any) {
    const [selectedDate, setSelectedDate] = useState<Date | null>(null);
    const [selectedTime, setSelectedTime] = useState<string | null>(null);
    const [selectedProvider, setSelectedProvider] = useState<any>(null);
    const [availableSlots, setAvailableSlots] = useState<any[]>([]);
    const [loadingSlots, setLoadingSlots] = useState(false);
    const [step, setStep] = useState(1);

    const { data, setData, post, processing, errors } = useForm({
        event_type_id: eventType.id,
        booking_provider_id: '' as string | number,
        guest_name: '',
        guest_email: '',
        guest_phone: '',
        starts_at: '',
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        notes: '',
    });

    // Generate next 14 days for the picker
    const today = startOfToday();
    const availableDates = Array.from({ length: 14 }).map((_, i) => addDays(today, i));

    // Dynamic slot calculation API call
    useEffect(() => {
        if (!selectedDate) {
            setAvailableSlots([]);
            return;
        }

        const fetchSlots = async () => {
            setLoadingSlots(true);
            try {
                const dateStr = format(selectedDate, 'yyyy-MM-dd');
                let url = `/booking/api/slots?event_type_id=${eventType.id}&date=${dateStr}`;
                if (selectedProvider) {
                    url += `&provider_id=${selectedProvider.id}`;
                }
                const res = await fetch(url);
                const result = await res.json();
                setAvailableSlots(result.slots || []);
            } catch (err) {
                console.error("Failed to load slots from API:", err);
                setAvailableSlots([]);
            } finally {
                setLoadingSlots(false);
            }
        };

        fetchSlots();
        setSelectedTime(null);
    }, [selectedDate, selectedProvider, eventType.id]);

    const handleSlotSelect = (slot: any) => {
        setSelectedTime(slot.time);
        setData(prev => ({
            ...prev,
            starts_at: slot.starts_at,
            booking_provider_id: slot.provider?.id || selectedProvider?.id || ''
        }));
    };

    const handleContinue = () => {
        if (selectedDate && selectedTime) {
            setStep(2);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('booking.public.store'));
    };

    const renderDateSelection = () => (
        <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
            {/* Step 1: Provider / Doctor selection if they exist */}
            {eventType.providers && eventType.providers.length > 0 && (
                <div className="space-y-3">
                    <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider">{__('general.select_a_provider')}</h3>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        {/* Any Available Option */}
                        <Card 
                            onClick={() => setSelectedProvider(null)}
                            className={`cursor-pointer transition-all border p-4 ${
                                selectedProvider === null 
                                    ? 'border-primary bg-primary/10 shadow-xs' 
                                    : 'border-border hover:bg-muted/40'
                            }`}
                        >
                            <div className="flex items-center gap-3">
                                <div className="h-10 w-10 rounded-full bg-muted flex items-center justify-center text-muted-foreground shrink-0">
                                    <Users className="h-5 w-5" />
                                </div>
                                <div className="min-w-0">
                                    <h4 className="font-semibold text-foreground text-sm">{__('general.any_provider')}</h4>
                                    <p className="text-xs text-muted-foreground truncate">{__('general.first_available_timeslot')}</p>
                                </div>
                            </div>
                        </Card>

                        {/* Doctors/Staff Roster */}
                        {eventType.providers.map((prov: any) => {
                            const isSelected = selectedProvider?.id === prov.id;
                            return (
                                <Card 
                                    key={prov.id}
                                    onClick={() => setSelectedProvider(prov)}
                                    className={`cursor-pointer transition-all border p-4 ${
                                        isSelected 
                                            ? 'border-primary bg-primary/10 shadow-xs' 
                                            : 'border-border hover:bg-muted/40'
                                    }`}
                                >
                                    <div className="flex items-center gap-3">
                                        <div className="h-10 w-10 rounded-full bg-gradient-to-tr from-slate-700 to-slate-900 flex items-center justify-center text-white font-semibold text-sm shrink-0">
                                            {prov.name.split(' ').map((n: string) => n[0]).join('').substring(0, 2).toUpperCase()}
                                        </div>
                                        <div className="min-w-0">
                                            <h4 className="font-semibold text-foreground text-sm truncate">{prov.name}</h4>
                                            <p className="text-xs text-muted-foreground truncate flex items-center">
                                                <Stethoscope className="w-3 h-3 me-0.5" />
                                                {prov.specialty || 'Specialist'}
                                            </p>
                                        </div>
                                    </div>
                                </Card>
                            );
                        })}
                    </div>
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 pt-4">
                {/* Date Grid */}
                <div className="lg:col-span-7 space-y-4">
                    <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider">{__('general.select_a_date')}</h3>
                    <div className="bg-muted/30 rounded-2xl p-4 border border-border">
                        <div className="flex items-center justify-end gap-4 mb-4">
                            <span className="font-semibold text-foreground">{format(today, 'MMMM yyyy')}</span>
                            <div className="flex gap-2">
                                <Button variant="outline" size="icon" className="h-8 w-8 rounded-lg border-border" disabled><ChevronLeft className="h-4 w-4" /></Button>
                                <Button variant="outline" size="icon" className="h-8 w-8 rounded-lg border-border" disabled><ChevronRight className="h-4 w-4" /></Button>
                            </div>
                        </div>
                        
                        <div className="grid grid-cols-7 gap-1.5 text-center text-xs mb-2">
                            {['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'].map(day => (
                                <div key={day} className="text-muted-foreground font-semibold py-1">{day}</div>
                            ))}
                        </div>
                        
                        <div className="grid grid-cols-7 gap-1.5">
                            {Array.from({ length: today.getDay() }).map((_, i) => (
                                <div key={`empty-${i}`} className="p-2" />
                            ))}
                            
                            {availableDates.map(date => {
                                const isSelected = selectedDate?.toDateString() === date.toDateString();
                                return (
                                    <button
                                        key={date.toISOString()}
                                        onClick={() => setSelectedDate(date)}
                                        className={`
                                            p-2.5 w-full text-center rounded-xl text-sm font-semibold transition-all
                                            ${isSelected 
                                                ? 'bg-primary text-primary-foreground shadow-sm scale-105' 
                                                : 'text-foreground hover:bg-muted'
                                            }
                                        `}
                                    >
                                        {format(date, 'd')}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* Slots Grid */}
                <div className="lg:col-span-5 space-y-4">
                    <div className="flex items-center justify-between">
                        <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider">{__('general.available_times')}</h3>
                        {selectedDate && (
                            <span className="text-xs text-muted-foreground font-medium">
                                {format(selectedDate, 'EEE, MMM d')}
                            </span>
                        )}
                    </div>

                    {selectedDate ? (
                        <div className="space-y-3">
                            {loadingSlots ? (
                                <div className="h-48 flex items-center justify-center text-muted-foreground text-sm">
                                    <Clock className="w-5 h-5 animate-spin me-2" />
                                    {__('general.loading_slots')}...
                                </div>
                            ) : availableSlots.length === 0 ? (
                                <div className="h-48 flex flex-col items-center justify-center text-muted-foreground border border-dashed border-border rounded-2xl p-4 text-center">
                                    <AlertCircle className="w-8 h-8 text-muted-foreground/60 mb-2" />
                                    <span className="text-xs font-semibold">{__('general.no_slots_available')}</span>
                                    <span className="text-[10px] text-muted-foreground mt-0.5">{__('general.try_picking_another_date_or_specialist')}</span>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 gap-2 max-h-72 overflow-y-auto pe-1 custom-scrollbar">
                                    {availableSlots.map(slot => {
                                        const isSelected = selectedTime === slot.time;
                                        return (
                                            <button
                                                key={slot.time}
                                                onClick={() => handleSlotSelect(slot)}
                                                className={`
                                                    py-3 px-4 rounded-xl border text-sm font-semibold transition-all text-start flex justify-between items-center
                                                    ${isSelected 
                                                        ? 'border-primary bg-primary/10 text-foreground ring-1 ring-primary' 
                                                        : 'border-border text-foreground hover:border-border hover:bg-muted/40'
                                                    }
                                                `}
                                            >
                                                <span>{slot.time}</span>
                                                {slot.provider && !selectedProvider && (
                                                    <span className="text-[10px] bg-muted text-muted-foreground px-2 py-0.5 rounded border border-border font-normal truncate max-w-32">
                                                        {slot.provider.name}
                                                    </span>
                                                )}
                                            </button>
                                        );
                                    })}
                                </div>
                            )}
                            
                            {selectedTime && (
                                <div className="pt-2 animate-in slide-in-from-bottom-2 fade-in">
                                    <Button onClick={handleContinue} className="w-full rounded-xl py-5 text-sm font-semibold shadow-sm flex items-center justify-center gap-1.5">
                                        {__('general.continue')}<ChevronRight className="w-4 h-4" />
                                    </Button>
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="h-56 flex flex-col items-center justify-center border-2 border-dashed border-border rounded-2xl p-6 text-center bg-muted/20">
                            <CalendarIcon className="w-8 h-8 text-muted-foreground mb-2 animate-bounce" />
                            <span className="text-muted-foreground text-xs font-semibold">{__('general.select_a_calendar_date')}</span>
                            <span className="text-[10px] text-muted-foreground mt-0.5">{__('general.slots_dynamically_calculated')}</span>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );

    const renderDetailsForm = () => (
        <div className="space-y-6 animate-in fade-in slide-in-from-end-8 duration-500">
            <div className="flex items-center mb-6">
                <Button variant="ghost" size="sm" onClick={() => setStep(1)} className="me-2 -ms-3 text-muted-foreground hover:text-foreground rounded-lg">
                    <ChevronLeft className="h-4 w-4 me-1" />{__('general.back_to_calendar')}</Button>
            </div>

            <div className="bg-muted/40 border border-border rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs">
                <div>
                    <h4 className="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-1">{__('general.appointment_schedule')}</h4>
                    <span className="text-sm font-semibold text-foreground block">
                        {selectedDate && format(selectedDate, 'EEEE, MMMM d, yyyy')}
                    </span>
                    <span className="text-xs font-medium text-muted-foreground mt-0.5 flex items-center gap-1">
                        <Clock className="w-3.5 h-3.5 text-muted-foreground" />
                        {selectedTime} ({eventType.duration_minutes} minutes)
                    </span>
                </div>

                {data.booking_provider_id && (
                    <div className="border-t sm:border-t-0 sm:border-s border-border sm:ps-4 pt-3 sm:pt-0">
                        <h4 className="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-1">{__('general.provider')}</h4>
                        <span className="text-sm font-semibold text-foreground block">
                            {selectedProvider?.name || availableSlots.find(s => s.time === selectedTime)?.provider?.name || 'Assigned Specialist'}
                        </span>
                        {selectedProvider?.specialty && (
                            <span className="text-xs font-medium text-foreground block mt-0.5">
                                {selectedProvider.specialty}
                            </span>
                        )}
                    </div>
                )}
            </div>

            <h3 className="text-lg font-semibold text-foreground">{__('general.enter_booking_details')}</h3>
            
            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="space-y-2">
                        <Label htmlFor="guest_name" className="text-sm font-semibold text-foreground">Your Full Name *</Label>
                        <Input 
                            id="guest_name" 
                            value={data.guest_name}
                            onChange={e => setData('guest_name', e.target.value)}
                            placeholder={__('general.john_doe')}
                            required
                            className="rounded-xl border-border bg-background text-foreground py-5"
                        />
                        {errors.guest_name && <p className="text-sm text-destructive">{errors.guest_name}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="guest_phone" className="text-sm font-semibold text-foreground">Phone Number (Optional)</Label>
                        <Input 
                            id="guest_phone" 
                            value={data.guest_phone || ''}
                            onChange={e => setData('guest_phone', e.target.value)}
                            placeholder="+1 (555) 000-0000"
                            className="rounded-xl border-border bg-background text-foreground py-5"
                        />
                    </div>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="guest_email" className="text-sm font-semibold text-foreground">Email Address *</Label>
                    <Input 
                        id="guest_email" 
                        type="email"
                        value={data.guest_email}
                        onChange={e => setData('guest_email', e.target.value)}
                        placeholder={__('general.john_example_com')}
                        required
                        className="rounded-xl border-border bg-background text-foreground py-5"
                    />
                    {errors.guest_email && <p className="text-sm text-destructive">{errors.guest_email}</p>}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="notes" className="text-sm font-semibold text-foreground">Additional Notes (Optional)</Label>
                    <Textarea 
                        id="notes" 
                        value={data.notes}
                        onChange={e => setData('notes', e.target.value)}
                        placeholder={__('general.please_share_anything_helpful_to_prepare_for_this_consultation')}
                        rows={3}
                        className="rounded-xl border-border bg-background text-foreground resize-none"
                    />
                </div>

                <div className="pt-4 border-t border-border mt-6">
                    <Button 
                        type="submit" 
                        disabled={processing} 
                        className="w-full bg-primary hover:bg-primary/90 text-primary-foreground rounded-xl py-6 text-base font-semibold shadow-sm flex items-center justify-center gap-2"
                    >
                        {processing ? (
                            'Processing booking...'
                        ) : eventType.requires_payment ? (
                            <>
                                <CreditCard className="w-5 h-5" />
                                Proceed to Payment ({eventType.price} {eventType.currency})
                            </>
                        ) : (
                            <>
                                <CheckCircle2 className="w-5 h-5" />{__('general.confirm_appointment')}</>
                        )}
                    </Button>
                </div>
                
                <p className="text-center text-xs text-muted-foreground mt-4">{__('general.secured_by_musoftwares_by_confirming_you_agree_to_our_terms_and_policies')}</p>
            </form>
        </div>
    );

    return (
        <div className="min-h-screen bg-background flex items-center justify-center p-4 py-12 selection:bg-muted">
            <Head title={`Book ${eventType.title} | Musoftwares`} />
            
            <div className="max-w-5xl w-full bg-card text-card-foreground rounded-3xl shadow-[0_8px_40px_rgba(0,0,0,0.03)] overflow-hidden border border-border flex flex-col md:flex-row">
                
                {/* Left Column - Event Info Panel */}
                <div className="w-full md:w-80 bg-muted/30 p-8 border-e border-border relative overflow-hidden flex flex-col justify-between shrink-0">
                    <div className="absolute top-0 start-0 w-full h-32 bg-gradient-to-b from-muted/50 to-transparent -z-10" />
                    
                    <div>
                        <div className="mb-8">
                            <div className="h-14 w-14 bg-primary text-primary-foreground rounded-2xl flex items-center justify-center shadow-xs mb-4 overflow-hidden font-bold text-lg">
                                {host.name.charAt(0).toUpperCase()}
                            </div>
                            <p className="text-muted-foreground font-bold text-xs uppercase tracking-widest mb-1">{__('general.host_profile')}</p>
                            <span className="text-foreground font-semibold text-sm block mb-4">{host.name}</span>
                            <h1 className="text-xl font-extrabold text-foreground tracking-tight leading-tight">{eventType.title}</h1>
                        </div>
                        
                        <div className="space-y-3.5">
                            <div className="flex items-center text-xs font-semibold text-foreground bg-card p-3 rounded-xl shadow-xs border border-border">
                                <Clock className="w-4 h-4 me-2.5 text-muted-foreground shrink-0" />
                                Duration: {eventType.duration_minutes} min
                            </div>
                            
                            {eventType.requires_payment && eventType.price > 0 && (
                                <div className="flex items-center text-xs font-semibold text-foreground bg-muted p-3 rounded-xl shadow-xs border border-border">
                                    <CreditCard className="w-4 h-4 me-2.5 text-muted-foreground shrink-0" />
                                    Fee: {eventType.price} {eventType.currency}
                                </div>
                            )}
                        </div>
                        
                        {eventType.description && (
                            <div className="mt-8 pt-6 border-t border-border">
                                <span className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest block mb-2">{__('general.description')}</span>
                                <p className="text-muted-foreground text-xs leading-relaxed">
                                    {eventType.description}
                                </p>
                            </div>
                        )}
                    </div>

                    <div className="mt-8 pt-6 border-t border-border text-[10px] text-muted-foreground flex items-center gap-1 font-medium">
                        <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500 shrink-0" />{__('general.verified_scheduling_page')}</div>
                </div>
                
                {/* Right Column - Step Booking Flow */}
                <div className="flex-1 p-8 md:p-10">
                    {step === 1 ? renderDateSelection() : renderDetailsForm()}
                </div>
                
            </div>
            
            <style dangerouslySetInnerHTML={{__html: `
                .custom-scrollbar::-webkit-scrollbar {
                    width: 4px;
                }
                .custom-scrollbar::-webkit-scrollbar-track {
                    background: transparent;
                }
                .custom-scrollbar::-webkit-scrollbar-thumb {
                    background-color: var(--border);
                    border-radius: 20px;
                }
            `}} />
        </div>
    );
}
