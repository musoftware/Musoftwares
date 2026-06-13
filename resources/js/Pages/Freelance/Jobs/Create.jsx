import React, { useState, useEffect } from 'react';
import FreelanceLayout from '../Layout';
import { useForm, router } from '@inertiajs/react';
import axios from 'axios';
import { useFreelanceMode } from '@/Components/Freelance/FreelanceModeContext';
import { cn } from '@/lib/utils';
import { Zap, Info } from 'lucide-react';
import { CurrencySelect } from '@/Components/CurrencySelect';
import { __ } from '@/lib/i18n';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

function FieldTooltip({ text }) {
    return (
        <TooltipProvider delayDuration={200}>
            <Tooltip>
                <TooltipTrigger type="button" tabIndex={-1} className="inline-flex items-center ml-1.5 focus:outline-none translate-y-0.5">
                    <Info className="h-4 w-4 text-gray-400 hover:text-indigo-500 transition-colors" />
                </TooltipTrigger>
                <TooltipContent className="max-w-xs text-sm font-normal">
                    {text}
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
}

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
        service_type: 'remote',
        country: '',
        city: '',
        district: '',
        latitude: '',
        longitude: '',
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

        const msg = `${__('general.you_need')} ${neededPoints} ${__('freelance.more_points_to_publish_this')} ${neededPoints} ${__('general.points_for')} ${costFormatted}?`;
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
                    <h2 className="text-3xl font-bold text-gray-900">{__('freelance.post_a_new_job')}</h2>
                    <p className="text-gray-600 mt-2">{__('erp.find_the_right_talent_for')}</p>
                </div>

                <form onSubmit={submit} className="bg-white p-8 rounded-xl border border-gray-200 shadow-sm space-y-8">

                    {/* Basic Info */}
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold border-b pb-2">{__('general.basic_information')}</h3>

                        <div>
                            <label className="flex items-center text-sm font-bold text-gray-700 mb-1">
                                {__('freelance.job_title')}
                                <FieldTooltip text={__('freelance.job_title_help')} />
                            </label>
                            <input
                                type="text"
                                value={data.title}
                                onChange={e => setData('title', e.target.value)}
                                placeholder={__('general.eg_full_stack_developer_needed')}
                                className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            {errors.title && <p className="text-red-500 text-xs mt-1">{errors.title}</p>}
                        </div>

                        <div>
                            <label className="flex items-center text-sm font-bold text-gray-700 mb-1">
                                {__('general.description')}
                                <FieldTooltip text={__('freelance.job_description_help')} />
                            </label>
                            <div className="border border-gray-300 rounded-lg shadow-sm overflow-hidden focus-within:ring-1 focus-within:ring-indigo-500 focus-within:border-indigo-500">
                                {/* Simple Textarea without mock toolbar */}
                                <textarea
                                    value={data.description}
                                    onChange={e => setData('description', e.target.value)}
                                    placeholder={__('erp.describe_the_project_scope_deliverables')}
                                    className="w-full border-0 focus:ring-0 p-4 min-h-[200px] resize-y"
                                ></textarea>
                            </div>
                            {errors.description && <p className="text-red-500 text-xs mt-1">{errors.description}</p>}
                        </div>
                    </div>

                    {/* Service Type & Location */}
                    <div className="space-y-4">
                        <h3 className="flex items-center text-lg font-semibold border-b pb-2">
                            {__('freelance.service_type_and_location')}
                            <FieldTooltip text={__('freelance.service_type_help')} />
                        </h3>

                        <div className="flex gap-4 mb-4">
                            <label className={`flex-1 flex items-center justify-center border-2 rounded-lg py-3 cursor-pointer transition ${data.service_type === 'remote' ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 hover:border-indigo-200'}`}>
                                <input type="radio" className="sr-only" name="service_type" value="remote" checked={data.service_type === 'remote'} onChange={() => setData('service_type', 'remote')} />
                                <span className="font-bold">{__('freelance.remote_service')}</span>
                            </label>
                            <label className={`flex-1 flex items-center justify-center border-2 rounded-lg py-3 cursor-pointer transition ${data.service_type === 'visit' ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 hover:border-indigo-200'}`}>
                                <input type="radio" className="sr-only" name="service_type" value="visit" checked={data.service_type === 'visit'} onChange={() => setData('service_type', 'visit')} />
                                <span className="font-bold">{__('freelance.on_site_visit')}</span>
                            </label>
                        </div>

                        {data.service_type === 'visit' && (
                            <div className="space-y-4 p-4 bg-gray-50 border rounded-lg">
                                <h4 className="font-medium text-gray-700">{__('freelance.visit_location')}</h4>
                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label className="block text-sm font-bold text-gray-700 mb-1">{__('general.country')}</label>
                                        <input type="text" value={data.country} onChange={e => setData('country', e.target.value)} className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500" />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-bold text-gray-700 mb-1">{__('general.city')}</label>
                                        <input type="text" value={data.city} onChange={e => setData('city', e.target.value)} className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500" />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-bold text-gray-700 mb-1">{__('general.district')}</label>
                                        <input type="text" value={data.district} onChange={e => setData('district', e.target.value)} className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500" />
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Budget & Duration */}
                    <div className="space-y-4">
                        <h3 className="flex items-center text-lg font-semibold border-b pb-2">
                            {__('erp.budget_duration')}
                            <FieldTooltip text={__('freelance.budget_duration_help')} />
                        </h3>

                        <div className="flex gap-4 mb-4">
                            <label className={`flex-1 flex items-center justify-center border-2 rounded-lg py-3 cursor-pointer transition ${data.type === 'fixed' ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 hover:border-indigo-200'}`}>
                                <input type="radio" className="sr-only" name="type" value="fixed" checked={data.type === 'fixed'} onChange={() => setData('type', 'fixed')} />
                                <span className="font-bold">{__('general.fixed_price')}</span>
                            </label>
                            <label className={`flex-1 flex items-center justify-center border-2 rounded-lg py-3 cursor-pointer transition ${data.type === 'hourly' ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 hover:border-indigo-200'}`}>
                                <input type="radio" className="sr-only" name="type" value="hourly" checked={data.type === 'hourly'} onChange={() => setData('type', 'hourly')} />
                                <span className="font-bold">{__('general.hourly_rate')}</span>
                            </label>
                        </div>

                        <div className="flex flex-col sm:flex-row gap-4">
                            <div className="w-full sm:w-1/3">
                                <label className="flex items-center text-sm font-bold text-gray-700 mb-1">
                                    {__('general.currency')}
                                    <FieldTooltip text={__('freelance.job_currency_help')} />
                                </label>
                                <CurrencySelect
                                    currencies={currencies}
                                    value={data.currency_id}
                                    onChange={val => setData('currency_id', parseInt(val))}
                                    valueKey="id"
                                />
                            </div>
                            <div className="w-full sm:w-1/3">
                                <label className="flex items-center text-sm font-bold text-gray-700 mb-1">
                                    {__('erp.project_budget')}
                                    <FieldTooltip text={__('freelance.project_budget_help')} />
                                </label>
                                <input
                                    type="number"
                                    value={data.budget}
                                    onChange={e => setData('budget', e.target.value)}
                                    placeholder={__('general.eg_500')}
                                    className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                />
                                {errors.budget && <p className="text-red-500 text-xs mt-1">{errors.budget}</p>}
                            </div>
                            <div className="w-full sm:w-1/3">
                                <label className="flex items-center text-sm font-bold text-gray-700 mb-1">
                                    {__('freelance.min_proposal_bid_points')}
                                    <FieldTooltip text={__('freelance.min_proposal_points_help')} />
                                </label>
                                <input
                                    type="number"
                                    value={data.min_proposal_points}
                                    onChange={e => setData('min_proposal_points', e.target.value)}
                                    placeholder={__('general.eg')}
                                    className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                />
                                {errors.min_proposal_points && <p className="text-red-500 text-xs mt-1">{errors.min_proposal_points}</p>}
                            </div>
                        </div>

                        <div>
                            <label className="flex items-center text-sm font-bold text-gray-700 mb-1">
                                {__('general.expected_duration')}
                                <FieldTooltip text={__('freelance.expected_duration_help')} />
                            </label>
                            <input
                                type="text"
                                value={data.duration}
                                onChange={e => setData('duration', e.target.value)}
                                placeholder={__('erp.expires_in_x_days_or')}
                                className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>
                    </div>

                    {/* Skills */}
                    <div className="space-y-4">
                        <h3 className="flex items-center text-lg font-semibold border-b pb-2">
                            {__('general.required_skills')}
                            <FieldTooltip text={__('freelance.required_skills_help')} />
                        </h3>
                        <p className="text-sm text-gray-500 mb-2">{__('general.these_skills_will_trigger_notifications')}</p>

                        {/* Selected Skills */}
                        {data.skills.length > 0 && (
                            <div className="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-4 space-y-3">
                                {data.skills.map(skill => (
                                    <div key={skill.id} className="flex items-center justify-between bg-white p-2 border rounded shadow-sm">
                                        <span className="font-medium text-gray-800">{skill.name}</span>
                                        <div className="flex items-center gap-4">
                                            <label className="flex items-center gap-2 cursor-pointer">
                                                <span className="text-sm text-gray-600">{__('general.required')}</span>
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
                                placeholder={__('general.search_to_add_skills')}
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
                                {__('erp.publishing_this_job_costs')} <strong>{pointsCost} {__('general.points')}</strong>
                            </p>
                            {currentPoints < pointsCost ? (
                                <p className="text-sm text-amber-700 font-semibold bg-amber-100/60 px-3 py-1 rounded-full inline-flex items-center gap-1.5 border border-amber-200/50">
                                    {__('general.insufficient_balance')} {currentPoints} {__('general.pts')} ({__('general.need')} {pointsCost - currentPoints} {__('general.pts_more')})
                                </p>
                            ) : (
                                <p className="text-sm text-indigo-700 flex items-center justify-center gap-2">
                                    {__('general.your_balance')} {currentPoints} {__('general.pts')} <span className="text-xl">&rarr;</span> {__('general.after')} <span className="font-bold">{currentPoints - pointsCost} {__('general.pts')}</span>
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
                                <Zap className="h-5 w-5 fill-amber-300 stroke-amber-100 animate-pulse" /> {__('general.buy')} {pointsCost - currentPoints} {__('general.points_to_publish')}
                            </button>
                        ) : (
                            <button
                                type="submit"
                                disabled={processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg shadow-sm transition text-lg w-full md:w-auto"
                            >
                                {__('freelance.publish_job')} — {pointsCost} {__('general.pts')}
                            </button>
                        )}
                    </div>
                </form>
            </div>
        </FreelanceLayout>
    );
}
