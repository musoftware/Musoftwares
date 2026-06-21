import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import CrmLayout from '@/Layouts/CrmLayout';
import { __ } from '@/lib/i18n';
import { ArrowLeft, Save, Globe, Settings, Type, Palette } from 'lucide-react';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';

export default function Form({ widget }: { widget?: any }) {
    const isEdit = !!widget;

    const { data, setData, post, put, processing, errors, transform } = useForm({
        name: widget?.name || '',
        is_active: widget?.is_active ?? true,
        allowed_domains: widget?.allowed_domains?.join(', ') || '',
        form_config: widget?.form_config || {
            title: __('general.contact_us'),
            description: __('general.please_fill_out_the_form_below_and_our_team_will_get_in_touch'),
            button_text: __('general.submit'),
            primary_color: '#4f46e5', // indigo-600
            fields: {
                name: { enabled: true, required: true, label: __('general.full_name') },
                email: { enabled: true, required: true, label: __('general.email_address') },
                phone: { enabled: true, required: false, label: __('general.phone_number') },
                company: { enabled: false, required: false, label: __('general.company_name') },
                message: { enabled: true, required: false, label: __('general.your_message') },
            }
        }
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        transform((formData) => ({
            ...formData,
            allowed_domains: formData.allowed_domains ? formData.allowed_domains.split(',').map((d: string) => d.trim()).filter((d: string) => d) : []
        }));

        if (isEdit) {
            put(route('crm.widgets.update', widget.id));
        } else {
            post(route('crm.widgets.store'));
        }
    };

    const updateField = (fieldKey: string, prop: string, value: any) => {
        setData('form_config', {
            ...data.form_config,
            fields: {
                ...data.form_config.fields,
                [fieldKey]: {
                    ...data.form_config.fields[fieldKey],
                    [prop]: value
                }
            }
        });
    };

    return (
        <CrmLayout title={isEdit ? __('general.edit_form') : __('general.create_form')} activeMenu="widgets">
            <div className="max-w-4xl mx-auto space-y-6 pb-12">
                <div className="flex items-center gap-4">
                    <Link href={route('crm.widgets.index')} className={buttonVariants({ variant: 'ghost', size: 'icon', className: 'rounded-full' })}>
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900">
                            {isEdit ? __('general.edit_form_settings') : __('general.create_new_web_form')}
                        </h1>
                        <p className="text-sm text-slate-500">
                            {__('general.configure_your_embeddable_lead_capture_widget')}
                        </p>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        <div className="md:col-span-2 space-y-6">
                            {/* General Settings */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg flex items-center gap-2">
                                        <Settings className="w-5 h-5 text-indigo-500" />
                                        {__('general.general_information')}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">{__('general.internal_name')} <span className="text-red-500">*</span></Label>
                                        <Input
                                            id="name"
                                            value={data.name}
                                            onChange={e => setData('name', e.target.value)}
                                            placeholder={__('general.e_g_website_footer_form')}
                                        />
                                        {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="allowed_domains">{__('general.allowed_domains')}</Label>
                                        <Input
                                            id="allowed_domains"
                                            value={data.allowed_domains}
                                            onChange={e => setData('allowed_domains', e.target.value)}
                                            placeholder={__('general.example_com_myblog_net_leave_empty_to_allow_all')}
                                        />
                                        <p className="text-xs text-slate-500">
                                            {__('general.comma_separated_list_of_domains_where_this_widget_is_allowed_to_load_leave_empty_to_allow_any_website')}
                                        </p>
                                    </div>

                                    <div className="flex items-center justify-between p-4 rounded-lg bg-slate-50 border border-slate-100">
                                        <div className="space-y-0.5">
                                            <Label className="text-base">{__('general.active_status')}</Label>
                                            <p className="text-sm text-slate-500">{__('general.if_disabled_the_form_will_not_accept_submissions')}</p>
                                        </div>
                                        <Switch
                                            checked={data.is_active}
                                            onCheckedChange={(checked) => setData('is_active', checked)}
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Appearance */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg flex items-center gap-2">
                                        <Palette className="w-5 h-5 text-indigo-500" />
                                        {__('general.appearance_copy')}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-2">
                                        <Label>{__('general.form_title')}</Label>
                                        <Input
                                            value={data.form_config.title}
                                            onChange={e => setData('form_config', { ...data.form_config, title: e.target.value })}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>{__('general.description_text')}</Label>
                                        <Input
                                            value={data.form_config.description}
                                            onChange={e => setData('form_config', { ...data.form_config, description: e.target.value })}
                                        />
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label>{__('general.button_text')}</Label>
                                            <Input
                                                value={data.form_config.button_text}
                                                onChange={e => setData('form_config', { ...data.form_config, button_text: e.target.value })}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>{__('general.primary_color')}</Label>
                                            <div className="flex gap-2">
                                                <Input
                                                    type="color"
                                                    value={data.form_config.primary_color}
                                                    onChange={e => setData('form_config', { ...data.form_config, primary_color: e.target.value })}
                                                    className="w-12 p-1 h-10"
                                                />
                                                <Input
                                                    value={data.form_config.primary_color}
                                                    onChange={e => setData('form_config', { ...data.form_config, primary_color: e.target.value })}
                                                    className="flex-1"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Fields Configuration */}
                        <div className="md:col-span-1 space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg flex items-center gap-2">
                                        <Type className="w-5 h-5 text-indigo-500" />
                                        {__('general.form_fields')}
                                    </CardTitle>
                                    <CardDescription>
                                        {__('general.toggle_fields_and_mark_them_as_required')}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    {Object.entries(data.form_config.fields).map(([key, field]: [string, any]) => (
                                        <div key={key} className="space-y-3 p-3 bg-slate-50 border border-slate-100 rounded-lg">
                                            <div className="flex items-center justify-between">
                                                <Label className="capitalize font-semibold">{key}</Label>
                                                <Switch
                                                    checked={field.enabled}
                                                    onCheckedChange={checked => updateField(key, 'enabled', checked)}
                                                />
                                            </div>
                                            {field.enabled && (
                                                <div className="pt-2 space-y-3 border-t border-slate-200">
                                                    <div className="space-y-1.5">
                                                        <Label className="text-xs text-slate-500">{__('general.label')}</Label>
                                                        <Input 
                                                            className="h-8 text-sm"
                                                            value={field.label}
                                                            onChange={e => updateField(key, 'label', e.target.value)}
                                                        />
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <input 
                                                            type="checkbox" 
                                                            id={`req-${key}`}
                                                            className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                            checked={field.required}
                                                            onChange={e => updateField(key, 'required', e.target.checked)}
                                                            disabled={key === 'name'} // Name usually always required if enabled
                                                        />
                                                        <Label htmlFor={`req-${key}`} className="text-xs text-slate-600 font-normal">
                                                            {__('general.required_field')}
                                                        </Label>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    <div className="flex justify-end pt-6 border-t border-slate-200">
                        <Button type="submit" disabled={processing} className="min-w-[150px]">
                            <Save className="w-4 h-4 me-2" />
                            {isEdit ? __('general.save_changes') : __('general.create_form')}
                        </Button>
                    </div>
                </form>
            </div>
        </CrmLayout>
    );
}
