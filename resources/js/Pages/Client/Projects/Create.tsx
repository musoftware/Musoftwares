import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, FolderKanban, Sparkles } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { __ } from '@/lib/i18n';

export default function CreateProject() {
    const { data, setData, post, processing, errors } = useForm({
        project_name: '',
        description: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('client.projects.store-new'));
    };

    return (
        <AuthenticatedLayout>
            <Head title={__('general.new_project') || 'New Project'} />
            <div className="mx-auto w-full max-w-3xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                
                {/* Header */}
                <header className="space-y-4">
                    <Link
                        href={route('client.projects.index')}
                        className="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400 transition-colors hover:text-slate-700"
                    >
                        <ArrowLeft className="h-4 w-4" /> {__('general.back_to_projects') || 'Back to Projects'}
                    </Link>
                    <div>
                        <h1 className="flex items-center gap-2 text-3xl font-extrabold tracking-tight text-slate-900">
                            <FolderKanban className="h-8 w-8 text-indigo-500" />
                            {__('general.start_new_project') || 'Start a New Project'}
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Create your project instantly. You can manage and adjust all details via a WhatsApp-style conversation.
                        </p>
                    </div>
                </header>

                <Card className="rounded-2xl border-slate-200/80 shadow-md">
                    <CardHeader className="border-b border-slate-50 bg-slate-50/50 px-6 py-5">
                        <CardTitle className="text-base font-bold text-slate-800 flex items-center gap-2">
                            <Sparkles className="h-5 w-5 text-indigo-500 animate-pulse" />
                            Project Scope Details
                        </CardTitle>
                        <CardDescription className="text-xs text-slate-400">
                            Give your project a name and briefly explain what you want to achieve.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-6">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            
                            {/* Project Name */}
                            <div className="space-y-2">
                                <label className="text-xs font-extrabold uppercase tracking-wider text-slate-500">
                                    Project Name <span className="text-rose-500">*</span>
                                </label>
                                <Input
                                    type="text"
                                    placeholder="e.g., E-Commerce App, Corporate Website"
                                    value={data.project_name}
                                    onChange={(e) => setData('project_name', e.target.value)}
                                    className="rounded-xl border-slate-250 focus:ring-indigo-500"
                                    required
                                />
                                {errors.project_name && (
                                    <p className="text-xs text-rose-500 font-medium">{errors.project_name}</p>
                                )}
                            </div>

                            {/* Initial Description */}
                            <div className="space-y-2">
                                <label className="text-xs font-extrabold uppercase tracking-wider text-slate-500">
                                    Initial Description / Message
                                </label>
                                <Textarea
                                    placeholder="Describe your project, features required, budget constraints, or timeline..."
                                    rows={5}
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="rounded-xl border-slate-250 focus:ring-indigo-500"
                                />
                                {errors.description && (
                                    <p className="text-xs text-rose-500 font-medium">{errors.description}</p>
                                )}
                                <span className="text-[10px] text-slate-400 italic block">
                                    Tip: You can change or add features anytime using the WhatsApp chat screen once the project is created!
                                </span>
                            </div>

                            {/* Buttons */}
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                                <Link href={route('client.projects.index')}>
                                    <Button type="button" variant="outline" className="rounded-xl px-5">
                                        Cancel
                                    </Button>
                                </Link>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="rounded-xl bg-slate-900 hover:bg-slate-800 text-white px-6 font-bold"
                                >
                                    {processing ? 'Creating...' : 'Create Project'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

            </div>
        </AuthenticatedLayout>
    );
}
