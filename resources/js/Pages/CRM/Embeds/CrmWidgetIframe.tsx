import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { CheckCircle2, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';

export default function CrmWidgetIframe({ widget, token, flash }: { widget: any, token: string, flash: any }) {
    const config = widget.form_config || {};
    const fields = config.fields || {};
    const primaryColor = config.primary_color || '#4f46e5';

    const [submitted, setSubmitted] = useState(!!flash?.success);

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        phone: '',
        company: '',
        message: '',
        custom_fields: {},
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/crm/w/${token}`, {
            preserveScroll: true,
            onSuccess: () => setSubmitted(true),
        });
    };

    if (submitted) {
        return (
            <div className="min-h-screen bg-transparent flex flex-col items-center justify-center p-6 text-center">
                <div className="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-4">
                    <CheckCircle2 className="w-8 h-8" />
                </div>
                <h3 className="text-xl font-bold text-slate-900 mb-2">{flash?.success || 'Thank you!'}</h3>
                <p className="text-slate-500">{__('general.your_information_has_been_received_successfully')}</p>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-transparent p-4 sm:p-6 flex flex-col font-sans">
            <Head title={config.title || 'Contact Form'} />
            
            <div className="w-full max-w-md mx-auto bg-white rounded-xl shadow-lg border border-slate-100 overflow-hidden">
                <div 
                    className="h-2 w-full" 
                    style={{ backgroundColor: primaryColor }}
                />
                
                <div className="p-6 sm:p-8">
                    <div className="mb-8">
                        <h2 className="text-2xl font-bold text-slate-900 tracking-tight">
                            {config.title || 'Get in Touch'}
                        </h2>
                        {config.description && (
                            <p className="mt-2 text-sm text-slate-500 leading-relaxed">
                                {config.description}
                            </p>
                        )}
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-5">
                        {fields.name?.enabled && (
                            <div className="space-y-2">
                                <Label htmlFor="name" className="text-sm font-medium text-slate-700">
                                    {fields.name.label || 'Full Name'} {fields.name.required && <span className="text-red-500">*</span>}
                                </Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required={fields.name.required}
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className={cn("bg-slate-50 border-slate-200 focus:bg-white transition-colors", errors.name && "border-red-500")}
                                />
                                {errors.name && <p className="text-xs text-red-500 mt-1">{errors.name}</p>}
                            </div>
                        )}

                        {fields.email?.enabled && (
                            <div className="space-y-2">
                                <Label htmlFor="email" className="text-sm font-medium text-slate-700">
                                    {fields.email.label || 'Email Address'} {fields.email.required && <span className="text-red-500">*</span>}
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required={fields.email.required}
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className={cn("bg-slate-50 border-slate-200 focus:bg-white transition-colors", errors.email && "border-red-500")}
                                />
                                {errors.email && <p className="text-xs text-red-500 mt-1">{errors.email}</p>}
                            </div>
                        )}

                        {fields.phone?.enabled && (
                            <div className="space-y-2">
                                <Label htmlFor="phone" className="text-sm font-medium text-slate-700">
                                    {fields.phone.label || 'Phone Number'} {fields.phone.required && <span className="text-red-500">*</span>}
                                </Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    required={fields.phone.required}
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    className={cn("bg-slate-50 border-slate-200 focus:bg-white transition-colors", errors.phone && "border-red-500")}
                                />
                                {errors.phone && <p className="text-xs text-red-500 mt-1">{errors.phone}</p>}
                            </div>
                        )}

                        {fields.company?.enabled && (
                            <div className="space-y-2">
                                <Label htmlFor="company" className="text-sm font-medium text-slate-700">
                                    {fields.company.label || 'Company Name'} {fields.company.required && <span className="text-red-500">*</span>}
                                </Label>
                                <Input
                                    id="company"
                                    type="text"
                                    required={fields.company.required}
                                    value={data.company}
                                    onChange={(e) => setData('company', e.target.value)}
                                    className={cn("bg-slate-50 border-slate-200 focus:bg-white transition-colors", errors.company && "border-red-500")}
                                />
                                {errors.company && <p className="text-xs text-red-500 mt-1">{errors.company}</p>}
                            </div>
                        )}

                        {fields.message?.enabled && (
                            <div className="space-y-2">
                                <Label htmlFor="message" className="text-sm font-medium text-slate-700">
                                    {fields.message.label || 'Message'} {fields.message.required && <span className="text-red-500">*</span>}
                                </Label>
                                <Textarea
                                    id="message"
                                    required={fields.message.required}
                                    value={data.message}
                                    onChange={(e) => setData('message', e.target.value)}
                                    className={cn("bg-slate-50 border-slate-200 focus:bg-white transition-colors min-h-[100px] resize-none", errors.message && "border-red-500")}
                                />
                                {errors.message && <p className="text-xs text-red-500 mt-1">{errors.message}</p>}
                            </div>
                        )}

                        {flash?.error && (
                            <div className="p-3 bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg">
                                {flash.error}
                            </div>
                        )}

                        <Button 
                            type="submit" 
                            disabled={processing} 
                            className="w-full h-12 text-base font-semibold shadow-sm transition-all hover:shadow-md mt-6"
                            style={{ 
                                backgroundColor: primaryColor, 
                                color: '#ffffff'
                            }}
                        >
                            {processing ? (
                                <Loader2 className="w-5 h-5 animate-spin mx-auto" />
                            ) : (
                                config.button_text || 'Submit'
                            )}
                        </Button>
                    </form>
                </div>
            </div>
        </div>
    );
}
