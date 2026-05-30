import React, { useState, useEffect, useRef } from 'react';
import { Mail, Send, Users, Server, FileText, Plus, Settings, Play, Pause, Trash2, Activity, CheckCircle2, XCircle, AlertCircle, Copy, Square, LayoutDashboard } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

import { useRuntimeWS } from '@/hooks/useRuntimeWS';
import { RuntimePluginModals } from '@/Components/Tools/RuntimePluginModals';

export default function EmailSenderRunner({ tool, subscription, runtimePort, pluginSlug = 'email-sender' }: any) {
    const [activeTab, setActiveTab] = useState('dashboard');
    const { connected, callRPC, installingPlugin, loginRequired, setLoginRequired } = useRuntimeWS(pluginSlug);
    
    // Data States
    const [globalStats, setGlobalStats] = useState({ campaigns: 0, sent: 0, opens: 0, clicks: 0, unsubscribes: 0 });
    const [campaigns, setCampaigns] = useState<any[]>([]);
    const [lists, setLists] = useState<any[]>([]);
    const [templates, setTemplates] = useState<any[]>([]);
    const [accounts, setAccounts] = useState<any[]>([]);
    const [settings, setSettings] = useState({ api_key: '' });

    // Modal States
    const [isCampaignModalOpen, setIsCampaignModalOpen] = useState(false);
    const [isListModalOpen, setIsListModalOpen] = useState(false);
    const [isTemplateModalOpen, setIsTemplateModalOpen] = useState(false);
    const [isAccountModalOpen, setIsAccountModalOpen] = useState(false);

    // Form States
    const [campaignForm, setCampaignForm] = useState({ name: '', list_id: '', template_id: '', smtp_account_id: '', delay_ms: '2000', is_warmup: false, warmup_initial: '50', warmup_increase: '20' });
    const [listForm, setListForm] = useState({ name: '', description: '', raw_contacts: '' });
    const [isContactViewerOpen, setIsContactViewerOpen] = useState(false);
    const [viewingContacts, setViewingContacts] = useState<any[]>([]);
    const [currentListId, setCurrentListId] = useState<string | null>(null);
    const [templateForm, setTemplateForm] = useState<{id?: string, name: string, subject: string, html_content: string}>({ name: '', subject: '', html_content: '' });
    const [isPreviewOpen, setIsPreviewOpen] = useState(false);
    const [previewHtml, setPreviewHtml] = useState('');
    const [isReportModalOpen, setIsReportModalOpen] = useState(false);
    const [reportLogs, setReportLogs] = useState<any[]>([]);
    const [accountForm, setAccountForm] = useState<{id?: string, name: string, host: string, port: string, username: string, password: string, encryption: string, from_name: string, from_email: string}>({ name: '', host: '', port: '465', username: '', password: '', encryption: 'ssl', from_name: '', from_email: '' });

    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        if (!connected) return;
        
        // Background sync loop for tracker
        const interval = setInterval(async () => {
            try {
                await callRPC('syncTracker');
                if (activeTab === 'dashboard' || activeTab === 'campaigns') fetchData();
            } catch (err) { }
        }, 60000); // Sync every minute

        return () => clearInterval(interval);
    }, [connected, activeTab]);

    useEffect(() => {
        if (connected) {
            fetchData();
        }
    }, [connected, activeTab]);

    const fetchData = async () => {
        try {
            if (activeTab === 'dashboard') {
                const res: any = await callRPC('getGlobalStats');
                setGlobalStats(res.stats || { campaigns: 0, sent: 0, opens: 0, clicks: 0, unsubscribes: 0 });
                const campRes: any = await callRPC('getCampaigns');
                setCampaigns(campRes.campaigns || []);
            } else if (activeTab === 'campaigns') {
                const res: any = await callRPC('getCampaigns');
                setCampaigns(res.campaigns || []);
            } else if (activeTab === 'lists') {
                const res: any = await callRPC('getLists');
                setLists(res.lists || []);
            } else if (activeTab === 'templates') {
                const res: any = await callRPC('getTemplates');
                setTemplates(res.templates || []);
            } else if (activeTab === 'accounts') {
                const res: any = await callRPC('getSmtpAccounts');
                setAccounts(res.accounts || []);
            } else if (activeTab === 'settings') {
                const res: any = await callRPC('getSettings');
                setSettings(res || { api_key: '' });
            }
        } catch (err) {
            console.error(err);
        }
    };

    // --- Action Handlers ---
    const handleViewReport = async (campaignId: string) => {
        try {
            const res: any = await callRPC('getCampaignLogs', { campaignId });
            setReportLogs(res.logs || []);
            setIsReportModalOpen(true);
        } catch(err: any) {
            alert('Error loading logs: ' + err.message);
        }
    };

    const handleEditAccount = (account: any) => {
        setAccountForm({
            id: account.id,
            name: account.name,
            host: account.host,
            port: account.port,
            username: account.username,
            password: account.password,
            encryption: account.encryption,
            from_name: account.from_name,
            from_email: account.from_email
        });
        setIsAccountModalOpen(true);
    };

    const handleExportCSV = () => {
        if (!viewingContacts || viewingContacts.length === 0) return;
        const header = "Name,Email,Status\n";
        const rows = viewingContacts.map(c => `"${c.name || ''}","${c.email}",${c.unsubscribed ? 'Unsubscribed' : 'Active'}`).join("\n");
        const blob = new Blob([header + rows], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `contacts_export.csv`;
        a.click();
    };

    const handleAction = async (action: string, id: string) => {
        try {
            await callRPC(action, { id });
            fetchData();
        } catch (err: any) {
            alert(`Error performing ${action}: ` + err.message);
        }
    };

    const handleDelete = async (action: string, id: string) => {
        if (!confirm('Are you sure you want to delete this item?')) return;
        try {
            await callRPC(action, { id });
            fetchData();
        } catch (err: any) {
            alert('Error deleting item: ' + err.message);
        }
    };

    const handleViewContacts = async (listId: string) => {
        try {
            setCurrentListId(listId);
            const res: any = await callRPC('getContacts', { listId });
            setViewingContacts(res.contacts || []);
            setIsContactViewerOpen(true);
        } catch(err: any) {
            alert('Error loading contacts: ' + err.message);
        }
    };

    const handleDeleteContact = async (id: string) => {
        if (!confirm('Are you sure you want to delete this contact?')) return;
        try {
            await callRPC('deleteContact', { id });
            if (currentListId) {
                const res: any = await callRPC('getContacts', { listId: currentListId });
                setViewingContacts(res.contacts || []);
            }
            fetchData();
        } catch(err: any) {
            alert('Error deleting contact: ' + err.message);
        }
    };

    const handleCreateAccount = async () => {
        try {
            setIsSubmitting(true);
            await callRPC('saveSmtpAccount', accountForm);
            setIsAccountModalOpen(false);
            setAccountForm({ name: '', host: '', port: '465', username: '', password: '', encryption: 'ssl', from_name: '', from_email: '' });
            fetchData();
        } catch (err: any) {
            alert('Error saving account: ' + err.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleTestAccount = async () => {
        try {
            setIsSubmitting(true);
            await callRPC('testSmtp', accountForm);
            alert('Connection successful!');
        } catch (err: any) {
            alert(err.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleCreateTemplate = async () => {
        try {
            setIsSubmitting(true);
            await callRPC('saveTemplate', templateForm);
            setIsTemplateModalOpen(false);
            setTemplateForm({ name: '', subject: '', html_content: '' });
            fetchData();
        } catch (err: any) {
            alert('Error saving template: ' + err.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleEditTemplate = (t: any) => {
        setTemplateForm({ id: t.id, name: t.name, subject: t.subject, html_content: t.html_content });
        setIsTemplateModalOpen(true);
    };

    const insertShortcode = (code: string) => {
        setTemplateForm(prev => ({ ...prev, html_content: prev.html_content + code }));
    };

    const handleCreateList = async () => {
        try {
            setIsSubmitting(true);
            const listId = (await callRPC('saveList', { name: listForm.name, description: listForm.description }) as any).id;
            
            // Parse raw contacts (comma, newline, or json)
            let parsedContacts: any[] = [];
            if (listForm.raw_contacts.trim().startsWith('[')) {
                try {
                    parsedContacts = JSON.parse(listForm.raw_contacts);
                } catch(e) {}
            } else {
                const lines = listForm.raw_contacts.split('\n').map(l => l.trim()).filter(Boolean);
                parsedContacts = lines.map(line => {
                    const parts = line.split(',');
                    return { email: parts[0]?.trim(), name: parts[1]?.trim() || '' };
                }).filter(c => c.email);
            }

            if (parsedContacts.length > 0) {
                await callRPC('insertContacts', { listId, contacts: parsedContacts });
            }

            setIsListModalOpen(false);
            setListForm({ name: '', description: '', raw_contacts: '' });
            fetchData();
        } catch (err: any) {
            alert('Error creating list: ' + err.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleCreateCampaign = async () => {
        try {
            setIsSubmitting(true);
            const campaignId = (await callRPC('createCampaign', {
                name: campaignForm.name,
                list_id: campaignForm.list_id,
                template_id: campaignForm.template_id,
                smtp_account_id: campaignForm.smtp_account_id,
                delay_ms: parseInt(campaignForm.delay_ms),
                is_warmup: campaignForm.is_warmup,
                warmup_initial: parseInt(campaignForm.warmup_initial),
                warmup_increase: parseInt(campaignForm.warmup_increase)
            }) as any).id;
            
            setIsCampaignModalOpen(false);
            setCampaignForm({ name: '', list_id: '', template_id: '', smtp_account_id: '', delay_ms: '2000', is_warmup: false, warmup_initial: '50', warmup_increase: '20' });
            
            // Auto start the campaign engine
            await fetch(`/api/plugins/email-sender/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ params: { action: 'send_campaign', campaign_id: campaignId } })
            });
            fetchData();
        } catch (err: any) {
            alert('Error starting campaign: ' + err.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    if (!connected) {
        return (
            <div className="min-h-screen bg-[#FDFDFD] flex items-center justify-center font-sans">
                <div className="text-center space-y-4">
                    <div className="w-8 h-8 border-2 border-slate-900 border-t-transparent rounded-full animate-spin mx-auto" />
                    <p className="text-sm font-medium text-slate-500">Connecting to Email Engine...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-[#FAFAFA] text-slate-900 font-sans flex flex-col selection:bg-black selection:text-white">
            <RuntimePluginModals 
                installingPlugin={installingPlugin} 
                loginRequired={loginRequired} 
                setLoginRequired={setLoginRequired} 
            />
            {/* Top Navigation */}
            <div className="h-14 border-b border-slate-200 bg-white flex items-center justify-between px-6 sticky top-0 z-10">
                <div className="flex items-center gap-6">
                    <div className="flex items-center gap-2">
                        <div className="w-6 h-6 bg-blue-600 rounded-md flex items-center justify-center shadow-sm">
                            <Mail className="w-3.5 h-3.5 text-white" />
                        </div>
                        <span className="font-semibold text-[15px] tracking-tight">Bulk Email Sender Pro</span>
                    </div>
                    
                    <div className="h-4 w-px bg-slate-200" />
                    
                    <nav className="flex items-center gap-1">
                        {[
                            { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard },
                            { id: 'campaigns', label: 'Campaigns', icon: Send },
                            { id: 'lists', label: 'Contacts', icon: Users },
                            { id: 'templates', label: 'Templates', icon: FileText },
                            { id: 'accounts', label: 'SMTP Accounts', icon: Server },
                            { id: 'settings', label: 'Settings', icon: Settings }
                        ].map(tab => (
                            <Button 
                                key={tab.id}
                                variant="ghost"
                                onClick={() => setActiveTab(tab.id)}
                                className={`h-8 px-3 text-sm font-medium transition-colors ${activeTab === tab.id ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'}`}
                            >
                                <tab.icon className="w-3.5 h-3.5 mr-2" />
                                {tab.label}
                            </Button>
                        ))}
                    </nav>
                </div>

                <div className="flex items-center gap-3">
                    <div className="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-100">
                        <div className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                        <span className="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Engine Online</span>
                    </div>
                </div>
            </div>

            {/* Main Content Area */}
            <main className="flex-1 max-w-6xl w-full mx-auto p-8">
                
                {/* Dashboard Workspace */}
                {activeTab === 'dashboard' && (
                    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">Dashboard</h1>
                            <p className="text-sm text-slate-500 mt-1">Overview of your email sending performance.</p>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="font-medium text-slate-500">Total Sent</h3>
                                    <Send className="w-5 h-5 text-blue-500" />
                                </div>
                                <p className="text-3xl font-bold text-slate-900">{globalStats.sent}</p>
                            </div>
                            <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="font-medium text-slate-500">Unique Opens</h3>
                                    <Activity className="w-5 h-5 text-emerald-500" />
                                </div>
                                <p className="text-3xl font-bold text-slate-900">{globalStats.opens}</p>
                            </div>
                            <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="font-medium text-slate-500">Total Clicks</h3>
                                    <CheckCircle2 className="w-5 h-5 text-indigo-500" />
                                </div>
                                <p className="text-3xl font-bold text-slate-900">{globalStats.clicks}</p>
                            </div>
                            <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="font-medium text-slate-500">Unsubscribes</h3>
                                    <XCircle className="w-5 h-5 text-rose-500" />
                                </div>
                                <p className="text-3xl font-bold text-slate-900">{globalStats.unsubscribes}</p>
                            </div>
                        </div>

                        <h2 className="text-lg font-bold tracking-tight mt-8 mb-4">Recent Campaigns</h2>
                        <div className="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                            {campaigns.length === 0 ? (
                                <div className="py-12 text-center">
                                    <p className="text-sm text-slate-500">No campaigns yet.</p>
                                </div>
                            ) : (
                                <table className="w-full text-sm text-left">
                                    <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-100 uppercase">
                                        <tr>
                                            <th className="px-6 py-3 font-medium">Campaign Name</th>
                                            <th className="px-6 py-3 font-medium">Status</th>
                                            <th className="px-6 py-3 font-medium">Progress</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {campaigns.slice(0, 5).map(c => (
                                            <tr key={c.id} className="hover:bg-slate-50/50 transition-colors">
                                                <td className="px-6 py-4 font-medium text-slate-900">{c.name}</td>
                                                <td className="px-6 py-4">
                                                    <span className={`inline-flex items-center px-2 py-1 rounded-md text-xs font-medium ${c.status === 'running' ? 'bg-blue-50 text-blue-700' : c.status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700'}`}>
                                                        {c.status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    {c.sent_count} / {c.total_recipients} sent
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </div>
                    </div>
                )}

                {/* Campaigns Workspace */}
                {activeTab === 'campaigns' && (
                    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight">Campaigns</h1>
                                <p className="text-sm text-slate-500 mt-1">Manage and track your email broadcasts.</p>
                            </div>
                            <Dialog open={isCampaignModalOpen} onOpenChange={setIsCampaignModalOpen}>
                                <DialogTrigger asChild>
                                    <Button className="gap-1.5 h-9 bg-black text-white hover:bg-slate-800 shadow-sm">
                                        <Plus className="w-4 h-4" /> New Campaign
                                    </Button>
                                </DialogTrigger>
                                <DialogContent className="sm:max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>Create New Campaign</DialogTitle>
                                    </DialogHeader>
                                    <div className="space-y-4 py-4">
                                        <div className="space-y-2">
                                            <Label>Campaign Name</Label>
                                            <Input value={campaignForm.name} onChange={e => setCampaignForm({...campaignForm, name: e.target.value})} placeholder="e.g. Weekly Newsletter" />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Contact List</Label>
                                            <Select value={campaignForm.list_id} onValueChange={v => setCampaignForm({...campaignForm, list_id: v})}>
                                                <SelectTrigger><SelectValue placeholder="Select audience" /></SelectTrigger>
                                                <SelectContent>
                                                    {lists.map(l => <SelectItem key={l.id} value={l.id}>{l.name} ({l.contact_count})</SelectItem>)}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Template</Label>
                                            <Select value={campaignForm.template_id} onValueChange={v => setCampaignForm({...campaignForm, template_id: v})}>
                                                <SelectTrigger><SelectValue placeholder="Select email design" /></SelectTrigger>
                                                <SelectContent>
                                                    {templates.map(t => <SelectItem key={t.id} value={t.id}>{t.name}</SelectItem>)}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <Label>SMTP Account</Label>
                                            <Select value={campaignForm.smtp_account_id} onValueChange={v => setCampaignForm({...campaignForm, smtp_account_id: v})}>
                                                <SelectTrigger><SelectValue placeholder="Select sender" /></SelectTrigger>
                                                <SelectContent>
                                                    {accounts.map(a => <SelectItem key={a.id} value={a.id}>{a.name}</SelectItem>)}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Pacing Delay (ms)</Label>
                                            <Input type="number" value={campaignForm.delay_ms} onChange={e => setCampaignForm({...campaignForm, delay_ms: e.target.value})} disabled={campaignForm.is_warmup} />
                                            <p className="text-xs text-slate-500">Wait time between emails (disabled in warm-up mode).</p>
                                        </div>
                                        <div className="flex items-center gap-2 pt-2">
                                            <input type="checkbox" id="is_warmup" className="rounded border-slate-300 text-black focus:ring-black" checked={campaignForm.is_warmup} onChange={e => setCampaignForm({...campaignForm, is_warmup: e.target.checked})} />
                                            <Label htmlFor="is_warmup" className="font-semibold cursor-pointer">Enable Progressive Warm-Up</Label>
                                        </div>
                                        {campaignForm.is_warmup && (
                                            <div className="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100">
                                                <div className="space-y-2">
                                                    <Label>Starting Daily Limit</Label>
                                                    <Input type="number" value={campaignForm.warmup_initial} onChange={e => setCampaignForm({...campaignForm, warmup_initial: e.target.value})} />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label>Daily Increase (%)</Label>
                                                    <Input type="number" value={campaignForm.warmup_increase} onChange={e => setCampaignForm({...campaignForm, warmup_increase: e.target.value})} />
                                                </div>
                                                <p className="text-xs text-slate-500 col-span-2">Delay between emails will be calculated automatically to distribute sends evenly over 24 hours.</p>
                                            </div>
                                        )}
                                        <Button className="w-full bg-black text-white hover:bg-slate-800 mt-4" onClick={handleCreateCampaign} disabled={isSubmitting}>
                                            {isSubmitting ? 'Starting...' : 'Start Campaign'}
                                        </Button>
                                    </div>
                                </DialogContent>
                            </Dialog>
                        </div>
                        {campaigns.length === 0 ? (
                            <div className="py-20 text-center border border-dashed border-slate-200 rounded-2xl bg-white">
                                <Send className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                <h3 className="text-sm font-semibold text-slate-900">No campaigns yet</h3>
                                <p className="text-sm text-slate-500 mt-1">Create your first campaign to start sending emails.</p>
                            </div>
                        ) : (
                            <div className="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                                <table className="w-full text-sm text-left">
                                    <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-100 uppercase">
                                        <tr>
                                            <th className="px-6 py-3 font-medium">Campaign Name</th>
                                            <th className="px-6 py-3 font-medium">Status</th>
                                            <th className="px-6 py-3 font-medium">Progress</th>
                                            <th className="px-6 py-3 font-medium">Opens</th>
                                            <th className="px-6 py-3 font-medium">Clicks</th>
                                            <th className="px-6 py-3 font-medium">Unsubs</th>
                                            <th className="px-6 py-3 font-medium text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {campaigns.map(c => (
                                            <tr key={c.id} className="hover:bg-slate-50/50 transition-colors">
                                                <td className="px-6 py-4 font-medium text-slate-900">{c.name}</td>
                                                <td className="px-6 py-4">
                                                    <span className={`inline-flex items-center px-2 py-1 rounded-md text-xs font-medium ${c.status === 'running' ? 'bg-blue-50 text-blue-700' : c.status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700'}`}>
                                                        {c.status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    {c.sent_count} / {c.total_recipients} sent
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="font-semibold text-slate-800">{c.open_count || 0}</span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="font-semibold text-slate-800">{c.click_count || 0}</span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="font-semibold text-rose-600">{c.unsubscribe_count || 0}</span>
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        {c.status === 'running' && (
                                                            <Button variant="ghost" size="icon" onClick={() => handleAction('pauseCampaign', c.id)} className="h-8 w-8 text-amber-500 hover:text-amber-600 hover:bg-amber-50">
                                                                <Pause className="w-4 h-4" />
                                                            </Button>
                                                        )}
                                                        {c.status === 'paused' && (
                                                            <Button variant="ghost" size="icon" onClick={() => handleAction('resumeCampaign', c.id)} className="h-8 w-8 text-emerald-500 hover:text-emerald-600 hover:bg-emerald-50">
                                                                <Play className="w-4 h-4" />
                                                            </Button>
                                                        )}
                                                        {(c.status === 'running' || c.status === 'paused') && (
                                                            <Button variant="ghost" size="icon" onClick={() => handleAction('cancelCampaign', c.id)} className="h-8 w-8 text-slate-400 hover:text-rose-600 hover:bg-rose-50">
                                                                <Square className="w-4 h-4 fill-current" />
                                                            </Button>
                                                        )}
                                                        <Button variant="ghost" size="icon" onClick={() => handleAction('duplicateCampaign', c.id)} className="h-8 w-8 text-slate-400 hover:text-blue-600 hover:bg-blue-50">
                                                            <Copy className="w-4 h-4" />
                                                        </Button>
                                                        <Button variant="ghost" size="icon" onClick={() => handleViewReport(c.id)} className="h-8 w-8 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50">
                                                            <Activity className="w-4 h-4" />
                                                        </Button>
                                                        <Button variant="ghost" size="icon" onClick={() => handleDelete('deleteCampaign', c.id)} className="h-8 w-8 text-slate-400 hover:text-rose-600 hover:bg-rose-50">
                                                            <Trash2 className="w-4 h-4" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                )}

                {/* Lists Workspace */}
                {activeTab === 'lists' && (
                    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight">Contact Lists</h1>
                                <p className="text-sm text-slate-500 mt-1">Manage your email contacts and audiences.</p>
                            </div>
                            <Dialog open={isListModalOpen} onOpenChange={setIsListModalOpen}>
                                <DialogTrigger asChild>
                                    <Button className="gap-1.5 h-9 bg-black text-white hover:bg-slate-800 shadow-sm">
                                        <Plus className="w-4 h-4" /> Import Contacts
                                    </Button>
                                </DialogTrigger>
                                <DialogContent className="sm:max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>Import Contacts</DialogTitle>
                                    </DialogHeader>
                                    <div className="space-y-4 py-4">
                                        <div className="space-y-2">
                                            <Label>List Name</Label>
                                            <Input value={listForm.name} onChange={e => setListForm({...listForm, name: e.target.value})} placeholder="e.g. VIP Customers" />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Description</Label>
                                            <Input value={listForm.description} onChange={e => setListForm({...listForm, description: e.target.value})} placeholder="Optional" />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Raw Contacts (CSV)</Label>
                                            <Textarea 
                                                value={listForm.raw_contacts} 
                                                onChange={e => setListForm({...listForm, raw_contacts: e.target.value})} 
                                                placeholder="email@example.com, John Doe&#10;another@example.com, Jane Doe" 
                                                className="min-h-[150px] font-mono text-xs"
                                            />
                                            <p className="text-xs text-slate-500">Format: email, name (one per line)</p>
                                        </div>
                                        <Button className="w-full bg-black text-white hover:bg-slate-800" onClick={handleCreateList} disabled={isSubmitting}>
                                            {isSubmitting ? 'Importing...' : 'Save & Import'}
                                        </Button>
                                    </div>
                                </DialogContent>
                            </Dialog>
                        </div>
                        {lists.length === 0 ? (
                            <div className="py-20 text-center border border-dashed border-slate-200 rounded-2xl bg-white">
                                <Users className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                <h3 className="text-sm font-semibold text-slate-900">No contact lists</h3>
                                <p className="text-sm text-slate-500 mt-1">Import contacts via CSV to get started.</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {lists.map(l => (
                                    <div key={l.id} className="bg-white border border-slate-200 rounded-2xl p-5 hover:shadow-sm transition-all group relative">
                                        <h3 className="font-semibold text-slate-900 pr-8">{l.name}</h3>
                                        <p className="text-2xl font-bold text-slate-800 mt-3">{l.contact_count}</p>
                                        <p className="text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1 mb-4">Subscribers</p>
                                        <Button variant="outline" size="sm" onClick={() => handleViewContacts(l.id)} className="w-full text-xs">View Contacts</Button>
                                        <Button 
                                            variant="ghost" 
                                            size="icon" 
                                            onClick={() => handleDelete('deleteList', l.id)}
                                            className="absolute top-4 right-4 opacity-0 group-hover:opacity-100 h-8 w-8 text-slate-400 hover:text-rose-600 transition-opacity"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Contact Viewer Modal */}
                        <Dialog open={isContactViewerOpen} onOpenChange={setIsContactViewerOpen}>
                            <DialogContent className="sm:max-w-3xl max-h-[80vh] flex flex-col">
                                <DialogHeader>
                                    <div className="flex items-center justify-between pr-8">
                                        <DialogTitle>Contact List</DialogTitle>
                                        <Button variant="outline" size="sm" onClick={handleExportCSV}>Export CSV</Button>
                                    </div>
                                </DialogHeader>
                                <div className="flex-1 overflow-y-auto mt-4 pr-2">
                                    <table className="w-full text-sm text-left">
                                        <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-100 uppercase sticky top-0">
                                            <tr>
                                                <th className="px-4 py-2 font-medium">Name</th>
                                                <th className="px-4 py-2 font-medium">Email</th>
                                                <th className="px-4 py-2 font-medium">Status</th>
                                                <th className="px-4 py-2 font-medium text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {viewingContacts.length === 0 && (
                                                <tr><td colSpan={4} className="text-center py-4 text-slate-500">No contacts found</td></tr>
                                            )}
                                            {viewingContacts.map(c => (
                                                <tr key={c.id} className="hover:bg-slate-50/50 transition-colors">
                                                    <td className="px-4 py-3 font-medium text-slate-900">{c.name || '-'}</td>
                                                    <td className="px-4 py-3 text-slate-600">{c.email}</td>
                                                    <td className="px-4 py-3">
                                                        {c.unsubscribed === 1 ? (
                                                            <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Unsubscribed</span>
                                                        ) : (
                                                            <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-right">
                                                        <Button variant="ghost" size="icon" onClick={() => handleDeleteContact(c.id)} className="h-7 w-7 text-slate-400 hover:text-rose-600">
                                                            <Trash2 className="w-3.5 h-3.5" />
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </DialogContent>
                        </Dialog>
                    </div>
                )}

                {/* Templates Workspace */}
                {activeTab === 'templates' && (
                    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight">Templates</h1>
                                <p className="text-sm text-slate-500 mt-1">Design and manage your email templates.</p>
                            </div>
                            <Dialog open={isTemplateModalOpen} onOpenChange={setIsTemplateModalOpen}>
                                <DialogTrigger asChild>
                                    <Button className="gap-1.5 h-9 bg-black text-white hover:bg-slate-800 shadow-sm">
                                        <Plus className="w-4 h-4" /> New Template
                                    </Button>
                                </DialogTrigger>
                                <DialogContent className="sm:max-w-2xl">
                                    <DialogHeader>
                                        <DialogTitle>Create HTML Template</DialogTitle>
                                    </DialogHeader>
                                    <div className="space-y-4 py-4">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label>Template Name</Label>
                                                <Input value={templateForm.name} onChange={e => setTemplateForm({...templateForm, name: e.target.value})} placeholder="e.g. Welcome Email" />
                                            </div>
                                            <div className="space-y-2">
                                                <Label>Email Subject</Label>
                                                <Input value={templateForm.subject} onChange={e => setTemplateForm({...templateForm, subject: e.target.value})} placeholder="Welcome to Musoftware!" />
                                            </div>
                                        </div>
                                        <div className="space-y-2">
                                            <div className="flex items-center justify-between">
                                                <Label>HTML Content</Label>
                                                <div className="flex gap-1.5">
                                                    <Button variant="outline" size="sm" className="h-6 text-xs px-2" onClick={() => insertShortcode('{name}')}>{`{name}`}</Button>
                                                    <Button variant="outline" size="sm" className="h-6 text-xs px-2" onClick={() => insertShortcode('{email}')}>{`{email}`}</Button>
                                                    <Button variant="outline" size="sm" className="h-6 text-xs px-2" onClick={() => insertShortcode('{unsubscribe}')}>{`{unsubscribe}`}</Button>
                                                </div>
                                            </div>
                                            <Textarea 
                                                value={templateForm.html_content} 
                                                onChange={e => setTemplateForm({...templateForm, html_content: e.target.value})} 
                                                placeholder="<h1>Hi {name},</h1><p>Welcome to our platform!</p>" 
                                                className="min-h-[300px] font-mono text-sm"
                                            />
                                        </div>
                                        <Button className="w-full bg-black text-white hover:bg-slate-800" onClick={handleCreateTemplate} disabled={isSubmitting}>
                                            {isSubmitting ? 'Saving...' : 'Save Template'}
                                        </Button>
                                    </div>
                                </DialogContent>
                            </Dialog>
                        </div>
                        {templates.length === 0 ? (
                            <div className="py-20 text-center border border-dashed border-slate-200 rounded-2xl bg-white">
                                <FileText className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                <h3 className="text-sm font-semibold text-slate-900">No templates</h3>
                                <p className="text-sm text-slate-500 mt-1">Create an HTML email template.</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {templates.map(t => (
                                    <div key={t.id} className="bg-white border border-slate-200 rounded-2xl p-5 hover:shadow-sm transition-all group relative">
                                        <h3 className="font-semibold text-slate-900 pr-8">{t.name}</h3>
                                        <p className="text-sm text-slate-500 mt-1 truncate mb-4">{t.subject}</p>
                                        <div className="flex gap-2">
                                            <Button variant="outline" size="sm" className="flex-1 text-xs" onClick={() => { setPreviewHtml(t.html_content); setIsPreviewOpen(true); }}>Preview</Button>
                                            <Button variant="outline" size="sm" className="flex-1 text-xs" onClick={() => handleEditTemplate(t)}>Edit</Button>
                                        </div>
                                        <Button 
                                            variant="ghost" 
                                            size="icon" 
                                            onClick={() => handleDelete('deleteTemplate', t.id)}
                                            className="absolute top-4 right-4 opacity-0 group-hover:opacity-100 h-8 w-8 text-slate-400 hover:text-rose-600 transition-opacity"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* HTML Preview Modal */}
                        <Dialog open={isPreviewOpen} onOpenChange={setIsPreviewOpen}>
                            <DialogContent className="sm:max-w-4xl h-[80vh] flex flex-col p-0 overflow-hidden">
                                <div className="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                                    <h3 className="font-semibold text-slate-800">Template Preview</h3>
                                </div>
                                <div className="flex-1 bg-white overflow-auto p-8">
                                    <div dangerouslySetInnerHTML={{ __html: previewHtml }} />
                                </div>
                            </DialogContent>
                        </Dialog>
                    </div>
                )}

                {/* Accounts Workspace */}
                {activeTab === 'accounts' && (
                    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight">SMTP Accounts</h1>
                                <p className="text-sm text-slate-500 mt-1">Connect your email sending providers.</p>
                            </div>
                            <Dialog open={isAccountModalOpen} onOpenChange={setIsAccountModalOpen}>
                                <DialogTrigger asChild>
                                    <Button className="gap-1.5 h-9 bg-black text-white hover:bg-slate-800 shadow-sm">
                                        <Plus className="w-4 h-4" /> Add SMTP
                                    </Button>
                                </DialogTrigger>
                                <DialogContent className="sm:max-w-lg">
                                    <DialogHeader>
                                        <DialogTitle>Add SMTP Account</DialogTitle>
                                    </DialogHeader>
                                    <div className="space-y-4 py-4">
                                        <div className="space-y-2">
                                            <Label>Account Nickname</Label>
                                            <Input value={accountForm.name} onChange={e => setAccountForm({...accountForm, name: e.target.value})} placeholder="e.g. Info@Company" />
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label>SMTP Host</Label>
                                                <Input value={accountForm.host} onChange={e => setAccountForm({...accountForm, host: e.target.value})} placeholder="smtp.gmail.com" />
                                            </div>
                                            <div className="space-y-2">
                                                <Label>SMTP Port</Label>
                                                <Input value={accountForm.port} onChange={e => setAccountForm({...accountForm, port: e.target.value})} placeholder="465" />
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label>Username</Label>
                                                <Input value={accountForm.username} onChange={e => setAccountForm({...accountForm, username: e.target.value})} />
                                            </div>
                                            <div className="space-y-2">
                                                <Label>Password</Label>
                                                <Input type="password" value={accountForm.password} onChange={e => setAccountForm({...accountForm, password: e.target.value})} />
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label>From Name</Label>
                                                <Input value={accountForm.from_name} onChange={e => setAccountForm({...accountForm, from_name: e.target.value})} placeholder="John Doe" />
                                            </div>
                                            <div className="space-y-2">
                                                <Label>From Email</Label>
                                                <Input value={accountForm.from_email} onChange={e => setAccountForm({...accountForm, from_email: e.target.value})} placeholder="john@example.com" />
                                            </div>
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Encryption</Label>
                                            <Select value={accountForm.encryption} onValueChange={v => setAccountForm({...accountForm, encryption: v})}>
                                                <SelectTrigger><SelectValue /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="ssl">SSL / TLS</SelectItem>
                                                    <SelectItem value="starttls">STARTTLS</SelectItem>
                                                    <SelectItem value="none">None</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="flex gap-3 pt-4">
                                            <Button variant="outline" className="flex-1" onClick={handleTestAccount} disabled={isSubmitting}>Test Connection</Button>
                                            <Button className="flex-1 bg-black text-white hover:bg-slate-800" onClick={handleCreateAccount} disabled={isSubmitting}>
                                                {isSubmitting ? 'Saving...' : 'Save Account'}
                                            </Button>
                                        </div>
                                    </div>
                                </DialogContent>
                            </Dialog>
                        </div>
                        {accounts.length === 0 ? (
                            <div className="py-20 text-center border border-dashed border-slate-200 rounded-2xl bg-white">
                                <Server className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                <h3 className="text-sm font-semibold text-slate-900">No SMTP accounts</h3>
                                <p className="text-sm text-slate-500 mt-1">Add an SMTP account to start sending emails.</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {accounts.map(a => (
                                    <div key={a.id} className="bg-white border border-slate-200 rounded-2xl p-5 hover:shadow-sm transition-all flex items-start justify-between relative group">
                                        <div>
                                            <h3 className="font-semibold text-slate-900">{a.name}</h3>
                                            <p className="text-sm text-slate-500 mt-1">{a.username}</p>
                                            <div className="mt-3 flex items-center gap-1.5 mb-4">
                                                <div className="w-2 h-2 rounded-full bg-emerald-500" />
                                                <span className="text-xs font-medium text-slate-600">Connected</span>
                                            </div>
                                            <Button variant="outline" size="sm" className="text-xs" onClick={() => handleEditAccount(a)}>Edit Account</Button>
                                        </div>
                                        <Button variant="ghost" size="icon" onClick={() => handleDelete('deleteSmtpAccount', a.id)} className="absolute top-4 right-4 opacity-0 group-hover:opacity-100 h-8 w-8 text-slate-400 hover:text-rose-600 transition-opacity">
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {/* Settings Workspace */}
                {activeTab === 'settings' && (
                    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight">Settings</h1>
                                <p className="text-sm text-slate-500 mt-1">Configure your email sender integration.</p>
                            </div>
                        </div>
                        <div className="bg-white border border-slate-200 rounded-2xl p-6 max-w-xl">
                            <h3 className="font-semibold text-slate-900 mb-4">Cloud Tracking Integration</h3>
                            <div className="space-y-4">
                                <div>
                                    <label className="text-sm font-medium text-slate-700">Musoftware API Key</label>
                                    <input 
                                        type="text" 
                                        className="mt-1 w-full border border-slate-200 rounded-lg h-10 px-3 text-sm focus:ring-black focus:border-black"
                                        placeholder="Paste your user API key here"
                                        value={settings.api_key}
                                        onChange={(e) => setSettings({ ...settings, api_key: e.target.value })}
                                    />
                                    <p className="text-xs text-slate-500 mt-2">Required to sync email open events from the cloud tracking server to your local machine.</p>
                                </div>
                                <Button 
                                    className="w-full bg-black text-white hover:bg-slate-800"
                                    onClick={async () => {
                                        await callRPC('saveSettings', { api_key: settings.api_key });
                                        // Just a subtle notification effect
                                        const btn = document.activeElement;
                                        if (btn) {
                                            const originalText = btn.textContent;
                                            btn.textContent = 'Saved!';
                                            setTimeout(() => btn.textContent = originalText, 2000);
                                        }
                                    }}
                                >
                                    Save Settings
                                </Button>
                            </div>
                        </div>
                    </div>
                )}

                {/* Campaign Report Modal */}
                <Dialog open={isReportModalOpen} onOpenChange={setIsReportModalOpen}>
                    <DialogContent className="sm:max-w-3xl max-h-[80vh] flex flex-col">
                        <DialogHeader>
                            <DialogTitle>Campaign Activity Logs</DialogTitle>
                        </DialogHeader>
                        <div className="flex-1 overflow-y-auto mt-4 pr-2">
                            <table className="w-full text-sm text-left">
                                <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-100 uppercase sticky top-0">
                                    <tr>
                                        <th className="px-4 py-2 font-medium">Time</th>
                                        <th className="px-4 py-2 font-medium">Event</th>
                                        <th className="px-4 py-2 font-medium">Details</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {reportLogs.length === 0 && (
                                        <tr><td colSpan={3} className="text-center py-4 text-slate-500">No logs found</td></tr>
                                    )}
                                    {reportLogs.map(log => (
                                        <tr key={log.id} className="hover:bg-slate-50/50 transition-colors">
                                            <td className="px-4 py-3 text-slate-500 whitespace-nowrap">{new Date(log.created_at).toLocaleString()}</td>
                                            <td className="px-4 py-3 font-medium text-slate-900 capitalize">{log.status}</td>
                                            <td className="px-4 py-3 text-slate-600 truncate max-w-xs" title={log.message}>{log.message}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </DialogContent>
                </Dialog>

            </main>
        </div>
    );
}
