import React from 'react';
import PublicLayout from './PublicLayout';
import { SeoHead } from '@/Components/ui/SeoHead';
import { __ } from '@/lib/i18n';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ui/accordion';

export default function AboutUs() {
    const faqs = [
        {
            question: __('freelance.faq_1_q', undefined, 'How much does it cost to use ArabiJobs?'),
            answer: __('freelance.faq_1_a', undefined, 'Joining and browsing is free. We charge a small service fee on completed contracts.'),
        },
        {
            question: __('freelance.faq_2_q', undefined, 'How do I get paid?'),
            answer: __('freelance.faq_2_a', undefined, 'Payments are held securely in escrow and released to you upon project completion.'),
        },
        {
            question: __('freelance.faq_3_q', undefined, 'Can I hire international freelancers?'),
            answer: __('freelance.faq_3_a', undefined, 'Yes, our platform connects you with top talent across the entire Arab world and beyond.'),
        },
    ];

    return (
        <PublicLayout>
            <SeoHead 
                title={`${__('freelance.nav.about_us', undefined, 'About Us')} - ${__('freelance.freelance')}`} 
                description={__('freelance.about_us_desc', undefined, 'Learn more about ArabiJobs and our mission to connect professionals.')}
            />

            <div className="mx-auto max-w-3xl text-center pb-16">
                <h1 className="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
                    {__('freelance.about_us_heading', undefined, 'About ArabiJobs')}
                </h1>
                <p className="mt-6 text-lg leading-8 text-slate-600">
                    {__('freelance.about_us_subheading', undefined, 'We are on a mission to build the most trusted network of independent professionals and businesses in the region.')}
                </p>
            </div>

            <div className="mx-auto max-w-4xl mt-8 mb-24">
                <div className="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 sm:p-12 text-center">
                    <h2 className="text-2xl font-bold text-slate-900 mb-4">{__('freelance.our_mission', undefined, 'Our Mission')}</h2>
                    <p className="text-slate-600 leading-relaxed max-w-2xl mx-auto">
                        {__('freelance.mission_statement', undefined, 'ArabiJobs was founded with a single goal: to empower individuals to build their careers on their own terms, while providing businesses with seamless access to world-class talent.')}
                    </p>
                </div>
            </div>

            <div className="mx-auto max-w-3xl mt-16">
                <h2 className="text-3xl font-bold tracking-tight text-slate-900 text-center mb-10">
                    {__('freelance.frequently_asked_questions', undefined, 'Frequently Asked Questions')}
                </h2>
                <Accordion className="w-full">
                    {faqs.map((faq, index) => (
                        <AccordionItem key={index} value={`item-${index}`}>
                            <AccordionTrigger className="text-left text-lg font-semibold text-slate-800 hover:text-emerald-600">
                                {faq.question}
                            </AccordionTrigger>
                            <AccordionContent className="text-slate-600 text-base leading-relaxed">
                                {faq.answer}
                            </AccordionContent>
                        </AccordionItem>
                    ))}
                </Accordion>
            </div>
        </PublicLayout>
    );
}
