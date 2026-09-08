import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Calculator, Save, CheckCircle2, Play, Loader2 } from 'lucide-react';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

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
            alert('Failed to calculate: ' + ((error as any).response?.data?.error || error.message));
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
            <Head title={__('general.price_calculator')} />
            
            <div className="mx-auto w-full max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-2">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-muted text-muted-foreground mb-3 border border-border">{__('general.freelance_tools')}</span>
                    <div className="flex items-baseline gap-3">
                        <h1 className="text-3xl font-bold tracking-tight text-foreground">{__('general.smart_price_calculator')}</h1>
                        <span className="text-muted-foreground font-medium">/ AI Proposals</span>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    {/* Left Side: Input & Settings */}
                    <div className="lg:col-span-1 space-y-6">
                        <Card className="border-border">
                            <CardHeader>
                                <CardTitle className="text-foreground">{__('general.ai_cost_estimator')}</CardTitle>
                                <CardDescription className="text-muted-foreground">{__('general.describe_the_project_requirements_in_detail_the_ai_will_generate_a_structured_breakdown_and_pricing_in_egp')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-foreground mb-1">{__('general.project_details')}</label>
                                    <Textarea
                                        rows={8}
                                        placeholder={__('general.e_g_a_marketplace_app_like_uber_needs_ios_and_android_apps_plus_a_web_dashboard_for_admins')}
                                        value={projectDetails}
                                        onChange={(e) => setProjectDetails(e.target.value)}
                                        className="border-border"
                                    />
                                </div>
                                <Button 
                                    className="w-full" 
                                    onClick={handleCalculate}
                                    disabled={isCalculating}
                                >
                                    {isCalculating ? (
                                        <><Loader2 className="me-2 h-4 w-4 animate-spin" />{__('general.calculating')}</>
                                    ) : (
                                        <><Play className="me-2 h-4 w-4" />{__('general.calculate_with_ai')}</>
                                    )}
                                </Button>
                            </CardContent>
                        </Card>

                        {/* Saved Proposals List */}
                        <Card className="border-border">
                            <CardHeader>
                                <CardTitle className="text-foreground">{__('general.saved_proposals')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {proposals.length > 0 ? proposals.map(proposal => (
                                        <div key={proposal.id} className="flex flex-col p-3 border rounded-lg bg-muted/30 border-border">
                                            <div className="flex justify-between items-start mb-2">
                                                <div>
                                                    <div className="font-semibold text-foreground text-sm">{proposal.project_name}</div>
                                                    <div className="text-xs text-muted-foreground">{new Date(proposal.created_at).toLocaleDateString()}</div>
                                                </div>
                                                <div className="text-sm font-bold text-foreground">
                                                    {formatMoney(proposal.total_cost_egp, proposal.currency || proposal.currency_id)}
                                                </div>
                                            </div>
                                            {proposal.status === 'converted_to_contract' ? (
                                                <div className="text-xs text-emerald-600 dark:text-emerald-400 font-medium flex items-center mt-2">
                                                    <CheckCircle2 className="w-3 h-3 me-1" />{__('general.converted_to_contract')}</div>
                                            ) : (
                                                <div className="mt-2 flex space-x-2">
                                                    <Button size="sm" className="w-full text-xs" onClick={() => handleConvertToContract(proposal.id)}>{__('general.convert_to_contract')}</Button>
                                                </div>
                                            )}
                                        </div>
                                    )) : (
                                        <div className="text-sm text-muted-foreground text-center py-4">{__('general.no_saved_proposals_yet')}</div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Side: Results */}
                    <div className="lg:col-span-2">
                        <Card className="h-full min-h-[600px] border-border">
                            <CardHeader className="flex flex-row items-center justify-between">
                                <div>
                                    <CardTitle className="text-foreground">{__('general.proposal_breakdown')}</CardTitle>
                                    <CardDescription className="text-muted-foreground">{__('general.review_the_generated_estimate_before_saving')}</CardDescription>
                                </div>
                                {aiResult && (
                                    <Button onClick={handleSaveProposal} disabled={isSaving} variant="default">
                                        {isSaving ? <Loader2 className="me-2 h-4 w-4 animate-spin" /> : <Save className="me-2 h-4 w-4" />}
                                        Save Proposal
                                    </Button>
                                )}
                            </CardHeader>
                            <CardContent>
                                {aiResult ? (
                                    <div className="space-y-6">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="p-4 rounded-lg border border-border bg-muted/40">
                                                <div className="text-sm text-muted-foreground font-medium">{__('general.estimated_duration')}</div>
                                                <div className="text-2xl font-bold text-foreground">{aiResult.parsed_data.total_duration_days} Days</div>
                                            </div>
                                            <div className="p-4 rounded-lg border border-border bg-muted/40">
                                                <div className="text-sm text-muted-foreground font-medium">{__('general.estimated_cost')}</div>
                                                <div className="text-2xl font-bold text-foreground">{formatMoney(aiResult.total_cost_egp, aiResult.currency || aiResult.currency_id)}</div>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <h3 className="font-semibold text-lg text-foreground mb-2">{__('general.proposal_document')}</h3>
                                            <div className="bg-muted/60 border border-border rounded-lg p-4 overflow-x-auto">
                                                <pre className="text-sm text-foreground font-mono whitespace-pre-wrap">
                                                    {aiResult.ascii_table}
                                                </pre>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="h-full flex flex-col items-center justify-center text-muted-foreground space-y-4 py-24">
                                        <Calculator className="w-16 h-16 opacity-20" />
                                        <p>{__('general.enter_project_details_and_click_calculate_to_view_results')}</p>
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
