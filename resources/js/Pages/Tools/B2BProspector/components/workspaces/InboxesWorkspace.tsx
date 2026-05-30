import React from 'react';
import { Mail, Zap, RefreshCw, Trash2, Info } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { B2BInbox } from '../../types/b2b.types';

interface InboxesWorkspaceProps {
    inboxes: B2BInbox[];
    newInboxEmail: string;
    setNewInboxEmail: (s: string) => void;
    newInboxHost: string;
    setNewInboxHost: (s: string) => void;
    newInboxPort: string;
    setNewInboxPort: (s: string) => void;
    newInboxUser: string;
    setNewInboxUser: (s: string) => void;
    newInboxPass: string;
    setNewInboxPass: (s: string) => void;
    newInboxImapHost: string;
    setNewInboxImapHost: (s: string) => void;
    newInboxImapPort: string;
    setNewInboxImapPort: (s: string) => void;
    inboxTestingId: string | null;
    handleConnectInbox: (e: React.FormEvent) => void;
    handleTestInbox: (inbox: B2BInbox) => void;
    handleDeleteInbox: (id: string) => void;
}

export function InboxesWorkspace(props: InboxesWorkspaceProps) {
    return (
        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
            <div>
                <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-slate-950 to-slate-800 bg-clip-text text-transparent">Sending Mailboxes</h1>
                <p className="text-xs text-slate-500 mt-1">Connect SMTP email accounts to send automated personalized outreach directly from your local network IP.</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="md:col-span-1 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
                    <h3 className="font-bold text-slate-900 text-xs uppercase tracking-wider">Connect Sending Mailbox</h3>
                    
                    <form onSubmit={props.handleConnectInbox} className="space-y-3 text-xs">
                        <div className="space-y-1">
                            <Label>Sender Email Address</Label>
                            <Input type="email" required placeholder="e.g. sales@yourdomain.com" value={props.newInboxEmail} onChange={e => props.setNewInboxEmail(e.target.value)} />
                        </div>
                        <div className="space-y-1">
                            <Label>SMTP Sending Host</Label>
                            <Input type="text" required placeholder="e.g. smtp.mailtrap.io" value={props.newInboxHost} onChange={e => props.setNewInboxHost(e.target.value)} />
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <div className="space-y-1">
                                <Label>SMTP Port</Label>
                                <Input type="text" required placeholder="587" value={props.newInboxPort} onChange={e => props.setNewInboxPort(e.target.value)} />
                            </div>
                            <div className="space-y-1">
                                <Label>IMAP Host (Opt.)</Label>
                                <Input type="text" placeholder="e.g. imap.domain.com" value={props.newInboxImapHost} onChange={e => props.setNewInboxImapHost(e.target.value)} />
                            </div>
                        </div>
                        <div className="space-y-1">
                            <Label>SMTP/IMAP Login User</Label>
                            <Input type="text" required placeholder="sales@yourdomain.com" value={props.newInboxUser} onChange={e => props.setNewInboxUser(e.target.value)} />
                        </div>
                        <div className="space-y-1">
                            <Label>SMTP/IMAP Secret Password</Label>
                            <Input type="password" required placeholder="••••••••••••" value={props.newInboxPass} onChange={e => props.setNewInboxPass(e.target.value)} />
                        </div>
                        <Button type="submit" className="w-full mt-4">Add sending Mailbox</Button>
                    </form>
                </div>

                <div className="md:col-span-2 space-y-4">
                    <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                        <h3 className="font-bold text-slate-900 text-xs uppercase tracking-wider mb-4">Active Sending Connections</h3>
                        
                        {props.inboxes.length === 0 ? (
                            <div className="py-16 text-center">
                                <Zap className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                <h4 className="text-xs font-bold text-slate-900">No sending mailboxes connected yet</h4>
                                <p className="text-[11px] text-slate-400 mt-1 max-w-xs mx-auto">Connecting a custom domain email account will enable automated outbound message triggers.</p>
                            </div>
                        ) : (
                            <div className="divide-y divide-slate-100">
                                {props.inboxes.map(inbox => {
                                    const isTesting = props.inboxTestingId === inbox.id;
                                    return (
                                        <div key={inbox.id} className="py-4 flex items-center justify-between first:pt-0 last:pb-0 gap-4">
                                            <div className="flex items-center gap-3">
                                                <div className="w-8 h-8 bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-lg flex items-center justify-center shadow-sm">
                                                    <Mail className="w-4 h-4" />
                                                </div>
                                                <div>
                                                    <span className="font-bold text-slate-900 text-xs block select-all">{inbox.email}</span>
                                                    <span className="text-[10px] text-slate-400 block font-mono">SMTP: {inbox.smtp_host}:{inbox.smtp_port}</span>
                                                </div>
                                            </div>
                                            
                                            <div className="flex items-center gap-2">
                                                <span className={`px-2 py-0.5 rounded-full text-[9px] font-bold border inline-block ${
                                                    inbox.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'
                                                }`}>
                                                    {inbox.status === 'active' ? 'Operational' : 'Failed Connection'}
                                                </span>
                                                
                                                <Button 
                                                    variant="outline" size="sm"
                                                    onClick={() => props.handleTestInbox(inbox)}
                                                    disabled={isTesting}
                                                    className="h-7 text-[10px]"
                                                >
                                                    {isTesting && <RefreshCw className="w-3 h-3 animate-spin mr-1" />}
                                                    Test SMTP
                                                </Button>
                                                
                                                <Button 
                                                    variant="ghost" size="icon"
                                                    onClick={() => props.handleDeleteInbox(inbox.id)}
                                                    className="h-7 w-7 text-slate-400 hover:text-rose-600 hover:bg-rose-50"
                                                >
                                                    <Trash2 className="w-3.5 h-3.5" />
                                                </Button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                    
                    <div className="bg-slate-50 border border-slate-200 rounded-xl p-4 flex gap-3 text-xs text-slate-500">
                        <Info className="w-5 h-5 text-indigo-500 shrink-0 mt-0.5" />
                        <div className="space-y-1">
                            <p className="font-bold text-slate-800">Why connect custom SMTP?</p>
                            <p className="leading-relaxed">Musoftware runs outbound emails **locally** from your network environment. This avoids shared cloud server IP ranges, drastically increasing inbox deliverability rates and ensuring enterprise safety.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
