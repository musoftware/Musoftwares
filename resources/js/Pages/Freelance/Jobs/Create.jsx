import { useForm } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';
import FreelanceLayout from '../Layout';

export default function CreateJob({ auth }) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        budget: '',
        currency_code: 'USD',
        type: 'fixed',
        duration: '',
        skills: [],
    });

    const [availableSkills, setAvailableSkills] = useState([]);
    const pointsCost = 10;

    useEffect(() => {
        axios.get(route('freelance.skills.index')).then((response) => {
            setAvailableSkills(response.data);
        });
    }, []);

    const submit = (e) => {
        e.preventDefault();
        post(route('freelance.jobs.store'));
    };

    const handleSkillToggle = (skillId) => {
        const newSkills = data.skills.includes(skillId)
            ? data.skills.filter((id) => id !== skillId)
            : [...data.skills, skillId];
        setData('skills', newSkills);
    };

    return (
        <FreelanceLayout auth={auth}>
            <div className="mx-auto max-w-2xl">
                <h2 className="mb-6 text-2xl font-bold">Post a New Job</h2>

                <div className="mb-6 border-l-4 border-yellow-400 bg-yellow-50 p-4">
                    <p className="text-sm text-yellow-700">
                        Posting a job costs <strong>{pointsCost} points</strong>
                        . You currently have {auth.user.points_balance || 0}{' '}
                        points.
                    </p>
                </div>

                {errors.points && (
                    <div className="mb-4 text-red-500">{errors.points}</div>
                )}

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
                                Budget ({data.currency_code})
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

                    <div className="mb-4">
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

                    <div className="mb-6">
                        <label className="mb-2 block text-sm font-bold text-gray-700">
                            Required Skills
                        </label>
                        <div className="flex flex-wrap gap-2">
                            {availableSkills.map((skill) => (
                                <button
                                    type="button"
                                    key={skill.id}
                                    onClick={() => handleSkillToggle(skill.id)}
                                    className={`rounded-full px-3 py-1 text-sm ${data.skills.includes(skill.id) ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                                >
                                    {skill.name}
                                </button>
                            ))}
                        </div>
                        {errors.skills && (
                            <p className="mt-1 text-xs text-red-500 italic">
                                {errors.skills}
                            </p>
                        )}
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="focus:shadow-outline w-full rounded bg-blue-600 px-4 py-2 font-bold text-white hover:bg-blue-700 focus:outline-none"
                    >
                        Publish Job (-{pointsCost} Points)
                    </button>
                </form>
            </div>
        </FreelanceLayout>
    );
}
