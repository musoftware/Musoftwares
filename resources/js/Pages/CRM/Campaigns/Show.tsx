import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import CrmLayout from '@/Layouts/CrmLayout';
import { Button } from '@/Components/ui/button';
import { Save, Sparkles, Send, PlayCircle, PauseCircle, Clock, CheckCircle2, Loader2, AlertCircle } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Textarea } from "@/Components/ui/textarea";

export default function Show({ campaign }) {
    const { auth } = usePage().props as any;
    const [contentForm, setContentForm] = useState({
        email_subject_en: campaign.email_subject?.en || '',
        email_content_en: campaign.email_content?.en || '',
        whatsapp_content_en: campaign.whatsapp_content?.en || '',
    });
    
    const [isSaving, setIsSaving] = useState(false);
    
    // AI State
    const [isAiOpen, setIsAiOpen] = useState(false);
    const [aiForm, setAiForm] = useState({ context: '', tone: 'professional', type: campaign.type });
    const [isGenerating, setIsGenerating] = useState(false);

    const handleSaveContent = () => {
        setIsSaving(true);
        router.put(route('crm.campaigns.update', campaign.id), contentForm, {
            onFinish: () => setIsSaving(false)
        });
    };

    const handleGenerateAI = async () => {
        setIsGenerating(true);
        try {
            const response = await window.axios.post(route('crm.campaigns.generate-ai'), aiForm);
            const aiContent = response.data.content;
            
            // Auto-fill the form
            setContentForm(prev => ({
                ...prev,
                email_subject_en: aiContent.email_subject_en || prev.email_subject_en,
                email_content_en: aiContent.email_content_en || prev.email_content_en,
                whatsapp_content_en: aiContent.whatsapp_content_en || prev.whatsapp_content_en,
            }));
            
            setIsAiOpen(false);
            alert('AI content generated successfully! Please review before saving.');
        } catch (error) {
            alert('Failed to generate content: ' + (error.response?.data?.error || error.message));
        } finally {
            setIsGenerating(false);
        }
    };

    const handleSchedule = () => {
        if(confirm('Ready to schedule this campaign? (It will be sent by the background processor)')) {
            router.post(route('crm.campaigns.schedule', campaign.id));
        }
    };

    const handlePause = () => {
        router.post(route('crm.campaigns.pause', campaign.id));
    };

    const handleResume = () => {
        router.post(route('crm.campaigns.resume', campaign.id));
    };

    const isEditable = campaign.status === 'draft';

    return (
        <CrmLayout title={`Campaign: ${campaign.name}`} activeMenu="campaigns">
            <div className="flex-1 space-y-4 p-8 pt-6">
            <div className="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 className="text-2xl font-bold text-gray-900 flex items-center">
                        {campaign.name}
                        {campaign.status === 'draft' && <span className="ml-3 inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">Draft</span>}
                        {campaign.status === 'scheduled' && <span className="ml-3 inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">Scheduled</span>}
                        {campaign.status === 'sending' && <span className="ml-3 inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">Sending...</span>}
                        {campaign.status === 'paused' && <span className="ml-3 inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800">Paused</span>}
                        {campaign.status === 'completed' && <span className="ml-3 inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Completed</span>}
                    </h2>
                    <p className="text-gray-500 mt-1">Target: {campaign.target_audience.replace('_', ' ')} • Type: <span className="capitalize">{campaign.type}</span></p>
                </div>
                
                <div className="flex flex-wrap items-center gap-2">
                    <Link href={route('crm.campaigns.index')}>
                        <Button variant="outline">Back</Button>
                    </Link>
                    
                    {campaign.status === 'draft' && (
                        <>
                            <Dialog open={isAiOpen} onOpenChange={setIsAiOpen}>
                                <DialogTrigger asChild>
                                    <Button variant="secondary" className="bg-indigo-100 text-indigo-700 hover:bg-indigo-200">
                                        <Sparkles className="w-4 h-4 mr-2" /> AI Copywriter
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>AI Copywriter</DialogTitle>
                                    </DialogHeader>
                                    <div className="space-y-4 py-4">
                                        <div className="space-y-2">
                                            <Label>What is this campaign about?</Label>
                                            <Textarea 
                                                placeholder="e.g. Announcing a new 50% discount on all web design services for the next 48 hours..."
                                                value={aiForm.context}
                                                onChange={e => setAiForm({...aiForm, context: e.target.value})}
                                                rows={4}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Tone</Label>
                                            <select 
                                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                                value={aiForm.tone}
                                                onChange={e => setAiForm({...aiForm, tone: e.target.value})}
                                            >
                                                <option value="professional">Professional</option>
                                                <option value="urgent">Urgent / Flash Sale</option>
                                                <option value="friendly">Friendly & Casual</option>
                                            </select>
                                        </div>
                                        <Button className="w-full" onClick={handleGenerateAI} disabled={isGenerating}>
                                            {isGenerating ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : <Sparkles className="w-4 h-4 mr-2" />}
                                            Generate Copy
                                        </Button>
                                    </div>
                                </DialogContent>
                            </Dialog>

                            <Button onClick={handleSaveContent} disabled={isSaving} className="bg-gray-900">
                                {isSaving ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : <Save className="w-4 h-4 mr-2" />} Save Content
                            </Button>
                            
                            <Button onClick={handleSchedule} className="bg-blue-600 hover:bg-blue-700">
                                <Clock className="w-4 h-4 mr-2" /> Schedule & Send
                            </Button>
                        </>
                    )}

                    {campaign.status === 'scheduled' || campaign.status === 'sending' ? (
                        <Button onClick={handlePause} variant="outline" className="border-orange-200 text-orange-600 hover:bg-orange-50">
                            <PauseCircle className="w-4 h-4 mr-2" /> Pause Campaign
                        </Button>
                    ) : null}

                    {campaign.status === 'paused' && (
                        <Button onClick={handleResume} className="bg-green-600 hover:bg-green-700">
                            <PlayCircle className="w-4 h-4 mr-2" /> Resume Campaign
                        </Button>
                    )}
                </div>
            </div>

            {!isEditable && (
                <div className="mb-6 bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-md flex">
                    <AlertCircle className="w-5 h-5 text-blue-400 mr-3 mt-0.5" />
                    <div>
                        <h3 className="text-sm font-medium text-blue-800">Campaign is locked</h3>
                        <p className="text-sm text-blue-700 mt-1">This campaign has already been scheduled or sent. You cannot edit the content.</p>
                    </div>
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {(campaign.type === 'email' || campaign.type === 'mixed') && (
                    <Card className={`border-t-4 border-t-blue-500 shadow-sm ${!isEditable ? 'opacity-80' : ''}`}>
                        <CardHeader>
                            <CardTitle>Email Content</CardTitle>
                            <CardDescription>This will be sent to the recipient's primary email address.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label>Subject Line</Label>
                                <Input 
                                    disabled={!isEditable}
                                    value={contentForm.email_subject_en}
                                    onChange={e => setContentForm({...contentForm, email_subject_en: e.target.value})}
                                    placeholder="Enter an engaging subject line..."
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Email Body</Label>
                                <Textarea 
                                    disabled={!isEditable}
                                    rows={12}
                                    value={contentForm.email_content_en}
                                    onChange={e => setContentForm({...contentForm, email_content_en: e.target.value})}
                                    placeholder="Write your email content here..."
                                />
                            </div>
                        </CardContent>
                    </Card>
                )}

                {(campaign.type === 'whatsapp' || campaign.type === 'mixed') && (
                    <Card className={`border-t-4 border-t-green-500 shadow-sm ${!isEditable ? 'opacity-80' : ''}`}>
                        <CardHeader>
                            <CardTitle>WhatsApp Content</CardTitle>
                            <CardDescription>Keep it short and actionable. WhatsApp formatting (*bold*, _italic_) is supported.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <Label>Message Body</Label>
                                <Textarea 
                                    disabled={!isEditable}
                                    rows={10}
                                    value={contentForm.whatsapp_content_en}
                                    onChange={e => setContentForm({...contentForm, whatsapp_content_en: e.target.value})}
                                    placeholder="Write your WhatsApp message here..."
                                />
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
            
            {/* Delivery Stats Preview */}
            {campaign.status !== 'draft' && (
                <div className="mt-8">
                    <h3 className="text-lg font-bold mb-4">Delivery Overview</h3>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="bg-white p-4 rounded-lg border shadow-sm text-center">
                            <div className="text-sm text-gray-500">Total Recipients</div>
                            <div className="text-2xl font-bold text-gray-900">{campaign.recipients_count || 0}</div>
                        </div>
                        <div className="bg-white p-4 rounded-lg border shadow-sm text-center">
                            <div className="text-sm text-gray-500">Delivered</div>
                            <div className="text-2xl font-bold text-green-600">--</div>
                        </div>
                        <div className="bg-white p-4 rounded-lg border shadow-sm text-center">
                            <div className="text-sm text-gray-500">Failed</div>
                            <div className="text-2xl font-bold text-red-600">--</div>
                        </div>
                        <div className="bg-white p-4 rounded-lg border shadow-sm text-center">
                            <div className="text-sm text-gray-500">Open Rate</div>
                            <div className="text-2xl font-bold text-indigo-600">--%</div>
                        </div>
                    </div>
                </div>
            )}
            </div>
        </CrmLayout>
    );
}
