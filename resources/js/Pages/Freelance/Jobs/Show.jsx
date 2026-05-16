import React, { useState } from 'react';
import FreelanceLayout from '../Layout';
import { useForm, router } from '@inertiajs/react';

export default function ShowJob({ auth, job, pointsCost }) {
    const isClient = auth.user.id === job.client_id;
    const hasSubmitted = job.proposals?.some(p => p.freelancer_id === auth.user.id);

    const { data, setData, post, processing, errors } = useForm({
        cover_letter: '',
        bid_amount: job.budget,
    });

    const [showProposalForm, setShowProposalForm] = useState(false);

    const submitProposal = (e) => {
        e.preventDefault();
        post(route('freelance.proposals.store', job.id), {
            onSuccess: () => setShowProposalForm(false)
        });
    };

    const handleAccept = (proposalId) => {
        router.post(route('freelance.proposals.accept', proposalId));
    };

    const handleReject = (proposalId) => {
        router.post(route('freelance.proposals.reject', proposalId));
    };

    return (
        <FreelanceLayout auth={auth}>
            <div className="max-w-4xl mx-auto">
                {/* Job Details Header */}
                <div className="border-b pb-6 mb-6">
                    <div className="flex justify-between items-start">
                        <h1 className="text-3xl font-bold text-gray-900">{job.title}</h1>
                        <span className={`px-3 py-1 text-sm rounded-full font-semibold ${
                            job.status === 'open' ? 'bg-green-100 text-green-800' :
                            job.status === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                            'bg-gray-100 text-gray-800'
                        }`}>
                            {job.status.toUpperCase()}
                        </span>
                    </div>

                    <div className="mt-4 flex flex-wrap gap-6 text-sm text-gray-600">
                        <div><strong className="block text-gray-900">Budget</strong> {job.currency_code} {job.budget}</div>
                        <div><strong className="block text-gray-900">Type</strong> <span className="capitalize">{job.type}</span></div>
                        <div><strong className="block text-gray-900">Duration</strong> {job.duration || 'Not specified'}</div>
                        <div><strong className="block text-gray-900">Client</strong> {job.client?.name}</div>
                    </div>
                </div>

                {/* Job Description */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-3">Job Description</h2>
                    <div className="whitespace-pre-wrap text-gray-700">{job.description}</div>
                </div>

                {/* Required Skills */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-3">Required Skills</h2>
                    <div className="flex flex-wrap gap-2">
                        {job.skills?.map(skill => (
                            <span key={skill.id} className="bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1 text-sm rounded-full">
                                {skill.name}
                            </span>
                        ))}
                    </div>
                </div>

                {/* Freelancer View: Submit Proposal */}
                {!isClient && job.status === 'open' && (
                    <div className="mt-8 pt-8 border-t">
                        {hasSubmitted ? (
                            <div className="bg-green-50 text-green-800 p-4 rounded border border-green-200">
                                You have already submitted a proposal for this job.
                            </div>
                        ) : !showProposalForm ? (
                            <button
                                onClick={() => setShowProposalForm(true)}
                                className="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg text-lg w-full md:w-auto"
                            >
                                Submit Proposal (-{pointsCost} Points)
                            </button>
                        ) : (
                            <div className="bg-gray-50 p-6 rounded-lg border">
                                <h3 className="text-xl font-bold mb-4">Submit Your Proposal</h3>
                                {errors.points && <div className="text-red-500 mb-4 bg-red-50 p-3 rounded">{errors.points}</div>}

                                <form onSubmit={submitProposal}>
                                    <div className="mb-4">
                                        <label className="block text-gray-700 text-sm font-bold mb-2">Bid Amount ({job.currency_code})</label>
                                        <input
                                            type="number"
                                            value={data.bid_amount}
                                            onChange={e => setData('bid_amount', e.target.value)}
                                            className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                        />
                                        {errors.bid_amount && <p className="text-red-500 text-xs mt-1">{errors.bid_amount}</p>}
                                    </div>

                                    <div className="mb-4">
                                        <label className="block text-gray-700 text-sm font-bold mb-2">Cover Letter</label>
                                        <textarea
                                            value={data.cover_letter}
                                            onChange={e => setData('cover_letter', e.target.value)}
                                            className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 h-32"
                                            placeholder="Introduce yourself and explain why you're a great fit..."
                                        ></textarea>
                                        {errors.cover_letter && <p className="text-red-500 text-xs mt-1">{errors.cover_letter}</p>}
                                    </div>

                                    <div className="flex space-x-4">
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded"
                                        >
                                            Submit
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setShowProposalForm(false)}
                                            className="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        )}
                    </div>
                )}

                {/* Client View: Proposals List */}
                {isClient && (
                    <div className="mt-8 pt-8 border-t">
                        <h2 className="text-2xl font-bold mb-6">Proposals ({job.proposals?.length || 0})</h2>

                        {!job.proposals || job.proposals.length === 0 ? (
                            <p className="text-gray-500 text-center py-8">No proposals received yet.</p>
                        ) : (
                            <div className="space-y-6">
                                {job.proposals.map(proposal => (
                                    <div key={proposal.id} className="border rounded-lg p-6 bg-white shadow-sm">
                                        <div className="flex justify-between items-start mb-4">
                                            <div>
                                                <h3 className="font-bold text-lg">{proposal.freelancer?.name}</h3>
                                                <div className="text-sm text-gray-500 mt-1">
                                                    Status: <span className="capitalize font-semibold">{proposal.status}</span>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <div className="text-xl font-bold text-gray-900">{proposal.currency_code} {proposal.bid_amount}</div>
                                            </div>
                                        </div>

                                        <div className="bg-gray-50 p-4 rounded mb-4 whitespace-pre-wrap text-sm text-gray-700">
                                            {proposal.cover_letter}
                                        </div>

                                        {job.status === 'open' && proposal.status === 'pending' && (
                                            <div className="flex space-x-3">
                                                <button
                                                    onClick={() => handleAccept(proposal.id)}
                                                    className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm font-semibold"
                                                >
                                                    Accept & Create Contract
                                                </button>
                                                <button
                                                    onClick={() => handleReject(proposal.id)}
                                                    className="bg-red-100 text-red-700 px-4 py-2 rounded hover:bg-red-200 text-sm font-semibold"
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
        </FreelanceLayout>
    );
}
