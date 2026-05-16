import React from 'react';
import FreelanceLayout from '../Layout';
import { Link, useForm } from '@inertiajs/react';

export default function BrowseJobs({ auth, jobs }) {
    return (
        <FreelanceLayout auth={auth}>
            <div className="mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Browse Jobs</h2>
                <p className="text-gray-600">Find the perfect project for your skills.</p>
            </div>

            {/* Basic filter placeholder - can be expanded later */}
            <div className="mb-6 flex gap-2">
                <input type="text" placeholder="Search skills or keywords..." className="shadow border rounded py-2 px-3 flex-grow" />
                <button className="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300">Filter</button>
            </div>

            {jobs.data.length === 0 ? (
                <div className="text-center py-10 text-gray-500">
                    No open jobs found.
                </div>
            ) : (
                <div className="space-y-4">
                    {jobs.data.map(job => (
                        <div key={job.id} className="border p-4 rounded-lg shadow-sm hover:shadow-md transition">
                            <div className="flex justify-between items-start">
                                <div>
                                    <h3 className="text-xl font-semibold">
                                        <Link href={route('freelance.jobs.show', job.id)} className="text-blue-600 hover:underline">
                                            {job.title}
                                        </Link>
                                    </h3>
                                    <p className="text-gray-500 text-sm mt-1">Posted by {job.client?.name}</p>
                                </div>
                                <div className="text-right">
                                    <div className="font-bold text-lg">{job.currency_code} {job.budget}</div>
                                    <div className="text-sm text-gray-500 capitalize">{job.type} • {job.duration}</div>
                                </div>
                            </div>

                            <p className="text-gray-700 mt-4 line-clamp-3">{job.description}</p>

                            <div className="mt-4 flex flex-wrap gap-2">
                                {job.skills?.map(skill => (
                                    <span key={skill.id} className="bg-gray-100 text-gray-700 px-2 py-1 text-xs rounded">
                                        {skill.name}
                                    </span>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </FreelanceLayout>
    );
}
