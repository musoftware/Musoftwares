import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef, useState } from 'react';
import { __ } from '@/lib/i18n';

export default function DeleteUserForm({
    className = '',
    hasUnpaidInvoices = false,
}: {
    className?: string;
    hasUnpaidInvoices?: boolean;
}) {
    const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);
    const passwordInput = useRef<HTMLInputElement>(null);

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm({
        password: '',
    });

    const confirmUserDeletion = () => {
        setConfirmingUserDeletion(true);
    };

    const deleteUser: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingUserDeletion(false);

        clearErrors();
        reset();
    };

    return (
        <section className={`space-y-6 ${className}`}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">{__('general.delete_account')}</h2>

                <p className="mt-1 text-sm text-gray-600">
                    {__('general.once_your_account_is_deleted_all_of_its')}</p>
            </header>

            {hasUnpaidInvoices && (
                <div className="rounded-md bg-amber-50 p-4 border border-amber-200 text-amber-800 text-sm">
                    {__('general.cannot_delete_account_with_unpaid_invoices')}
                </div>
            )}

            <DangerButton onClick={confirmUserDeletion} disabled={hasUnpaidInvoices}>{__('general.delete_account')}</DangerButton>

            <Modal show={confirmingUserDeletion} onClose={closeModal}>
                <form onSubmit={deleteUser} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">{__('general.are_you_sure_you_want_to_delete_your_account')}</h2>

                    <p className="mt-1 text-sm text-gray-600">
                        {__('general.once_your_account_is_deleted_all_of_its')}</p>

                    <div className="mt-6">
                        <InputLabel
                            htmlFor="password"
                            value="Password"
                            className="sr-only"
                        />

                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            ref={passwordInput}
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            className="mt-1 block w-3/4"
                            isFocused
                            placeholder={__('general.password')}
                        />

                        <InputError
                            message={errors.password}
                            className="mt-2"
                        />
                    </div>

                    <div className="mt-6 flex justify-end">
                        <SecondaryButton onClick={closeModal}>
                            {__('general.cancel')}</SecondaryButton>

                        <DangerButton className="ms-3" disabled={processing}>{__('general.delete_account')}</DangerButton>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
