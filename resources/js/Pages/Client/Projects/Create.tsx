import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, FolderKanban, Sparkles } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { __ } from '@/lib/i18n';

export default function CreateProject() {
    const { data, setData, post, processing, errors } = useForm({
        project_name: '',
        description: '',
    });

    React.useEffect(() => {
        if (typeof window !== 'undefined') {
            const params = new URLSearchParams(window.location.search);
            const prefill = params.get('prefill_desc');
            if (prefill) {
                setData('description', decodeURIComponent(prefill));
            }
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('client.projects.store-new'));
    };

    return (
        <AuthenticatedLayout>
            <Head title={`${__('general.new_project') || 'New Project'} — Musoftwares Studio`} />

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* Hero Header */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto space-y-1.5">
                        <Link
                            href={route('client.projects.index')}
                            className="inline-flex items-center text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] transition-colors mb-1"
                        >
                            <ArrowLeft className="me-1.5 h-3.5 w-3.5" />
                            {__('general.back_to_projects') || 'Back to Projects'}
                        </Link>
                        <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                            {__('general.start_new_project') || 'Start a New Project'}
                        </h1>
                        <p className="text-xs sm:text-sm text-[#1d1d1f]/60 font-sans">
                            Create your project workspace instantly. Scope details and sprint timelines will be tracked automatically.
                        </p>
                    </div>
                </div>

                {/* Form Container */}
                <div className="max-w-[900px] mx-auto px-6 sm:px-10 py-8 space-y-6">
                    
                    <div className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 shadow-sm space-y-6">
                        
                        <div className="flex items-center gap-3 pb-4 border-b border-black/5">
                            <div className="w-10 h-10 rounded-xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                <Sparkles className="w-5 h-5" />
                            </div>
                            <div>
                                <h2 className="text-base font-bold text-[#1d1d1f] font-sans">
                                    Project Scope &amp; Details
                                </h2>
                                <p className="text-xs text-[#1d1d1f]/60">
                                    Give your project a name and briefly explain what you want to achieve.
                                </p>
                            </div>
                        </div>

                        {/* Presets */}
                        <div className="space-y-2">
                            <label className="text-[11px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50 block">
                                {__('general.start_with_preset') || 'Or start with a preset template:'}
                            </label>
                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                {[
                                    {
                                        title: __('presets.ecommerce_title') || 'E-Commerce App',
                                        name: __('presets.ecommerce_name') || 'My Online Store',
                                        desc: __('presets.ecommerce_desc') || 'I want an online store to sell retail products...',
                                    },
                                    {
                                        title: __('presets.whatsapp_title') || 'WhatsApp Bot',
                                        name: __('presets.whatsapp_name') || 'WhatsApp Marketing Assistant',
                                        desc: __('presets.whatsapp_desc') || 'I need an automated WhatsApp chatbot helper...',
                                    },
                                    {
                                        title: __('presets.corporate_title') || 'Corporate Web',
                                        name: __('presets.corporate_name') || 'Company Portfolio Website',
                                        desc: __('presets.corporate_desc') || 'I need a professional landing page...',
                                    },
                                    {
                                        title: __('presets.saas_title') || 'SaaS Client Portal',
                                        name: __('presets.saas_name') || 'Custom SaaS Client Portal',
                                        desc: __('presets.saas_desc') || 'I want a secure subscription client portal...',
                                    },
                                ].map((preset, idx) => (
                                    <button
                                        key={idx}
                                        type="button"
                                        onClick={() => {
                                            setData((prev) => ({
                                                ...prev,
                                                project_name: preset.name,
                                                description: preset.desc,
                                            }));
                                        }}
                                        className="flex flex-col items-start p-3.5 text-start bg-[#f5f5f7] hover:bg-[#0071e3]/5 hover:border-[#0071e3]/30 border border-black/5 rounded-2xl transition-all cursor-pointer group"
                                    >
                                        <span className="text-xs font-bold text-[#1d1d1f] group-hover:text-[#0071e3] transition-colors">{preset.title}</span>
                                        <span className="text-[10px] text-[#1d1d1f]/50 mt-1">{__('general.quick_select') || 'Quick Select'}</span>
                                    </button>
                                ))}
                            </div>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-6 pt-2">
                            
                            {/* Project Name */}
                            <div className="space-y-2">
                                <label className="text-xs font-semibold text-[#1d1d1f] block">
                                    Project Name <span className="text-rose-500">*</span>
                                </label>
                                <Input
                                    type="text"
                                    placeholder="e.g., E-Commerce App, Corporate Website"
                                    value={data.project_name}
                                    onChange={(e) => setData('project_name', e.target.value)}
                                    className="h-11 rounded-xl bg-white border-black/10 text-xs sm:text-sm font-semibold focus:ring-2 focus:ring-[#0071e3]"
                                    required
                                />
                                {errors.project_name && (
                                    <p className="text-xs text-rose-600 font-medium">{errors.project_name}</p>
                                )}
                            </div>

                            {/* Initial Description */}
                            <div className="space-y-2">
                                <label className="text-xs font-semibold text-[#1d1d1f] block">
                                    Initial Description / Scope
                                </label>
                                <Textarea
                                    placeholder="Describe your project, required features, budget constraints, or expected timeline..."
                                    rows={5}
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="rounded-xl bg-white border-black/10 text-xs sm:text-sm focus:ring-2 focus:ring-[#0071e3]"
                                />
                                {errors.description && (
                                    <p className="text-xs text-rose-600 font-medium">{errors.description}</p>
                                )}
                                <span className="text-[11px] text-[#1d1d1f]/50 italic block">
                                    Tip: You can refine or add features anytime using the real-time project board after creation.
                                </span>
                            </div>

                            {/* Action Buttons */}
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-black/5">
                                <Link
                                    href={route('client.projects.index')}
                                    className="px-5 py-2.5 text-xs font-semibold text-[#1d1d1f]/70 hover:text-[#1d1d1f] rounded-full transition-colors"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing || !data.project_name.trim()}
                                    className="px-6 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] disabled:opacity-50 text-white text-xs font-semibold rounded-[980px] shadow-sm shadow-blue-500/20 transition-all cursor-pointer"
                                >
                                    {processing ? 'Creating...' : 'Create Project Workspace'}
                                </button>
                            </div>
                        </form>

                    </div>

                </div>

            </div>
        </AuthenticatedLayout>
    );
}
