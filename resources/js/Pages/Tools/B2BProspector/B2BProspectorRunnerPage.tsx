import React, { useState } from 'react';
import { useRuntimeWS } from '@/hooks/useRuntimeWS';
import { RuntimePluginModals } from '@/Components/Tools/RuntimePluginModals';
import { B2BProspectorHeader } from './components/shared/Header';
import { B2BProspectorSidebar } from './components/shared/Sidebar';
import { CampaignsWorkspace } from './components/workspaces/CampaignsWorkspace';
import { LeadsWorkspace } from './components/workspaces/LeadsWorkspace';
import { InboxesWorkspace } from './components/workspaces/InboxesWorkspace';
import { OutreachWorkspace } from './components/workspaces/OutreachWorkspace';
import { LinkedInWorkspace } from './components/workspaces/LinkedInWorkspace';
import { 
    useB2BProspectorState,
    useProspectingCampaigns,
    useProspectingLeads,
    useProspectingInboxes,
    useProspectingSequences,
    useProspectingLinkedIn
} from './hooks/useB2BProspector';

export default function B2BProspectorRunnerPage({ tool, subscription, runtimePort, pluginSlug }: any) {
    const [activeTab, setActiveTab] = useState<'campaigns' | 'leads' | 'inboxes' | 'outreach' | 'linked-profiles'>('campaigns');

    // Setup base state
    const { realtimeLogs, addRealtimeLog } = useB2BProspectorState(false, null);

    // Runtime WS handler
    const onBroadcast = (event: string, data: any) => {
        if (event === 'prospecting.lead.extracted.ui' && data.lead) {
            addRealtimeLog(`Harvested: ${data.lead.name || 'Decision Maker'} (${data.lead.company || 'Unknown Inc.'})`);
            // The leads hook will fetch on its own when necessary or we can let it auto-refresh via polling if needed.
            // For now, we will rely on standard refetches since we decoupled the massive hook.
            // Ideally we'd trigger a fetch but we just re-fetch in the hooks on dependencies.
        }
        if (event === 'prospecting.campaign.updated') {
            const { campaignId, status, error } = data;
            if (status === 'running') addRealtimeLog(`Campaign started successfully.`);
            else if (status === 'completed') addRealtimeLog(`Campaign finished! All leads enriched and verified.`);
            else if (status === 'failed') addRealtimeLog(`Campaign error: ${error || 'Unknown issue'}`);
        }
        if (event === 'lead.saved') {
            addRealtimeLog(`Email verified: Status is ${data.emailStatus || 'unverified'} (Score: ${data.score || 0}/100)`);
        }
        if (event === 'outreach.sent') {
            addRealtimeLog(`Outbound Sequence Sent for Lead ID: ${data.leadId}`);
        }
        if (event === 'export.progress') {
            // Handled inside leads hook if needed, but for now we just show in leads workspace
        }
        if (event === 'export.completed') {
            addRealtimeLog(`Leads exported successfully to local path.`);
        }
        if (event === 'inbox.connected') {
            addRealtimeLog(`Email connected: ${data.email} is ready to send messages.`);
        }
        if (event === 'inbox.failed') {
            addRealtimeLog(`Email connection failed for ${data.email}: ${data.error}`);
        }
    };

    const { connected: agentConnected, callRPC, installingPlugin, loginRequired, setLoginRequired } = useRuntimeWS('b2b-prospector', onBroadcast);

    // Initialize specific hooks
    const campaignsHook = useProspectingCampaigns(agentConnected, callRPC, addRealtimeLog);
    const leadsHook = useProspectingLeads(agentConnected, callRPC, addRealtimeLog);
    const inboxesHook = useProspectingInboxes(agentConnected, callRPC, addRealtimeLog);
    const sequencesHook = useProspectingSequences(agentConnected, callRPC, addRealtimeLog);
    const linkedInHook = useProspectingLinkedIn(agentConnected, callRPC, addRealtimeLog);

    if (!agentConnected) {
        return (
            <div className="min-h-screen bg-[#F8FAFC] flex items-center justify-center font-sans">
                <div className="text-center space-y-4">
                    <div className="w-10 h-10 border-2 border-slate-900 border-t-transparent rounded-full animate-spin mx-auto" />
                    <p className="text-sm font-semibold text-slate-600">{__('general.syncing_with_local_runtime_agent')}</p>
                    <p className="text-xs text-slate-400">{__('general.make_sure_your_musoftware_runtime_is_running_on_your_machine')}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-[#F8FAFC] text-slate-900 font-sans flex flex-col antialiased selection:bg-teal-500 selection:text-white">
            <RuntimePluginModals 
                installingPlugin={installingPlugin} 
                loginRequired={loginRequired} 
                setLoginRequired={setLoginRequired} 
            />

            <B2BProspectorHeader activeTab={activeTab} setActiveTab={setActiveTab} />

            <div className="flex-1 flex overflow-hidden">
                <B2BProspectorSidebar 
                    realtimeLogs={realtimeLogs}
                    linkedInSession={linkedInHook.linkedInSession}
                    inboxes={inboxesHook.inboxes}
                    runningCampaignIds={campaignsHook.runningCampaignIds}
                />

                <main className="flex-1 overflow-y-auto p-8 max-w-5xl mx-auto w-full">
                    {activeTab === 'campaigns' && (
                        <CampaignsWorkspace 
                            {...campaignsHook} 
                            onViewLeads={(id) => {
                                leadsHook.setSelectedCampaignId(id);
                                setActiveTab('leads');
                            }}
                        />
                    )}
                    
                    {activeTab === 'leads' && (
                        <LeadsWorkspace 
                            {...leadsHook} 
                            campaigns={campaignsHook.campaigns} 
                        />
                    )}
                    
                    {activeTab === 'inboxes' && (
                        <InboxesWorkspace 
                            {...inboxesHook} 
                        />
                    )}
                    
                    {activeTab === 'outreach' && (
                        <OutreachWorkspace 
                            {...sequencesHook}
                            campaigns={campaignsHook.campaigns} 
                        />
                    )}
                    
                    {activeTab === 'linked-profiles' && (
                        <LinkedInWorkspace 
                            {...linkedInHook} 
                        />
                    )}
                </main>
            </div>
        </div>
    );
}
