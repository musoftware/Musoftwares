import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Calculator, Save, CheckCircle2, Play, Loader2 } from 'lucide-react';

export default function Index({ proposals }) {
    const [projectDetails, setProjectDetails] = useState('');
    const [isCalculating, setIsCalculating] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [aiResult, setAiResult] = useState<any>(null);

    const handleCalculate = async () => {
        if (!projectDetails || projectDetails.length < 20) {
            alert('Please enter at least 20 characters of project details.');
            return;
        }

        setIsCalculating(true);
        setAiResult(null);

        try {
            const response = await window.axios.post(route('isaas.calculator.calculate-ai'), {
                project_details: projectDetails
            });
            
            setAiResult(response.data);
        } catch (error: any) {
            alert('Failed to calculate: ' + (error.response?.data?.error || error.message));
        } finally {
            setIsCalculating(false);
        }
    };

    const handleSaveProposal = () => {
        if (!aiResult) return;

        setIsSaving(true);
        
        router.post(route('isaas.calculator.save'), {
            project_details: aiResult.project_details,
            parsed_data: aiResult.parsed_data,
            total_cost_egp: aiResult.total_cost_egp,
            ascii_table: aiResult.ascii_table,
        }, {
            onSuccess: () => {
                setIsSaving(false);
                alert('Proposal Saved!');
            },
            onError: () => {
                setIsSaving(false);
                alert('Failed to save proposal.');
            }
        });
    };

    const handleConvertToContract = (id) => {
        if (confirm('Are you sure you want to convert this proposal into a Contract?')) {
            router.post(route('isaas.calculator.convert', id));
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Price Calculator" />
            
            <div className="mx-auto w-full max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-2">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600 mb-3 border border-slate-200">
                        Freelance Tools
                    </span>
                    <div className="flex items-baseline gap-3">
                        <h1 className="text-3xl font-bold tracking-tight text-slate-900">Smart Price Calculator</h1>
                        <span className="text-slate-500 font-medium">/ AI Proposals</span>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    {/* Left Side: Input & Settings */}
                    <div className="lg:col-span-1 space-y-6">
                        <Card className="border border-slate-200">
                            <CardHeader>
                                <CardTitle>AI Cost Estimator</CardTitle>
                                <CardDescription>
                                    Describe the project requirements in detail. The AI will generate a structured breakdown and pricing in EGP.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Project Details
                                    </label>
                                    <Textarea
                                        rows={8}
                                        placeholder="e.g. A marketplace app like Uber, needs iOS and Android apps, plus a web dashboard for admins..."
                                        value={projectDetails}
                                        onChange={(e) => setProjectDetails(e.target.value)}
                                    />
                                </div>
                                <Button 
                                    className="w-full bg-slate-900 hover:bg-slate-800 text-white" 
                                    onClick={handleCalculate}
                                    disabled={isCalculating}
                                >
                                    {isCalculating ? (
                                        <><Loader2 className="mr-2 h-4 w-4 animate-spin" /> Calculating...</>
                                    ) : (
                                        <><Play className="mr-2 h-4 w-4" /> Calculate with AI</>
                                    )}
                                </Button>
                            </CardContent>
                        </Card>

                        {/* Saved Proposals List */}
                        <Card className="border border-slate-200">
                            <CardHeader>
                                <CardTitle>Saved Proposals</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {proposals.length > 0 ? proposals.map(proposal => (
                                        <div key={proposal.id} className="flex flex-col p-3 border rounded-lg bg-gray-50/50 border-slate-200">
                                            <div className="flex justify-between items-start mb-2">
                                                <div>
                                                    <div className="font-semibold text-slate-800 text-sm">{proposal.project_name}</div>
                                                    <div className="text-xs text-gray-400">{new Date(proposal.created_at).toLocaleDateString()}</div>
                                                </div>
                                                <div className="text-sm font-bold text-slate-900">
                                                    {parseFloat(proposal.total_cost_egp).toLocaleString()} EGP
                                                </div>
                                            </div>
                                            {proposal.status === 'converted_to_contract' ? (
                                                <div className="text-xs text-green-600 font-medium flex items-center mt-2">
                                                    <CheckCircle2 className="w-3 h-3 mr-1" /> Converted to Contract
                                                </div>
                                            ) : (
                                                <div className="mt-2 flex space-x-2">
                                                    <Button size="sm" className="w-full text-xs bg-slate-900 hover:bg-slate-800 text-white" onClick={() => handleConvertToContract(proposal.id)}>
                                                        Convert to Contract
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    )) : (
                                        <div className="text-sm text-gray-500 text-center py-4">No saved proposals yet.</div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Side: Results */}
                    <div className="lg:col-span-2">
                        <Card className="h-full min-h-[600px] border border-slate-200">
                            <CardHeader className="flex flex-row items-center justify-between">
                                <div>
                                    <CardTitle>Proposal & Breakdown</CardTitle>
                                    <CardDescription>Review the generated estimate before saving.</CardDescription>
                                </div>
                                {aiResult && (
                                    <Button onClick={handleSaveProposal} disabled={isSaving} variant="default" className="bg-slate-900 hover:bg-slate-800 text-white">
                                        {isSaving ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />}
                                        Save Proposal
                                    </Button>
                                )}
                            </CardHeader>
                            <CardContent>
                                {aiResult ? (
                                    <div className="space-y-6">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="p-4 rounded-lg border border-slate-200 bg-slate-50/50">
                                                <div className="text-sm text-slate-500 font-medium">Estimated Duration</div>
                                                <div className="text-2xl font-bold text-slate-900">{aiResult.parsed_data.total_duration_days} Days</div>
                                            </div>
                                            <div className="p-4 rounded-lg border border-slate-200 bg-slate-50/50">
                                                <div className="text-sm text-slate-500 font-medium">Estimated Cost</div>
                                                <div className="text-2xl font-bold text-slate-900">{aiResult.total_cost_egp.toLocaleString()} EGP</div>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <h3 className="font-semibold text-lg text-slate-800 mb-2">Proposal Document</h3>
                                            <div className="bg-slate-900 rounded-lg p-4 overflow-x-auto">
                                                <pre className="text-sm text-slate-200 font-mono whitespace-pre-wrap">
                                                    {aiResult.ascii_table}
                                                </pre>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="h-full flex flex-col items-center justify-center text-gray-400 space-y-4 py-24">
                                        <Calculator className="w-16 h-16 opacity-20" />
                                        <p>Enter project details and click Calculate to view results.</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
