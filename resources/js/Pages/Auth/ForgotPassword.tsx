import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { ArrowLeft, Loader2 } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title={__('general.forgot_password')} />

            <div className="space-y-6">
                <div className="space-y-1.5">
                    <h1 className="text-2xl font-bold tracking-tight text-[#1d1d1f] font-sans">{__('general.reset_your_password')}</h1>
                    <p className="text-xs text-[#1d1d1f]/60 font-sans">{__('general.enter_your_email_address_and_we_will_send_you_a_link_to_reset_your_password')}</p>
                </div>

                {status && (
                    <div className="rounded-xl bg-[#34c759]/10 border border-[#34c759]/20 p-3 text-xs font-semibold text-[#28a745]">
                        {status}
                    </div>
                )}

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="email" className="text-xs font-semibold text-[#1d1d1f]/80">{__('general.email_address_1')}</Label>
                        <Input
                            id="email"
                            type="email"
                            placeholder={__('general.name_company_com')}
                            value={data.email}
                            autoFocus
                            onChange={(e) => setData('email', e.target.value)}
                            required
                            className="h-10 px-3 py-2 text-sm rounded-xl border-black/10 bg-[#f5f5f7]/50 focus:bg-white focus-visible:ring-2 focus-visible:ring-[#0071e3] transition-all font-normal"
                        />
                        {errors.email && (
                            <p className="text-xs text-[#ff3b30] font-medium mt-1">{errors.email}</p>
                        )}
                    </div>

                    <div className="pt-2">
                        <Button
                            className="w-full h-11 bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold text-sm rounded-[980px] shadow-md shadow-blue-500/20 transition-all flex items-center justify-center space-x-2 cursor-pointer"
                            disabled={processing}
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="w-4 h-4 animate-spin text-white" />
                                    <span>{__('general.sending_reset_link')}</span>
                                </>
                            ) : (
                                <span>{__('general.send_reset_link')}</span>
                            )}
                        </Button>
                    </div>
                </form>

                <div className="text-center pt-4 border-t border-black/5 mt-6">
                    <Link
                        href={route('login')}
                        className="inline-flex items-center space-x-2 text-xs font-semibold text-[#0071e3] hover:underline transition-colors"
                    >
                        <ArrowLeft className="w-3.5 h-3.5" />
                        <span>{__('general.back_to_log_in')}</span>
                    </Link>
                </div>
            </div>
        </GuestLayout>
    );
}
