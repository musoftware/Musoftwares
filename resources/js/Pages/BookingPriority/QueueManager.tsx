import React from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { AlertCircle, Clock } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function QueueManager({ queueItems }) {
    return (
        <div className="p-6 max-w-7xl mx-auto space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">{__('general.live_queue_manager')}</h1>
                    <p className="text-muted-foreground mt-2">{__('general.realtime_sorting_based_on_vip_weights_and_emergency_flags')}</p>
                </div>
                <Button variant="destructive">
                    <AlertCircle className="me-2 h-4 w-4" />{__('general.trigger_emergency')}</Button>
            </div>

            <div className="space-y-4">
                {queueItems.map((item, index) => (
                    <Card key={item.id} className={`transition-all ${item.priority_weight > 100 ? 'border-red-200 bg-red-50/20' : ''}`}>
                        <CardContent className="p-4 flex items-center justify-between">
                            <div className="flex items-center gap-4">
                                <div className="flex flex-col items-center justify-center h-10 w-10 bg-slate-100 rounded-lg font-bold text-slate-500">
                                    {index + 1}
                                </div>
                                <div>
                                    <div className="font-medium text-lg flex items-center gap-2">
                                        {item.customer_name} 
                                        {item.priority_weight > 100 && <Badge variant="destructive">{__('general.emergency')}</Badge>}
                                        {item.priority_weight > 0 && item.priority_weight <= 100 && <Badge variant="default" className="bg-amber-500 hover:bg-amber-600">VIP</Badge>}
                                    </div>
                                    <div className="text-sm text-muted-foreground flex items-center gap-1 mt-1">
                                        <Clock className="h-3 w-3" /> Scheduled: {item.time}
                                    </div>
                                </div>
                            </div>
                            <div>
                                <Button variant="outline" size="sm">{__('general.manage')}</Button>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
}
