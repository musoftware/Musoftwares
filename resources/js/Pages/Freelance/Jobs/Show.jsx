import React, { useState } from 'react';
import FreelanceLayout from '../Layout';
import { useForm, router } from '@inertiajs/react';
import { useFreelanceMode } from '@/Components/Freelance/FreelanceModeContext';

export default function ShowJob({ auth, job: initialJob }) {
    const { mode } = useFreelanceMode();
    // Use fallback mock job if backend provides none
    const job = initialJob || {
        id: 1, title: 'Full Stack Developer', budget: '500 - 1,500', currency_code: '$', type: 'fixed', duration: '2 weeks',
        client: { name: 'Acme Corp' }, description: '<h2>Project Overview</h2><p>Need an experienced developer to build a SaaS dashboard using Laravel and React.</p><ul><li>User auth</li><li>Stripe integration</li><li>Admin panel</li></ul>',
        skills: [{id: 1, name: 'Laravel'}, {id: 2, name: 'React'}, {id: 3, name: 'MySQL'}],
        status: 'open', client_id: mode === 'client' ? auth.user.id : 999,
        proposals: mode === 'client' ? [
            { id: 1, freelancer: { name: 'Alice Smith' }, bid_amount: '1200.00', delivery_days: 14, cover_letter: "I can build this fast.", status: 'pending', currency_code: '$' },
            { id: 2, freelancer: { name: 'Bob Jones' }, bid_amount: '800.00', delivery_days: 20, cover_letter: "I have 5 years experience.", status: 'pending', currency_code: '$' }
        ] : []
    };

    const isClient = mode === 'client';

    const hasSubmitted = !isClient && job.proposals?.some(p => p.freelancer_id === auth.user.id);
    const pointsCost = 5;

    const { data, setData, post, processing, errors } = useForm({
        job_id: job.id,
        bid_amount: '',
        delivery_days: '',
        cover_letter: '',
        currency_code: job.currency_code
    });

    const submitProposal = (e) => {
        e.preventDefault();
        post(route('freelance.proposals.store'));
    };

    const handleAccept = (proposalId) => {
        router.post(route('freelance.proposals.accept', proposalId));
    };

    const handleReject = (proposalId) => {
        router.post(route('freelance.proposals.reject', proposalId));
    };

    return (
        <FreelanceLayout auth={auth}>
            <div className="flex flex-col md:flex-row gap-8 max-w-6xl mx-auto">
                {/* Left Side: Job Details & Proposals (65%) */}
                <div className="w-full md:w-[65%]">
                    <div className="bg-white rounded-lg p-6 shadow-sm border border-gray-100">
                        <div className="flex justify-between items-start mb-6 border-b border-gray-100 pb-6">
                            <h1 className="text-3xl font-bold text-gray-900">{job.title}</h1>
                            <span className={`px-3 py-1 text-xs rounded-full font-bold uppercase tracking-wider ${
                                job.status === 'open' ? 'bg-green-100 text-green-800' :
                                job.status === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                                'bg-gray-100 text-gray-800'
                            }`}>
                                {job.status}
                            </span>
                        </div>

                        {/* Description (Rich Text) */}
                        <div className="prose max-w-none text-gray-700 mb-8" dangerouslySetInnerHTML={{ __html: job.description }}></div>

                        {/* Skills */}
                        <div className="mb-8 border-t border-gray-100 pt-6">
                            <h3 className="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Skills and Expertise</h3>
                            <div className="flex flex-wrap gap-2">
                                {job.skills?.map(skill => (
                                    <span key={skill.id} className="bg-gray-100 text-gray-700 px-3 py-1 text-sm rounded-full font-medium">
                                        {skill.name}
                                    </span>
                                ))}
                            </div>
                        </div>

                        {/* Client View: Proposals List */}
                        {isClient && (
                            <div className="mt-8 pt-8 border-t border-gray-100">
                                <h2 className="text-2xl font-bold mb-6">Proposals Received ({job.proposals?.length || 0})</h2>

                                {!job.proposals || job.proposals.length === 0 ? (
                                    <p className="text-gray-500 text-center py-8">No proposals received yet.</p>
                                ) : (
                                    <div className="space-y-6">
                                        {job.proposals.map(proposal => (
                                            <div key={proposal.id} className="border border-gray-200 rounded-lg p-6 bg-white shadow-sm hover:shadow-md transition">
                                                <div className="flex justify-between items-start mb-4">
                                                    <div>
                                                        <h3 className="font-bold text-lg">{proposal.freelancer?.name}</h3>
                                                        <div className="text-sm text-gray-500 mt-1">
                                                            Delivery: {proposal.delivery_days} days
                                                        </div>
                                                    </div>
                                                    <div className="text-right">
                                                        <div className="text-xl font-bold text-indigo-600">{proposal.currency_code} {proposal.bid_amount}</div>
                                                    </div>
                                                </div>

                                                <div className="bg-gray-50 p-4 rounded mb-4 whitespace-pre-wrap text-sm text-gray-700">
                                                    {proposal.cover_letter}
                                                </div>

                                                {job.status === 'open' && proposal.status === 'pending' && (
                                                    <div className="flex space-x-3 mt-4">
                                                        <button
                                                            onClick={() => handleAccept(proposal.id)}
                                                            className="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm font-semibold transition"
                                                        >
                                                            Accept & Create Contract
                                                        </button>
                                                        <button
                                                            onClick={() => handleReject(proposal.id)}
                                                            className="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 text-sm font-semibold transition"
                                                        >
                                                            Reject
                                                        </button>
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>

                {/* Right Side: Action Card & Summary (35%) */}
                <div className="w-full md:w-[35%]">
                    <div className="bg-white p-6 rounded-lg border border-gray-100 shadow-sm sticky top-6">

                        {/* Job Summary */}
                        <div className="space-y-4 mb-6 border-b border-gray-100 pb-6">
                            <div>
                                <p className="text-sm text-gray-500">Budget Range</p>
                                <p className="font-bold text-lg text-gray-900">{job.currency_code}{job.budget}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-500">Project Type</p>
                                <p className="font-medium text-gray-900 capitalize">{job.type}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-500">Deadline / Duration</p>
                                <p className="font-medium text-gray-900">{job.duration || 'Not specified'}</p>
                            </div>
                        </div>

                        {/* Actions based on mode */}
                        {isClient ? (
                            <div>
                                <div className="bg-indigo-50 p-4 rounded-lg border border-indigo-100 mb-4">
                                    <h4 className="font-bold text-indigo-900">Activity Stats</h4>
                                    <p className="text-indigo-700 mt-1">{job.proposals?.length || 0} Proposals received</p>
                                </div>
                                <button className="w-full border-2 border-indigo-600 text-indigo-600 font-bold py-2 px-4 rounded-lg hover:bg-indigo-50 transition">
                                    View All Proposals &rarr;
                                </button>
                            </div>
                        ) : (
                            <div>
                                {job.status !== 'open' ? (
                                    <div className="bg-gray-100 text-center p-4 rounded-lg font-medium text-gray-600 border border-gray-200">
                                        This job is no longer accepting proposals.
                                    </div>
                                ) : hasSubmitted ? (
                                    <div className="bg-green-50 text-green-800 p-4 rounded-lg border border-green-200 text-center font-medium shadow-sm">
                                        Proposal Submitted ✅
                                    </div>
                                ) : (
                                    <div>
                                        <div className="text-center mb-6 bg-indigo-50 py-4 rounded-lg border border-indigo-100">
                                            <p className="text-gray-600 font-medium mb-1">Required to apply:</p>
                                            <p className="font-extrabold text-indigo-600 text-2xl">{pointsCost} points</p>
                                            <p className="text-sm text-gray-500 mt-1">Current balance: {auth.user.points_balance || 340} pts</p>
                                        </div>

                                        <form onSubmit={submitProposal} className="space-y-4">
                                            {errors.points && <div className="text-red-500 text-sm bg-red-50 p-3 rounded-lg border border-red-200">{errors.points}</div>}

                                            <div>
                                                <label className="block text-sm font-bold text-gray-700 mb-1">Your Price ({job.currency_code})</label>
                                                <input
                                                    type="number"
                                                    value={data.bid_amount}
                                                    onChange={e => setData('bid_amount', e.target.value)}
                                                    className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                    required
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-bold text-gray-700 mb-1">Delivery in (days)</label>
                                                <input
                                                    type="number"
                                                    value={data.delivery_days}
                                                    onChange={e => setData('delivery_days', e.target.value)}
                                                    className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                    required
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-bold text-gray-700 mb-1">Cover Letter</label>
                                                <textarea
                                                    value={data.cover_letter}
                                                    onChange={e => setData('cover_letter', e.target.value)}
                                                    className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 h-32"
                                                    placeholder="Why are you the best fit?"
                                                    required
                                                ></textarea>
                                            </div>

                                            <button
                                                type="submit"
                                                disabled={processing || (auth.user.points_balance || 340) < pointsCost}
                                                className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg shadow-sm transition disabled:opacity-50"
                                            >
                                                Submit Proposal — {pointsCost} pts
                                            </button>
                                        </form>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </FreelanceLayout>
    );
}
