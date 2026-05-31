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
            title: __('Contact Us'),
            description: __('Please fill out the form below and our team will get in touch.'),
            button_text: __('Submit'),
            primary_color: '#4f46e5', // indigo-600
            fields: {
                name: { enabled: true, required: true, label: __('Full Name') },
                email: { enabled: true, required: true, label: __('Email Address') },
                phone: { enabled: true, required: false, label: __('Phone Number') },
                company: { enabled: false, required: false, label: __('Company Name') },
                message: { enabled: true, required: false, label: __('Your Message') },
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
        <CrmLayout title={isEdit ? __('Edit Form') : __('Create Form')} activeMenu="widgets">
            <div className="max-w-4xl mx-auto space-y-6 pb-12">
                <div className="flex items-center gap-4">
                    <Link href={route('crm.widgets.index')} className={buttonVariants({ variant: 'ghost', size: 'icon', className: 'rounded-full' })}>
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900">
                            {isEdit ? __('Edit Form Settings') : __('Create New Web Form')}
                        </h1>
                        <p className="text-sm text-slate-500">
                            {__('Configure your embeddable lead capture widget.')}
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
                                        {__('General Information')}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">{__('Internal Name')} <span className="text-red-500">*</span></Label>
                                        <Input
                                            id="name"
                                            value={data.name}
                                            onChange={e => setData('name', e.target.value)}
                                            placeholder={__('e.g. Website Footer Form')}
                                        />
                                        {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="allowed_domains">{__('Allowed Domains')}</Label>
                                        <Input
                                            id="allowed_domains"
                                            value={data.allowed_domains}
                                            onChange={e => setData('allowed_domains', e.target.value)}
                                            placeholder="example.com, myblog.net (leave empty to allow all)"
                                        />
                                        <p className="text-xs text-slate-500">
                                            {__('Comma-separated list of domains where this widget is allowed to load. Leave empty to allow any website.')}
                                        </p>
                                    </div>

                                    <div className="flex items-center justify-between p-4 rounded-lg bg-slate-50 border border-slate-100">
                                        <div className="space-y-0.5">
                                            <Label className="text-base">{__('Active Status')}</Label>
                                            <p className="text-sm text-slate-500">{__('If disabled, the form will not accept submissions.')}</p>
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
                                        {__('Appearance & Copy')}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-2">
                                        <Label>{__('Form Title')}</Label>
                                        <Input
                                            value={data.form_config.title}
                                            onChange={e => setData('form_config', { ...data.form_config, title: e.target.value })}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>{__('Description Text')}</Label>
                                        <Input
                                            value={data.form_config.description}
                                            onChange={e => setData('form_config', { ...data.form_config, description: e.target.value })}
                                        />
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label>{__('Button Text')}</Label>
                                            <Input
                                                value={data.form_config.button_text}
                                                onChange={e => setData('form_config', { ...data.form_config, button_text: e.target.value })}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>{__('Primary Color')}</Label>
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
                                        {__('Form Fields')}
                                    </CardTitle>
                                    <CardDescription>
                                        {__('Toggle fields and mark them as required.')}
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
                                                        <Label className="text-xs text-slate-500">{__('Label')}</Label>
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
                                                            {__('Required Field')}
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
                            <Save className="w-4 h-4 mr-2" />
                            {isEdit ? __('Save Changes') : __('Create Form')}
                        </Button>
                    </div>
                </form>
            </div>
        </CrmLayout>
    );
}
