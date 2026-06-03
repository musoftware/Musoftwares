import React, { useState, useEffect } from 'react';
import FreelanceLayout from '../Layout';
import { useForm, router } from '@inertiajs/react';
import axios from 'axios';
import { useFreelanceMode } from '@/Components/Freelance/FreelanceModeContext';
import { cn } from '@/lib/utils';
import { Zap } from 'lucide-react';
import { CurrencySelect } from '@/Components/CurrencySelect';
import { __ } from '@/lib/i18n';

export default function CreateJob({ auth, currencies = [], egpToPreferredRate = 1.00, preferredCurrency = 'USD' }) {
    const freelanceModeContext = useFreelanceMode();

    useEffect(() => {
        if (freelanceModeContext && freelanceModeContext.setMode) {
            freelanceModeContext.setMode('client');
        }
    }, [freelanceModeContext]);

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        budget: '',
        currency_id: auth?.user?.currency_id ?? (currencies[0]?.id ?? null),
        min_proposal_points: 0,
        type: 'fixed',
        duration: '',
        skills: []
    });

    const [availableSkills, setAvailableSkills] = useState([
        { id: 1, name: 'Laravel' },
        { id: 2, name: 'React' },
        { id: 3, name: 'Vue.js' },
        { id: 4, name: 'Node.js' },
        { id: 5, name: 'Tailwind CSS' },
        { id: 6, name: 'MySQL' },
    ]);
    const [skillSearch, setSkillSearch] = useState('');

    const pointsCost = 25 + (parseInt(data.min_proposal_points) || 0);
    const currentPoints = auth.user.points_balance !== undefined ? auth.user.points_balance : 0;

    useEffect(() => {
        // Fallback for when API doesn't exist
        try {
            axios.get(route('freelance.skills.index')).then(response => {
                if (response.data && response.data.length > 0) {
                    setAvailableSkills(response.data);
                }
            }).catch(() => {});
        } catch (e) { /* empty */ }
    }, []);

    const submit = (e) => {
        e.preventDefault();
        post(route('freelance.jobs.store'));
    };

    const handleBuyPointsToPublish = () => {
        const neededPoints = pointsCost - currentPoints;
        const cost = neededPoints * egpToPreferredRate;
        
        const costFormatted = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: preferredCurrency
        }).format(cost);

        const msg = `${__('You need')} ${neededPoints} ${__('more points to publish this job. Charge')} ${neededPoints} ${__('points for')} ${costFormatted}?`;
        if (confirm(msg)) {
            router.post(route('freelance.point-purchases.store-wallet'), { points: neededPoints });
        }
    };

    const handleSkillToggle = (skill) => {
        const existingIndex = data.skills.findIndex(s => s.id === skill.id);
        if (existingIndex >= 0) {
            const newSkills = [...data.skills];
            newSkills.splice(existingIndex, 1);
            setData('skills', newSkills);
        } else {
            setData('skills', [...data.skills, { ...skill, required: true }]);
        }
    };

    const handleSkillRequireToggle = (skillId) => {
        const newSkills = data.skills.map(s => {
            if (s.id === skillId) {
                return { ...s, required: !s.required };
            }
            return s;
        });
        setData('skills', newSkills);
    };

    const filteredSkills = availableSkills.filter(s =>
        s.name.toLowerCase().includes(skillSearch.toLowerCase()) &&
        !data.skills.some(selected => selected.id === s.id)
    );

    return (
        <FreelanceLayout>
            <div className="max-w-3xl mx-auto">
                <div className="mb-8">
                    <h2 className="text-3xl font-bold text-gray-900">{__('Post a New Job')}</h2>
                    <p className="text-gray-600 mt-2">{__('Find the right talent for your project.')}</p>
                </div>

                <form onSubmit={submit} className="bg-white p-8 rounded-xl border border-gray-200 shadow-sm space-y-8">

                    {/* Basic Info */}
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold border-b pb-2">{__('Basic Information')}</h3>

                        <div>
                            <label className="block text-sm font-bold text-gray-700 mb-1">{__('Job Title')}</label>
                            <input
                                type="text"
                                value={data.title}
                                onChange={e => setData('title', e.target.value)}
                                placeholder={__('e.g. Full Stack Developer needed for SaaS app')}
                                className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            {errors.title && <p className="text-red-500 text-xs mt-1">{errors.title}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-bold text-gray-700 mb-1">{__('Description')}</label>
                            <div className="border border-gray-300 rounded-lg shadow-sm overflow-hidden focus-within:ring-1 focus-within:ring-indigo-500 focus-within:border-indigo-500">
                                {/* Simple Textarea without mock toolbar */}
                                <textarea
                                    value={data.description}
                                    onChange={e => setData('description', e.target.value)}
                                    placeholder={__('Describe the project scope, deliverables, and any specific requirements...')}
                                    className="w-full border-0 focus:ring-0 p-4 min-h-[200px] resize-y"
                                ></textarea>
                            </div>
                            {errors.description && <p className="text-red-500 text-xs mt-1">{errors.description}</p>}
                        </div>
                    </div>

                    {/* Budget & Duration */}
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold border-b pb-2">{__('Budget & Duration')}</h3>

                        <div className="flex gap-4 mb-4">
                            <label className={`flex-1 flex items-center justify-center border-2 rounded-lg py-3 cursor-pointer transition ${data.type === 'fixed' ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 hover:border-indigo-200'}`}>
                                <input type="radio" className="sr-only" name="type" value="fixed" checked={data.type === 'fixed'} onChange={() => setData('type', 'fixed')} />
                                <span className="font-bold">{__('Fixed Price')}</span>
                            </label>
                            <label className={`flex-1 flex items-center justify-center border-2 rounded-lg py-3 cursor-pointer transition ${data.type === 'hourly' ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 hover:border-indigo-200'}`}>
                                <input type="radio" className="sr-only" name="type" value="hourly" checked={data.type === 'hourly'} onChange={() => setData('type', 'hourly')} />
                                <span className="font-bold">{__('Hourly Rate')}</span>
                            </label>
                        </div>

                        <div className="flex flex-col sm:flex-row gap-4">
                            <div className="w-full sm:w-1/3">
                                <label className="block text-sm font-bold text-gray-700 mb-1">{__('Currency')}</label>
                                <CurrencySelect
                                    currencies={currencies}
                                    value={data.currency_id}
                                    onChange={val => setData('currency_id', parseInt(val))}
                                    valueKey="id"
                                />
                            </div>
                            <div className="w-full sm:w-1/3">
                                <label className="block text-sm font-bold text-gray-700 mb-1">{__('Project Budget')}</label>
                                <input
                                    type="number"
                                    value={data.budget}
                                    onChange={e => setData('budget', e.target.value)}
                                    placeholder={__('e.g. 500')}
                                    className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                />
                                {errors.budget && <p className="text-red-500 text-xs mt-1">{errors.budget}</p>}
                            </div>
                            <div className="w-full sm:w-1/3">
                                <label className="block text-sm font-bold text-gray-700 mb-1">{__('Min Proposal Bid (Points)')}</label>
                                <input
                                    type="number"
                                    value={data.min_proposal_points}
                                    onChange={e => setData('min_proposal_points', e.target.value)}
                                    placeholder={__('e.g. 0')}
                                    className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                />
                                {errors.min_proposal_points && <p className="text-red-500 text-xs mt-1">{errors.min_proposal_points}</p>}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-bold text-gray-700 mb-1">{__('Expected Duration')}</label>
                            <input
                                type="text"
                                value={data.duration}
                                onChange={e => setData('duration', e.target.value)}
                                placeholder={__('Expires in X days, or state project timeline (e.g. 2 weeks)')}
                                className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>
                    </div>

                    {/* Skills */}
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold border-b pb-2">{__('Required Skills')}</h3>
                        <p className="text-sm text-gray-500 mb-2">{__('These skills will trigger notifications to matching freelancers.')}</p>

                        {/* Selected Skills */}
                        {data.skills.length > 0 && (
                            <div className="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-4 space-y-3">
                                {data.skills.map(skill => (
                                    <div key={skill.id} className="flex items-center justify-between bg-white p-2 border rounded shadow-sm">
                                        <span className="font-medium text-gray-800">{skill.name}</span>
                                        <div className="flex items-center gap-4">
                                            <label className="flex items-center gap-2 cursor-pointer">
                                                <span className="text-sm text-gray-600">{__('Required')}</span>
                                                <input
                                                    type="checkbox"
                                                    checked={skill.required}
                                                    onChange={() => handleSkillRequireToggle(skill.id)}
                                                    className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                />
                                            </label>
                                            <button
                                                type="button"
                                                onClick={() => handleSkillToggle(skill)}
                                                className="text-red-500 hover:text-red-700"
                                            >
                                                &times;
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Search & Add */}
                        <div className="relative">
                            <input
                                type="text"
                                value={skillSearch}
                                onChange={e => setSkillSearch(e.target.value)}
                                placeholder={__('Search to add skills...')}
                                className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            {skillSearch && filteredSkills.length > 0 && (
                                <div className="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                    {filteredSkills.map(skill => (
                                        <button
                                            key={skill.id}
                                            type="button"
                                            onClick={() => {
                                                handleSkillToggle(skill);
                                                setSkillSearch('');
                                            }}
                                            className="w-full text-left px-4 py-2 hover:bg-indigo-50 hover:text-indigo-700"
                                        >
                                            {skill.name}
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Submit & Points Preview */}
                    <div className={cn(
                        "p-6 rounded-xl border flex flex-col items-center",
                        currentPoints < pointsCost 
                            ? "bg-amber-50 border-amber-200" 
                            : "bg-indigo-50 border-indigo-100"
                    )}>
                        <div className="text-center mb-4">
                            <p className={cn(
                                "text-lg font-medium mb-1",
                                currentPoints < pointsCost ? "text-amber-900" : "text-indigo-900"
                            )}>
                                {__('Publishing this job costs')} <strong>{pointsCost} {__('points')}</strong>
                            </p>
                            {currentPoints < pointsCost ? (
                                <p className="text-sm text-amber-700 font-semibold bg-amber-100/60 px-3 py-1 rounded-full inline-flex items-center gap-1.5 border border-amber-200/50">
                                    {__('Insufficient balance:')} {currentPoints} {__('pts')} ({__('Need')} {pointsCost - currentPoints} {__('pts more')})
                                </p>
                            ) : (
                                <p className="text-sm text-indigo-700 flex items-center justify-center gap-2">
                                    {__('Your balance:')} {currentPoints} {__('pts')} <span className="text-xl">&rarr;</span> {__('after:')} <span className="font-bold">{currentPoints - pointsCost} {__('pts')}</span>
                                </p>
                            )}
                        </div>

                        {errors.points && <div className="text-red-500 mb-4 bg-red-50 p-3 rounded-lg border border-red-200 w-full text-center">{errors.points}</div>}

                        {currentPoints < pointsCost ? (
                            <button
                                type="button"
                                onClick={handleBuyPointsToPublish}
                                className="bg-amber-600 hover:bg-amber-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition text-lg w-full md:w-auto flex items-center justify-center gap-2 hover:scale-[1.02] transform duration-150"
                            >
                                <Zap className="h-5 w-5 fill-amber-300 stroke-amber-100 animate-pulse" /> {__('Buy')} {pointsCost - currentPoints} {__('Points to Publish')}
                            </button>
                        ) : (
                            <button
                                type="submit"
                                disabled={processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg shadow-sm transition text-lg w-full md:w-auto"
                            >
                                {__('Publish Job')} — {pointsCost} {__('pts')}
                            </button>
                        )}
                    </div>
                </form>
            </div>
        </FreelanceLayout>
    );
}
