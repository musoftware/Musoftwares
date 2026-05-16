import { useForm } from '@inertiajs/react';
import FreelanceLayout from '../Layout';

export default function EditJob({ auth, job }) {
    const { data, setData, put, processing, errors } = useForm({
        title: job.title || '',
        description: job.description || '',
        budget: job.budget || '',
        type: job.type || 'fixed',
        duration: job.duration || '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('freelance.jobs.update', job.id));
    };

    return (
        <FreelanceLayout auth={auth}>
            <div className="mx-auto max-w-2xl">
                <h2 className="mb-6 text-2xl font-bold">
                    Edit Job: {job.title}
                </h2>

                <form onSubmit={submit}>
                    <div className="mb-4">
                        <label className="mb-2 block text-sm font-bold text-gray-700">
                            Title
                        </label>
                        <input
                            type="text"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            className="focus:shadow-outline w-full appearance-none rounded border px-3 py-2 leading-tight text-gray-700 shadow focus:outline-none"
                        />
                        {errors.title && (
                            <p className="mt-1 text-xs text-red-500 italic">
                                {errors.title}
                            </p>
                        )}
                    </div>

                    <div className="mb-4">
                        <label className="mb-2 block text-sm font-bold text-gray-700">
                            Description
                        </label>
                        <textarea
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            className="focus:shadow-outline h-32 w-full appearance-none rounded border px-3 py-2 leading-tight text-gray-700 shadow focus:outline-none"
                        ></textarea>
                        {errors.description && (
                            <p className="mt-1 text-xs text-red-500 italic">
                                {errors.description}
                            </p>
                        )}
                    </div>

                    <div className="mb-4 flex space-x-4">
                        <div className="w-1/2">
                            <label className="mb-2 block text-sm font-bold text-gray-700">
                                Type
                            </label>
                            <select
                                value={data.type}
                                onChange={(e) =>
                                    setData('type', e.target.value)
                                }
                                className="w-full rounded border px-3 py-2 text-gray-700 shadow"
                            >
                                <option value="fixed">Fixed Price</option>
                                <option value="hourly">Hourly Rate</option>
                            </select>
                        </div>
                        <div className="w-1/2">
                            <label className="mb-2 block text-sm font-bold text-gray-700">
                                Budget
                            </label>
                            <input
                                type="number"
                                value={data.budget}
                                onChange={(e) =>
                                    setData('budget', e.target.value)
                                }
                                className="w-full appearance-none rounded border px-3 py-2 text-gray-700 shadow"
                            />
                            {errors.budget && (
                                <p className="mt-1 text-xs text-red-500 italic">
                                    {errors.budget}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="mb-6">
                        <label className="mb-2 block text-sm font-bold text-gray-700">
                            Duration (e.g. "2 weeks", "3 months")
                        </label>
                        <input
                            type="text"
                            value={data.duration}
                            onChange={(e) =>
                                setData('duration', e.target.value)
                            }
                            className="w-full appearance-none rounded border px-3 py-2 text-gray-700 shadow"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="focus:shadow-outline w-full rounded bg-blue-600 px-4 py-2 font-bold text-white hover:bg-blue-700 focus:outline-none"
                    >
                        Update Job
                    </button>
                </form>
            </div>
        </FreelanceLayout>
    );
}
