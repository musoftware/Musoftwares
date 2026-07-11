import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { MessageSquare, ArrowLeft } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { PageHeader } from '@/Components/ui/PageHeader';
import { SectionCard } from '@/Components/ui/SectionCard';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { AppPage } from '@/Components/ui/AppPage';
import { __ } from '@/lib/i18n';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        subject: '',
        priority: 'Medium',
        description: '',
    });

    const submitTicket = (e) => {
        e.preventDefault();
        post(route('tickets.store'));
    };

    return (
        <AuthenticatedLayout header={__('general.open_new_ticket') || 'Open Support Ticket'}>
            <Head title={__('general.open_new_ticket') || 'Open Support Ticket'} />

            <AppPage>
                <PageHeader
                    title={__('general.open_new_ticket') || 'Open Support Ticket'}
                    subtitle={__('general.please_describe_your_issue_below_we_ll_get_back_to_you_as_soon_as_possible') || 'Please describe your issue below, we will get back to you as soon as possible.'}
                    icon={MessageSquare}
                    actions={
                        <Link href={route('tickets.index')}>
                            <Button variant="outline" className="shadow-none">
                                <ArrowLeft className="w-4 h-4 me-2" />
                                {__('general.back') || 'Back'}
                            </Button>
                        </Link>
                    }
                />

                <SectionCard className="w-full">
                    <form onSubmit={submitTicket} className="space-y-6 max-w-4xl">
                        <div className="space-y-2">
                            <InputLabel htmlFor="subject" value={__('general.subject') || 'Subject'} className="text-sm font-medium" />
                            <TextInput
                                id="subject"
                                type="text"
                                className="block w-full border-gray-200 focus:ring-indigo-500 focus:border-indigo-500 rounded-lg shadow-sm"
                                value={data.subject}
                                onChange={(e) => setData('subject', e.target.value)}
                                required
                                placeholder={__('general.e_g_problem_with_billing_invoice') || 'e.g. Problem with billing / invoice'}
                            />
                            <InputError message={errors.subject} />
                        </div>

                        <div className="space-y-2">
                            <InputLabel htmlFor="priority" value={__('general.priority') || 'Priority'} className="text-sm font-medium" />
                            <select
                                id="priority"
                                className="block w-full rounded-lg border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm h-10 px-3"
                                value={data.priority}
                                onChange={(e) => setData('priority', e.target.value)}
                            >
                                <option value="Low">{__('general.low_general_question') || 'Low (General Question)'}</option>
                                <option value="Medium">{__('general.medium_issue_bug') || 'Medium (Issue / Bug)'}</option>
                                <option value="High">{__('general.high_urgent_blocker') || 'High (Urgent Blocker)'}</option>
                            </select>
                            <InputError message={errors.priority} />
                        </div>

                        <div className="space-y-2">
                            <InputLabel htmlFor="description" value={__('general.description') || 'Description'} className="text-sm font-medium" />
                            <textarea
                                id="description"
                                rows="8"
                                className="block w-full rounded-lg border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm resize-none p-3"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                required
                                placeholder={__('general.please_provide_detailed_information_about_your_request') || 'Please provide detailed information about your request.'}
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="pt-4 flex justify-end gap-3 border-t border-gray-100">
                            <Link href={route('tickets.index')}>
                                <Button type="button" variant="outline">
                                    {__('general.cancel') || 'Cancel'}
                                </Button>
                            </Link>
                            <Button type="submit" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">
                                {__('general.submit_ticket') || 'Submit Ticket'}
                            </Button>
                        </div>
                    </form>
                </SectionCard>
            </AppPage>
        </AuthenticatedLayout>
    );
}
