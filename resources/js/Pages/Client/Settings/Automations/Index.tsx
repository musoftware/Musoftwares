import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Plus, Settings2, Trash } from 'lucide-react';
import { router } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

export default function Index({ auth, rules }) {
    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this automation rule?')) {
            router.delete(route('settings.automations.destroy', id));
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-slate-800 leading-tight">{__('general.eventdriven_automation')}</h2>}>
            <Head title={__('general.automations')} />

            <div className="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div className="flex justify-between items-center">
                    <div>
                        <h3 className="text-lg font-medium text-slate-900">{__('general.automation_rules')}</h3>
                        <p className="text-sm text-slate-500">{__('general.configure_rules_to_trigger_actions_autom')}</p>
                    </div>
                    <Link href={route('settings.automations.create')}>
                        <Button className="bg-indigo-600 hover:bg-indigo-700">
                            <Plus className="w-4 h-4 me-2" />
                            {__('general.create_rule')}</Button>
                    </Link>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {rules.length === 0 && (
                        <div className="col-span-full py-12 text-center text-slate-500 border-2 border-dashed border-slate-200 rounded-lg bg-slate-50">
                            {__('general.no_automation_rules_found_create_your_fi')}</div>
                    )}
                    {rules.map((rule) => (
                        <Card key={rule.id} className="border-slate-200">
                            <CardHeader className="bg-slate-50 border-b border-slate-100 flex flex-row items-center justify-between py-3">
                                <CardTitle className="text-md font-medium flex items-center gap-2">
                                    <Settings2 className="w-4 h-4 text-slate-500" />
                                    {rule.name}
                                </CardTitle>
                                <div className="flex items-center gap-2">
                                    <span className={`px-2 py-1 text-xs font-semibold rounded-full ${rule.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>
                                        {rule.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                            </CardHeader>
                            <CardContent className="pt-4 space-y-4">
                                <div>
                                    <span className="text-xs text-slate-500 uppercase tracking-wider font-semibold">{__('general.trigger_event')}</span>
                                    <p className="text-sm font-medium text-slate-800 truncate">{rule.event_trigger}</p>
                                </div>
                                <div>
                                    <span className="text-xs text-slate-500 uppercase tracking-wider font-semibold">{__('general.actions')}</span>
                                    <p className="text-sm font-medium text-slate-800">{rule.actions?.length || 0} configured</p>
                                </div>
                                <div className="pt-2 flex justify-end gap-2 border-t border-slate-100">
                                    <Link href={route('settings.automations.edit', rule.id)}>
                                        <Button variant="outline" size="sm">{__('general.edit')}</Button>
                                    </Link>
                                    <Button variant="ghost" size="sm" className="text-red-600 hover:text-red-700 hover:bg-red-50" onClick={() => handleDelete(rule.id)}>
                                        <Trash className="w-4 h-4" />
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
