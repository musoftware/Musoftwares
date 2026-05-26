import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { DataTable } from '@/Components/UI/DataTable';

interface User {
    id: number;
    name: string;
}

interface FeedbackItem {
    content: string;
    reject_reason: string | null;
}

interface UserJobFeedback {
    id: number;
    user: User;
    status: string;
    created_at: string;
    feedbacks: FeedbackItem[];
}

interface JobTask {
    id: number;
    title: string;
}

interface FeedbackProps {
    jobTask: JobTask;
    feedbacks: UserJobFeedback[];
}

export default function Feedback({ jobTask, feedbacks }: FeedbackProps) {
    const [rejectReason, setRejectReason] = useState<string>('');
    const [showRejectModal, setShowRejectModal] = useState<number | null>(null);

    const handleApprove = (id: number) => {
        if (confirm('Are you sure you want to approve this feedback?')) {
            router.put(route('admin.job-tasks.approve-feedback', id));
        }
    };

    const handleReject = (id: number) => {
        if (!rejectReason) {
            alert('Please enter a rejection reason.');
            return;
        }
        router.delete(route('admin.job-tasks.reject-feedback', id), {
            data: { reject_reason: rejectReason },
            onSuccess: () => {
                setShowRejectModal(null);
                setRejectReason('');
            }
        });
    };

    const headers = [
        { key: 'user.name', label: 'User', sortable: true },
        { 
            key: 'content', 
            label: 'Feedback Content',
            render: (item: UserJobFeedback) => (
                <div>
                    {item.feedbacks.map((f, i) => (
                        <p key={i} className="mb-1">{f.content}</p>
                    ))}
                </div>
            )
        },
        { key: 'status', label: 'Status', sortable: true },
        { key: 'created_at', label: 'Submitted At', sortable: true },
    ];

    const actions = [
        {
            type: 'button',
            label: 'Approve',
            icon: 'fas fa-check',
            variant: 'success',
            size: 'sm',
            visible: (item: UserJobFeedback) => item.status === 'completed',
            onclick: (item: UserJobFeedback) => handleApprove(item.id)
        },
        {
            type: 'button',
            label: 'Reject',
            icon: 'fas fa-times',
            variant: 'danger',
            size: 'sm',
            visible: (item: UserJobFeedback) => item.status === 'completed',
            onclick: (item: UserJobFeedback) => setShowRejectModal(item.id)
        }
    ];

    return (
        <AdminSidebarLayout title="Job Task Feedback" header="Job Task Feedback">
            <Head title={`Feedback: ${jobTask.title}`} />

            <div className="dashboard-container at-mobile-scroll-fix admin-table-mobile-cards">
                <PageHeader
                    title={`Feedback: ${jobTask.title}`}
                    subtitle="Review user feedback for this task."
                    actions={
                        <Link href={route('admin.job-tasks.index')} className="at-btn at-btn-ghost touch-target">
                            <i className="fas fa-arrow-left me-2"></i> Back to Tasks
                        </Link>
                    }
                />

                <div className="at-card admin-table-card mt-4">
                    <div className="card-body p-0">
                        <DataTable 
                            headers={headers}
                            items={feedbacks}
                            actions={actions}
                            emptyMessage="No feedback submitted yet."
                            hover={true}
                            striped={false}
                        />
                    </div>
                </div>
            </div>

            {/* Simple Inline Reject Modal equivalent */}
            {showRejectModal && (
                <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content border-0 rounded-0 shadow-lg">
                            <div className="modal-header border-bottom-0 pb-0">
                                <h5 className="modal-title fw-bold">Reject Feedback</h5>
                                <button type="button" className="btn-close" onClick={() => setShowRejectModal(null)}></button>
                            </div>
                            <div className="modal-body">
                                <label className="form-label fw-bold">Rejection Reason</label>
                                <textarea 
                                    className="form-control" 
                                    rows={3}
                                    value={rejectReason}
                                    onChange={(e) => setRejectReason(e.target.value)}
                                    placeholder="Explain why this feedback is rejected..."
                                    required
                                ></textarea>
                            </div>
                            <div className="modal-footer border-top-0 pt-0">
                                <button type="button" className="at-btn at-btn-ghost" onClick={() => setShowRejectModal(null)}>Cancel</button>
                                <button type="button" className="at-btn at-btn-danger" onClick={() => handleReject(showRejectModal)}>Confirm Rejection</button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AdminSidebarLayout>
    );
}
