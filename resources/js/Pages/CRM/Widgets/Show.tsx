import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import CrmLayout from '@/Layouts/CrmLayout';
import { __ } from '@/lib/i18n';
import { ArrowLeft, Copy, Check, ExternalLink, Code } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { toast } from 'sonner';

export default function Show({ widget }: { widget: any }) {
    const [copied, setCopied] = useState(false);
    
    // Build the iframe URL based on current app URL
    const appUrl = window.location.origin;
    const iframeUrl = `${appUrl}/crm/w/${widget.embed_token}`;
    
    const embedCode = `<iframe 
    src="${iframeUrl}" 
    width="100%" 
    height="600" 
    frameborder="0" 
    style="border: none; background: transparent;"
></iframe>`;

    const handleCopy = () => {
        navigator.clipboard.writeText(embedCode);
        setCopied(true);
        toast.success(__('Embed code copied to clipboard!'));
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <CrmLayout title={__('Form Embed Code')} activeMenu="widgets">
            <div className="max-w-4xl mx-auto space-y-6 pb-12">
                <div className="flex items-center justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild className="rounded-full">
                            <Link href={route('crm.widgets.index')}>
                                <ArrowLeft className="w-5 h-5" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-slate-900">
                                {widget.name}
                            </h1>
                            <p className="text-sm text-slate-500">
                                {__('Get your embed code or preview the form.')}
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={route('crm.widgets.edit', widget.id)}>
                                {__('Edit Settings')}
                            </Link>
                        </Button>
                        <Button asChild className="gap-2">
                            <a href={iframeUrl} target="_blank" rel="noopener noreferrer">
                                <ExternalLink className="w-4 h-4" />
                                {__('Live Preview')}
                            </a>
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="md:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg flex items-center gap-2">
                                    <Code className="w-5 h-5 text-indigo-500" />
                                    {__('Embed Code')}
                                </CardTitle>
                                <CardDescription>
                                    {__('Copy and paste this HTML code into your website (WordPress, Shopify, custom site) where you want the form to appear.')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="relative">
                                    <pre className="bg-slate-900 text-slate-50 p-4 rounded-lg overflow-x-auto text-sm font-mono leading-relaxed">
                                        {embedCode}
                                    </pre>
                                    <Button 
                                        size="sm" 
                                        variant="secondary" 
                                        className="absolute top-3 right-3 gap-2 bg-white/10 hover:bg-white/20 text-white border-none"
                                        onClick={handleCopy}
                                    >
                                        {copied ? <Check className="w-4 h-4 text-emerald-400" /> : <Copy className="w-4 h-4" />}
                                        {copied ? __('Copied!') : __('Copy Code')}
                                    </Button>
                                </div>
                                <div className="p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                                    <strong>{__('Note')}: </strong>
                                    {__('If you specified Allowed Domains in the settings, this form will ONLY load when embedded on those specific websites. If it appears blank on your site, verify your domain settings.')}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="md:col-span-1 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">{__('Quick Stats')}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex justify-between items-center py-2 border-b border-slate-100">
                                    <span className="text-slate-500">{__('Status')}</span>
                                    <span className={`font-medium ${widget.is_active ? 'text-emerald-600' : 'text-slate-500'}`}>
                                        {widget.is_active ? __('Active') : __('Inactive')}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center py-2 border-b border-slate-100">
                                    <span className="text-slate-500">{__('Leads Captured')}</span>
                                    <span className="font-semibold text-slate-900">{widget.leads_count || 0}</span>
                                </div>
                                <div className="flex justify-between items-center py-2">
                                    <span className="text-slate-500">{__('Created On')}</span>
                                    <span className="text-slate-900 text-sm">
                                        {new Date(widget.created_at).toLocaleDateString()}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </CrmLayout>
    );
}
