import React from 'react';
import { __ } from '@/lib/i18n';
import { Briefcase, ArrowRight, CheckCircle2 } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Link } from '@inertiajs/react';

interface Project {
    id: number;
    name: string;
    status: string;
    due_date: string;
    budget: string;
    currency: { symbol: string } | null;
}

export default function ManagerProjectsList({ projects = [] }: { projects: Project[] }) {
    if (!projects || projects.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center p-6 text-sm text-slate-500 border border-dashed rounded-lg bg-slate-50/30">
                <div className="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                    <CheckCircle2 className="w-5 h-5 text-slate-400" />
                </div>
                <span className="font-medium text-slate-700">{__('general.no_active_projects')}</span>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {projects.map((project) => (
                <div key={project.id} className="flex items-center justify-between p-3 border rounded-lg hover:bg-slate-50 transition-colors">
                    <div className="flex items-start gap-3">
                        <div className="mt-0.5">
                            <div className="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                <Briefcase className="w-4 h-4 text-blue-600" />
                            </div>
                        </div>
                        <div className="flex flex-col">
                            <span className="font-medium text-slate-900">{project.name}</span>
                            <div className="flex items-center gap-2 mt-1">
                                <Badge variant="secondary" className="text-[10px] px-1.5 h-4 font-normal">
                                    {project.status}
                                </Badge>
                                <span className="text-xs text-slate-500">
                                    {__('general.due')}: <span className="font-medium">{project.due_date}</span>
                                </span>
                                {project.budget && (
                                    <span className="text-xs text-slate-500">
                                        • {project.currency?.symbol || '$'}{project.budget}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                    
                    <Button variant="ghost" size="sm" asChild className="text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50">
                        <Link href={`/erp/projects/${project.id}`}>
                            {__('general.view')}
                            <ArrowRight className="w-4 h-4 ml-1" />
                        </Link>
                    </Button>
                </div>
            ))}
        </div>
    );
}
