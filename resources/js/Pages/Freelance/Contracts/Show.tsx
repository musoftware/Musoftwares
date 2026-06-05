import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import FreelanceLayout from '../Layout';
import { useFreelanceMode } from '@/Components/Freelance/FreelanceModeContext';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { formatMoney, formatDate } from '@/lib/utils';
import { Alert, AlertDescription, AlertTitle } from "@/Components/ui/alert";
import { 
    Clock, DollarSign, CheckCircle2, AlertCircle, 
    Send, Paperclip, FileText, User, UploadCloud, FileCheck 
} from 'lucide-react';
import { CurrencyDisplay as FinancialAmount } from '@/Components/ui/CurrencyDisplay';
import { __ } from '@/lib/i18n';

function ShowContractContent({ auth, contract }: any) {
    const { mode } = useFreelanceMode();
    const isClient = mode === 'client';
    const globalCurrency = auth?.user?.preferred_currency || 'USD';

    const [messageInput, setMessageInput] = useState('');
    const [deliveryDescription, setDeliveryDescription] = useState('');

    const daysRemaining = Math.ceil((new Date(contract.deadline).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));

    const handleSendMessage = (e: React.FormEvent) => {
        e.preventDefault();
        if (!messageInput.trim()) return;
        // In a real app, send via Reverb/axios
        console.log('Sending message:', messageInput);
        setMessageInput('');
    };

    const handleMarkCompleted = () => {
        if(confirm(__('freelance.are_you_sure_you_want'))) {
            router.post(route('freelance.contracts.complete', contract.id));
        }
    };

    const handleRaiseDispute = () => {
        if(confirm(__('freelance.are_you_sure_you_want_2'))) {
            router.post(route('freelance.contracts.dispute', contract.id));
        }
    };

    const handleSubmitDelivery = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('freelance.contracts.deliver', contract.id), {
            description: deliveryDescription
        });
    };

    return (
        <>
            <Head title={`${__('freelance.contract')}: ${contract.title}`} />

            <div className="w-full space-y-6 pb-12">
                
                {/* Header Banner */}
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-xl border border-slate-200/60 shadow-sm">
                    <div className="space-y-1">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold tracking-tight text-slate-900">
                                {contract.title}
                            </h1>
                            <Badge variant={contract.status === 'active' ? 'default' : contract.status === 'completed' ? 'secondary' : 'destructive'} 
                                className={`uppercase tracking-wider font-semibold ${contract.status === 'active' ? 'bg-indigo-600 hover:bg-indigo-700' : contract.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : ''}`}
                            >
                                {__(contract.status)}
                            </Badge>
                        </div>
                        <p className="text-sm text-slate-500">{__('freelance.contract_workspace_messages')}</p>
                    </div>
                    
                    <div className="flex items-center gap-6">
                        <div className="text-right">
                            <p className="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-0.5">{__('freelance.contract_value')}</p>
                            <p className="text-xl font-bold text-slate-900 font-mono">
                                <FinancialAmount amount={contract.agreed_price} currency={contract.job?.currency} />
                            </p>
                        </div>
                        <div className="h-10 w-px bg-slate-200 hidden sm:block"></div>
                        <div className="text-right hidden sm:block">
                            <p className="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-0.5">{__('general.deadline')}</p>
                            <p className={`text-lg font-bold ${daysRemaining <= 2 ? 'text-red-600' : 'text-slate-900'}`}>
                                {daysRemaining} {__('general.days_left')}
                            </p>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    {/* Left Column - Workspace (8 cols) */}
                    <div className="lg:col-span-8 space-y-6">
                        
                        {/* Parties */}
                        <Card className="shadow-sm border-slate-200/60 overflow-hidden">
                            <CardContent className="p-0 flex items-stretch">
                                <div className="flex-1 p-6 text-center space-y-2 border-r border-slate-100 bg-slate-50/30">
                                    <div className="h-12 w-12 rounded-full bg-indigo-100 text-indigo-700 font-bold text-xl flex items-center justify-center mx-auto">
                                        {contract.client.avatar}
                                    </div>
                                    <div>
                                        <p className="text-xs font-bold uppercase tracking-wider text-slate-400">{__('erp.client')}</p>
                                        <p className="font-semibold text-slate-900">{contract.client.name}</p>
                                    </div>
                                </div>
                                <div className="flex-1 p-6 text-center space-y-2 bg-slate-50/30">
                                    <div className="h-12 w-12 rounded-full bg-emerald-100 text-emerald-700 font-bold text-xl flex items-center justify-center mx-auto">
                                        {contract.freelancer.avatar}
                                    </div>
                                    <div>
                                        <p className="text-xs font-bold uppercase tracking-wider text-slate-400">{__('general.freelancer')}</p>
                                        <p className="font-semibold text-slate-900">{contract.freelancer.name}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Real-time Chat */}
                        <Card className="shadow-sm border-slate-200/60 flex flex-col h-[500px]">
                            <CardHeader className="bg-slate-50/50 border-b border-slate-100 py-4 px-6 flex flex-row items-center justify-between">
                                <CardTitle className="text-sm font-semibold flex items-center gap-2 text-slate-900">
                                    <span className="relative flex h-2 w-2">
                                      <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                      <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    {__('general.live_chat')}
                                </CardTitle>
                            </CardHeader>

                            <CardContent className="flex-1 p-6 overflow-y-auto space-y-6 bg-slate-50/20">
                                {contract.chat_messages.map((msg: any) => {
                                    const isMe = msg.sender_id === auth.user.id || (isClient && msg.sender_name === contract.client.name) || (!isClient && msg.sender_name === contract.freelancer.name);

                                    return (
                                        <div key={msg.id} className={`flex flex-col ${isMe ? 'items-end' : 'items-start'}`}>
                                            <span className="text-[10px] text-slate-400 font-medium mb-1 mx-1">
                                                {msg.sender_name} • {msg.time}
                                            </span>
                                            <div className={`px-4 py-2.5 rounded-2xl max-w-[85%] text-sm shadow-sm ${
                                                isMe ? 'bg-indigo-600 text-white rounded-tr-sm' : 'bg-white border border-slate-200 text-slate-800 rounded-tl-sm'
                                            }`}>
                                                {msg.text}
                                            </div>
                                        </div>
                                    );
                                })}
                            </CardContent>

                            {contract.status === 'active' && (
                                <div className="p-4 border-t border-slate-100 bg-white">
                                    <form onSubmit={handleSendMessage} className="flex gap-3">
                                        <Button type="button" variant="outline" size="icon" className="shrink-0 text-slate-500 border-slate-200 rounded-full h-11 w-11 hover:bg-slate-50">
                                            <Paperclip className="h-4 w-4" />
                                        </Button>
                                        <Input
                                            type="text"
                                            value={messageInput}
                                            onChange={(e) => setMessageInput(e.target.value)}
                                            placeholder={__('general.type_your_message')}
                                            className="flex-1 h-11 rounded-full px-5 bg-slate-50 border-slate-200 focus-visible:ring-indigo-500"
                                        />
                                        <Button type="submit" className="shrink-0 h-11 px-6 rounded-full bg-indigo-600 hover:bg-indigo-700 font-bold">
                                            <Send className="h-4 w-4 mr-2" /> {__('general.send')}
                                        </Button>
                                    </form>
                                </div>
                            )}
                        </Card>
                    </div>

                    {/* Right Column - Actions (4 cols) */}
                    <div className="lg:col-span-4 space-y-6">
                        {contract.status === 'active' && (
                            <Card className="shadow-sm border-slate-200/60 sticky top-6">
                                <CardHeader className="border-b border-slate-100 bg-slate-50/50 pb-5">
                                    <CardTitle className="text-base font-semibold text-slate-900">{__('freelance.contract_actions')}</CardTitle>
                                </CardHeader>
                                <CardContent className="p-5">
                                    {isClient ? (
                                        <div className="space-y-4">
                                            <Alert className="bg-indigo-50 border-indigo-100 text-indigo-800">
                                                <FileCheck className="h-4 w-4 text-indigo-600" />
                                                <AlertTitle className="text-indigo-900 font-bold">{__('general.review_approve')}</AlertTitle>
                                                <AlertDescription className="text-indigo-700 text-xs">
                                                    {__('general.review_the_work_delivered_in')}
                                                </AlertDescription>
                                            </Alert>

                                            <div className="space-y-3 pt-2">
                                                <Button
                                                    onClick={handleMarkCompleted}
                                                    className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold h-12"
                                                >
                                                    <CheckCircle2 className="mr-2 h-5 w-5" /> {__('payment.approve_release_payment')}
                                                </Button>
                                                <Button
                                                    onClick={handleRaiseDispute}
                                                    variant="outline"
                                                    className="w-full border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700 h-12 font-bold"
                                                >
                                                    <AlertCircle className="mr-2 h-4 w-4" /> {__('general.raise_dispute')}
                                                </Button>
                                            </div>
                                        </div>
                                    ) : (
                                        <form onSubmit={handleSubmitDelivery} className="space-y-4">
                                            <Alert className="bg-indigo-50 border-indigo-100 text-indigo-800 mb-2">
                                                <UploadCloud className="h-4 w-4 text-indigo-600" />
                                                <AlertTitle className="text-indigo-900 font-bold">{__('general.submit_work')}</AlertTitle>
                                                <AlertDescription className="text-indigo-700 text-xs">
                                                    {__('general.ready_to_submit_your_final')}
                                                </AlertDescription>
                                            </Alert>

                                            <div className="space-y-1.5">
                                                <label className="text-sm font-bold text-slate-700">{__('general.delivery_notes')}</label>
                                                <Textarea
                                                    value={deliveryDescription}
                                                    onChange={(e) => setDeliveryDescription(e.target.value)}
                                                    className="resize-none h-24 focus-visible:ring-indigo-500"
                                                    placeholder={__('general.describe_what_you_have_completed')}
                                                    required
                                                />
                                            </div>

                                            <div className="space-y-1.5">
                                                <label className="text-sm font-bold text-slate-700">{__('general.attach_files')}</label>
                                                <div className="border-2 border-dashed border-slate-300 rounded-lg p-6 flex flex-col items-center justify-center text-center hover:bg-slate-50 hover:border-indigo-300 cursor-pointer transition-colors">
                                                    <UploadCloud className="h-8 w-8 text-slate-400 mb-2" />
                                                    <p className="text-sm font-medium text-slate-600">{__('general.click_to_upload_or_drag')}</p>
                                                    <p className="text-xs text-slate-400 mt-1">{__('general.svg_png_jpg_or_pdf')}</p>
                                                </div>
                                            </div>

                                            <Button
                                                type="submit"
                                                className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold h-12 mt-2"
                                            >
                                                <CheckCircle2 className="mr-2 h-5 w-5" /> {__('general.submit_delivery')}
                                            </Button>
                                        </form>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        {contract.status === 'completed' && (
                            <Card className="shadow-sm border-emerald-200 bg-emerald-50 overflow-hidden">
                                <CardContent className="p-8 flex flex-col items-center justify-center text-center">
                                    <div className="h-16 w-16 bg-emerald-100 rounded-full flex items-center justify-center mb-4">
                                        <CheckCircle2 className="h-8 w-8 text-emerald-600" />
                                    </div>
                                    <h3 className="text-xl font-bold text-emerald-900 mb-2">{__('freelance.contract_completed')}</h3>
                                    <p className="text-sm text-emerald-700 mb-6 font-medium">{__('general.funds_have_been_released_successfully')}</p>
                                    
                                    {isClient && (
                                        <Button variant="outline" className="border-emerald-300 text-emerald-700 hover:bg-emerald-100 font-bold w-full bg-white">
                                            {__('general.leave_feedback')}
                                        </Button>
                                    )}
                                </CardContent>
                            </Card>
                        )}
                        
                        {contract.status === 'disputed' && (
                            <Card className="shadow-sm border-red-200 bg-red-50 overflow-hidden">
                                <CardContent className="p-8 flex flex-col items-center justify-center text-center">
                                    <div className="h-16 w-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                                        <AlertCircle className="h-8 w-8 text-red-600" />
                                    </div>
                                    <h3 className="text-xl font-bold text-red-900 mb-2">{__('freelance.contract_disputed')}</h3>
                                    <p className="text-sm text-red-700 font-medium">{__('admin.an_admin_will_review_the')}</p>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

export default function ShowContract({ auth, contract }: any) {
    if (!contract) {
        return (
            <FreelanceLayout clean={true}>
                <div className="w-full py-12 text-center text-slate-500">
                    {__('freelance.contract_not_found')}
                </div>
            </FreelanceLayout>
        );
    }

    return (
        <FreelanceLayout clean={true}>
            <ShowContractContent auth={auth} contract={contract} />
        </FreelanceLayout>
    );
}
