import { Button } from '@/components/ui/button';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { Loader2 } from 'lucide-react';

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
                <div className="space-y-1.5">
                    <h1 className="text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">{__('general.check_your_email')}</h1>
                    <p className="text-xs text-zinc-500 dark:text-zinc-400 font-normal">{__('general.we_sent_a_verification_link_to_your_inbox')}</p>
                </div>

                <p className="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed font-normal">{__('general.please_verify_your_email_address_to_unlock_full_workspace_access_if_you_didn_t_receive_the_email_we_will_gladly_send_you_another')}</p>

                {status === 'verification-link-sent' && (
                    <div className="rounded-lg bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 p-3 text-xs font-medium text-emerald-600 dark:text-emerald-400">{__('general.a_new_verification_link_has_been_sent_to_your_email_address')}</div>
                )}

                <form onSubmit={submit} className="space-y-4 pt-2">
                    <Button
                        className="w-full h-10 bg-zinc-900 hover:bg-zinc-800 text-white dark:bg-zinc-100 dark:hover:bg-zinc-200 dark:text-zinc-900 font-medium text-sm rounded-lg shadow-xs transition-all flex items-center justify-center space-x-2"
                        disabled={processing}
                    >
                        {processing ? (
                            <>
                                <Loader2 className="w-4 h-4 animate-spin text-zinc-400 dark:text-zinc-600" />
                                <span>{__('general.sending_verification_link')}</span>
                            </>
                        ) : (
                            <span>{__('general.resend_verification_email')}</span>
                        )}
                    </Button>

                    <div className="text-center pt-4 border-t border-zinc-100 dark:border-zinc-800/80">
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="text-xs font-medium text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100 transition-colors underline underline-offset-4 font-normal"
                        >{__('general.log_out')}</Link>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
