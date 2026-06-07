import React, { useState } from 'react';
import { Head, useForm, router, Link } from '@inertiajs/react';
import FreelanceLayout from '../Layout';
import { useFreelanceMode } from '@/Components/Freelance/FreelanceModeContext';
import { CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import { FreelanceCard } from '@/Components/Freelance/ui/FreelanceCard';
import { FreelanceStatusPill } from '@/Components/Freelance/ui/FreelanceStatusPill';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { formatMoney, formatDate } from '@/lib/utils';
import { Clock, DollarSign, Briefcase, MapPin, CheckCircle2, AlertCircle, FileText, Send, User, Eye, Bell } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from "@/Components/ui/alert";
import { CurrencyDisplay as FinancialAmount } from '@/Components/ui/CurrencyDisplay';
import { __ } from '@/lib/i18n';

function ShowJobContent({ auth, job, pointsCost, userCurrency }: any) {
    const { mode } = useFreelanceMode();
    const isClient = mode === 'client';
    const globalCurrency = userCurrency;

    const hasSubmitted = !isClient && job.proposals?.some((p: any) => p.freelancer_id === auth.user.id);
    const userPoints = auth.user.points_balance || 0;

    const { data, setData, post, processing, errors } = useForm({
        job_id: job.id,
        bid_amount: '',
        delivery_days: '',
        cover_letter: '',
        currency_id: job.currency_id,
        points_spent: job.min_proposal_points || 2
    });

    const submitProposal = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('freelance.proposals.store'));
    };

    const handleAccept = (proposalId: number) => {
        router.post(route('freelance.proposals.accept', proposalId));
    };

    const handleReject = (proposalId: number) => {
        router.post(route('freelance.proposals.reject', proposalId));
    };

    const lastPokedAt = job.last_poked_at ? new Date(job.last_poked_at) : null;
    const canPoke = job.status === 'open' && (!lastPokedAt || (new Date().getTime() - lastPokedAt.getTime()) > 24 * 60 * 60 * 1000);

    const handlePoke = () => {
        router.post(route('freelance.jobs.poke', job.id));
    };

    return (
        <>
            <Head title={`${job.title} | ${__('freelance.jobs')}`} />
            
            <div className="w-full space-y-8 pb-12">
                <div className="flex flex-col gap-2">
                    <Link href="/freelance/jobs/browse" className="text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors w-fit">
                        &larr; {__('freelance.back_to_job_search')}
                    </Link>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    {/* Left Column (Job Details - 8 cols) */}
                    <div className="lg:col-span-8 space-y-6">
                        <FreelanceCard>
                            <CardHeader className="bg-slate-50/50 border-b border-slate-100 pb-6">
                                <div className="flex justify-between items-start gap-4">
                                    <div className="space-y-1">
                                        <CardTitle className="text-2xl font-bold text-slate-900 tracking-tight">
                                            {job.title}
                                        </CardTitle>
                                        <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-500 pt-2">
                                            <span className="flex items-center gap-1.5"><Clock className="h-4 w-4" /> {__('general.posted')} {formatDate(job.created_at)}</span>
                                            <span className="flex items-center gap-1.5"><MapPin className="h-4 w-4" /> {__('general.worldwide')}</span>
                                        </div>
                                    </div>
                                    <div className="flex flex-col items-end gap-2">
                                        <Badge variant={job.status === 'open' ? 'default' : 'secondary'} className={`uppercase tracking-wider font-semibold ${job.status === 'open' ? 'bg-emerald-500 hover:bg-emerald-600' : ''}`}>
                                            {__(job.status.replace('_', ' '))}
                                        </Badge>
                                        
                                        {isClient && job.status === 'open' && (
                                            <Button 
                                                variant="outline" 
                                                size="sm" 
                                                className="text-indigo-600 border-indigo-200 hover:bg-indigo-50"
                                                disabled={!canPoke}
                                                onClick={handlePoke}
                                                title={!canPoke ? __('freelance.poke_too_soon') : ''}
                                            >
                                                <Bell className="w-3.5 h-3.5 mr-1.5" />
                                                {__('freelance.poke_freelancers')}
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="p-6 md:p-8 space-y-8">
                                <div className="prose prose-slate max-w-none text-slate-700 leading-relaxed" dangerouslySetInnerHTML={{ __html: job.description }}></div>

                                <div className="space-y-4">
                                    <h3 className="text-sm font-bold uppercase tracking-wider text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-2">
                                        <Briefcase className="h-4 w-4 text-indigo-500" /> {__('general.skills_and_expertise')}
                                    </h3>
                                    <div className="flex flex-wrap gap-2">
                                        {job.skills?.map((skill: any) => (
                                            <Badge key={skill.id} variant="secondary" className="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs py-1 px-3">
                                                {skill.name}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                            </CardContent>
                        </FreelanceCard>

                        {/* Client View: Proposals Management */}
                        {isClient && (
                            <div className="space-y-4">
                                <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
                                    <User className="h-5 w-5 text-indigo-600" /> 
                                    {__('freelance.proposals_received')} ({job.proposals?.length || 0})
                                </h2>

                                {!job.proposals || job.proposals.length === 0 ? (
                                    <FreelanceCard className="border-dashed shadow-none bg-slate-50 border-slate-200">
                                        <CardContent className="py-12 flex flex-col items-center justify-center text-slate-500">
                                            <FileText className="h-10 w-10 text-slate-300 mb-3" />
                                            <p className="font-medium">{__('freelance.no_proposals_received_yet')}</p>
                                        </CardContent>
                                    </FreelanceCard>
                                ) : (
                                    <div className="space-y-4">
                                        {job.proposals.map((proposal: any) => (
                                            <FreelanceCard key={proposal.id} interactive>
                                                <CardContent className="p-6">
                                                    <div className="flex justify-between items-start mb-4">
                                                        <div className="space-y-1">
                                                            <h3 className="font-bold text-lg text-slate-900">{proposal.freelancer?.name}</h3>
                                                            <div className="flex items-center gap-3 text-sm text-slate-500">
                                                                <span className="flex items-center gap-1"><Clock className="h-3.5 w-3.5" /> {proposal.delivery_days} {__('general.days_delivery')}</span>
                                                                <span className="flex items-center gap-1 font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full"><AlertCircle className="h-3.5 w-3.5" /> {proposal.points_spent} {__('freelance.points')}</span>
                                                            </div>
                                                        </div>
                                                        <div className="text-right">
                                                            <div className="text-xl font-bold text-indigo-700 font-mono">
                                                                {proposal.bid_amount !== null && proposal.bid_amount !== undefined ? formatMoney(proposal.bid_amount, userCurrency) : `${proposal.proposed_budget_points} ${__('freelance.pts', undefined, 'pts')}`}
                                                            </div>
                                                            <Badge variant="outline" className="mt-1 bg-amber-50 text-amber-700 border-amber-200">
                                                                {__(proposal.status)}
                                                            </Badge>
                                                        </div>
                                                    </div>

                                                    <div className="bg-slate-50 border border-slate-100 rounded-lg p-4 text-sm text-slate-700 leading-relaxed">
                                                        {proposal.cover_letter}
                                                    </div>

                                                    {job.status === 'open' && proposal.status === 'pending' && (
                                                        <div className="flex flex-wrap gap-3 mt-6 pt-4 border-t border-slate-100">
                                                            <Button onClick={() => handleAccept(proposal.id)} className="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">
                                                                <CheckCircle2 className="mr-2 h-4 w-4" /> {__('freelance.accept_create_contract')}
                                                            </Button>
                                                            <Button onClick={() => handleReject(proposal.id)} variant="outline" className="text-slate-600 hover:text-red-600 hover:bg-red-50">
                                                                {__('freelance.reject_proposal')}
                                                            </Button>
                                                        </div>
                                                    )}
                                                </CardContent>
                                            </FreelanceCard>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Right Column (Actions - 4 cols) */}
                    <div className="lg:col-span-4 space-y-6">
                        <FreelanceCard className="sticky top-6">
                            <CardHeader className="border-b border-slate-100 bg-slate-50/50 pb-5">
                                <CardTitle className="text-base font-semibold text-slate-900">{__('erp.project_overview')}</CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="divide-y divide-slate-100">
                                    <div className="p-5 flex items-start gap-4">
                                        <div className="bg-indigo-50 p-2.5 rounded-lg text-indigo-600">
                                            <DollarSign className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">{__('erp.budget')}</p>
                                            <div className="font-bold text-slate-900 font-mono">
                                                {job.budget !== null && job.budget !== undefined ? formatMoney(job.budget, userCurrency) : `${job.budget_points} ${__('freelance.pts', undefined, 'pts')}`}
                                            </div>
                                            <p className="text-xs text-slate-500 mt-0.5 capitalize">{__(job.type)} {__('general.price')}</p>
                                        </div>
                                    </div>
                                    
                                    <div className="p-5 flex items-start gap-4">
                                        <div className="bg-amber-50 p-2.5 rounded-lg text-amber-600">
                                            <Clock className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">{__('general.duration')}</p>
                                            <p className="font-medium text-slate-900">{job.duration || __('general.not_specified')}</p>
                                        </div>
                                    </div>
                                    
                                    <div className="p-5">
                                        <div className="flex items-center gap-3">
                                            <div className="h-10 w-10 rounded-full bg-slate-200 flex items-center justify-center font-bold text-slate-600">
                                                {(job.client?.name || 'C').charAt(0)}
                                            </div>
                                            <div>
                                                <p className="font-semibold text-slate-900">{job.client?.name}</p>
                                                <div className="flex items-center gap-1 text-xs text-slate-500 mt-0.5">
                                                    <CheckCircle2 className="h-3 w-3 text-emerald-500" /> {__('payment.payment_verified')}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                            
                            {isClient && (
                                <CardFooter className="p-5 bg-slate-50/50 border-t border-slate-100 flex-col gap-4 items-stretch">
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="bg-white shadow-sm border border-slate-200 rounded-xl p-4 flex flex-col items-center justify-center text-center">
                                            <div className="bg-indigo-50 p-2 rounded-full mb-3">
                                                <Send className="h-5 w-5 text-indigo-600" />
                                            </div>
                                            <p className="text-2xl font-bold text-slate-900 leading-none">{job.notifications_sent_count || 0}</p>
                                            <p className="text-[10px] sm:text-xs text-slate-500 font-bold uppercase tracking-wider mt-2">{__('freelance.notified')}</p>
                                        </div>
                                        <div className="bg-white shadow-sm border border-slate-200 rounded-xl p-4 flex flex-col items-center justify-center text-center">
                                            <div className="bg-emerald-50 p-2 rounded-full mb-3">
                                                <Eye className="h-5 w-5 text-emerald-600" />
                                            </div>
                                            <p className="text-2xl font-bold text-slate-900 leading-none">{job.views_count || 0}</p>
                                            <p className="text-[10px] sm:text-xs text-slate-500 font-bold uppercase tracking-wider mt-2">{__('freelance.views')}</p>
                                        </div>
                                    </div>
                                </CardFooter>
                            )}

                            {!isClient && (
                                <CardFooter className="p-5 bg-slate-50/50 border-t border-slate-100 flex-col gap-4 items-stretch">
                                    {job.status !== 'open' ? (
                                        <Alert>
                                            <AlertCircle className="h-4 w-4" />
                                            <AlertTitle>{__('general.closed')}</AlertTitle>
                                            <AlertDescription>{__('freelance.this_job_is_no_longer')}</AlertDescription>
                                        </Alert>
                                    ) : hasSubmitted ? (
                                        <Alert className="bg-emerald-50 border-emerald-200 text-emerald-800">
                                            <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                                            <AlertTitle className="text-emerald-800 font-bold">{__('freelance.proposal_submitted')}</AlertTitle>
                                            <AlertDescription className="text-emerald-700">{__('freelance.you_have_already_applied_for')}</AlertDescription>
                                        </Alert>
                                    ) : (
                                        <>
                                            <div className="bg-white rounded-lg border border-slate-200 p-4 text-center space-y-2">
                                                <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">{__('freelance.connects_required')}</p>
                                                <div className="flex items-center justify-center gap-2">
                                                    <span className="text-2xl font-bold text-indigo-600 font-mono">{data.points_spent}</span>
                                                </div>
                                                <p className="text-xs text-slate-500">{__('general.your_balance')} <span className="font-bold text-slate-700">{userPoints}</span></p>
                                            </div>
                                            
                                            {(errors as any).points && (
                                                <Alert variant="destructive">
                                                    <AlertCircle className="h-4 w-4" />
                                                    <AlertTitle>{__('general.error')}</AlertTitle>
                                                    <AlertDescription>{(errors as any).points}</AlertDescription>
                                                </Alert>
                                            )}

                                            <form onSubmit={submitProposal} className="space-y-4">
                                                <div className="space-y-1.5">
                                                    <Label htmlFor="bid_amount">{__('freelance.your_bid')} ({job.currency?.symbol || job.currency?.currency || globalCurrency})</Label>
                                                    <Input
                                                        id="bid_amount"
                                                        type="number"
                                                        value={data.bid_amount}
                                                        onChange={e => setData('bid_amount', e.target.value)}
                                                        required
                                                    />
                                                </div>
                                                <div className="space-y-1.5">
                                                    <Label htmlFor="delivery_days">{__('general.delivery_time_days')}</Label>
                                                    <Input
                                                        id="delivery_days"
                                                        type="number"
                                                        value={data.delivery_days}
                                                        onChange={e => setData('delivery_days', e.target.value)}
                                                        required
                                                    />
                                                </div>
                                                <div className="space-y-1.5">
                                                    <Label htmlFor="points_spent">{__('freelance.points_to_spend')} ({__('general.min')}: {job.min_proposal_points || 2})</Label>
                                                    <Input
                                                        id="points_spent"
                                                        type="number"
                                                        min={job.min_proposal_points || 2}
                                                        value={data.points_spent}
                                                        onChange={e => setData('points_spent', parseInt(e.target.value) || 0)}
                                                        required
                                                    />
                                                    <p className="text-xs text-slate-500">{__('freelance.spend_more_points_to_rank_higher')}</p>
                                                </div>
                                                <div className="space-y-1.5">
                                                    <Label htmlFor="cover_letter">{__('general.cover_letter')}</Label>
                                                    <Textarea
                                                        id="cover_letter"
                                                        value={data.cover_letter}
                                                        onChange={e => setData('cover_letter', e.target.value)}
                                                        className="h-32 resize-none"
                                                        placeholder={__('erp.explain_why_you_are_the')}
                                                        required
                                                    />
                                                </div>
                                                <Button 
                                                    type="submit" 
                                                    disabled={processing || userPoints < data.points_spent}
                                                    className="w-full bg-indigo-600 hover:bg-indigo-700 font-bold"
                                                >
                                                    <Send className="mr-2 h-4 w-4" /> {__('freelance.submit_proposal')}
                                                </Button>
                                            </form>
                                        </>
                                    )}
                                </CardFooter>
                            )}
                        </FreelanceCard>
                    </div>
                </div>
            </div>
        </>
    );
}

export default function ShowJob({ auth, job, pointsCost = 5 }: any) {
    if (!job) {
        return (
            <FreelanceLayout clean={true}>
                <div className="w-full py-12 text-center text-slate-500">
                    {__('freelance.job_not_found')}
                </div>
            </FreelanceLayout>
        );
    }

    return (
        <FreelanceLayout clean={true}>
            <ShowJobContent auth={auth} job={job} pointsCost={pointsCost} userCurrency={userCurrency} />
        </FreelanceLayout>
    );
}
