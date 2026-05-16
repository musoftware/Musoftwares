import React, { useState, useEffect } from 'react';
import FreelanceLayout from '../Layout';
import { useForm } from '@inertiajs/react';
import axios from 'axios';

export default function CreateJob({ auth }) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        budget: '',
        currency_code: 'USD',
        type: 'fixed',
        duration: '',
        skills: []
    });

    const [availableSkills, setAvailableSkills] = useState([]);
    const pointsCost = 10;

    useEffect(() => {
        axios.get(route('freelance.skills.index')).then(response => {
            setAvailableSkills(response.data);
        });
    }, []);

    const submit = (e) => {
        e.preventDefault();
        post(route('freelance.jobs.store'));
    };

    const handleSkillToggle = (skillId) => {
        const newSkills = data.skills.includes(skillId)
            ? data.skills.filter(id => id !== skillId)
            : [...data.skills, skillId];
        setData('skills', newSkills);
    };

    return (
        <FreelanceLayout auth={auth}>
            <div className="max-w-2xl mx-auto">
                <h2 className="text-2xl font-bold mb-6">Post a New Job</h2>

                <div className="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <p className="text-sm text-yellow-700">
                        Posting a job costs <strong>{pointsCost} points</strong>. You currently have {auth.user.points_balance || 0} points.
                    </p>
                </div>

                {errors.points && <div className="text-red-500 mb-4">{errors.points}</div>}

                <form onSubmit={submit}>
                    <div className="mb-4">
                        <label className="block text-gray-700 text-sm font-bold mb-2">Title</label>
                        <input
                            type="text"
                            value={data.title}
                            onChange={e => setData('title', e.target.value)}
                            className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        />
                        {errors.title && <p className="text-red-500 text-xs italic mt-1">{errors.title}</p>}
                    </div>

                    <div className="mb-4">
                        <label className="block text-gray-700 text-sm font-bold mb-2">Description</label>
                        <textarea
                            value={data.description}
                            onChange={e => setData('description', e.target.value)}
                            className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline h-32"
                        ></textarea>
                        {errors.description && <p className="text-red-500 text-xs italic mt-1">{errors.description}</p>}
                    </div>

                    <div className="flex space-x-4 mb-4">
                        <div className="w-1/2">
                            <label className="block text-gray-700 text-sm font-bold mb-2">Type</label>
                            <select
                                value={data.type}
                                onChange={e => setData('type', e.target.value)}
                                className="shadow border rounded w-full py-2 px-3 text-gray-700"
                            >
                                <option value="fixed">Fixed Price</option>
                                <option value="hourly">Hourly Rate</option>
                            </select>
                        </div>
                        <div className="w-1/2">
                            <label className="block text-gray-700 text-sm font-bold mb-2">Budget ({data.currency_code})</label>
                            <input
                                type="number"
                                value={data.budget}
                                onChange={e => setData('budget', e.target.value)}
                                className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                            />
                            {errors.budget && <p className="text-red-500 text-xs italic mt-1">{errors.budget}</p>}
                        </div>
                    </div>

                    <div className="mb-4">
                        <label className="block text-gray-700 text-sm font-bold mb-2">Duration (e.g. "2 weeks", "3 months")</label>
                        <input
                            type="text"
                            value={data.duration}
                            onChange={e => setData('duration', e.target.value)}
                            className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                        />
                    </div>

                    <div className="mb-6">
                        <label className="block text-gray-700 text-sm font-bold mb-2">Required Skills</label>
                        <div className="flex flex-wrap gap-2">
                            {availableSkills.map(skill => (
                                <button
                                    type="button"
                                    key={skill.id}
                                    onClick={() => handleSkillToggle(skill.id)}
                                    className={`px-3 py-1 rounded-full text-sm ${data.skills.includes(skill.id) ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                                >
                                    {skill.name}
                                </button>
                            ))}
                        </div>
                        {errors.skills && <p className="text-red-500 text-xs italic mt-1">{errors.skills}</p>}
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full"
                    >
                        Publish Job (-{pointsCost} Points)
                    </button>
                </form>
            </div>
        </FreelanceLayout>
    );
}
