import React from 'react';
import { Download, CheckCircle, Search, MailQuestion } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { B2BCampaign, B2BLead } from '../../types/b2b.types';
import { __ } from '@/lib/i18n';

interface LeadsWorkspaceProps {
    leads: B2BLead[];
    totalLeads: number;
    leadsOffset: number;
    setLeadsOffset: React.Dispatch<React.SetStateAction<number>>;
    leadsLimit: number;
    leadsSearch: string;
    setLeadsSearch: (s: string) => void;
    leadsEmailFilter: string;
    setLeadsEmailFilter: (s: string) => void;
    selectedCampaignId: string;
    setSelectedCampaignId: (id: string) => void;
    campaigns: B2BCampaign[];
    exportingJobId: string | null;
    exportProgress: number | null;
    exportFilePath: string | null;
    handleExportLeads: () => void;
}

export function LeadsWorkspace(props: LeadsWorkspaceProps) {
    const getEmailBadgeColor = (status: string) => {
        switch (status) {
            case 'valid': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            case 'invalid': return 'bg-rose-50 text-rose-700 border-rose-200';
            case 'catchall': return 'bg-amber-50 text-amber-700 border-amber-200';
            case 'unknown':
            default: return 'bg-slate-50 text-slate-500 border-slate-200';
        }
    };

    const getEmailLabel = (status: string) => {
        switch (status) {
            case 'valid': return 'Verified Clean';
            case 'invalid': return 'Risky Bounce';
            case 'catchall': return 'Accept All';
            case 'unknown':
            default: return 'Unverified';
        }
    };

    const getScoreBadge = (score: number) => {
        if (score >= 80) return 'bg-teal-50 text-teal-700 border-teal-200 font-semibold';
        if (score >= 50) return 'bg-indigo-50 text-indigo-600 border-indigo-200';
        return 'bg-slate-50 text-slate-500 border-slate-200';
    };

    return (
        <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-slate-950 to-slate-800 bg-clip-text text-transparent">{__('general.lead_database')}</h1>
                    <p className="text-xs text-slate-500 mt-1">{__('general.review_harvested_decision_makers_verified_contact_emails_and_outbound_status')}</p>
                </div>
                <Button onClick={props.handleExportLeads} disabled={!!props.exportingJobId} variant="outline" className="gap-1.5">
                    <Download className="w-4 h-4" /> 
                    {props.exportingJobId ? `Exporting (${props.exportProgress || 0} rows)...` : 'Download CSV'}
                </Button>
            </div>

            {props.exportFilePath && (
                <div className="p-3.5 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-xl text-xs flex items-start gap-2.5">
                    <CheckCircle className="w-4.5 h-4.5 text-emerald-600 shrink-0 mt-0.5" />
                    <div>
                        <p className="font-bold">{__('general.leads_exported_to_local_drive')}</p>
                        <p className="text-[11px] opacity-90 mt-0.5 font-mono select-all">{props.exportFilePath}</p>
                    </div>
                </div>
            )}

            <div className="p-4 bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col md:flex-row items-center gap-3.5">
                <div className="relative flex-1 w-full">
                    <Search className="w-4 h-4 text-slate-400 absolute start-3 top-3.5" />
                    <Input 
                        type="text" placeholder={__('general.search_by_name_company_or_email')} 
                        value={props.leadsSearch}
                        onChange={(e) => { props.setLeadsSearch(e.target.value); props.setLeadsOffset(0); }}
                        className="ps-9 bg-slate-50 border-slate-200 h-10 w-full"
                    />
                </div>

                <div className="flex items-center gap-3.5 w-full md:w-auto shrink-0">
                    <select 
                        value={props.selectedCampaignId}
                        onChange={(e) => { props.setSelectedCampaignId(e.target.value); props.setLeadsOffset(0); }}
                        className="md:w-44 bg-slate-50 border border-slate-200 h-10 text-xs rounded-lg px-2.5 outline-none focus:border-ring focus:ring-3 focus:ring-ring/50 transition-colors"
                    >
                        <option value="">{__('general.all_campaigns')}</option>
                        {props.campaigns.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                    </select>

                    <select 
                        value={props.leadsEmailFilter}
                        onChange={(e) => { props.setLeadsEmailFilter(e.target.value); props.setLeadsOffset(0); }}
                        className="md:w-44 bg-slate-50 border border-slate-200 h-10 text-xs rounded-lg px-2.5 outline-none focus:border-ring focus:ring-3 focus:ring-ring/50 transition-colors"
                    >
                        <option value="">{__('general.all_verification_states')}</option>
                        <option value="valid">{__('general.verified_clean')}</option>
                        <option value="invalid">{__('general.risky_bounce')}</option>
                        <option value="catchall">{__('general.accept_all')}</option>
                        <option value="unverified">{__('general.unverified')}</option>
                    </select>
                </div>
            </div>

            {props.leads.length === 0 ? (
                <div className="py-20 text-center bg-white border border-slate-200 rounded-2xl">
                    <MailQuestion className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                    <h3 className="text-sm font-bold text-slate-900">{__('general.no_matching_leads_found')}</h3>
                    <p className="text-xs text-slate-500 mt-1 max-w-7xl mx-auto">{__('general.verify_that_you_have_launched_an_active_lead_finder_campaign_or_try_clearing_search_queries')}</p>
                </div>
            ) : (
                <div className="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-start border-collapse">
                            <thead>
                                <tr className="bg-slate-50/50 border-b border-slate-200 text-slate-500 text-[10px] font-bold uppercase tracking-wider">
                                    <th className="p-4 ps-6">{__('general.profile')}</th>
                                    <th className="p-4">{__('general.corporate_role')}</th>
                                    <th className="p-4">{__('general.contact_info')}</th>
                                    <th className="p-4">{__('general.quality_score')}</th>
                                    <th className="p-4">{__('general.outbox_status')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 text-xs">
                                {props.leads.map(lead => (
                                    <tr key={lead.id} className="hover:bg-slate-50/50 transition-colors">
                                        <td className="p-4 ps-6">
                                            <div className="flex items-center gap-3">
                                                <div className="w-8 h-8 bg-gradient-to-tr from-slate-100 to-slate-200 text-slate-600 rounded-lg flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                                                    {lead.name ? lead.name[0] : '?'}
                                                </div>
                                                <div>
                                                    <span className="font-bold text-slate-900 block">{lead.name || 'Decision Maker'}</span>
                                                    <span className="text-[10px] text-slate-400 block font-mono">{lead.source}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="p-4">
                                            <span className="text-slate-900 font-medium block">{lead.title || 'Executive'}</span>
                                            <span className="text-[10px] text-slate-400 block">{lead.company || 'Confidential Company'}</span>
                                        </td>
                                        <td className="p-4">
                                            <span className="text-slate-900 font-mono block select-all">{lead.email || 'Searching email...'}</span>
                                            {lead.email && (
                                                <span className={`px-2 py-0.5 rounded-full text-[9px] font-bold border inline-block mt-1 ${getEmailBadgeColor(lead.email_status)}`}>
                                                    {getEmailLabel(lead.email_status)}
                                                </span>
                                            )}
                                        </td>
                                        <td className="p-4">
                                            <span className={`px-2.5 py-1 rounded-lg text-[10px] font-bold border ${getScoreBadge(lead.lead_score)}`}>
                                                {lead.lead_score ? `${lead.lead_score}/100` : 'Evaluating...'}
                                            </span>
                                        </td>
                                        <td className="p-4">
                                            <span className={`px-2 py-0.5 rounded-full text-[9px] font-bold uppercase ${
                                                lead.outreach_status === 'sent' ? 'bg-indigo-50 text-indigo-700' :
                                                'bg-slate-50 text-slate-500'
                                            }`}>
                                                {lead.outreach_status}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    
                    <div className="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Showing {props.leads.length} of {props.totalLeads} harvested leads</span>
                        <div className="flex items-center gap-2">
                            <Button 
                                onClick={() => props.setLeadsOffset(prev => Math.max(0, prev - props.leadsLimit))}
                                disabled={props.leadsOffset === 0}
                                variant="outline" size="sm"
                            >{__('general.back')}</Button>
                            <Button 
                                onClick={() => props.setLeadsOffset(prev => prev + props.leadsLimit)}
                                disabled={props.leadsOffset + props.leadsLimit >= props.totalLeads}
                                variant="outline" size="sm"
                            >{__('general.next')}</Button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
