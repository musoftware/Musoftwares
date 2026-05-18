import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent } from '@/Components/ui/card';
import { CalendarIcon, Clock, CreditCard, ChevronRight, ChevronLeft, Globe } from 'lucide-react';
import { format, addDays, startOfToday, setHours, setMinutes } from 'date-fns';

export default function Show({ host, eventType }: any) {
    const [selectedDate, setSelectedDate] = useState<Date | null>(null);
    const [selectedTime, setSelectedTime] = useState<string | null>(null);
    const [step, setStep] = useState(1);

    const { data, setData, post, processing, errors } = useForm({
        event_type_id: eventType.id,
        guest_name: '',
        guest_email: '',
        guest_phone: '',
        starts_at: '',
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        notes: '',
    });

    // Generate next 14 days for the simple picker
    const today = startOfToday();
    const availableDates = Array.from({ length: 14 }).map((_, i) => addDays(today, i));

    // Generate some available times (e.g., 9 AM to 5 PM)
    const availableTimes = [
        '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
        '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00'
    ];

    const handleTimeSelect = (time: string) => {
        if (!selectedDate) return;
        
        setSelectedTime(time);
        
        // Parse time and combine with selected date
        const [hours, minutes] = time.split(':').map(Number);
        const datetime = setMinutes(setHours(selectedDate, hours), minutes);
        
        setData('starts_at', datetime.toISOString());
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
            <h3 className="text-lg font-medium text-slate-900">Select a Date & Time</h3>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                {/* Date Picker (Custom minimal grid) */}
                <div>
                    <div className="flex items-center justify-between mb-4">
                        <span className="font-medium text-slate-800">{format(today, 'MMMM yyyy')}</span>
                        <div className="flex gap-2">
                            <Button variant="outline" size="icon" className="h-8 w-8"><ChevronLeft className="h-4 w-4" /></Button>
                            <Button variant="outline" size="icon" className="h-8 w-8"><ChevronRight className="h-4 w-4" /></Button>
                        </div>
                    </div>
                    
                    <div className="grid grid-cols-7 gap-1 text-center text-sm mb-2">
                        {['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'].map(day => (
                            <div key={day} className="text-slate-500 font-medium py-1">{day}</div>
                        ))}
                    </div>
                    
                    <div className="grid grid-cols-7 gap-1">
                        {/* Placeholder for offset days if month doesn't start on Sunday - simplified for mockup */}
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
                                        p-2 w-full text-center rounded-full text-sm font-medium transition-all
                                        ${isSelected 
                                            ? 'bg-slate-900 text-white shadow-md scale-105' 
                                            : 'text-slate-700 hover:bg-slate-100'
                                        }
                                    `}
                                >
                                    {format(date, 'd')}
                                </button>
                            );
                        })}
                    </div>
                    
                    <div className="mt-6 flex items-center text-sm text-slate-500">
                        <Globe className="h-4 w-4 mr-2" />
                        {data.timezone}
                    </div>
                </div>

                {/* Time Selection */}
                <div>
                    {selectedDate ? (
                        <div className="space-y-2 animate-in fade-in duration-300">
                            <span className="text-sm font-medium text-slate-700 block mb-3">
                                {format(selectedDate, 'EEEE, MMMM d')}
                            </span>
                            <div className="grid grid-cols-2 gap-2 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                                {availableTimes.map(time => (
                                    <button
                                        key={time}
                                        onClick={() => handleTimeSelect(time)}
                                        className={`
                                            py-3 px-4 rounded-xl border text-sm font-medium transition-all
                                            ${selectedTime === time 
                                                ? 'border-slate-900 bg-slate-900 text-white shadow-md' 
                                                : 'border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50'
                                            }
                                        `}
                                    >
                                        {time}
                                    </button>
                                ))}
                            </div>
                            
                            {selectedTime && (
                                <div className="pt-6 animate-in slide-in-from-bottom-2 fade-in">
                                    <Button onClick={handleContinue} className="w-full rounded-xl" size="lg">
                                        Next
                                    </Button>
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="h-full flex items-center justify-center border-2 border-dashed border-slate-100 rounded-xl p-6">
                            <span className="text-slate-400 text-sm">Select a date to see available times</span>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );

    const renderDetailsForm = () => (
        <div className="space-y-6 animate-in fade-in slide-in-from-right-8 duration-500">
            <div className="flex items-center text-slate-600 mb-6">
                <Button variant="ghost" size="sm" onClick={() => setStep(1)} className="mr-2 -ml-3">
                    <ChevronLeft className="h-4 w-4 mr-1" /> Back
                </Button>
                <span className="text-sm">
                    {selectedDate && selectedTime ? `${format(selectedDate, 'MMM d, yyyy')} at ${selectedTime}` : ''}
                </span>
            </div>

            <h3 className="text-lg font-medium text-slate-900">Enter Details</h3>
            
            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-2">
                    <Label htmlFor="guest_name">Name *</Label>
                    <Input 
                        id="guest_name" 
                        value={data.guest_name}
                        onChange={e => setData('guest_name', e.target.value)}
                        placeholder="John Doe"
                        required
                        className="rounded-xl"
                    />
                    {errors.guest_name && <p className="text-sm text-red-500">{errors.guest_name}</p>}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="guest_email">Email *</Label>
                    <Input 
                        id="guest_email" 
                        type="email"
                        value={data.guest_email}
                        onChange={e => setData('guest_email', e.target.value)}
                        placeholder="john@example.com"
                        required
                        className="rounded-xl"
                    />
                    {errors.guest_email && <p className="text-sm text-red-500">{errors.guest_email}</p>}
                </div>
                
                <div className="space-y-2">
                    <Label htmlFor="guest_phone">Phone (Optional)</Label>
                    <Input 
                        id="guest_phone" 
                        value={data.guest_phone || ''}
                        onChange={e => setData('guest_phone', e.target.value)}
                        placeholder="+1 234 567 890"
                        className="rounded-xl"
                    />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="notes">Please share anything that will help prepare for our meeting</Label>
                    <Textarea 
                        id="notes" 
                        value={data.notes}
                        onChange={e => setData('notes', e.target.value)}
                        rows={3}
                        className="rounded-xl resize-none"
                    />
                </div>

                <div className="pt-4">
                    <Button 
                        type="submit" 
                        disabled={processing} 
                        className="w-full rounded-xl h-12 text-base font-medium"
                    >
                        {processing ? 'Processing...' : eventType.requires_payment ? `Continue to Payment (${eventType.price} ${eventType.currency})` : 'Confirm Booking'}
                    </Button>
                </div>
                
                <p className="text-center text-xs text-slate-500 mt-4">
                    By confirming, you agree to our Terms of Service and Privacy Policy.
                </p>
            </form>
        </div>
    );

    return (
        <div className="min-h-screen bg-slate-50 flex items-center justify-center p-4 py-12 selection:bg-slate-200">
            <Head title={`Book ${eventType.title} with ${host.name}`} />
            
            <div className="max-w-4xl w-full bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden border border-slate-100 flex flex-col md:flex-row">
                
                {/* Left Column - Event Details */}
                <div className="w-full md:w-1/3 bg-slate-50/50 p-8 border-r border-slate-100 relative overflow-hidden">
                    {/* Subtle decorative background element */}
                    <div className="absolute top-0 left-0 w-full h-32 bg-gradient-to-b from-slate-100/80 to-transparent -z-10" />
                    
                    <div className="mb-8">
                        <div className="h-16 w-16 bg-white border border-slate-200 rounded-full flex items-center justify-center shadow-sm mb-4 overflow-hidden">
                            {host.avatar ? (
                                <img src={host.avatar} alt={host.name} className="h-full w-full object-cover" />
                            ) : (
                                <span className="text-xl font-medium text-slate-700">{host.name.charAt(0)}</span>
                            )}
                        </div>
                        <p className="text-slate-500 font-medium text-sm mb-1">{host.name}</p>
                        <h1 className="text-2xl font-semibold text-slate-900 tracking-tight leading-tight">{eventType.title}</h1>
                    </div>
                    
                    <div className="space-y-4 text-slate-600">
                        <div className="flex items-center text-sm font-medium text-slate-700 bg-white p-3 rounded-xl shadow-sm border border-slate-100">
                            <Clock className="w-4 h-4 mr-3 text-slate-400" />
                            {eventType.duration_minutes} min
                        </div>
                        
                        {eventType.requires_payment && eventType.price > 0 && (
                            <div className="flex items-center text-sm font-medium text-slate-700 bg-white p-3 rounded-xl shadow-sm border border-slate-100">
                                <CreditCard className="w-4 h-4 mr-3 text-slate-400" />
                                {eventType.price} {eventType.currency}
                            </div>
                        )}
                    </div>
                    
                    {eventType.description && (
                        <div className="mt-8 pt-8 border-t border-slate-200/60">
                            <p className="text-slate-600 text-sm leading-relaxed">
                                {eventType.description}
                            </p>
                        </div>
                    )}
                </div>
                
                {/* Right Column - Booking Flow */}
                <div className="w-full md:w-2/3 p-8">
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
                    background-color: #e2e8f0;
                    border-radius: 20px;
                }
            `}} />
        </div>
    );
}
