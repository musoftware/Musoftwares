import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { MessageSquare, ArrowLeft, Send } from 'lucide-react';
import InputError from '@/Components/InputError';
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
        <AuthenticatedLayout>
            <Head title={`${__('general.open_new_ticket') || 'Open Support Ticket'} — Musoftwares Studio`} />

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* Hero Header */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto space-y-1.5">
                        <Link
                            href={route('tickets.index')}
                            className="inline-flex items-center text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] transition-colors mb-1"
                        >
                            <ArrowLeft className="me-1.5 h-3.5 w-3.5" />
                            {__('general.back') || 'Back'}
                        </Link>
                        <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                            {__('general.open_new_ticket') || 'Open Support Ticket'}
                        </h1>
                        <p className="text-xs sm:text-sm text-[#1d1d1f]/60 font-sans">
                            {__('general.please_describe_your_issue_below_we_ll_get_back_to_you_as_soon_as_possible') || 'Please describe your inquiry or issue below. Our team responds promptly.'}
                        </p>
                    </div>
                </div>

                {/* Form Container */}
                <div className="max-w-[850px] mx-auto px-6 sm:px-10 py-8 space-y-6">
                    
                    <div className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 shadow-sm">
                        <form onSubmit={submitTicket} className="space-y-6">
                            
                            {/* Subject */}
                            <div className="space-y-2">
                                <label htmlFor="subject" className="text-xs font-semibold text-[#1d1d1f] block">
                                    {__('general.subject') || 'Subject'} <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    id="subject"
                                    type="text"
                                    className="h-11 w-full rounded-xl bg-white border border-black/10 px-3.5 text-xs sm:text-sm text-[#1d1d1f] font-semibold focus:ring-2 focus:ring-[#0071e3] focus:outline-none"
                                    value={data.subject}
                                    onChange={(e) => setData('subject', e.target.value)}
                                    required
                                    placeholder={__('general.e_g_problem_with_billing_invoice') || 'e.g. Problem with billing / invoice'}
                                />
                                <InputError message={errors.subject} />
                            </div>

                            {/* Priority */}
                            <div className="space-y-2">
                                <label htmlFor="priority" className="text-xs font-semibold text-[#1d1d1f] block">
                                    {__('general.priority') || 'Priority'}
                                </label>
                                <select
                                    id="priority"
                                    className="h-11 w-full rounded-xl bg-white border border-black/10 px-3.5 text-xs sm:text-sm text-[#1d1d1f] font-medium focus:ring-2 focus:ring-[#0071e3] focus:outline-none"
                                    value={data.priority}
                                    onChange={(e) => setData('priority', e.target.value)}
                                >
                                    <option value="Low">{__('general.low_general_question') || 'Low (General Question)'}</option>
                                    <option value="Medium">{__('general.medium_issue_bug') || 'Medium (Issue / Bug)'}</option>
                                    <option value="High">{__('general.high_urgent_blocker') || 'High (Urgent Blocker)'}</option>
                                </select>
                                <InputError message={errors.priority} />
                            </div>

                            {/* Description */}
                            <div className="space-y-2">
                                <label htmlFor="description" className="text-xs font-semibold text-[#1d1d1f] block">
                                    {__('general.description') || 'Description'} <span className="text-rose-500">*</span>
                                </label>
                                <textarea
                                    id="description"
                                    rows={7}
                                    className="w-full rounded-xl bg-white border border-black/10 p-3.5 text-xs sm:text-sm text-[#1d1d1f] leading-relaxed resize-none focus:ring-2 focus:ring-[#0071e3] focus:outline-none"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    required
                                    placeholder={__('general.please_provide_detailed_information_about_your_request') || 'Please provide detailed information about your request.'}
                                />
                                <InputError message={errors.description} />
                            </div>

                            {/* Action Buttons */}
                            <div className="pt-4 flex items-center justify-end gap-3 border-t border-black/5">
                                <Link
                                    href={route('tickets.index')}
                                    className="px-5 py-2.5 text-xs font-semibold text-[#1d1d1f]/70 hover:text-[#1d1d1f] transition-colors rounded-full"
                                >
                                    {__('general.cancel') || 'Cancel'}
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing || !data.subject.trim() || !data.description.trim()}
                                    className="px-6 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] disabled:opacity-50 text-white rounded-[980px] text-xs font-semibold shadow-sm shadow-blue-500/20 transition-all flex items-center gap-1.5 cursor-pointer"
                                >
                                    <Send className="w-3.5 h-3.5" />
                                    <span>{processing ? 'Submitting...' : (__('general.submit_ticket') || 'Submit Ticket')}</span>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>

            </div>
        </AuthenticatedLayout>
    );
}
