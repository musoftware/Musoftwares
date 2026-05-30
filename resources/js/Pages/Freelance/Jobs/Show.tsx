import React, { useState } from 'react';
import { Head, useForm, router, Link } from '@inertiajs/react';
import FreelanceLayout from '../Layout';
import { useFreelanceMode } from '@/Components/Freelance/FreelanceModeContext';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { formatMoney, formatDate } from '@/lib/utils';
import { Clock, DollarSign, Briefcase, MapPin, CheckCircle2, AlertCircle, FileText, Send, User } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from "@/Components/ui/alert";
import { CurrencyDisplay as FinancialAmount } from '@/Components/ui/CurrencyDisplay';
import { __ } from '@/lib/i18n';

function ShowJobContent({ auth, job, pointsCost }: any) {
    const { mode } = useFreelanceMode();
    const isClient = mode === 'client';
    const globalCurrency = auth?.user?.preferred_currency || 'USD';

    const hasSubmitted = !isClient && job.proposals?.some((p: any) => p.freelancer_id === auth.user.id);
    const userPoints = auth.user.points_balance || 0;

    const { data, setData, post, processing, errors } = useForm({
        job_id: job.id,
        bid_amount: '',
        delivery_days: '',
        cover_letter: '',
        currency_id: job.currency_id
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

    return (
        <>
            <Head title={`${job.title} | ${__('Jobs')}`} />
            
            <div className="w-full space-y-8 pb-12">
                <div className="flex flex-col gap-2">
                    <Link href="/freelance/jobs/browse" className="text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors w-fit">
                        &larr; {__('Back to job search')}
                    </Link>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    {/* Left Column (Job Details - 8 cols) */}
                    <div className="lg:col-span-8 space-y-6">
                        <Card className="shadow-sm border-slate-200/60 overflow-hidden">
                            <CardHeader className="bg-slate-50/50 border-b border-slate-100 pb-6">
                                <div className="flex justify-between items-start gap-4">
                                    <div className="space-y-1">
                                        <CardTitle className="text-2xl font-bold text-slate-900 tracking-tight">
                                            {job.title}
                                        </CardTitle>
                                        <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-500 pt-2">
                                            <span className="flex items-center gap-1.5"><Clock className="h-4 w-4" /> {__('Posted')} {formatDate(job.created_at)}</span>
                                            <span className="flex items-center gap-1.5"><MapPin className="h-4 w-4" /> {__('Worldwide')}</span>
                                        </div>
                                    </div>
                                    <Badge variant={job.status === 'open' ? 'default' : 'secondary'} className={`uppercase tracking-wider font-semibold ${job.status === 'open' ? 'bg-emerald-500 hover:bg-emerald-600' : ''}`}>
                                        {__(job.status.replace('_', ' '))}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="p-6 md:p-8 space-y-8">
                                <div className="prose prose-slate max-w-none text-slate-700 leading-relaxed" dangerouslySetInnerHTML={{ __html: job.description }}></div>

                                <div className="space-y-4">
                                    <h3 className="text-sm font-bold uppercase tracking-wider text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-2">
                                        <Briefcase className="h-4 w-4 text-indigo-500" /> {__('Skills and Expertise')}
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
                        </Card>

                        {/* Client View: Proposals Management */}
                        {isClient && (
                            <div className="space-y-4">
                                <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
                                    <User className="h-5 w-5 text-indigo-600" /> 
                                    {__('Proposals Received')} ({job.proposals?.length || 0})
                                </h2>

                                {!job.proposals || job.proposals.length === 0 ? (
                                    <Card className="border-dashed shadow-none bg-slate-50 border-slate-200">
                                        <CardContent className="py-12 flex flex-col items-center justify-center text-slate-500">
                                            <FileText className="h-10 w-10 text-slate-300 mb-3" />
                                            <p className="font-medium">{__('No proposals received yet.')}</p>
                                        </CardContent>
                                    </Card>
                                ) : (
                                    <div className="space-y-4">
                                        {job.proposals.map((proposal: any) => (
                                            <Card key={proposal.id} className="shadow-sm border-slate-200/70 hover:border-indigo-200 transition-colors overflow-hidden">
                                                <CardContent className="p-6">
                                                    <div className="flex justify-between items-start mb-4">
                                                        <div className="space-y-1">
                                                            <h3 className="font-bold text-lg text-slate-900">{proposal.freelancer?.name}</h3>
                                                            <div className="flex items-center gap-3 text-sm text-slate-500">
                                                                <span className="flex items-center gap-1"><Clock className="h-3.5 w-3.5" /> {proposal.delivery_days} {__('days delivery')}</span>
                                                            </div>
                                                        </div>
                                                        <div className="text-right">
                                                            <div className="text-xl font-bold text-indigo-700 font-mono">
                                                                <FinancialAmount amount={proposal.bid_amount} currency={globalCurrency} />
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
                                                                <CheckCircle2 className="mr-2 h-4 w-4" /> {__('Accept & Create Contract')}
                                                            </Button>
                                                            <Button onClick={() => handleReject(proposal.id)} variant="outline" className="text-slate-600 hover:text-red-600 hover:bg-red-50">
                                                                {__('Reject Proposal')}
                                                            </Button>
                                                        </div>
                                                    )}
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Right Column (Actions - 4 cols) */}
                    <div className="lg:col-span-4 space-y-6">
                        <Card className="shadow-sm border-slate-200/60 sticky top-6">
                            <CardHeader className="border-b border-slate-100 bg-slate-50/50 pb-5">
                                <CardTitle className="text-base font-semibold text-slate-900">{__('Project Overview')}</CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="divide-y divide-slate-100">
                                    <div className="p-5 flex items-start gap-4">
                                        <div className="bg-indigo-50 p-2.5 rounded-lg text-indigo-600">
                                            <DollarSign className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">{__('Budget')}</p>
                                            <div className="font-bold text-slate-900"><FinancialAmount amount={job.budget} currency={globalCurrency} /></div>
                                            <p className="text-xs text-slate-500 mt-0.5 capitalize">{__(job.type)} {__('Price')}</p>
                                        </div>
                                    </div>
                                    
                                    <div className="p-5 flex items-start gap-4">
                                        <div className="bg-amber-50 p-2.5 rounded-lg text-amber-600">
                                            <Clock className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">{__('Duration')}</p>
                                            <p className="font-medium text-slate-900">{job.duration || __('Not specified')}</p>
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
                                                    <CheckCircle2 className="h-3 w-3 text-emerald-500" /> {__('Payment Verified')}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                            
                            {!isClient && (
                                <CardFooter className="p-5 bg-slate-50/50 border-t border-slate-100 flex-col gap-4 items-stretch">
                                    {job.status !== 'open' ? (
                                        <Alert>
                                            <AlertCircle className="h-4 w-4" />
                                            <AlertTitle>{__('Closed')}</AlertTitle>
                                            <AlertDescription>{__('This job is no longer accepting proposals.')}</AlertDescription>
                                        </Alert>
                                    ) : hasSubmitted ? (
                                        <Alert className="bg-emerald-50 border-emerald-200 text-emerald-800">
                                            <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                                            <AlertTitle className="text-emerald-800 font-bold">{__('Proposal Submitted')}</AlertTitle>
                                            <AlertDescription className="text-emerald-700">{__('You have already applied for this job.')}</AlertDescription>
                                        </Alert>
                                    ) : (
                                        <>
                                            <div className="bg-white rounded-lg border border-slate-200 p-4 text-center space-y-2">
                                                <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">{__('Connects Required')}</p>
                                                <div className="flex items-center justify-center gap-2">
                                                    <span className="text-2xl font-bold text-indigo-600 font-mono">{pointsCost}</span>
                                                </div>
                                                <p className="text-xs text-slate-500">{__('Your balance:')} <span className="font-bold text-slate-700">{userPoints}</span></p>
                                            </div>
                                            
                                            {(errors as any).points && (
                                                <Alert variant="destructive">
                                                    <AlertCircle className="h-4 w-4" />
                                                    <AlertTitle>{__('Error')}</AlertTitle>
                                                    <AlertDescription>{(errors as any).points}</AlertDescription>
                                                </Alert>
                                            )}

                                            <form onSubmit={submitProposal} className="space-y-4">
                                                <div className="space-y-1.5">
                                                    <Label htmlFor="bid_amount">{__('Your Bid')} ({globalCurrency})</Label>
                                                    <Input
                                                        id="bid_amount"
                                                        type="number"
                                                        value={data.bid_amount}
                                                        onChange={e => setData('bid_amount', e.target.value)}
                                                        required
                                                    />
                                                </div>
                                                <div className="space-y-1.5">
                                                    <Label htmlFor="delivery_days">{__('Delivery time (days)')}</Label>
                                                    <Input
                                                        id="delivery_days"
                                                        type="number"
                                                        value={data.delivery_days}
                                                        onChange={e => setData('delivery_days', e.target.value)}
                                                        required
                                                    />
                                                </div>
                                                <div className="space-y-1.5">
                                                    <Label htmlFor="cover_letter">{__('Cover Letter')}</Label>
                                                    <Textarea
                                                        id="cover_letter"
                                                        value={data.cover_letter}
                                                        onChange={e => setData('cover_letter', e.target.value)}
                                                        className="h-32 resize-none"
                                                        placeholder={__('Explain why you are the best fit for this project...')}
                                                        required
                                                    />
                                                </div>
                                                <Button 
                                                    type="submit" 
                                                    disabled={processing || userPoints < pointsCost}
                                                    className="w-full bg-indigo-600 hover:bg-indigo-700 font-bold"
                                                >
                                                    <Send className="mr-2 h-4 w-4" /> {__('Submit Proposal')}
                                                </Button>
                                            </form>
                                        </>
                                    )}
                                </CardFooter>
                            )}
                        </Card>
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
                    {__('Job not found.')}
                </div>
            </FreelanceLayout>
        );
    }

    return (
        <FreelanceLayout clean={true}>
            <ShowJobContent auth={auth} job={job} pointsCost={pointsCost} />
        </FreelanceLayout>
    );
}
