import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { FolderOpen, Plus, Calendar, DollarSign, Clock, CheckCircle, ArrowRight } from 'lucide-react';

interface Project {
    id: number;
    name: string;
    description?: string;
    status: string;
    budget?: number;
    currency?: string;
    due_date?: string;
    created_at: string;
    client?: { id: number; name: string };
}

interface Props {
    projects: Project[];
}

const statusConfig: Record<string, { label: string; color: string }> = {
    planning:    { label: 'Planning',    color: 'bg-blue-500/15 text-blue-400 border-blue-500/30' },
    active:      { label: 'Active',      color: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' },
    on_hold:     { label: 'On Hold',     color: 'bg-amber-500/15 text-amber-400 border-amber-500/30' },
    completed:   { label: 'Completed',   color: 'bg-teal-500/15 text-teal-400 border-teal-500/30' },
    cancelled:   { label: 'Cancelled',   color: 'bg-red-500/15 text-red-400 border-red-500/30' },
};

export default function ProjectsIndex({ projects }: Props) {
    const stats = {
        total:     projects.length,
        active:    projects.filter(p => p.status === 'active').length,
        completed: projects.filter(p => p.status === 'completed').length,
    };

    return (
        <AuthenticatedLayout>
            <Head title="Projects" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-white tracking-tight">Projects</h1>
                        <p className="text-zinc-400 text-sm mt-1">{stats.total} projects · {stats.active} active</p>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-3 gap-4">
                    {[
                        { label: 'Total Projects', value: stats.total, icon: FolderOpen },
                        { label: 'Active', value: stats.active, icon: Clock },
                        { label: 'Completed', value: stats.completed, icon: CheckCircle },
                    ].map(({ label, value, icon: Icon }) => (
                        <Card key={label} className="bg-zinc-900 border-zinc-800">
                            <CardContent className="p-4 flex items-center gap-3">
                                <div className="w-9 h-9 rounded-lg bg-zinc-800 flex items-center justify-center">
                                    <Icon className="w-4 h-4 text-violet-400" />
                                </div>
                                <div>
                                    <p className="text-xl font-bold text-white">{value}</p>
                                    <p className="text-xs text-zinc-500">{label}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Projects Grid */}
                {projects.length === 0 ? (
                    <div className="text-center py-20">
                        <FolderOpen className="w-12 h-12 text-zinc-700 mx-auto mb-4" />
                        <p className="text-zinc-400 text-lg font-medium">No projects yet</p>
                        <p className="text-zinc-500 text-sm mt-1">Projects are created when converting appointments or from client profiles.</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {projects.map(project => {
                            const cfg = statusConfig[project.status] ?? { label: project.status, color: 'bg-zinc-700/50 text-zinc-400' };
                            return (
                                <Card key={project.id} className="bg-zinc-900 border-zinc-800 hover:border-zinc-700 transition-colors group">
                                    <CardContent className="p-5 space-y-4">
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="flex-1">
                                                <h3 className="text-white font-semibold text-sm leading-tight">{project.name}</h3>
                                                {project.client && (
                                                    <Link href={route('erp.clients.show', project.client.id)}
                                                        className="text-xs text-violet-400 hover:text-violet-300 mt-0.5 block">
                                                        {project.client.name}
                                                    </Link>
                                                )}
                                            </div>
                                            <Badge className={`text-xs border capitalize shrink-0 ${cfg.color}`}>{cfg.label}</Badge>
                                        </div>

                                        {project.description && (
                                            <p className="text-zinc-400 text-xs line-clamp-2">{project.description}</p>
                                        )}

                                        <div className="flex items-center justify-between pt-1">
                                            <div className="flex items-center gap-3 text-xs text-zinc-500">
                                                {project.budget && (
                                                    <span className="flex items-center gap-1">
                                                        <DollarSign className="w-3 h-3" />
                                                        {project.currency ?? 'USD'} {Number(project.budget).toLocaleString()}
                                                    </span>
                                                )}
                                                {project.due_date && (
                                                    <span className="flex items-center gap-1">
                                                        <Calendar className="w-3 h-3" />
                                                        {new Date(project.due_date).toLocaleDateString()}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
