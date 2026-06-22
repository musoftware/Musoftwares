import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { __ } from '@/lib/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Copy, Link, Code } from 'lucide-react';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';

export default function Index({ auth, referral, commission_percentage }: any) {
    const origin = window.location.origin;
    const referralLink = `${origin}/r/${referral?.slug || referral?.key || ''}`;
    const formattedCommission = ((commission_percentage - 1) * 100).toFixed(0) + '%';
    const [copied, setCopied] = useState(false);

    const handleCopy = (text: string) => {
        navigator.clipboard.writeText(text);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

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

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Link className="w-5 h-5 text-indigo-500" />
                                {__('messages.your_referral_link')}
                            </CardTitle>
                            <CardDescription>
                                {__('messages.share_referral_desc')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center space-x-2">
                                <Input value={referralLink} readOnly className="bg-gray-50" />
                                <Button onClick={() => handleCopy(referralLink)} variant="outline">
                                    <Copy className="w-4 h-4 me-2" />
                                    {copied ? __('general.copied') : __('general.copy')}
                                </Button>
                            </div>

                            <div className="mt-4 p-4 bg-indigo-50 rounded-lg border border-indigo-100 flex items-center justify-between">
                                <div>
                                    <h4 className="font-semibold text-indigo-900">{__('general.your_commission_rate')}</h4>
                                    <p className="text-sm text-indigo-700">{__('general.you_earn_this_percentage_from_all_paymen')}</p>
                                </div>
                                <Badge className="bg-indigo-600 text-white text-lg py-1 px-3">
                                    {formattedCommission}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Code className="w-5 h-5 text-emerald-500" />
                                {__('messages.embed_modules')}
                            </CardTitle>
                            <CardDescription>
                                {__('general.you_can_embed_modules_or_tools_directly')}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="bg-gray-50 p-6 rounded-lg text-center border border-dashed border-gray-300">
                                <p className="text-gray-600 mb-4">{__('general.you_have_not_generated_any_embed_keys_ye')}</p>
                                <Button>
                                    {__('general.generate_embed_key')}</Button>
                            </div>
                        </CardContent>
                    </Card>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
