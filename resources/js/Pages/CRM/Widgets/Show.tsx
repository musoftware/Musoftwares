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
        toast.success(__('general.embed_code_copied_to_clipboard'));
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <CrmLayout title={__('general.form_embed_code')} activeMenu="widgets">
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
                                {__('general.get_your_embed_code_or_preview_the_form')}
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={route('crm.widgets.edit', widget.id)}>
                                {__('admin.edit_settings')}
                            </Link>
                        </Button>
                        <Button asChild className="gap-2">
                            <a href={iframeUrl} target="_blank" rel="noopener noreferrer">
                                <ExternalLink className="w-4 h-4" />
                                {__('general.live_preview')}
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
                                    {__('general.embed_code')}
                                </CardTitle>
                                <CardDescription>
                                    {__('general.copy_and_paste_this_html_code_into_your_website_wordpress_shopify_custom_site_where_you_want_the_form_to_appear')}
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
                                        className="absolute top-3 end-3 gap-2 bg-white/10 hover:bg-white/20 text-white border-none"
                                        onClick={handleCopy}
                                    >
                                        {copied ? <Check className="w-4 h-4 text-emerald-400" /> : <Copy className="w-4 h-4" />}
                                        {copied ? __('general.copied') : __('general.copy_code')}
                                    </Button>
                                </div>
                                <div className="p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                                    <strong>{__('general.note')}: </strong>
                                    {__('general.if_you_specified_allowed_domains_in_the_settings_this_form_will_only_load_when_embedded_on_those_specific_websites_if_it_appears_blank_on_your_site_verify_your_domain_settings')}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="md:col-span-1 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">{__('general.quick_stats')}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex justify-between items-center py-2 border-b border-slate-100">
                                    <span className="text-slate-500">{__('general.status')}</span>
                                    <span className={`font-medium ${widget.is_active ? 'text-emerald-600' : 'text-slate-500'}`}>
                                        {widget.is_active ? __('general.active') : __('general.inactive')}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center py-2 border-b border-slate-100">
                                    <span className="text-slate-500">{__('general.leads_captured')}</span>
                                    <span className="font-semibold text-slate-900">{widget.leads_count || 0}</span>
                                </div>
                                <div className="flex justify-between items-center py-2">
                                    <span className="text-slate-500">{__('general.created_on')}</span>
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
