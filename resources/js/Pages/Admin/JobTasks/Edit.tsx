import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { PageHeader } from '@/Components/UI/PageHeader';

interface JobTask {
    id: number;
    title: string;
    mission: string;
    description: string;
    notice: string | null;
    points: number;
    user_limit: number;
    required_rank: number;
    completion_policy: string;
}

interface EditProps {
    jobTask: JobTask;
}

export default function Edit({ jobTask }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        title: jobTask.title,
        mission: jobTask.mission,
        description: jobTask.description,
        notice: jobTask.notice || '',
        points: jobTask.points,
        user_limit: jobTask.user_limit,
        required_rank: jobTask.required_rank,
        completion_policy: jobTask.completion_policy
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('admin.job-tasks.update', jobTask.id));
    };

    return (
        <AdminSidebarLayout title="Edit Job Task" header="Edit Job Task">
            <Head title="Edit Job Task" />

            <div className="dashboard-container at-mobile-scroll-fix">
                <PageHeader
                    title="Edit Task"
                    subtitle="Edit micro task details."
                    actions={
                        <Link href={route('admin.job-tasks.index')} className="at-btn at-btn-ghost touch-target">
                            <i className="fas fa-arrow-left me-2"></i> Back to Tasks
                        </Link>
                    }
                />

                <div className="at-card mt-4 mobile-card">
                    <div className="card-body">
                        {errors.pointsToConvert && (
                            <div className="alert alert-danger mb-4">
                                {errors.pointsToConvert}
                            </div>
                        )}
                        <form onSubmit={submit} className="mobile-stack">
                            <div className="row g-4">
                                <div className="col-md-12">
                                    <label className="form-label fw-bold">Title</label>
                                    <input 
                                        type="text" 
                                        className={`form-control ${errors.title ? 'is-invalid' : ''}`}
                                        value={data.title}
                                        onChange={e => setData('title', e.target.value)}
                                        required 
                                    />
                                    {errors.title && <div className="invalid-feedback">{errors.title}</div>}
                                </div>

                                <div className="col-md-12">
                                    <label className="form-label fw-bold">Mission Summary</label>
                                    <textarea 
                                        className={`form-control ${errors.mission ? 'is-invalid' : ''}`}
                                        rows={2}
                                        value={data.mission}
                                        onChange={e => setData('mission', e.target.value)}
                                        required 
                                    ></textarea>
                                    {errors.mission && <div className="invalid-feedback">{errors.mission}</div>}
                                </div>

                                <div className="col-md-12">
                                    <label className="form-label fw-bold">Full Description & Instructions</label>
                                    <textarea 
                                        className={`form-control ${errors.description ? 'is-invalid' : ''}`}
                                        rows={5}
                                        value={data.description}
                                        onChange={e => setData('description', e.target.value)}
                                        required 
                                    ></textarea>
                                    {errors.description && <div className="invalid-feedback">{errors.description}</div>}
                                </div>

                                <div className="col-md-12">
                                    <label className="form-label fw-bold">Important Notice (Optional)</label>
                                    <textarea 
                                        className={`form-control ${errors.notice ? 'is-invalid' : ''}`}
                                        rows={2}
                                        value={data.notice}
                                        onChange={e => setData('notice', e.target.value)}
                                    ></textarea>
                                    {errors.notice && <div className="invalid-feedback">{errors.notice}</div>}
                                </div>

                                <div className="col-md-6 col-lg-3">
                                    <label className="form-label fw-bold">Points per User</label>
                                    <input 
                                        type="number" 
                                        className={`form-control ${errors.points ? 'is-invalid' : ''}`}
                                        min="1"
                                        value={data.points}
                                        onChange={e => setData('points', parseInt(e.target.value))}
                                        required 
                                    />
                                    {errors.points && <div className="invalid-feedback">{errors.points}</div>}
                                </div>

                                <div className="col-md-6 col-lg-3">
                                    <label className="form-label fw-bold">User Limit (Slots)</label>
                                    <input 
                                        type="number" 
                                        className={`form-control ${errors.user_limit ? 'is-invalid' : ''}`}
                                        min="1"
                                        value={data.user_limit}
                                        onChange={e => setData('user_limit', parseInt(e.target.value))}
                                        required 
                                    />
                                    {errors.user_limit && <div className="invalid-feedback">{errors.user_limit}</div>}
                                    <small className="text-muted d-block mt-1">
                                        Total Points Required: <strong>{data.points * data.user_limit}</strong>
                                    </small>
                                </div>

                                <div className="col-md-6 col-lg-3">
                                    <label className="form-label fw-bold">Required Rank</label>
                                    <input 
                                        type="number" 
                                        className={`form-control ${errors.required_rank ? 'is-invalid' : ''}`}
                                        min="1"
                                        value={data.required_rank}
                                        onChange={e => setData('required_rank', parseInt(e.target.value))}
                                        required 
                                    />
                                    {errors.required_rank && <div className="invalid-feedback">{errors.required_rank}</div>}
                                </div>

                                <div className="col-md-6 col-lg-3">
                                    <label className="form-label fw-bold">Completion Policy</label>
                                    <select 
                                        className={`form-select ${errors.completion_policy ? 'is-invalid' : ''}`}
                                        value={data.completion_policy}
                                        onChange={e => setData('completion_policy', e.target.value)}
                                        required
                                    >
                                        <option value="once">Once per User</option>
                                        <option value="multiple">Multiple Times Allowed</option>
                                    </select>
                                    {errors.completion_policy && <div className="invalid-feedback">{errors.completion_policy}</div>}
                                </div>

                                <div className="col-12 mt-4 text-end">
                                    <button 
                                        type="submit" 
                                        className="at-btn at-btn-primary touch-target mobile-full-width" 
                                        disabled={processing}
                                    >
                                        {processing ? 'Saving...' : 'Save Changes'}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
