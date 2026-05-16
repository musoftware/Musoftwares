import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import FreelanceLayout from '../Layout';

export default function ShowJob({ auth, job, pointsCost }) {
    const isClient = auth.user.id === job.client_id;
    const hasSubmitted = job.proposals?.some(
        (p) => p.freelancer_id === auth.user.id,
    );

    const { data, setData, post, processing, errors } = useForm({
        cover_letter: '',
        bid_amount: job.budget,
    });

    const [showProposalForm, setShowProposalForm] = useState(false);

    const submitProposal = (e) => {
        e.preventDefault();
        post(route('freelance.proposals.store', job.id), {
            onSuccess: () => setShowProposalForm(false),
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
            <div className="mx-auto max-w-4xl">
                {/* Job Details Header */}
                <div className="mb-6 border-b pb-6">
                    <div className="flex items-start justify-between">
                        <h1 className="text-3xl font-bold text-gray-900">
                            {job.title}
                        </h1>
                        <span
                            className={`rounded-full px-3 py-1 text-sm font-semibold ${
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

                    <div className="mt-4 flex flex-wrap gap-6 text-sm text-gray-600">
                        <div>
                            <strong className="block text-gray-900">
                                Budget
                            </strong>{' '}
                            {job.currency_code} {job.budget}
                        </div>
                        <div>
                            <strong className="block text-gray-900">
                                Type
                            </strong>{' '}
                            <span className="capitalize">{job.type}</span>
                        </div>
                        <div>
                            <strong className="block text-gray-900">
                                Duration
                            </strong>{' '}
                            {job.duration || 'Not specified'}
                        </div>
                        <div>
                            <strong className="block text-gray-900">
                                Client
                            </strong>{' '}
                            {job.client?.name}
                        </div>
                    </div>
                </div>

                {/* Job Description */}
                <div className="mb-8">
                    <h2 className="mb-3 text-xl font-semibold">
                        Job Description
                    </h2>
                    <div className="whitespace-pre-wrap text-gray-700">
                        {job.description}
                    </div>
                </div>

                {/* Required Skills */}
                <div className="mb-8">
                    <h2 className="mb-3 text-xl font-semibold">
                        Required Skills
                    </h2>
                    <div className="flex flex-wrap gap-2">
                        {job.skills?.map((skill) => (
                            <span
                                key={skill.id}
                                className="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-sm text-blue-700"
                            >
                                {skill.name}
                            </span>
                        ))}
                    </div>
                </div>

                {/* Freelancer View: Submit Proposal */}
                {!isClient && job.status === 'open' && (
                    <div className="mt-8 border-t pt-8">
                        {hasSubmitted ? (
                            <div className="rounded border border-green-200 bg-green-50 p-4 text-green-800">
                                You have already submitted a proposal for this
                                job.
                            </div>
                        ) : !showProposalForm ? (
                            <button
                                onClick={() => setShowProposalForm(true)}
                                className="w-full rounded-lg bg-green-600 px-6 py-3 text-lg font-bold text-white hover:bg-green-700 md:w-auto"
                            >
                                Submit Proposal (-{pointsCost} Points)
                            </button>
                        ) : (
                            <div className="rounded-lg border bg-gray-50 p-6">
                                <h3 className="mb-4 text-xl font-bold">
                                    Submit Your Proposal
                                </h3>
                                {errors.points && (
                                    <div className="mb-4 rounded bg-red-50 p-3 text-red-500">
                                        {errors.points}
                                    </div>
                                )}

                                <form onSubmit={submitProposal}>
                                    <div className="mb-4">
                                        <label className="mb-2 block text-sm font-bold text-gray-700">
                                            Bid Amount ({job.currency_code})
                                        </label>
                                        <input
                                            type="number"
                                            value={data.bid_amount}
                                            onChange={(e) =>
                                                setData(
                                                    'bid_amount',
                                                    e.target.value,
                                                )
                                            }
                                            className="w-full appearance-none rounded border px-3 py-2 text-gray-700 shadow"
                                        />
                                        {errors.bid_amount && (
                                            <p className="mt-1 text-xs text-red-500">
                                                {errors.bid_amount}
                                            </p>
                                        )}
                                    </div>

                                    <div className="mb-4">
                                        <label className="mb-2 block text-sm font-bold text-gray-700">
                                            Cover Letter
                                        </label>
                                        <textarea
                                            value={data.cover_letter}
                                            onChange={(e) =>
                                                setData(
                                                    'cover_letter',
                                                    e.target.value,
                                                )
                                            }
                                            className="h-32 w-full appearance-none rounded border px-3 py-2 text-gray-700 shadow"
                                            placeholder="Introduce yourself and explain why you're a great fit..."
                                        ></textarea>
                                        {errors.cover_letter && (
                                            <p className="mt-1 text-xs text-red-500">
                                                {errors.cover_letter}
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex space-x-4">
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="rounded bg-green-600 px-6 py-2 font-bold text-white hover:bg-green-700"
                                        >
                                            Submit
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setShowProposalForm(false)
                                            }
                                            className="rounded bg-gray-300 px-6 py-2 font-bold text-gray-800 hover:bg-gray-400"
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
                    <div className="mt-8 border-t pt-8">
                        <h2 className="mb-6 text-2xl font-bold">
                            Proposals ({job.proposals?.length || 0})
                        </h2>

                        {!job.proposals || job.proposals.length === 0 ? (
                            <p className="py-8 text-center text-gray-500">
                                No proposals received yet.
                            </p>
                        ) : (
                            <div className="space-y-6">
                                {job.proposals.map((proposal) => (
                                    <div
                                        key={proposal.id}
                                        className="rounded-lg border bg-white p-6 shadow-sm"
                                    >
                                        <div className="mb-4 flex items-start justify-between">
                                            <div>
                                                <h3 className="text-lg font-bold">
                                                    {proposal.freelancer?.name}
                                                </h3>
                                                <div className="mt-1 text-sm text-gray-500">
                                                    Status:{' '}
                                                    <span className="font-semibold capitalize">
                                                        {proposal.status}
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <div className="text-xl font-bold text-gray-900">
                                                    {proposal.currency_code}{' '}
                                                    {proposal.bid_amount}
                                                </div>
                                            </div>
                                        </div>

                                        <div className="mb-4 rounded bg-gray-50 p-4 text-sm whitespace-pre-wrap text-gray-700">
                                            {proposal.cover_letter}
                                        </div>

                                        {job.status === 'open' &&
                                            proposal.status === 'pending' && (
                                                <div className="flex space-x-3">
                                                    <button
                                                        onClick={() =>
                                                            handleAccept(
                                                                proposal.id,
                                                            )
                                                        }
                                                        className="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                                    >
                                                        Accept & Create Contract
                                                    </button>
                                                    <button
                                                        onClick={() =>
                                                            handleReject(
                                                                proposal.id,
                                                            )
                                                        }
                                                        className="rounded bg-red-100 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-200"
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
