import { Link } from '@inertiajs/react';
import FreelanceLayout from '../Layout';

export default function MyJobs({ auth, jobs }) {
    return (
        <FreelanceLayout auth={auth}>
            <div className="mb-6 flex items-center justify-between">
                <h2 className="text-2xl font-bold text-gray-800">
                    My Posted Jobs
                </h2>
                <Link
                    href={route('freelance.jobs.create')}
                    className="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                >
                    Post New Job
                </Link>
            </div>

            {jobs.data.length === 0 ? (
                <div className="py-10 text-center text-gray-500">
                    You haven't posted any jobs yet.
                </div>
            ) : (
                <div className="space-y-4">
                    {jobs.data.map((job) => (
                        <div
                            key={job.id}
                            className="rounded-lg border p-4 shadow-sm transition hover:shadow-md"
                        >
                            <div className="flex items-start justify-between">
                                <div>
                                    <h3 className="text-xl font-semibold">
                                        <Link
                                            href={route(
                                                'freelance.jobs.show',
                                                job.id,
                                            )}
                                            className="text-blue-600 hover:underline"
                                        >
                                            {job.title}
                                        </Link>
                                    </h3>
                                    <p className="mt-1 line-clamp-2 text-gray-600">
                                        {job.description}
                                    </p>
                                </div>
                                <span
                                    className={`rounded-full px-2 py-1 text-xs font-semibold ${
                                        job.status === 'open'
                                            ? 'bg-green-100 text-green-800'
                                            : job.status === 'in_progress'
                                              ? 'bg-blue-100 text-blue-800'
                                              : 'bg-gray-100 text-gray-800'
                                    }`}
                                >
                                    {job.status.toUpperCase()}
                                </span>
                            </div>

                            <div className="mt-4 flex items-center justify-between text-sm text-gray-500">
                                <div className="flex space-x-4">
                                    <span>Type: {job.type}</span>
                                    <span>
                                        Budget: {job.currency_code} {job.budget}
                                    </span>
                                    <span>
                                        Proposals:{' '}
                                        <strong className="text-blue-600">
                                            {job.proposals_count || 0}
                                        </strong>
                                    </span>
                                </div>
                                <div>
                                    <Link
                                        href={route(
                                            'freelance.jobs.show',
                                            job.id,
                                        )}
                                        className="text-blue-600 hover:underline"
                                    >
                                        View Proposals &rarr;
                                    </Link>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </FreelanceLayout>
    );
}
