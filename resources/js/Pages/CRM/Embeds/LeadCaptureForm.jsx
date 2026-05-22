import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';

export default function LeadCaptureForm({ campaign, token, flash }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        phone: '',
        message: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('crm.embed.capture.store', token), {
            onSuccess: () => reset(),
        });
    };

    return (
        <div className="min-h-screen bg-transparent p-4 flex flex-col justify-center items-center font-sans">
            <Head title={`Contact - ${campaign?.title || 'Form'}`} />

            <Card className="w-full max-w-md shadow-xl border-slate-200/60 bg-white/95 backdrop-blur-sm">
                <CardHeader className="text-center pb-4">
                    <CardTitle className="text-2xl font-bold tracking-tight text-slate-900">
                        {campaign?.form_title || 'Get in Touch'}
                    </CardTitle>
                    <CardDescription className="text-sm text-slate-500 mt-1">
                        {campaign?.form_description || 'Fill out the form below and we will contact you shortly.'}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    {flash?.success ? (
                        <div className="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg p-4 text-center">
                            <p className="font-medium">{flash.success}</p>
                        </div>
                    ) : (
                        <form onSubmit={submit} className="space-y-4">
                            {flash?.error && (
                                <div className="bg-red-50 text-red-600 text-sm p-3 rounded-md border border-red-100">
                                    {flash.error}
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="name" className="text-slate-700 font-medium">Full Name <span className="text-red-500">*</span></Label>
                                <Input
                                    id="name"
                                    name="name"
                                    value={data.name}
                                    className="h-11 bg-slate-50 border-slate-200 focus:bg-white"
                                    autoComplete="name"
                                    isFocused={true}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                />
                                {errors.name && <p className="text-sm text-red-500 mt-1">{errors.name}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="email" className="text-slate-700 font-medium">Email Address <span className="text-red-500">*</span></Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className="h-11 bg-slate-50 border-slate-200 focus:bg-white"
                                    autoComplete="email"
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                />
                                {errors.email && <p className="text-sm text-red-500 mt-1">{errors.email}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="phone" className="text-slate-700 font-medium">Phone Number</Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    value={data.phone}
                                    className="h-11 bg-slate-50 border-slate-200 focus:bg-white"
                                    autoComplete="tel"
                                    onChange={(e) => setData('phone', e.target.value)}
                                />
                                {errors.phone && <p className="text-sm text-red-500 mt-1">{errors.phone}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="message" className="text-slate-700 font-medium">Message (Optional)</Label>
                                <Textarea
                                    id="message"
                                    name="message"
                                    value={data.message}
                                    className="min-h-[100px] resize-none bg-slate-50 border-slate-200 focus:bg-white"
                                    onChange={(e) => setData('message', e.target.value)}
                                />
                                {errors.message && <p className="text-sm text-red-500 mt-1">{errors.message}</p>}
                            </div>

                            <div className="pt-2">
                                <Button className="w-full h-11 text-base font-semibold shadow-md" disabled={processing}>
                                    {processing ? 'Submitting...' : (campaign?.button_text || 'Submit')}
                                </Button>
                            </div>
                        </form>
                    )}
                </CardContent>
            </Card>
            <div className="mt-4 text-center">
                <a href="https://musoftwares.com" target="_blank" rel="noopener noreferrer" className="text-[10px] text-slate-400 font-medium hover:text-slate-500 transition-colors uppercase tracking-widest flex items-center justify-center gap-1">
                    Powered by <span className="font-bold">Musoftware</span>
                </a>
            </div>
        </div>
    );
}
