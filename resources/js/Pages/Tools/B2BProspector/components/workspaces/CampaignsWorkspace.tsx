import React from 'react';
import { Users, Plus, ChevronRight, Square, Play, CheckCircle } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { B2BCampaign, B2BCampaignStats } from '../../types/b2b.types';
import { __ } from '@/lib/i18n';

interface CampaignsWorkspaceProps {
    campaigns: B2BCampaign[];
    runningCampaignIds: string[];
    campaignStats: Record<string, B2BCampaignStats>;
    showNewCampaignModal: boolean;
    setShowNewCampaignModal: (show: boolean) => void;
    newCampName: string;
    setNewCampName: (name: string) => void;
    newCampKeyword: string;
    setNewCampKeyword: (k: string) => void;
    newCampCountry: string;
    setNewCampCountry: (c: string) => void;
    newCampCity: string;
    setNewCampCity: (c: string) => void;
    newCampSources: string[];
    toggleSource: (s: string) => void;
    newCampLimit: number;
    setNewCampLimit: (l: number) => void;
    handleCreateCampaign: (e: React.FormEvent) => void;
    handleStartCampaign: (id: string) => void;
    handleStopCampaign: (id: string) => void;
    onViewLeads: (id: string) => void;
}

export function CampaignsWorkspace(props: CampaignsWorkspaceProps) {
    return (
        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-slate-950 to-slate-800 bg-clip-text text-transparent">{__('general.lead_finder')}</h1>
                    <p className="text-xs text-slate-500 mt-1">{__('general.create_searching_sequences_to_discover_target_accounts_and_verify_their_email_data_privately')}</p>
                </div>
                <Button onClick={() => props.setShowNewCampaignModal(true)} className="gap-1.5">
                    <Plus className="w-4 h-4" />{__('general.new_search_campaign')}</Button>
            </div>

            {props.campaigns.length === 0 ? (
                <Card className="py-24 text-center border-dashed">
                    <Users className="w-10 h-10 text-slate-300 mx-auto mb-4" />
                    <h3 className="text-sm font-bold text-slate-900">{__('general.no_campaigns_launched_yet')}</h3>
                    <p className="text-xs text-slate-500 mt-1.5 max-w-xs mx-auto">{__('general.create_your_first_lead_finder_campaign_to_start_sourcing_verified_emails_locally')}</p>
                    <Button onClick={() => props.setShowNewCampaignModal(true)} className="mt-6">{__('general.create_campaign')}</Button>
                </Card>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {props.campaigns.map(camp => {
                        const stats = props.campaignStats[camp.id] || { total: 0, valid: 0, emailed: 0 };
                        const isRunning = props.runningCampaignIds.includes(camp.id);
                        
                        return (
                            <Card key={camp.id} className="p-6 hover:shadow-md transition-all flex flex-col justify-between relative overflow-hidden group">
                                {isRunning && (
                                    <div className="absolute top-0 start-0 end-0 h-1 bg-gradient-to-r from-teal-400 to-indigo-500 animate-pulse" />
                                )}
                                
                                <div>
                                    <div className="flex items-start justify-between mb-4">
                                        <div>
                                            <h3 className="font-bold text-slate-900 text-sm leading-tight group-hover:text-teal-600 transition-colors">{camp.name}</h3>
                                            <span className="text-[10px] text-slate-400 mt-0.5 block font-mono">{camp.keyword} • {camp.city || camp.country || 'Global'}</span>
                                        </div>
                                        <Badge variant="outline" className={`uppercase tracking-wide ${
                                            camp.status === 'running' ? 'bg-teal-50 text-teal-700 border-teal-200' :
                                            camp.status === 'completed' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' :
                                            'bg-slate-50 text-slate-500 border-slate-200'
                                        }`}>
                                            {camp.status}
                                        </Badge>
                                    </div>

                                    <div className="flex items-center gap-2 mt-2 py-1.5 px-2 bg-slate-50 rounded-lg text-[10px] text-slate-500">
                                        <span className="font-bold">Sources:</span>
                                        <span>{camp.sources?.join(', ') || 'LinkedIn'}</span>
                                        <span className="h-2 w-px bg-slate-200" />
                                        <span className="font-bold">Speed Limit:</span>
                                        <span>{camp.daily_limit} leads</span>
                                    </div>

                                    <div className="grid grid-cols-3 gap-2.5 my-5">
                                        <div className="bg-slate-50/50 p-2.5 rounded-xl border border-slate-100 text-center">
                                            <span className="text-slate-400 text-[10px] font-semibold block">{__('general.extracted')}</span>
                                            <span className="text-slate-900 text-sm font-bold block mt-0.5">{stats.total}</span>
                                        </div>
                                        <div className="bg-emerald-50/20 p-2.5 rounded-xl border border-emerald-100/30 text-center">
                                            <span className="text-emerald-700/60 text-[10px] font-semibold block">{__('general.clean_emails')}</span>
                                            <span className="text-emerald-700 text-sm font-bold block mt-0.5">{stats.valid}</span>
                                        </div>
                                        <div className="bg-slate-50/50 p-2.5 rounded-xl border border-slate-100 text-center">
                                            <span className="text-slate-400 text-[10px] font-semibold block">{__('general.emailed')}</span>
                                            <span className="text-slate-900 text-sm font-bold block mt-0.5">{stats.emailed}</span>
                                        </div>
                                    </div>
                                </div>

                                <div className="border-t border-slate-100 pt-4 flex items-center justify-between gap-3">
                                    <Button 
                                        variant="ghost" size="sm"
                                        onClick={() => props.onViewLeads(camp.id)}
                                        className="text-xs text-slate-500 hover:text-slate-900"
                                    >{__('general.view_leads')}<ChevronRight className="w-3.5 h-3.5 ms-1" />
                                    </Button>
                                    
                                    {isRunning ? (
                                        <Button 
                                            variant="outline" size="sm"
                                            onClick={() => props.handleStopCampaign(camp.id)}
                                            className="h-8 bg-rose-50 hover:bg-rose-100 text-rose-700 border-rose-100 text-[11px]"
                                        >
                                            <Square className="w-3 h-3 fill-rose-700 me-1" />{__('general.pause_search')}</Button>
                                    ) : (
                                        <Button 
                                            variant="outline" size="sm"
                                            onClick={() => props.handleStartCampaign(camp.id)}
                                            className="h-8 bg-teal-50 hover:bg-teal-100 text-teal-700 border-teal-100 text-[11px]"
                                        >
                                            <Play className="w-3 h-3 fill-teal-700 me-1" />{__('general.launch_scraper')}</Button>
                                    )}
                                </div>
                            </Card>
                        );
                    })}
                </div>
            )}

            {/* Modal */}
            {props.showNewCampaignModal && (
                <div className="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-200">
                    <div className="bg-white w-full max-w-lg rounded-2xl shadow-xl overflow-hidden border border-slate-200/80 animate-in zoom-in-95 duration-200">
                        <div className="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <h3 className="font-bold text-slate-900 text-sm tracking-tight">{__('general.new_search_campaign')}</h3>
                            <Button
                                variant="ghost" size="icon"
                                onClick={() => props.setShowNewCampaignModal(false)}
                                className="h-6 w-6 text-slate-400 hover:text-slate-900 font-bold text-sm hover:bg-transparent"
                            >✕</Button>
                        </div>
                        <form onSubmit={props.handleCreateCampaign} className="p-6 space-y-4 text-xs">
                            <div className="space-y-1">
                                <Label>{__('general.campaign_name')}</Label>
                                <Input type="text" required placeholder={__('general.e_g_us_tech_founders')} value={props.newCampName} onChange={e => props.setNewCampName(e.target.value)} />
                            </div>
                            <div className="space-y-1">
                                <Label>Target Search Keyword (Job title or company domain)</Label>
                                <Input type="text" required placeholder={__('general.e_g_chief_executive_officer')} value={props.newCampKeyword} onChange={e => props.setNewCampKeyword(e.target.value)} />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-1">
                                    <Label>{__('general.country_filter')}</Label>
                                    <Input type="text" placeholder="USA" value={props.newCampCountry} onChange={e => props.setNewCampCountry(e.target.value)} />
                                </div>
                                <div className="space-y-1">
                                    <Label>City Filter (Optional)</Label>
                                    <Input type="text" placeholder={__('general.san_francisco')} value={props.newCampCity} onChange={e => props.setNewCampCity(e.target.value)} />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label>{__('general.lead_search_sources')}</Label>
                                <div className="flex items-center gap-3">
                                    <Button type="button" variant={props.newCampSources.includes('linkedin') ? 'default' : 'outline'} onClick={() => props.toggleSource('linkedin')} className="gap-1.5">
                                        <CheckCircle className={`w-3.5 h-3.5 ${props.newCampSources.includes('linkedin') ? 'text-teal-400' : 'opacity-30'}`} />{__('general.linkedin_profile_search')}</Button>
                                    <Button type="button" variant={props.newCampSources.includes('google_maps') ? 'default' : 'outline'} onClick={() => props.toggleSource('google_maps')} className="gap-1.5">
                                        <CheckCircle className={`w-3.5 h-3.5 ${props.newCampSources.includes('google_maps') ? 'text-teal-400' : 'opacity-30'}`} />{__('general.google_maps_places')}</Button>
                                </div>
                            </div>
                            <div className="space-y-1 max-w-[200px]">
                                <Label>Daily Speed Limit (leads)</Label>
                                <Input type="number" min={10} max={1000} value={props.newCampLimit} onChange={e => props.setNewCampLimit(parseInt(e.target.value) || 100)} className="text-center font-bold" />
                            </div>
                            <div className="pt-4 border-t border-slate-100 flex items-center justify-end gap-3.5">
                                <Button type="button" variant="ghost" onClick={() => props.setShowNewCampaignModal(false)}>{__('general.cancel')}</Button>
                                <Button type="submit">{__('general.save_draft_campaign')}</Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
