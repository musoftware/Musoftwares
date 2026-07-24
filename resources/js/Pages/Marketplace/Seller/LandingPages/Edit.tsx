import React, { useState } from 'react';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Globe,
    Sparkles,
    Save,
    Plus,
    Trash2,
    CheckCircle2,
    Eye,
    HelpCircle,
    Sliders,
    Share2,
    Layers
} from 'lucide-react';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { SellerNav } from '@/Components/Marketplace/Seller/SellerNav';
import { __ } from '@/lib/i18n';

interface EditLandingPageProps {
    service: {
        id: number;
        title: string;
    };
    landingPage: {
        id: number;
        slug: string;
        hero_title: string;
        hero_description?: string;
        hero_cta_text?: string;
        template: string;
        is_active: boolean;
        description?: string;
        meta_title?: string;
        meta_description?: string;
        facebook_pixel_id?: string;
        google_analytics_id?: string;
        sticky_cta_enabled?: boolean;
        sticky_cta_text?: string;
        exit_intent_enabled?: boolean;
        exit_intent_title?: string;
        exit_intent_message?: string;
        ab_testing_enabled?: boolean;
        faqs?: any[];
        questions?: any[];
        variants?: any[];
    };
}

export default function Edit({ service, landingPage }: EditLandingPageProps) {
    const [activeTab, setActiveTab] = useState<'content' | 'faqs' | 'questions' | 'ab_testing' | 'seo'>('content');

    const [faqs, setFaqs] = useState<any[]>(landingPage?.faqs || []);
    const [questions, setQuestions] = useState<any[]>(landingPage?.questions || []);

    const { data, setData, put, processing, errors } = useForm({
        hero_title: landingPage?.hero_title || '',
        hero_description: landingPage?.hero_description || '',
        hero_cta_text: landingPage?.hero_cta_text || 'Get Started',
        slug: landingPage?.slug || '',
        template: landingPage?.template || 'modern',
        is_active: landingPage?.is_active ?? true,
        description: landingPage?.description || '',
        meta_title: landingPage?.meta_title || '',
        meta_description: landingPage?.meta_description || '',
        facebook_pixel_id: landingPage?.facebook_pixel_id || '',
        google_analytics_id: landingPage?.google_analytics_id || '',
        sticky_cta_enabled: landingPage?.sticky_cta_enabled ?? false,
        sticky_cta_text: landingPage?.sticky_cta_text || '',
        exit_intent_enabled: landingPage?.exit_intent_enabled ?? false,
        exit_intent_title: landingPage?.exit_intent_title || '',
        exit_intent_message: landingPage?.exit_intent_message || '',
        ab_testing_enabled: landingPage?.ab_testing_enabled ?? false,
        faqs: landingPage?.faqs || [],
        questions: landingPage?.questions || [],
    });

    const addFaq = () => {
        setFaqs([...faqs, { question: '', answer: '' }]);
    };

    const removeFaq = (index: number) => {
        setFaqs(faqs.filter((_, i) => i !== index));
    };

    const updateFaq = (index: number, field: string, val: string) => {
        const updated = [...faqs];
        updated[index][field] = val;
        setFaqs(updated);
        setData('faqs', updated);
    };

    const addQuestion = () => {
        setQuestions([...questions, { question_text: '', field_type: 'text', is_required: false }]);
    };

    const removeQuestion = (index: number) => {
        setQuestions(questions.filter((_, i) => i !== index));
    };

    const updateQuestion = (index: number, field: string, val: any) => {
        const updated = [...questions];
        updated[index][field] = val;
        setQuestions(updated);
        setData('questions', updated);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/marketplace/landing-pages/${service.id}/${landingPage.id}`);
    };

    return (
        <MarketplaceLayout>
            <Head title={`Edit Landing Page — ${service?.title}`} />

            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="space-y-1">
                        <Link
                            href="/marketplace/landing-pages"
                            className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900 transition"
                        >
                            <ArrowLeft className="w-3.5 h-3.5" />
                            Back to Landing Pages
                        </Link>
                        <h2 className="text-xl font-bold text-slate-900">
                            Landing Page Builder: {service?.title}
                        </h2>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link
                            href={`/s/${landingPage.slug}`}
                            target="_blank"
                            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-medium transition"
                        >
                            <Eye className="w-4 h-4" />
                            View Public Page
                        </Link>
                        <button
                            onClick={handleSubmit}
                            disabled={processing}
                            className="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-slate-900 text-white text-xs font-medium hover:bg-slate-800 transition shadow-sm"
                        >
                            <Save className="w-4 h-4" />
                            Save Changes
                        </button>
                    </div>
                </div>

                <SellerNav />

                {/* Tab Navigation */}
                <div className="border-b border-slate-200 flex space-x-6 text-sm font-medium">
                    <button
                        onClick={() => setActiveTab('content')}
                        className={`pb-3 border-b-2 transition ${activeTab === 'content' ? 'border-indigo-600 text-indigo-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-800'}`}
                    >
                        Hero & Content
                    </button>
                    <button
                        onClick={() => setActiveTab('faqs')}
                        className={`pb-3 border-b-2 transition ${activeTab === 'faqs' ? 'border-indigo-600 text-indigo-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-800'}`}
                    >
                        FAQs Builder ({faqs.length})
                    </button>
                    <button
                        onClick={() => setActiveTab('questions')}
                        className={`pb-3 border-b-2 transition ${activeTab === 'questions' ? 'border-indigo-600 text-indigo-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-800'}`}
                    >
                        Lead Form Fields ({questions.length})
                    </button>
                    <button
                        onClick={() => setActiveTab('ab_testing')}
                        className={`pb-3 border-b-2 transition ${activeTab === 'ab_testing' ? 'border-indigo-600 text-indigo-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-800'}`}
                    >
                        A/B Testing & Popups
                    </button>
                    <button
                        onClick={() => setActiveTab('seo')}
                        className={`pb-3 border-b-2 transition ${activeTab === 'seo' ? 'border-indigo-600 text-indigo-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-800'}`}
                    >
                        SEO & Analytics Pixels
                    </button>
                </div>

                <OperationalCard>
                    <form onSubmit={handleSubmit} className="p-6 space-y-6">
                        {activeTab === 'content' && (
                            <div className="space-y-4">
                                <div className="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                                    <div>
                                        <div className="font-semibold text-slate-900 text-sm">Published Status</div>
                                        <div className="text-xs text-slate-500">Make this landing page accessible to public visitors.</div>
                                    </div>
                                    <input
                                        type="checkbox"
                                        checked={data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                        className="h-5 w-5 text-indigo-600 rounded focus:ring-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">Hero Main Title</label>
                                    <input
                                        type="text"
                                        value={data.hero_title}
                                        onChange={(e) => setData('hero_title', e.target.value)}
                                        className="w-full rounded-xl border border-slate-200 py-2.5 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                        required
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">Hero Subtitle</label>
                                    <textarea
                                        rows={3}
                                        value={data.hero_description}
                                        onChange={(e) => setData('hero_description', e.target.value)}
                                        className="w-full rounded-xl border border-slate-200 p-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">Full Section Body / Description</label>
                                    <textarea
                                        rows={5}
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        className="w-full rounded-xl border border-slate-200 p-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                        placeholder="Detailed service explanation, features list, and deliverables..."
                                    />
                                </div>
                            </div>
                        )}

                        {activeTab === 'faqs' && (
                            <div className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <h4 className="font-semibold text-slate-900 text-sm">Frequently Asked Questions</h4>
                                    <button
                                        type="button"
                                        onClick={addFaq}
                                        className="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-800"
                                    >
                                        <Plus className="w-3.5 h-3.5" /> Add FAQ Item
                                    </button>
                                </div>

                                {faqs.map((faq, index) => (
                                    <div key={index} className="border border-slate-200 rounded-xl p-4 space-y-3 bg-slate-50/50">
                                        <div className="flex items-center justify-between">
                                            <span className="text-xs font-bold text-slate-400">FAQ #{index + 1}</span>
                                            <button type="button" onClick={() => removeFaq(index)} className="text-red-500 hover:text-red-700">
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </div>
                                        <input
                                            type="text"
                                            placeholder="Question..."
                                            value={faq.question}
                                            onChange={(e) => updateFaq(index, 'question', e.target.value)}
                                            className="w-full rounded-lg border border-slate-200 p-2 text-sm"
                                        />
                                        <textarea
                                            rows={2}
                                            placeholder="Answer..."
                                            value={faq.answer}
                                            onChange={(e) => updateFaq(index, 'answer', e.target.value)}
                                            className="w-full rounded-lg border border-slate-200 p-2 text-sm"
                                        />
                                    </div>
                                ))}
                            </div>
                        )}

                        {activeTab === 'questions' && (
                            <div className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <h4 className="font-semibold text-slate-900 text-sm">Custom Questionnaire Fields</h4>
                                    <button
                                        type="button"
                                        onClick={addQuestion}
                                        className="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-800"
                                    >
                                        <Plus className="w-3.5 h-3.5" /> Add Question Field
                                    </button>
                                </div>

                                {questions.map((q, index) => (
                                    <div key={index} className="border border-slate-200 rounded-xl p-4 space-y-3 bg-slate-50/50">
                                        <div className="flex items-center justify-between">
                                            <span className="text-xs font-bold text-slate-400">Field #{index + 1}</span>
                                            <button type="button" onClick={() => removeQuestion(index)} className="text-red-500 hover:text-red-700">
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </div>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <input
                                                type="text"
                                                placeholder="Question Label (e.g. Your Website URL)"
                                                value={q.question_text}
                                                onChange={(e) => updateQuestion(index, 'question_text', e.target.value)}
                                                className="w-full rounded-lg border border-slate-200 p-2 text-sm"
                                            />
                                            <select
                                                value={q.field_type || 'text'}
                                                onChange={(e) => updateQuestion(index, 'field_type', e.target.value)}
                                                className="w-full rounded-lg border border-slate-200 p-2 text-sm"
                                            >
                                                <option value="text">Text Input</option>
                                                <option value="textarea">Multi-line Textarea</option>
                                                <option value="select">Dropdown Select</option>
                                            </select>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {activeTab === 'ab_testing' && (
                            <div className="space-y-4">
                                <div className="flex items-center justify-between p-3 bg-indigo-50/50 rounded-xl border border-indigo-100">
                                    <div>
                                        <div className="font-semibold text-slate-900 text-sm">Enable A/B Testing</div>
                                        <div className="text-xs text-slate-500">Automatically split visitor traffic across variant pages to test headlines.</div>
                                    </div>
                                    <input
                                        type="checkbox"
                                        checked={data.ab_testing_enabled}
                                        onChange={(e) => setData('ab_testing_enabled', e.target.checked)}
                                        className="h-5 w-5 text-indigo-600 rounded focus:ring-indigo-500"
                                    />
                                </div>
                            </div>
                        )}

                        {activeTab === 'seo' && (
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">SEO Meta Title</label>
                                    <input
                                        type="text"
                                        value={data.meta_title}
                                        onChange={(e) => setData('meta_title', e.target.value)}
                                        className="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-sm"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">SEO Meta Description</label>
                                    <textarea
                                        rows={3}
                                        value={data.meta_description}
                                        onChange={(e) => setData('meta_description', e.target.value)}
                                        className="w-full rounded-xl border border-slate-200 p-3 text-sm"
                                    />
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">Facebook Pixel ID</label>
                                        <input
                                            type="text"
                                            value={data.facebook_pixel_id}
                                            onChange={(e) => setData('facebook_pixel_id', e.target.value)}
                                            className="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm font-mono"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">Google Analytics ID</label>
                                        <input
                                            type="text"
                                            value={data.google_analytics_id}
                                            onChange={(e) => setData('google_analytics_id', e.target.value)}
                                            className="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm font-mono"
                                        />
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 transition shadow-sm"
                            >
                                <Save className="w-4 h-4" />
                                Save Landing Page
                            </button>
                        </div>
                    </form>
                </OperationalCard>
            </div>
        </MarketplaceLayout>
    );
}
