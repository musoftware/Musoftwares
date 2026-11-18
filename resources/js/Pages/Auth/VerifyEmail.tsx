import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { MailCheck } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { AuthFooter, AuthHeader, AuthStatus, AuthSubmit } from '@/Components/Auth/AuthParts';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    const sent = status === 'verification-link-sent';

    return (
        <GuestLayout>
            <Head title={__('general.verify_email')} />

            <div className="space-y-8">
                <span className="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#F2F2F2] text-[#111] dark:bg-zinc-800 dark:text-white">
                    <MailCheck className="h-7 w-7" strokeWidth={1.75} />
                </span>
                <AuthHeader title={__('general.check_your_email')} subtitle={__('general.we_sent_a_verification_link_to_your_inbox')} />
                <AuthStatus>{sent && __('general.a_new_verification_link_has_been_sent_to_your_email_address')}</AuthStatus>

                <form onSubmit={submit}>
                    <AuthSubmit busy={processing} label={__('general.resend_verification_email')} busyLabel={__('general.sending_verification_link')} />
                </form>

                <AuthFooter>
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="font-medium text-[#555] hover:text-[#111] dark:text-zinc-400 dark:hover:text-white"
                    >
                        {__('general.log_out')}
                    </Link>
                </AuthFooter>
            </div>
        </GuestLayout>
    );
}
