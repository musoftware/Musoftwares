import React, { useState } from 'react';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import {
    Briefcase,
    Plus,
    DollarSign,
    Clock,
    Send,
    User,
    CheckCircle2,
    Search,
    ChevronRight
} from 'lucide-react';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { MetricCard } from '@/Components/ui/MetricCard';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { formatMoney, formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface ProjectItem {
    id: number;
    title: string;
    description: string;
    budget: number;
    deadline?: string;
    status: string;
    created_at: string;
    user?: {
        id: number;
        name: string;
    };
}

interface ProjectsIndexProps {
    projects: {
        data: ProjectItem[];
        links: any[];
        total: number;
    };
}

export default function Index({ projects }: ProjectsIndexProps) {
    const { auth } = usePage().props as any;
    const [showPostModal, setShowPostModal] = useState(false);
    const [selectedProject, setSelectedProject] = useState<ProjectItem | null>(null);
    const [searchTerm, setSearchTerm] = useState('');

    const postForm = useForm({
        title: '',
        description: '',
        budget: '',
        deadline: '',
    });

    const proposalForm = useForm({
        price: '',
        delivery_days: 3,
        proposal_letter: '',
    });

    const handlePostSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        postForm.post('/marketplace/projects', {
            onSuccess: () => {
                postForm.reset();
                setShowPostModal(false);
            },
        });
    };

    const handleProposalSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedProject) return;

        proposalForm.post(`/marketplace/projects/${selectedProject.id}/bid`, {
            onSuccess: () => {
                proposalForm.reset();
                setSelectedProject(null);
            },
        });
    };

    const filteredProjects = (projects?.data ?? []).filter((p) => {
        const matches =
            (p.title ?? '').toLowerCase().includes(searchTerm.toLowerCase()) ||
            (p.description ?? '').toLowerCase().includes(searchTerm.toLowerCase());
        return matches;
    });


    return (
        <MarketplaceLayout>
            <Head title={__('general.custom_projects_bidding') || 'Custom Bidding Projects'} />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <ModulePageHeader
                        title={__('general.custom_projects_bidding') || 'Custom Projects & Proposals Feed'}
                        description={__('general.custom_projects_sub') || 'Post project briefs or submit competitive proposals for custom freelancer work.'}
                    />

                    <button
                        onClick={() => setShowPostModal(true)}
                        className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 transition shadow-sm"
                    >
                        <Plus className="w-4 h-4" />
                        {__('general.post_project_brief') || 'Post Project Brief'}
                    </button>
                </div>

                <OperationalCard
                    title={__('general.open_projects_feed') || 'Open Client Briefs'}
                    description={`${projects.total || projects.data.length} active projects open for bids.`}
                >
                    <div className="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div className="relative w-full sm:w-80">
                            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                type="text"
                                placeholder={__('general.search_projects') || 'Search project title or keywords...'}
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white"
                            />
                        </div>
                    </div>

                    <div className="divide-y divide-slate-100">
                        {filteredProjects.length === 0 ? (
                            <div className="p-12 text-center text-slate-400 space-y-3">
                                <Briefcase className="w-10 h-10 mx-auto text-slate-300 stroke-[1.5]" />
                                <p className="text-sm font-medium text-slate-700">No open project briefs found.</p>
                            </div>
                        ) : (
                            filteredProjects.map((project) => (
                                <div key={project.id} className="p-6 hover:bg-slate-50/50 transition flex flex-col md:flex-row md:items-center justify-between gap-4">
                                    <div className="space-y-2 max-w-3xl">
                                        <div className="flex items-center gap-2">
                                            <h4 className="font-bold text-slate-900 text-base">
                                                {project.title}
                                            </h4>
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">
                                                {project.status || 'Open'}
                                            </span>
                                        </div>

                                        <p className="text-slate-600 text-sm line-clamp-2">
                                            {project.description}
                                        </p>

                                        <div className="flex flex-wrap items-center gap-4 text-xs text-slate-500 pt-1">
                                            <span className="font-medium text-slate-700 flex items-center gap-1">
                                                <User className="w-3.5 h-3.5 text-slate-400" />
                                                Client: {project.user?.name || 'Verified Buyer'}
                                            </span>
                                            <span>•</span>
                                            <span className="font-bold text-slate-900 flex items-center gap-1">
                                                <DollarSign className="w-3.5 h-3.5 text-emerald-600" />
                                                Budget: {formatMoney(project.budget, auth?.user?.currency || 'USD')}
                                            </span>
                                            <span>•</span>
                                            <span className="flex items-center gap-1">
                                                <Clock className="w-3.5 h-3.5 text-slate-400" />
                                                Posted: {formatDate(project.created_at)}
                                            </span>
                                        </div>
                                    </div>

                                    <div className="pt-2 md:pt-0">
                                        <button
                                            onClick={() => {
                                                setSelectedProject(project);
                                                proposalForm.setData('price', String(project.budget));
                                            }}
                                            className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition shadow-sm"
                                        >
                                            <Send className="w-3.5 h-3.5" />
                                            Submit Proposal
                                        </button>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </OperationalCard>

                {/* Post Project Modal */}
                {showPostModal && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
                        <div className="bg-white rounded-2xl max-w-lg w-full p-6 space-y-6 shadow-2xl">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-semibold text-slate-900">Post Custom Project Brief</h3>
                                <button onClick={() => setShowPostModal(false)} className="text-slate-400 hover:text-slate-600">✕</button>
                            </div>

                            <form onSubmit={handlePostSubmit} className="space-y-4">
                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">Project Title</label>
                                    <input
                                        type="text"
                                        placeholder="e.g. Build Custom Laravel Inertia Web Dashboard"
                                        value={postForm.data.title}
                                        onChange={(e) => postForm.setData('title', e.target.value)}
                                        className="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                        required
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">Project Requirements & Description</label>
                                    <textarea
                                        rows={4}
                                        placeholder="Describe what you need, deliverable requirements, and expectations..."
                                        value={postForm.data.description}
                                        onChange={(e) => postForm.setData('description', e.target.value)}
                                        className="w-full rounded-xl border border-slate-200 p-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                        required
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">Budget ({typeof auth?.user?.currency === 'object' ? auth.user.currency.currency : (auth?.user?.currency || 'USD')})</label>
                                    <input
                                        type="number"
                                        placeholder="e.g. 500"
                                        value={postForm.data.budget}
                                        onChange={(e) => postForm.setData('budget', e.target.value)}
                                        className="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                        required
                                    />
                                </div>

                                <div className="flex items-center justify-end gap-3 pt-2">
                                    <button
                                        type="button"
                                        onClick={() => setShowPostModal(false)}
                                        className="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={postForm.processing}
                                        className="px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-medium hover:bg-slate-800 transition"
                                    >
                                        Publish Brief
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* Submit Proposal Modal */}
                {selectedProject && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
                        <div className="bg-white rounded-2xl max-w-lg w-full p-6 space-y-6 shadow-2xl">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-semibold text-slate-900">Submit Proposal for "{selectedProject.title}"</h3>
                                <button onClick={() => setSelectedProject(null)} className="text-slate-400 hover:text-slate-600">✕</button>
                            </div>

                            <form onSubmit={handleProposalSubmit} className="space-y-4">
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">Bid Price ({typeof auth?.user?.currency === 'object' ? auth.user.currency.currency : (auth?.user?.currency || 'USD')})</label>
                                        <input
                                            type="number"
                                            value={proposalForm.data.price}
                                            onChange={(e) => proposalForm.setData('price', e.target.value)}
                                            className="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">Delivery Time (Days)</label>
                                        <input
                                            type="number"
                                            value={proposalForm.data.delivery_days}
                                            onChange={(e) => proposalForm.setData('delivery_days', Number(e.target.value))}
                                            className="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                            required
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">Cover Letter & Pitch</label>
                                    <textarea
                                        rows={5}
                                        placeholder="Explain why you are the best fit for this project and your approach..."
                                        value={proposalForm.data.proposal_letter}
                                        onChange={(e) => proposalForm.setData('proposal_letter', e.target.value)}
                                        className="w-full rounded-xl border border-slate-200 p-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                        required
                                    />
                                </div>

                                <div className="flex items-center justify-end gap-3 pt-2">
                                    <button
                                        type="button"
                                        onClick={() => setSelectedProject(null)}
                                        className="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={proposalForm.processing}
                                        className="px-5 py-2 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition"
                                    >
                                        Submit Bid Proposal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </MarketplaceLayout>
    );
}
