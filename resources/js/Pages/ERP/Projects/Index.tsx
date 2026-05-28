import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
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
    planning:    { label: 'Planning',    color: 'bg-blue-50 text-blue-600 border-blue-200' },
    active:      { label: 'Active',      color: 'bg-emerald-50 text-emerald-600 border-emerald-200' },
    on_hold:     { label: 'On Hold',     color: 'bg-amber-50 text-amber-600 border-amber-200' },
    completed:   { label: 'Completed',   color: 'bg-teal-50 text-teal-600 border-teal-200' },
    cancelled:   { label: 'Cancelled',   color: 'bg-red-50 text-red-600 border-red-200' },
};

export default function ProjectsIndex({ projects }: Props) {
    const stats = {
        total:     projects.length,
        active:    projects.filter(p => p.status === 'active').length,
        completed: projects.filter(p => p.status === 'completed').length,
    };

    const { menuItems, workspaceName, tenantId } = useERPMenu('projects');

    return (
        <ERPLayout title="Projects" workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems}>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Projects</h1>
                        <p className="text-slate-500 text-sm mt-1">{stats.total} projects · {stats.active} active</p>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-3 gap-4">
                    {[
                        { label: 'Total Projects', value: stats.total, icon: FolderOpen },
                        { label: 'Active', value: stats.active, icon: Clock },
                        { label: 'Completed', value: stats.completed, icon: CheckCircle },
                    ].map(({ label, value, icon: Icon }) => (
                        <Card key={label} className="bg-white border border-slate-200 shadow-sm">
                            <CardContent className="p-4 flex items-center gap-3">
                                <div className="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center">
                                    <Icon className="w-4 h-4 text-primary" />
                                </div>
                                <div>
                                    <p className="text-xl font-bold text-slate-900">{value}</p>
                                    <p className="text-xs text-slate-500">{label}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Projects Grid */}
                {projects.length === 0 ? (
                    <div className="text-center py-20">
                        <FolderOpen className="w-12 h-12 text-slate-300 mx-auto mb-4" />
                        <p className="text-slate-600 text-lg font-medium">No projects yet</p>
                        <p className="text-slate-500 text-sm mt-1">Projects are created when converting appointments or from client profiles.</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {projects.map(project => {
                            const cfg = statusConfig[project.status] ?? { label: project.status, color: 'bg-slate-100 text-slate-500' };
                            return (
                                <Card key={project.id} className="bg-white border border-slate-200 shadow-sm hover:border-slate-300 hover:shadow transition-all group">
                                    <CardContent className="p-5 space-y-4">
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="flex-1">
                                                <h3 className="text-slate-900 font-semibold text-sm leading-tight">{project.name}</h3>
                                                {project.client && (
                                                    <Link href={route('erp.clients.show', project.client.id)}
                                                        className="text-xs text-primary hover:underline mt-0.5 block">
                                                        {project.client.name}
                                                    </Link>
                                                )}
                                            </div>
                                            <Badge className={`text-xs border capitalize shrink-0 ${cfg.color}`}>{cfg.label}</Badge>
                                        </div>

                                        {project.description && (
                                            <p className="text-slate-500 text-xs line-clamp-2">{project.description}</p>
                                        )}

                                        <div className="flex items-center justify-between pt-1">
                                            <div className="flex items-center gap-3 text-xs text-slate-500">
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
        </ERPLayout>
    );
}
