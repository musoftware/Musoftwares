import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { __ } from '@/lib/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Copy, Link as LinkIcon, Code, Save, Lock } from 'lucide-react';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Tabs, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Label } from '@/Components/ui/label';

export default function Index({ auth, referral, commission_percentage, embedKey }: any) {
    const origin = window.location.origin;
    const referralLink = `${origin}/r/${referral?.slug || referral?.key || ''}`;
    const formattedCommission = ((commission_percentage - 1) * 100).toFixed(0) + '%';
    const [copied, setCopied] = useState(false);
    const [copiedEmbed, setCopiedEmbed] = useState(false);

    const handleCopy = (text: string, isEmbed = false) => {
        navigator.clipboard.writeText(text);
        if (isEmbed) {
            setCopiedEmbed(true);
            setTimeout(() => setCopiedEmbed(false), 2000);
        } else {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    };

    const handleTabChange = (val: string) => {
        router.visit(route(val));
    };

    // Form for custom slug
    const { data, setData, post, processing, errors } = useForm({
        slug: referral?.slug || '',
    });

    const handleUpdateSlug = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('referrals.update_slug'), {
            preserveScroll: true,
        });
    };

    const handleGenerateEmbedKey = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('referrals.generate_embed_key'), {}, {
            preserveScroll: true,
        });
    };

    const hasCustomSlug = referral?.slug && referral.slug.trim() !== '';

    return (
        <AuthenticatedLayout>
            <Head title={__('general.referrals')} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="flex justify-between items-center">
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            {__('general.referrals')}
                        </h2>
                    </div>

                    <Tabs value="referrals.index" onValueChange={handleTabChange} className="w-full">
                        <TabsList className="grid w-full grid-cols-3 max-w-md bg-slate-100 p-1 rounded-lg">
                            <TabsTrigger value="referrals.index" className="data-[state=active]:bg-white data-[state=active]:shadow-sm rounded-md py-1.5 text-sm font-medium transition-all">
                                {__('general.referral_link')}
                            </TabsTrigger>
                            <TabsTrigger value="referrals.earns" className="data-[state=active]:bg-white data-[state=active]:shadow-sm rounded-md py-1.5 text-sm font-medium transition-all">
                                {__('general.referral_earnings')}
                            </TabsTrigger>
                            <TabsTrigger value="referrals.registers" className="data-[state=active]:bg-white data-[state=active]:shadow-sm rounded-md py-1.5 text-sm font-medium transition-all">
                                {__('general.referred_users')}
                            </TabsTrigger>
                        </TabsList>
                    </Tabs>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <LinkIcon className="w-5 h-5 text-indigo-500" />
                                {__('messages.your_referral_link') || __('general.referral_link')}
                            </CardTitle>
                            <CardDescription>
                                {__('messages.share_referral_desc') || 'Share this link to earn commission when users register.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center space-x-2">
                                <Input value={referralLink} readOnly className="bg-gray-50 font-mono text-sm" />
                                <Button onClick={() => handleCopy(referralLink, false)} variant="outline">
                                    <Copy className="w-4 h-4 me-2" />
                                    {copied ? __('general.copied') : __('general.copy')}
                                </Button>
                            </div>

                            <div className="mt-4 p-4 bg-indigo-50 rounded-lg border border-indigo-100 flex items-center justify-between gap-4">
                                <div>
                                    <h4 className="font-semibold text-indigo-900">{__('general.your_commission_rate')}</h4>
                                    <p className="text-sm text-indigo-700">{__('general.you_earn_this_percentage_from_all_paymen') || 'Percentage of payments made by referred users.'}</p>
                                </div>
                                <Badge className="bg-indigo-600 text-white text-lg py-1 px-3">
                                    {formattedCommission}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Custom Slug Section */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <LinkIcon className="w-5 h-5 text-indigo-500" />
                                {__('general.custom_referral_slug') || 'Custom Referral Slug'}
                            </CardTitle>
                            <CardDescription>
                                {__('general.customize_your_link_slug') || 'Set a custom ending for your referral link. Once set, it cannot be changed.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleUpdateSlug} className="space-y-4">
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="slug">{__('general.slug') || 'Slug'}</Label>
                                    <div className="flex items-center space-x-2">
                                        <div className="flex-1 relative">
                                            <Input
                                                id="slug"
                                                value={data.slug}
                                                onChange={(e) => setData('slug', e.target.value)}
                                                disabled={hasCustomSlug || processing}
                                                className={`bg-slate-50 font-mono ${hasCustomSlug ? 'pe-10' : ''}`}
                                                placeholder="my-custom-code"
                                            />
                                            {hasCustomSlug && (
                                                <div className="absolute inset-y-0 right-3 flex items-center text-slate-400">
                                                    <Lock className="w-4 h-4" />
                                                </div>
                                            )}
                                        </div>
                                        {!hasCustomSlug && (
                                            <Button type="submit" disabled={processing} className="bg-slate-900 hover:bg-slate-800 text-white">
                                                <Save className="w-4 h-4 me-2" />
                                                {__('general.save') || 'Save'}
                                            </Button>
                                        )}
                                    </div>
                                    {errors.slug && <p className="text-sm text-red-600 font-medium">{errors.slug}</p>}
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Embed Keys Card */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Code className="w-5 h-5 text-emerald-500" />
                                {__('messages.embed_modules') || __('general.embed_modules')}
                            </CardTitle>
                            <CardDescription>
                                {__('general.you_can_embed_modules_or_tools_directly') || 'You can embed tools directly using your key.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {embedKey ? (
                                <div className="space-y-4">
                                    <Label className="text-slate-700 font-medium">{__('general.embed_key') || 'Your Embed Key'}</Label>
                                    <div className="flex items-center space-x-2">
                                        <Input value={embedKey.key} readOnly className="bg-gray-50 font-mono text-sm" />
                                        <Button onClick={() => handleCopy(embedKey.key, true)} variant="outline">
                                            <Copy className="w-4 h-4 me-2" />
                                            {copiedEmbed ? __('general.copied') : __('general.copy')}
                                        </Button>
                                    </div>
                                </div>
                            ) : (
                                <div className="bg-gray-50 p-6 rounded-lg text-center border border-dashed border-gray-300">
                                    <p className="text-gray-600 mb-4">{__('general.you_have_not_generated_any_embed_keys_ye')}</p>
                                    <form onSubmit={handleGenerateEmbedKey}>
                                        <Button type="submit">
                                            {__('general.generate_embed_key')}
                                        </Button>
                                    </form>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
