import { Button } from '@/components/ui/button';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { Loader2, MailCheck } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title={__('general.verify_email')} />

            <div className="space-y-6">
                <div className="flex justify-center">
                    <div className="w-16 h-16 bg-[#0071e3]/10 rounded-2xl flex items-center justify-center border border-[#0071e3]/20 shadow-xs">
                        <MailCheck className="w-8 h-8 text-[#0071e3]" />
                    </div>
                </div>
                
                <div className="space-y-1.5 text-center">
                    <h1 className="text-2xl font-bold tracking-tight text-[#1d1d1f] font-sans">{__('general.check_your_email')}</h1>
                    <p className="text-xs text-[#1d1d1f]/60 font-sans">{__('general.we_sent_a_verification_link_to_your_inbox')}</p>
                </div>

                <p className="text-xs text-center text-[#1d1d1f]/70 leading-relaxed font-sans">{__('general.please_verify_your_email_address_to_unlock_full_workspace_access_if_you_didn_t_receive_the_email_we_will_gladly_send_you_another')}</p>

                {status === 'verification-link-sent' && (
                    <div className="rounded-xl bg-[#34c759]/10 border border-[#34c759]/20 p-3 text-xs font-semibold text-[#28a745] text-center">{__('general.a_new_verification_link_has_been_sent_to_your_email_address')}</div>
                )}

                <form onSubmit={submit} className="space-y-4 pt-2">
                    <Button
                        className="w-full h-11 bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold text-sm rounded-[980px] shadow-md shadow-blue-500/20 transition-all flex items-center justify-center space-x-2 cursor-pointer"
                        disabled={processing}
                    >
                        {processing ? (
                            <>
                                <Loader2 className="w-4 h-4 animate-spin text-white" />
                                <span>{__('general.sending_verification_link')}</span>
                            </>
                        ) : (
                            <span>{__('general.resend_verification_email')}</span>
                        )}
                    </Button>

                    <div className="text-center pt-4 border-t border-black/5">
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="text-xs font-semibold text-[#1d1d1f]/60 hover:text-[#1d1d1f] transition-colors cursor-pointer"
                        >{__('general.log_out')}</Link>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
