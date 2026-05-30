import React from 'react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { LinkedInSession } from '../../types/b2b.types';

interface LinkedInWorkspaceProps {
    linkedInSession: LinkedInSession;
    sessionCookieInput: string;
    setSessionCookieInput: (s: string) => void;
    savingCookie: boolean;
    handleSaveLinkedInCookie: (e: React.FormEvent) => void;
}

export function LinkedInWorkspace(props: LinkedInWorkspaceProps) {
    return (
        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
            <div>
                <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-slate-950 to-slate-800 bg-clip-text text-transparent">Linked Accounts</h1>
                <p className="text-xs text-slate-500 mt-1">Connect your accounts securely to power local deep crawling engines.</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="md:col-span-2 space-y-6">
                    <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-5">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-slate-100 text-slate-900 rounded-xl flex items-center justify-center font-bold text-sm">
                                in
                            </div>
                            <div>
                                <h3 className="font-bold text-slate-900 text-xs uppercase tracking-wider">LinkedIn Session Authenticator</h3>
                                <span className="text-[10px] text-slate-400 block mt-0.5">Powers deep crawler to harvest business roles privately.</span>
                            </div>
                        </div>

                        <div className="p-4 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between text-xs">
                            <div>
                                <span className="text-slate-500 font-semibold block">Authentication Status:</span>
                                <span className={`font-bold mt-0.5 block text-xs ${props.linkedInSession.hasSession ? 'text-emerald-600' : 'text-slate-500'}`}>
                                    {props.linkedInSession.hasSession ? 'Session Key Linked (Active)' : 'Not Connected'}
                                </span>
                            </div>
                            {props.linkedInSession.validatedAt && (
                                <div className="text-right">
                                    <span className="text-slate-400 block text-[10px]">Last validated:</span>
                                    <span className="font-mono text-slate-500 text-[10px] block mt-0.5">{new Date(props.linkedInSession.validatedAt).toLocaleDateString()}</span>
                                </div>
                            )}
                        </div>

                        <form onSubmit={props.handleSaveLinkedInCookie} className="space-y-4 text-xs">
                            <div className="space-y-1">
                                <Label>LinkedIn Connection Key (`li_at` value)</Label>
                                <Input 
                                    type="password" required placeholder="Paste the session value e.g. AQEDAT..."
                                    value={props.sessionCookieInput} onChange={e => props.setSessionCookieInput(e.target.value)}
                                />
                                <p className="text-[10px] text-slate-400 mt-1 leading-relaxed">Your session token is saved securely in your **local SQLite database** only. It is never transmitted to our cloud backend servers.</p>
                            </div>

                            <div className="flex justify-end pt-2 border-t border-slate-100">
                                <Button type="submit" disabled={props.savingCookie}>
                                    {props.savingCookie ? 'Linking Token...' : 'Link Connection Key'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>

                <div className="md:col-span-1 space-y-4 text-xs">
                    <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3">
                        <h4 className="font-bold text-slate-900 uppercase tracking-wider text-[10px]">How to locate session key</h4>
                        <p className="text-slate-500 leading-relaxed">1. Open Chrome or Edge and go to LinkedIn.com (log in).</p>
                        <p className="text-slate-500 leading-relaxed">2. Right-click anywhere and choose **Inspect** or press F12.</p>
                        <p className="text-slate-500 leading-relaxed">3. Go to the **Application** tab (Chrome) or **Storage** (Firefox).</p>
                        <p className="text-slate-500 leading-relaxed">4. Click **Cookies** in the left sidebar, then click `https://www.linkedin.com`.</p>
                        <p className="text-slate-500 leading-relaxed">5. Search for the cookie named **`li_at`** and copy its entire text Value.</p>
                    </div>
                </div>
            </div>
        </div>
    );
}
