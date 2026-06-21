import React from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Calendar as CalendarIcon, Zap } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Slot {
    time: string;
    is_smart_optimized: boolean;
}

export default function SmartCalendar({ slots, date }: { slots: Slot[], date: string }) {
    return (
        <div className="p-6 max-w-5xl mx-auto space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">{__('general.dynamic_availability')}</h1>
                    <p className="text-muted-foreground mt-2">Visualizing gaps and smart recommendations for {date}.</p>
                </div>
                <Button variant="outline">
                    <CalendarIcon className="me-2 h-4 w-4" />{__('general.change_date')}</Button>
            </div>

            <Card>
                <CardHeader className="bg-slate-50 border-b">
                    <CardTitle className="text-lg">{__('general.generated_smart_slots')}</CardTitle>
                </CardHeader>
                <CardContent className="p-6">
                    <div className="grid grid-cols-4 gap-4">
                        {slots.map((slot, index) => (
                            <div 
                                key={index} 
                                className={`p-4 border rounded-lg flex flex-col items-center justify-center space-y-2 
                                    ${slot.is_smart_optimized ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'bg-white'}`}
                            >
                                <span className="text-lg font-bold">{slot.time}</span>
                                {slot.is_smart_optimized && (
                                    <Badge className="bg-emerald-500 hover:bg-emerald-600">
                                        <Zap className="w-3 h-3 me-1" />{__('general.smart_slot')}</Badge>
                                )}
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
