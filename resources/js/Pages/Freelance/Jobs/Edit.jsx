import React, { useState, useEffect } from 'react';
import FreelanceLayout from '../Layout';
import { useForm } from '@inertiajs/react';
import axios from 'axios';

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
            <div className="max-w-2xl mx-auto">
                <h2 className="text-2xl font-bold mb-6">Edit Job: {job.title}</h2>

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
                            <label className="block text-gray-700 text-sm font-bold mb-2">Budget</label>
                            <input
                                type="number"
                                value={data.budget}
                                onChange={e => setData('budget', e.target.value)}
                                className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                            />
                            {errors.budget && <p className="text-red-500 text-xs italic mt-1">{errors.budget}</p>}
                        </div>
                    </div>

                    <div className="mb-6">
                        <label className="block text-gray-700 text-sm font-bold mb-2">Duration (e.g. "2 weeks", "3 months")</label>
                        <input
                            type="text"
                            value={data.duration}
                            onChange={e => setData('duration', e.target.value)}
                            className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full"
                    >
                        Update Job
                    </button>
                </form>
            </div>
        </FreelanceLayout>
    );
}
