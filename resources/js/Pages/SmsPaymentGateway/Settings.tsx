import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card';
import { Switch } from '@/Components/ui/switch';
import { toast } from 'sonner';

interface Props {
    settings: {
        wallet_phone_number: string | null;
        instapay_phone_number: string | null;
        vodafone_cash_phone_number: string | null;
        is_instapay_enabled: boolean;
        is_vodafone_cash_enabled: boolean;
    };
}

export default function Settings({ settings }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        wallet_phone_number: settings?.wallet_phone_number || '',
        instapay_phone_number: settings?.instapay_phone_number || '',
        vodafone_cash_phone_number: settings?.vodafone_cash_phone_number || '',
        is_instapay_enabled: settings?.is_instapay_enabled ?? true,
        is_vodafone_cash_enabled: settings?.is_vodafone_cash_enabled ?? true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('sms-payment-gateway.settings.store'), {
            preserveScroll: true,
            onSuccess: () => toast.success('تم حفظ الإعدادات بنجاح'),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="إعدادات بوابة الدفع" />

            <div className="max-w-3xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900">إعدادات بوابة الدفع</h1>
                    <p className="mt-2 text-sm text-gray-600">قم بضبط رقم التحويل وطرق الدفع المتاحة لعملائك.</p>
                </div>

                <form onSubmit={submit}>
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>بيانات التحويل</CardTitle>
                            <CardDescription>هذا هو الرقم الذي سيظهر للمشتري ليقوم بتحويل المبلغ إليه.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            
                            <div className="space-y-2">
                                <Label htmlFor="wallet_phone_number">الرقم الأساسي (للمحافظ وإنستاباي)</Label>
                                <Input
                                    id="wallet_phone_number"
                                    type="text"
                                    dir="ltr"
                                    className="text-left font-mono max-w-md"
                                    value={data.wallet_phone_number}
                                    onChange={(e) => setData('wallet_phone_number', e.target.value)}
                                    placeholder="مثال: 01012345678"
                                />
                                <p className="text-xs text-gray-500 max-w-md">يستخدم هذا الرقم بشكل افتراضي في حال لم يتم تحديد رقم مخصص لكل طريقة دفع.</p>
                                {errors.wallet_phone_number && <p className="text-sm text-red-600">{errors.wallet_phone_number}</p>}
                            </div>

                            <hr className="my-6" />

                            <div className="space-y-4">
                                <h3 className="text-lg font-medium">طرق الدفع المتاحة والأرقام المخصصة</h3>
                                
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between max-w-md p-4 border rounded-lg bg-gray-50/50">
                                        <div className="space-y-0.5">
                                            <Label className="text-base">إنستاباي (Instapay)</Label>
                                            <p className="text-sm text-gray-500">السماح للمشتري باختيار إنستاباي كوسيلة تحويل.</p>
                                        </div>
                                        <Switch
                                            checked={data.is_instapay_enabled}
                                            onCheckedChange={(checked) => setData('is_instapay_enabled', checked)}
                                        />
                                    </div>
                                    {data.is_instapay_enabled && (
                                        <div className="max-w-md p-4 border rounded-lg border-indigo-100 bg-indigo-50/30">
                                            <Label htmlFor="instapay_phone_number">رقم/عنوان إنستاباي المخصص (اختياري)</Label>
                                            <Input
                                                id="instapay_phone_number"
                                                type="text"
                                                dir="ltr"
                                                className="text-left font-mono mt-2"
                                                value={data.instapay_phone_number}
                                                onChange={(e) => setData('instapay_phone_number', e.target.value)}
                                                placeholder="مثال: user@instapay"
                                            />
                                            {errors.instapay_phone_number && <p className="text-sm text-red-600 mt-1">{errors.instapay_phone_number}</p>}
                                        </div>
                                    )}
                                </div>

                                <div className="space-y-3 pt-4">
                                    <div className="flex items-center justify-between max-w-md p-4 border rounded-lg bg-gray-50/50">
                                        <div className="space-y-0.5">
                                            <Label className="text-base">فودافون كاش / المحافظ</Label>
                                            <p className="text-sm text-gray-500">السماح باختيار المحافظ الإلكترونية.</p>
                                        </div>
                                        <Switch
                                            checked={data.is_vodafone_cash_enabled}
                                            onCheckedChange={(checked) => setData('is_vodafone_cash_enabled', checked)}
                                        />
                                    </div>
                                    {data.is_vodafone_cash_enabled && (
                                        <div className="max-w-md p-4 border rounded-lg border-indigo-100 bg-indigo-50/30">
                                            <Label htmlFor="vodafone_cash_phone_number">رقم المحفظة المخصص (اختياري)</Label>
                                            <Input
                                                id="vodafone_cash_phone_number"
                                                type="text"
                                                dir="ltr"
                                                className="text-left font-mono mt-2"
                                                value={data.vodafone_cash_phone_number}
                                                onChange={(e) => setData('vodafone_cash_phone_number', e.target.value)}
                                                placeholder="مثال: 01012345678"
                                            />
                                            {errors.vodafone_cash_phone_number && <p className="text-sm text-red-600 mt-1">{errors.vodafone_cash_phone_number}</p>}
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="pt-6">
                                <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                                    حفظ الإعدادات
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
