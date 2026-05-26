import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { DataTable } from '@/Components/UI/DataTable';

interface JobTask {
    id: number;
    title: string;
    points: number;
    user_limit: number;
    users_count: number;
    required_rank: number;
    completed_feedbacks_count: number;
    paused: number;
}

interface IndexProps {
    jobs: {
        data: JobTask[];
        links: any[];
        meta: any;
    };
}

export default function Index({ jobs }: IndexProps) {
    const headers = [
        { key: 'title', label: 'Title', sortable: true },
        { 
            key: 'points', 
            label: 'Points', 
            sortable: true,
            render: (job: JobTask) => (
                <span className="at-badge at-badge-primary fw-bold" style={{ fontSize: '0.7rem' }}>
                    {job.points}
                </span>
            )
        },
        { 
            key: 'slots_left', 
            label: 'Slots Left',
            render: (job: JobTask) => Math.max(0, job.user_limit - job.users_count)
        },
        { key: 'required_rank', label: 'Required Rank', sortable: true },
    ];

    const actions = [
        {
            type: 'link',
            label: 'Review Feedback',
            href: (job: JobTask) => route('admin.job-tasks.feedback', job.id),
            icon: 'fas fa-comments',
            variant: 'outline-dark',
            size: 'sm',
            render: (job: JobTask) => {
                return (
                    <Link 
                        href={route('admin.job-tasks.feedback', job.id)} 
                        className="btn btn-sm btn-outline-dark rounded-0 d-flex align-items-center gap-2"
                    >
                        Review Feedback
                        {job.completed_feedbacks_count > 0 && (
                            <span className="badge bg-dark rounded-0">{job.completed_feedbacks_count}</span>
                        )}
                    </Link>
                );
            }
        },
        {
            type: 'link',
            label: 'Edit',
            href: (job: JobTask) => route('admin.job-tasks.edit', job.id),
            icon: 'fas fa-edit',
            variant: 'outline-dark',
            size: 'sm',
            title: 'Edit'
        },
        {
            type: 'button',
            label: 'Pause',
            icon: 'fas fa-pause',
            variant: 'outline-dark',
            size: 'sm',
            title: 'Pause',
            onclick: (job: JobTask) => {
                if (confirm('Are you sure you want to pause this task?')) {
                    router.post(route('admin.job-tasks.pause', job.id));
                }
            }
        },
        {
            type: 'button',
            label: 'Stop',
            icon: 'fas fa-stop',
            variant: 'outline-dark',
            size: 'sm',
            title: 'Stop',
            onclick: (job: JobTask) => {
                if (confirm('Are you sure you want to stop this task?')) {
                    router.post(route('admin.job-tasks.stop', job.id));
                }
            }
        }
    ];

    return (
        <AdminSidebarLayout title="Job Tasks" header="Job Tasks">
            <Head title="Job Tasks" />

            <div className="dashboard-container at-mobile-scroll-fix admin-table-mobile-cards">
                <PageHeader
                    title="Micro Tasks"
                    subtitle="Manage your micro tasks and track progress."
                    actions={
                        <Link href={route('admin.job-tasks.create')} className="at-btn at-btn-primary touch-target">
                            <i className="fas fa-plus me-2"></i> Add Micro Task
                        </Link>
                    }
                />

                <div className="at-card admin-table-card mt-4">
                    <div className="card-body p-0">
                        <DataTable 
                            headers={headers}
                            items={jobs.data}
                            actions={actions}
                            pagination={jobs}
                            hover={true}
                            striped={false}
                        />
                    </div>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
