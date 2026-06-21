import React from 'react';
import { ChevronRight, FileText } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { B2BCampaign, B2BSequence } from '../../types/b2b.types';
import { __ } from '@/lib/i18n';

interface OutreachWorkspaceProps {
    campaigns: B2BCampaign[];
    selectedSequenceCampaignId: string;
    setSelectedSequenceCampaignId: (id: string) => void;
    seqSubject: string;
    setSeqSubject: (s: string) => void;
    seqBody: string;
    setSeqBody: (s: string) => void;
    seqDelay: number;
    setSeqDelay: (n: number) => void;
    savingSequence: boolean;
    handleSaveSequence: (e: React.FormEvent) => void;
}

export function OutreachWorkspace(props: OutreachWorkspaceProps) {
    return (
        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
            <div>
                <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-slate-950 to-slate-800 bg-clip-text text-transparent">{__('general.outreach_sequences')}</h1>
                <p className="text-xs text-slate-500 mt-1">{__('general.write_automated_cold_email_content_sequence_templates_personalize_content_using_lead_data_variables')}</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="md:col-span-1 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <h3 className="font-bold text-slate-900 text-xs uppercase tracking-wider mb-4">{__('general.select_target_campaign')}</h3>
                    
                    <div className="space-y-2">
                        {props.campaigns.length === 0 ? (
                            <p className="text-xs text-slate-400">{__('general.create_a_campaign_first_to_configure_its_email_sequences')}</p>
                        ) : (
                            props.campaigns.map(camp => (
                                <button
                                    key={camp.id}
                                    onClick={() => props.setSelectedSequenceCampaignId(camp.id)}
                                    className={`w-full text-start p-3 rounded-xl border text-xs font-semibold flex items-center justify-between transition-all ${
                                        props.selectedSequenceCampaignId === camp.id 
                                            ? 'border-slate-900 bg-slate-950 text-white shadow-sm' 
                                            : 'border-slate-100 hover:border-slate-200 hover:bg-slate-50 text-slate-700'
                                    }`}
                                >
                                    <div>
                                        <span className="block truncate">{camp.name}</span>
                                        <span className={`text-[9px] uppercase font-mono tracking-wider ${props.selectedSequenceCampaignId === camp.id ? 'text-teal-300' : 'text-slate-400'}`}>
                                            {camp.keyword}
                                        </span>
                                    </div>
                                    <ChevronRight className="w-4 h-4 shrink-0" />
                                </button>
                            ))
                        )}
                    </div>
                </div>

                <div className="md:col-span-2">
                    {props.selectedSequenceCampaignId ? (
                        <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                                <h3 className="font-bold text-slate-900 text-xs uppercase tracking-wider">Outreach Sequence Step 1</h3>
                                <span className="px-2 py-0.5 rounded-full text-[9px] bg-slate-50 text-slate-500 font-bold border border-slate-200">{__('general.step_1_outreach')}</span>
                            </div>

                            <form onSubmit={props.handleSaveSequence} className="space-y-4 text-xs">
                                <div className="space-y-1">
                                    <Label>{__('general.email_subject_line')}</Label>
                                    <Input 
                                        type="text" required placeholder={__('general.e_g_quick_question_about_company')}
                                        value={props.seqSubject} onChange={e => props.setSeqSubject(e.target.value)}
                                    />
                                </div>

                                <div className="space-y-1">
                                    <div className="flex items-center justify-between mb-1">
                                        <Label>{__('general.message_body')}</Label>
                                        <span className="text-[10px] text-slate-400 font-mono">Variables: {"{{name}}"}, {"{{company}}"}, {"{{title}}"}</span>
                                    </div>
                                    <Textarea 
                                        required rows={8}
                                        placeholder={`Hi {{name}},\n\nSaw you are the {{title}} at {{company}}.\n\nWould love to discuss your local scraping setup.\n\nBest,\nSales Team`}
                                        value={props.seqBody} onChange={e => props.setSeqBody(e.target.value)}
                                    />
                                </div>

                                <div className="space-y-1 max-w-xs">
                                    <Label>{__('general.time_delay_before_sending')}</Label>
                                    <div className="flex items-center gap-2">
                                        <Input 
                                            type="number" min={1} 
                                            value={props.seqDelay} onChange={e => props.setSeqDelay(parseInt(e.target.value) || 1)}
                                            className="w-20 text-center font-bold"
                                        />
                                        <span className="text-slate-500">{__('general.days_after_trigger')}</span>
                                    </div>
                                </div>

                                <div className="pt-3 border-t border-slate-100 flex justify-end">
                                    <Button type="submit" disabled={props.savingSequence}>
                                        {props.savingSequence ? 'Saving Template...' : 'Save Outreach sequence'}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    ) : (
                        <div className="bg-white border border-slate-200 rounded-2xl p-16 text-center shadow-sm">
                            <FileText className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                            <h4 className="text-xs font-bold text-slate-900">{__('general.no_target_campaign_selected')}</h4>
                            <p className="text-[11px] text-slate-400 mt-1 max-w-xs mx-auto">{__('general.select_an_active_search_campaign_from_the_left_sidebar_list_to_edit_its_cold_message_sequences')}</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
