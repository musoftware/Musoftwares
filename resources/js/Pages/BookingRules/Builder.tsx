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
                    <h1 className="text-3xl font-bold tracking-tight">{__('general.rule_builder')}</h1>
                    <p className="text-muted-foreground mt-2">{__('general.visually_design_conditions_and_actions')}</p>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline"><Play className="mr-2 h-4 w-4" />{__('general.simulate_rule')}</Button>
                    <Button>{__('general.save_rule')}</Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{__('general.rule_metadata')}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">{__('general.rule_name')}</label>
                            <Input placeholder={__('general.e_g_block_vip_double_bookings')} defaultValue={rule?.name} />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">{__('general.trigger_event')}</label>
                            <Input placeholder={__('general.e_g_booking_created')} defaultValue={rule?.event_trigger} />
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
                                <Plus className="h-4 w-4 mr-1" />{__('general.add_group')}</Button>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6 space-y-4">
                        {conditions.length === 0 ? (
                            <div className="text-center p-6 text-muted-foreground border-2 border-dashed rounded-lg">{__('general.no_conditions_defined_rule_will_always_execute')}</div>
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
                                <Plus className="h-4 w-4 mr-1" />{__('general.add_action')}</Button>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6 space-y-4">
                        {actions.length === 0 ? (
                            <div className="text-center p-6 text-muted-foreground border-2 border-dashed rounded-lg">{__('general.add_an_action_to_execute_when_conditions_are_met')}</div>
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
