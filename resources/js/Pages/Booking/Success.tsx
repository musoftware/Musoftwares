import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { CheckCircle2, Calendar, Clock, ArrowRight } from 'lucide-react';
import { format } from 'date-fns';
import { __ } from '@/lib/i18n';
import ThemeToggle from '@/Components/ThemeToggle';

export default function Success({ booking }: any) {
    const { eventType } = booking;

    return (
        <div className="min-h-screen bg-background text-foreground flex flex-col items-center justify-center p-4 py-12 selection:bg-primary selection:text-white transition-colors duration-200">
            <Head title={__('general.booking_confirmed')} />
            
            <div className="max-w-md w-full">
                <div className="flex justify-end mb-4">
                    <ThemeToggle className="h-9 w-9" />
                </div>
                <Card className="border-border shadow-[0_8px_30px_rgb(0,0,0,0.04)] rounded-3xl overflow-hidden text-center relative z-10 bg-card text-card-foreground">
                    <div className="absolute top-0 start-0 w-full h-32 bg-primary/20 -z-10" />
                    
                    <CardContent className="pt-12 p-8">
                        <div className="mx-auto w-20 h-20 bg-background rounded-full flex items-center justify-center shadow-sm mb-6 border-4 border-card">
                            <CheckCircle2 className="h-10 w-10 text-emerald-500" />
                        </div>
                        
                        <h1 className="text-2xl font-semibold text-foreground tracking-tight mb-2">{__('general.booking_confirmed')}</h1>
                        <p className="text-muted-foreground mb-8 text-sm">{__('general.a_calendar_invitation_has_been_sent_to_your_email_address')}</p>
                        
                        <div className="bg-muted/30 dark:bg-slate-800/40 rounded-2xl p-6 mb-8 space-y-4 text-start border border-border">
                            <div>
                                <h3 className="font-medium text-foreground mb-1">{eventType.title}</h3>
                                <p className="text-sm text-muted-foreground">with {eventType.user?.name || 'the host'}</p>
                            </div>
                            
                            <div className="pt-4 border-t border-border space-y-3">
                                <div className="flex items-center text-sm text-muted-foreground">
                                    <Calendar className="h-4 w-4 me-3 text-muted-foreground" />
                                    <span className="font-medium text-foreground">
                                        {format(new Date(booking.starts_at), 'EEEE, MMMM d, yyyy')}
                                    </span>
                                </div>
                                <div className="flex items-center text-sm text-muted-foreground">
                                    <Clock className="h-4 w-4 me-3 text-muted-foreground" />
                                    <span className="font-medium text-foreground">
                                        {format(new Date(booking.starts_at), 'h:mm a')}
                                    </span>
                                    <span className="ms-1 text-muted-foreground">
                                        ({eventType.duration_minutes} min)
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <a 
                            href="/"
                            className="inline-flex items-center justify-center rounded-xl bg-primary hover:bg-primary/90 px-4 py-2 text-base font-medium text-primary-foreground transition-colors h-12 w-full shadow-sm"
                        >{__('general.return_home')}</a>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
