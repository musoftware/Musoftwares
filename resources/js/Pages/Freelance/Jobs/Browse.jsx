import React, { useState } from 'react';
import FreelanceLayout from '../Layout';
import { Link, useForm } from '@inertiajs/react';
import { useFreelanceMode } from '@/Components/Freelance/FreelanceModeContext';

export default function BrowseJobs({ auth, jobs }) {
    const { mode, setMode } = useFreelanceMode();
    const [searchTerm, setSearchTerm] = useState('');
    const [budgetRange, setBudgetRange] = useState(1500);

    // Force Freelancer Mode if accessed directly
    React.useEffect(() => {
        if (mode !== 'freelancer') {
            setMode('freelancer');
        }
    }, []);

    // Mock my skills for the "🔥 NEW" badge logic
    const mySkills = ['Laravel', 'React', 'MySQL'];

    // Fallback jobs to show mockup if backend provides none
    const displayJobs = jobs?.data?.length ? jobs.data : [
        {
            id: 1, title: 'Full Stack Developer', budget: '500 - 1,500', currency_code: '$', type: 'fixed', duration: '2 weeks',
            client: { name: 'Acme Corp' }, description: 'Need an experienced developer to build a SaaS dashboard...',
            skills: [{id: 1, name: 'Laravel'}, {id: 2, name: 'React'}, {id: 3, name: 'MySQL'}],
            created_at: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
            proposals_count: 3
        }
    ];

    const isMatch = (jobSkills) => {
        if (!jobSkills) return false;
        return jobSkills.some(skill => mySkills.includes(skill.name));
    };

    return (
        <FreelanceLayout auth={auth}>
            <div className="flex flex-col md:flex-row gap-6">

                {/* Left Sidebar: Filters (25%) */}
                <div className="w-full md:w-1/4">
                    <div className="bg-white p-5 rounded-lg border shadow-sm sticky top-6">
                        <h3 className="text-lg font-bold mb-4">Filters</h3>

                        <div className="space-y-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input
                                    type="text"
                                    placeholder="Keywords..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Skills</label>
                                <select multiple className="w-full border-gray-300 rounded-md shadow-sm h-24">
                                    <option>Laravel</option>
                                    <option>React</option>
                                    <option>Vue.js</option>
                                    <option>Tailwind CSS</option>
                                    <option>MySQL</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Budget Max: ${budgetRange}</label>
                                <input
                                    type="range"
                                    min="100"
                                    max="5000"
                                    value={budgetRange}
                                    onChange={(e) => setBudgetRange(e.target.value)}
                                    className="w-full accent-indigo-600"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                <div className="flex gap-4">
                                    <label className="inline-flex items-center">
                                        <input type="checkbox" className="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" defaultChecked />
                                        <span className="ml-2 text-sm text-gray-600">Fixed</span>
                                    </label>
                                    <label className="inline-flex items-center">
                                        <input type="checkbox" className="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" defaultChecked />
                                        <span className="ml-2 text-sm text-gray-600">Hourly</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                                <select className="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option>Newest</option>
                                    <option>Budget: High to Low</option>
                                    <option>Budget: Low to High</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Right Side: Jobs Grid (75%) */}
                <div className="w-full md:w-3/4">
                    <div className="mb-6 flex justify-between items-center">
                        <div>
                            <h2 className="text-2xl font-bold text-gray-800">Browse Jobs</h2>
                            <p className="text-gray-600">Find the perfect project for your skills.</p>
                        </div>
                        <div className="text-sm text-gray-500">
                            Showing {displayJobs.length} results
                        </div>
                    </div>

                    {!displayJobs.length ? (
                        <div className="text-center py-16 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                            <span className="text-4xl">🔍</span>
                            <h3 className="mt-4 text-lg font-medium text-gray-900">No jobs found</h3>
                            <p className="mt-1 text-gray-500">Try adjusting your search or filters.</p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {displayJobs.map(job => {
                                const matched = isMatch(job.skills);
                                const isNew = new Date(job.created_at) > new Date(Date.now() - 24 * 60 * 60 * 1000);

                                return (
                                <div key={job.id} className={`border rounded-lg p-6 bg-white transition hover:shadow-md ${matched ? 'border-indigo-200 shadow-sm' : ''}`}>
                                    <div className="flex justify-between items-start mb-2">
                                        <div>
                                            <h3 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                                                {matched && isNew && <span className="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded uppercase tracking-wider font-bold">🔥 NEW</span>}
                                                {job.title}
                                            </h3>
                                        </div>
                                    </div>

                                    <div className="text-sm font-semibold text-gray-900 mb-3">
                                        Budget: {job.currency_code}{job.budget}
                                    </div>

                                    <div className="flex flex-wrap gap-2 mb-4">
                                        <span className="text-sm font-medium text-gray-500 mr-1">Skills:</span>
                                        {job.skills?.map(skill => (
                                            <span key={skill.id} className="bg-gray-100 text-gray-700 px-2.5 py-0.5 text-xs rounded-full font-medium">
                                                {skill.name}
                                            </span>
                                        ))}
                                    </div>

                                    <div className="flex justify-between items-end mt-6 pt-4 border-t border-gray-100">
                                        <div className="text-sm text-gray-500 flex flex-col gap-1">
                                            <span>Posted: {isNew ? '2 hours ago' : new Date(job.created_at).toLocaleDateString()}</span>
                                            <span>Proposals: {job.proposals_count || 0}</span>
                                        </div>
                                        <div>
                                            <Link
                                                href={route('freelance.jobs.show', job.id)}
                                                className="inline-flex items-center justify-center bg-indigo-50 text-indigo-700 font-semibold py-2 px-4 rounded-lg hover:bg-indigo-100 transition"
                                            >
                                                View Job — 5 pts &rarr;
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </FreelanceLayout>
    );
}
