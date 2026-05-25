import React, { useState } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Plus, Settings2, Play, GitMerge } from 'lucide-react';

export default function RuleBuilder({ rule }) {
    const [conditions, setConditions] = useState(rule?.conditions || []);
    const [actions, setActions] = useState(rule?.actions || []);

    return (
        <div className="p-6 max-w-5xl mx-auto space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Rule Builder</h1>
                    <p className="text-muted-foreground mt-2">Visually design conditions and actions.</p>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline"><Play className="mr-2 h-4 w-4" /> Simulate Rule</Button>
                    <Button>Save Rule</Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Rule Metadata</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Rule Name</label>
                            <Input placeholder="e.g. Block VIP Double Bookings" defaultValue={rule?.name} />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Trigger Event</label>
                            <Input placeholder="e.g. booking.created" defaultValue={rule?.event_trigger} />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Conditions Builder */}
                <Card className="border-blue-100">
                    <CardHeader className="bg-blue-50/50">
                        <div className="flex items-center justify-between">
                            <CardTitle className="flex items-center text-blue-700">
                                <Settings2 className="mr-2 h-5 w-5" /> IF (Conditions)
                            </CardTitle>
                            <Button size="sm" variant="outline" className="text-blue-600">
                                <Plus className="h-4 w-4 mr-1" /> Add Group
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6 space-y-4">
                        {conditions.length === 0 ? (
                            <div className="text-center p-6 text-muted-foreground border-2 border-dashed rounded-lg">
                                No conditions defined. Rule will always execute.
                            </div>
                        ) : (
                            // Render draggable condition groups here
                            <div>[Visual Condition Nodes]</div>
                        )}
                    </CardContent>
                </Card>

                {/* Actions Builder */}
                <Card className="border-emerald-100">
                    <CardHeader className="bg-emerald-50/50">
                        <div className="flex items-center justify-between">
                            <CardTitle className="flex items-center text-emerald-700">
                                <GitMerge className="mr-2 h-5 w-5" /> THEN (Actions)
                            </CardTitle>
                            <Button size="sm" variant="outline" className="text-emerald-600">
                                <Plus className="h-4 w-4 mr-1" /> Add Action
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6 space-y-4">
                        {actions.length === 0 ? (
                            <div className="text-center p-6 text-muted-foreground border-2 border-dashed rounded-lg">
                                Add an action to execute when conditions are met.
                            </div>
                        ) : (
                            // Render action stack here
                            <div>[Visual Action Nodes]</div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
