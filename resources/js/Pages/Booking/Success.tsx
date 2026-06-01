import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { CheckCircle2, Calendar, Clock, ArrowRight } from 'lucide-react';
import { format } from 'date-fns';

export default function Success({ booking }: any) {
    const { eventType } = booking;

    return (
        <div className="min-h-screen bg-slate-50 flex items-center justify-center p-4 py-12 selection:bg-slate-200">
            <Head title={__('general.booking_confirmed')} />
            
            <div className="max-w-md w-full">
                <Card className="border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] rounded-3xl overflow-hidden text-center relative z-10">
                    <div className="absolute top-0 left-0 w-full h-32 bg-slate-900 -z-10" />
                    
                    <CardContent className="pt-12 p-8">
                        <div className="mx-auto w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-sm mb-6 border-4 border-slate-50">
                            <CheckCircle2 className="h-10 w-10 text-slate-900" />
                        </div>
                        
                        <h1 className="text-2xl font-semibold text-slate-900 tracking-tight mb-2">{__('general.booking_confirmed')}</h1>
                        <p className="text-slate-500 mb-8">{__('general.a_calendar_invitation_has_been_sent_to_your_email_address')}</p>
                        
                        <div className="bg-slate-50 rounded-2xl p-6 mb-8 space-y-4 text-left">
                            <div>
                                <h3 className="font-medium text-slate-900 mb-1">{eventType.title}</h3>
                                <p className="text-sm text-slate-500">with {eventType.user?.name || 'the host'}</p>
                            </div>
                            
                            <div className="pt-4 border-t border-slate-200 space-y-3">
                                <div className="flex items-center text-sm text-slate-600">
                                    <Calendar className="h-4 w-4 mr-3 text-slate-400" />
                                    <span className="font-medium text-slate-900">
                                        {format(new Date(booking.starts_at), 'EEEE, MMMM d, yyyy')}
                                    </span>
                                </div>
                                <div className="flex items-center text-sm text-slate-600">
                                    <Clock className="h-4 w-4 mr-3 text-slate-400" />
                                    <span className="font-medium text-slate-900">
                                        {format(new Date(booking.starts_at), 'h:mm a')}
                                    </span>
                                    <span className="ml-1 text-slate-500">
                                        ({eventType.duration_minutes} min)
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <a 
                            href="/"
                            className="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-base font-medium text-white hover:bg-slate-800 transition-colors h-12 w-full shadow-sm"
                        >{__('general.return_home')}</a>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
