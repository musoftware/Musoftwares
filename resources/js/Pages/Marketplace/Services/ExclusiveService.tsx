import React, { useState, useRef } from 'react';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, router } from '@inertiajs/react';
import { ShieldAlert, Send } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import { __ } from '@/lib/i18n';
import ReCAPTCHA from 'react-google-recaptcha';

export default function ExclusiveService({ serviceSlug }: { serviceSlug: string }) {
    const [form, setForm] = useState({
        name: '',
        email: '',
        phone: '',
        description: `أرغب في الاستفسار عن الخدمة الحصرية: ${serviceSlug}`,
        'g-recaptcha-response': '',
    });
    
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isSuccess, setIsSuccess] = useState(false);
    
    const recaptchaRef = useRef<ReCAPTCHA>(null);

    const onReCAPTCHAChange = (captchaCode: string | null) => {
        setForm({ ...form, 'g-recaptcha-response': captchaCode || '' });
        if (errors['g-recaptcha-response']) {
            setErrors({ ...errors, 'g-recaptcha-response': '' });
        }
    };

    const submitRequest = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (import.meta.env.VITE_RECAPTCHA_SITE_KEY && !form['g-recaptcha-response']) {
            setErrors({ ...errors, 'g-recaptcha-response': __('general.recaptcha_required') || 'يرجى إكمال اختبار الكابتشا.' });
            return;
        }

        setIsSubmitting(true);
        
        router.post(route('tickets.guest.store'), form, {
            preserveState: true,
            onError: (err) => {
                setErrors(err);
                setIsSubmitting(false);
                if (recaptchaRef.current) {
                    recaptchaRef.current.reset();
                    setForm({ ...form, 'g-recaptcha-response': '' });
                }
            },
            onSuccess: () => {
                setIsSuccess(true);
                setIsSubmitting(false);
            }
        });
    };

    return (
        <MarketplaceLayout>
            <Head title={__('general.exclusive_service') || 'Exclusive Service'} />
            
            <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-xl w-full space-y-8">
                    {isSuccess ? (
                        <Card className="border-emerald-100 shadow-xl overflow-hidden">
                            <div className="bg-emerald-500 h-2 w-full"></div>
                            <CardContent className="pt-8 pb-8 text-center space-y-4">
                                <div className="mx-auto w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center">
                                    <Send className="w-8 h-8 text-emerald-600" />
                                </div>
                                <h2 className="text-2xl font-bold text-gray-900">تم استلام طلبك بنجاح</h2>
                                <p className="text-gray-500 max-w-md mx-auto">
                                    شكراً لتواصلك معنا. سنقوم بمراجعة طلبك للخدمة الحصرية والتواصل معك قريباً عبر البريد الإلكتروني أو الهاتف الذي قدمته.
                                </p>
                                <div className="pt-4">
                                    <Button onClick={() => window.location.href = route('marketplace.services.index')} variant="outline">
                                        العودة للسوق
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ) : (
                        <Card className="border-indigo-100 shadow-xl overflow-hidden">
                            <div className="bg-indigo-600 h-2 w-full"></div>
                            <CardHeader className="text-center pb-4">
                                <div className="mx-auto w-12 h-12 bg-indigo-50 rounded-full flex items-center justify-center mb-4">
                                    <ShieldAlert className="w-6 h-6 text-indigo-600" />
                                </div>
                                <CardTitle className="text-2xl font-bold text-gray-900">
                                    خدمة حصرية
                                </CardTitle>
                                <CardDescription className="text-base text-gray-600 mt-2">
                                    هذه الخدمة حصرية وغير متاحة للجميع. يرجى ترك بياناتك وسنقوم بالتواصل معك لتحديد إمكانية تقديمها لك.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submitRequest} className="space-y-5">
                                    <div className="space-y-1">
                                        <InputLabel htmlFor="name" value="الاسم الكامل" className="font-medium" />
                                        <TextInput
                                            id="name"
                                            type="text"
                                            className="w-full mt-1"
                                            value={form.name}
                                            onChange={(e) => setForm({ ...form, name: e.target.value })}
                                            required
                                            placeholder="أدخل اسمك الكريم"
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div className="space-y-1">
                                            <InputLabel htmlFor="email" value="البريد الإلكتروني" className="font-medium" />
                                            <TextInput
                                                id="email"
                                                type="email"
                                                className="w-full mt-1"
                                                value={form.email}
                                                onChange={(e) => setForm({ ...form, email: e.target.value })}
                                                required
                                                placeholder="example@domain.com"
                                                
                                            />
                                            <InputError message={errors.email} />
                                        </div>
                                        
                                        <div className="space-y-1">
                                            <InputLabel htmlFor="phone" value="رقم الهاتف" className="font-medium" />
                                            <TextInput
                                                id="phone"
                                                type="tel"
                                                className="w-full mt-1"
                                                value={form.phone}
                                                onChange={(e) => setForm({ ...form, phone: e.target.value })}
                                                required
                                                placeholder="+201xxxxxxxxx"
                                                
                                            />
                                            <InputError message={errors.phone} />
                                        </div>
                                    </div>

                                    <div className="space-y-1">
                                        <InputLabel htmlFor="description" value="تفاصيل الطلب (اختياري)" className="font-medium" />
                                        <textarea
                                            id="description"
                                            rows={4}
                                            className="block w-full rounded-lg border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm resize-none mt-1"
                                            value={form.description}
                                            onChange={(e) => setForm({ ...form, description: e.target.value })}
                                            required
                                        />
                                        <InputError message={errors.description} />
                                    </div>

                                    <div className="pt-2 flex flex-col items-center">
                                        {import.meta.env.VITE_RECAPTCHA_SITE_KEY && (
                                            <ReCAPTCHA
                                                ref={recaptchaRef}
                                                sitekey={import.meta.env.VITE_RECAPTCHA_SITE_KEY}
                                                onChange={onReCAPTCHAChange}
                                                hl="ar"
                                            />
                                        )}
                                        <InputError message={errors['g-recaptcha-response']} className="mt-2" />
                                    </div>

                                    <div className="pt-2">
                                        <Button 
                                            type="submit" 
                                            className="w-full bg-indigo-600 hover:bg-indigo-700 text-white shadow-md text-base h-11"
                                            disabled={isSubmitting}
                                        >
                                            {isSubmitting ? 'جاري إرسال الطلب...' : 'إرسال طلب الخدمة الحصرية'}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </MarketplaceLayout>
    );
}
