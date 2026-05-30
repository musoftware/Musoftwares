import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import CrmLayout from '@/Layouts/CrmLayout';
import { Button } from '@/Components/ui/button';
import { Clock, Mail, MessageCircle, Trash2, ArrowRight, Plus, Sparkles, Loader2 } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Textarea } from "@/Components/ui/textarea";

export default function Show({ sequence }) {
    const { auth } = usePage().props as any;
    const [isAiOpen, setIsAiOpen] = useState(false);
    const [aiForm, setAiForm] = useState({ context: '', num_steps: 3, tone: 'professional' });
    const [isGenerating, setIsGenerating] = useState(false);
    const [generatedSteps, setGeneratedSteps] = useState(null);

    const handleGenerateAI = async () => {
        setIsGenerating(true);
        setGeneratedSteps(null);
        try {
            const response = await window.axios.post(route('crm.sequences.generate-ai', sequence.id), aiForm);
            setGeneratedSteps(response.data.steps);
        } catch (error) {
            alert('Failed to generate steps: ' + (error.response?.data?.error || error.message));
        } finally {
            setIsGenerating(false);
        }
    };

    const handleApplyAI = () => {
        if (!generatedSteps) return;
        router.post(route('crm.sequences.apply-ai', sequence.id), { steps: generatedSteps }, {
            onSuccess: () => {
                setIsAiOpen(false);
                setGeneratedSteps(null);
            }
        });
    };

    const handleDeleteStep = (id) => {
        if(confirm('Delete this step?')) {
            router.delete(route('crm.sequences.steps.destroy', id));
        }
    };

    return (
        <CrmLayout title={`Sequence: ${sequence.name}`} activeMenu="sequences">
            <div className="flex-1 space-y-4 p-8 pt-6">
            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h2 className="text-2xl font-bold text-gray-900">{sequence.name}</h2>
                    <p className="text-gray-500">Trigger: {sequence.trigger_type.replace('_', ' ')}</p>
                </div>
                <div className="flex space-x-3">
                    <Link href={route('crm.sequences.index')}>
                        <Button variant="outline">Back to Sequences</Button>
                    </Link>
                    <Dialog open={isAiOpen} onOpenChange={setIsAiOpen}>
                        <DialogTrigger asChild>
                            <Button className="bg-indigo-600 hover:bg-indigo-700">
                                <Sparkles className="w-4 h-4 mr-2" /> Auto-Generate with AI
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-[600px]">
                            <DialogHeader>
                                <DialogTitle>AI Sequence Generator</DialogTitle>
                            </DialogHeader>
                            {!generatedSteps ? (
                                <div className="space-y-4 py-4">
                                    <div className="space-y-2">
                                        <Label>Campaign Goal / Context</Label>
                                        <Textarea 
                                            placeholder="e.g. A welcome series for new freelancers explaining how to get their first job..."
                                            value={aiForm.context}
                                            onChange={e => setAiForm({...aiForm, context: e.target.value})}
                                            rows={3}
                                        />
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label>Number of Steps</Label>
                                            <Input type="number" min="1" max="5" value={aiForm.num_steps} onChange={e => setAiForm({...aiForm, num_steps: parseInt(e.target.value)})} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Tone</Label>
                                            <select 
                                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                                value={aiForm.tone}
                                                onChange={e => setAiForm({...aiForm, tone: e.target.value})}
                                            >
                                                <option value="professional">Professional</option>
                                                <option value="friendly">Friendly & Welcoming</option>
                                                <option value="urgent">Urgent / Sales</option>
                                            </select>
                                        </div>
                                    </div>
                                    <Button className="w-full" onClick={handleGenerateAI} disabled={isGenerating}>
                                        {isGenerating ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : <Sparkles className="w-4 h-4 mr-2" />}
                                        Generate Steps
                                    </Button>
                                </div>
                            ) : (
                                <div className="space-y-4 py-4 max-h-[60vh] overflow-y-auto pr-2">
                                    <div className="bg-green-50 text-green-800 p-3 rounded-md text-sm mb-4">
                                        Generated {generatedSteps.length} steps. Review them below and apply to save.
                                    </div>
                                    {generatedSteps.map((step, idx) => (
                                        <div key={idx} className="border p-4 rounded-lg bg-gray-50 mb-3">
                                            <div className="font-semibold text-gray-700 mb-2">Step {idx + 1} (Wait {step.delay} {step.unit}s)</div>
                                            {step.send_email && (
                                                <div className="mb-2">
                                                    <div className="text-xs text-gray-500 uppercase tracking-wider font-bold">Email Subject</div>
                                                    <div className="text-sm font-medium">{step.email_subject_en}</div>
                                                    <div className="text-xs text-gray-500 uppercase tracking-wider font-bold mt-2">Email Body</div>
                                                    <div className="text-sm whitespace-pre-wrap">{step.email_content_en}</div>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                    <div className="flex justify-end space-x-3 mt-4">
                                        <Button variant="outline" onClick={() => setGeneratedSteps(null)}>Discard</Button>
                                        <Button onClick={handleApplyAI}>Apply Steps to Sequence</Button>
                                    </div>
                                </div>
                            )}
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <div className="max-w-3xl mx-auto py-8">
                {/* Trigger Node */}
                <div className="flex flex-col items-center mb-8">
                    <div className="bg-gray-900 text-white px-6 py-3 rounded-full font-medium shadow-lg flex items-center">
                        <ArrowRight className="w-5 h-5 mr-2 text-indigo-400" />
                        Trigger: {sequence.trigger_type.toUpperCase()}
                    </div>
                    <div className="w-1 h-8 bg-gray-300 my-2"></div>
                </div>

                {/* Steps List */}
                <div className="space-y-6">
                    {sequence.steps.map((step, index) => (
                        <div key={step.id} className="relative">
                            <Card className="border-l-4 border-l-indigo-500 shadow-md">
                                <CardHeader className="py-4 border-b bg-gray-50 flex flex-row items-center justify-between">
                                    <div className="flex items-center space-x-4">
                                        <div className="flex items-center text-sm font-medium text-gray-600 bg-white border px-3 py-1 rounded-full">
                                            <Clock className="w-4 h-4 mr-2 text-indigo-500" />
                                            Wait {step.delay} {step.unit}s
                                        </div>
                                    </div>
                                    <div className="flex space-x-2">
                                        <Button variant="ghost" size="sm" className="text-red-500 hover:text-red-700" onClick={() => handleDeleteStep(step.id)}>
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>
                                </CardHeader>
                                <CardContent className="p-0">
                                    <div className="divide-y">
                                        {step.send_email && (
                                            <div className="p-4 flex items-start">
                                                <div className="bg-blue-100 p-2 rounded-lg mr-4">
                                                    <Mail className="w-5 h-5 text-blue-600" />
                                                </div>
                                                <div className="flex-1">
                                                    <h4 className="text-sm font-semibold text-gray-900">Email: {step.email_subject?.en || 'No Subject'}</h4>
                                                    <p className="text-sm text-gray-500 mt-1 line-clamp-2">{step.email_content?.en || 'No Content'}</p>
                                                </div>
                                            </div>
                                        )}
                                        {step.send_whatsapp && (
                                            <div className="p-4 flex items-start">
                                                <div className="bg-green-100 p-2 rounded-lg mr-4">
                                                    <MessageCircle className="w-5 h-5 text-green-600" />
                                                </div>
                                                <div className="flex-1">
                                                    <h4 className="text-sm font-semibold text-gray-900">WhatsApp Message</h4>
                                                    <p className="text-sm text-gray-500 mt-1 line-clamp-2">{step.whatsapp_content?.en || 'No Content'}</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                            {index < sequence.steps.length - 1 && (
                                <div className="w-1 h-6 bg-gray-300 mx-auto my-2"></div>
                            )}
                        </div>
                    ))}

                    {sequence.steps.length === 0 && (
                        <div className="text-center py-12 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50">
                            <p className="text-gray-500 mb-4">No steps in this sequence yet.</p>
                            <Button variant="outline" onClick={() => setIsAiOpen(true)}>
                                <Sparkles className="w-4 h-4 mr-2 text-indigo-600" /> Generate with AI
                            </Button>
                        </div>
                    )}
                </div>
            </div>
            </div>
        </CrmLayout>
    );
}
