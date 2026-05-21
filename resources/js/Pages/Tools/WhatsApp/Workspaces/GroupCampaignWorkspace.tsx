import React, { useState } from 'react';
import {
    Users, MessageSquare, Rocket, ChevronRight, ChevronLeft,
    Trash2, Plus, CheckCircle2, Image, Video, FileArchive, Mic, FileText,
    Loader2, UsersRound, Zap
} from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';

const STEPS = ['Account & Group', 'Add Contacts', 'Compose Message', 'Launch'];
const MEDIA_TYPES = [
    { value: 'text',     label: 'Text',    icon: FileText },
    { value: 'image',    label: 'Image',   icon: Image },
    { value: 'video',    label: 'Video',   icon: Video },
    { value: 'document', label: 'Doc',     icon: FileArchive },
    { value: 'audio',    label: 'Audio',   icon: Mic },
];

function StepIndicator({ currentStep }: { currentStep: number }) {
    return (
        <div className="flex items-center gap-0 mb-8">
            {STEPS.map((label, i) => (
                <React.Fragment key={i}>
                    <div className="flex flex-col items-center gap-1.5">
                        <div className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all duration-300 ${
                            i < currentStep  ? 'bg-emerald-500 text-white shadow-md' :
                            i === currentStep? 'bg-teal-600 text-white shadow-md' :
                                               'bg-muted text-muted-foreground'
                        }`}>
                            {i < currentStep ? <CheckCircle2 className="w-4 h-4" /> : i + 1}
                        </div>
                        <span className={`text-[10px] font-bold uppercase tracking-wider whitespace-nowrap ${i === currentStep ? 'text-teal-600' : 'text-muted-foreground'}`}>{label}</span>
                    </div>
                    {i < STEPS.length - 1 && (
                        <div className={`flex-1 h-0.5 mx-2 mb-5 transition-all duration-300 ${i < currentStep ? 'bg-emerald-400' : 'bg-muted'}`} />
                    )}
                </React.Fragment>
            ))}
        </div>
    );
}

export default function GroupCampaignWorkspace({ callRPC, sessions }: any) {
    const [step, setStep]               = useState(0);
    const [accountId, setAccountId]     = useState(sessions.find((s: any) => s.state === 'connected')?.accountId || '');
    const [groupName, setGroupName]     = useState('');
    const [deleteAfter, setDeleteAfter] = useState(false);
    const [contactsText, setContactsText] = useState('');
    const [message, setMessage]         = useState('');
    const [mediaType, setMediaType]     = useState('text');
    const [mediaUrl, setMediaUrl]       = useState('');
    const [launching, setLaunching]     = useState(false);
    const [launched, setLaunched]       = useState<any>(null);

    const connectedSessions = sessions.filter((s: any) => s.state === 'connected');

    const parseContacts = () => {
        return contactsText.split('\n').map(line => {
            const parts = line.split(',');
            return {
                phone:   parts[0]?.trim().replace(/[^0-9+]/g, '') || '',
                name:    parts[1]?.trim() || '',
                company: parts[2]?.trim() || ''
            };
        }).filter(c => c.phone.length >= 7);
    };

    const contacts = parseContacts();

    const canNext = () => {
        if (step === 0) return accountId && groupName.trim().length >= 2;
        if (step === 1) return contacts.length >= 1;
        if (step === 2) return message.trim() || (mediaType !== 'text' && mediaUrl.trim());
        return true;
    };

    const handleLaunch = async () => {
        setLaunching(true);
        try {
            const res: any = await callRPC('createGroupCampaign', {
                accountId,
                groupName,
                contacts,
                message,
                mediaUrl: mediaUrl || null,
                mediaType,
                deleteGroupAfter: deleteAfter,
            });
            setLaunched(res);
        } catch (e: any) {
            alert(`Group Campaign Error: ${e.message}`);
        }
        setLaunching(false);
    };

    if (launched) {
        return (
            <div className="flex flex-col items-center justify-center py-16 gap-6 animate-in fade-in duration-300">
                <div className="w-20 h-20 rounded-3xl bg-teal-600 flex items-center justify-center shadow-lg">
                    <Zap className="w-10 h-10 text-white" />
                </div>
                <div className="text-center">
                    <h2 className="text-2xl font-black">Group Campaign Launched!</h2>
                    <p className="text-muted-foreground mt-2 text-sm">Creating group <strong>{groupName}</strong> and sending to {contacts.length} contacts…</p>
                    <p className="text-xs font-mono text-muted-foreground/70 mt-2">{launched.campaignId}</p>
                </div>
                <Button onClick={() => { setLaunched(null); setStep(0); setGroupName(''); setContactsText(''); setMessage(''); }} size="lg">
                    Start Another Group Campaign
                </Button>
            </div>
        );
    }

    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            <div>
                <h2 className="text-xl font-bold tracking-tight">Group Campaign</h2>
                <p className="text-sm text-muted-foreground mt-1">Create a WhatsApp group, add contacts, send a message — all in one flow.</p>
            </div>

            <Card>
                <CardContent className="p-8">
                    <StepIndicator currentStep={step} />

                    {/* Step 0: Account & Group */}
                    {step === 0 && (
                        <div className="space-y-5 animate-in fade-in duration-200">
                            <div className="space-y-2">
                                <Label>WhatsApp Account</Label>
                                {connectedSessions.length === 0 ? (
                                    <div className="text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                                        ⚠️ No connected accounts. Please connect a WhatsApp account first.
                                    </div>
                                ) : (
                                    <select value={accountId} onChange={e => setAccountId(e.target.value)} className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 font-medium">
                                        {connectedSessions.map((s: any) => (
                                            <option key={s.accountId} value={s.accountId}>{s.accountId}{s.phone ? ` (+${s.phone})` : ''}</option>
                                        ))}
                                    </select>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label>Group Name</Label>
                                <Input value={groupName} onChange={e => setGroupName(e.target.value)} placeholder="e.g. VIP Customers Q2 2025" />
                            </div>

                            <div className="flex items-center gap-3 p-4 bg-muted/50 rounded-xl border cursor-pointer hover:bg-muted transition-colors" onClick={() => setDeleteAfter(!deleteAfter)}>
                                <Switch checked={deleteAfter} onCheckedChange={setDeleteAfter} />
                                <div>
                                    <span className="font-bold text-sm">Delete group after sending</span>
                                    <p className="text-xs text-muted-foreground mt-0.5">The bot will leave the group automatically once the message is delivered.</p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Step 1: Contacts */}
                    {step === 1 && (
                        <div className="space-y-4 animate-in fade-in duration-200">
                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label>Contact List</Label>
                                    {contacts.length > 0 && (
                                        <span className="text-xs font-bold text-teal-600 bg-teal-50 px-2.5 py-1 rounded-full">{contacts.length} valid contacts</span>
                                    )}
                                </div>
                                <Textarea
                                    value={contactsText}
                                    onChange={e => setContactsText(e.target.value)}
                                    placeholder={`One per line — phone,name,company format:\n966501234567,Ahmed Al-Rashid,Aramco\n966507654321,Fatima,\n971501234567`}
                                    rows={12}
                                    className="font-mono text-xs resize-none"
                                />
                                <p className="text-[10px] text-muted-foreground">WhatsApp limits groups to 1024 participants.</p>
                            </div>

                            {contacts.length > 0 && (
                                <div className="max-h-40 overflow-y-auto border rounded-xl divide-y">
                                    {contacts.slice(0, 8).map((c, i) => (
                                        <div key={i} className="flex items-center gap-3 px-4 py-2.5 bg-muted/50">
                                            <div className="w-6 h-6 rounded-full bg-teal-600 flex items-center justify-center text-white text-[10px] font-bold">{(c.name || c.phone)[0]}</div>
                                            <span className="text-sm font-mono">{c.phone}</span>
                                            {c.name && <span className="text-xs text-muted-foreground">{c.name}</span>}
                                        </div>
                                    ))}
                                    {contacts.length > 8 && <div className="px-4 py-2 text-xs text-muted-foreground text-center bg-muted/20">+ {contacts.length - 8} more</div>}
                                </div>
                            )}
                        </div>
                    )}

                    {/* Step 2: Compose */}
                    {step === 2 && (
                        <div className="space-y-5 animate-in fade-in duration-200">
                            <div className="space-y-2">
                                <Label>Message Type</Label>
                                <div className="grid grid-cols-5 gap-2">
                                    {MEDIA_TYPES.map(({ value, label, icon: Icon }) => (
                                        <Button 
                                            key={value} 
                                            variant={mediaType === value ? 'default' : 'outline'}
                                            onClick={() => setMediaType(value)} 
                                            className={`h-auto flex flex-col items-center gap-1.5 py-3 px-2 ${mediaType === value ? 'bg-teal-600 hover:bg-teal-700' : ''}`}
                                        >
                                            <Icon className="w-5 h-5" />
                                            <span className="text-[9px] font-bold uppercase tracking-wider">{label}</span>
                                        </Button>
                                    ))}
                                </div>
                            </div>

                            {mediaType !== 'text' && (
                                <div className="space-y-2">
                                    <Label>Media URL</Label>
                                    <Input value={mediaUrl} onChange={e => setMediaUrl(e.target.value)} placeholder="https://example.com/promo.jpg" className="font-mono" />
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label>Message / Caption</Label>
                                <Textarea value={message} onChange={e => setMessage(e.target.value)} placeholder="Hello group members! We have an exclusive offer for you…" rows={6} className="resize-none" />
                            </div>
                        </div>
                    )}

                    {/* Step 3: Review & Launch */}
                    {step === 3 && (
                        <div className="space-y-5 animate-in fade-in duration-200">
                            <div className="bg-teal-50/50 dark:bg-teal-950/20 border border-teal-200/60 rounded-2xl p-6 space-y-4">
                                <h3 className="font-bold text-sm uppercase tracking-wider">Campaign Summary</h3>
                                <div className="grid grid-cols-2 gap-4 text-sm">
                                    <div><span className="text-muted-foreground text-xs block mb-0.5">Account</span><span className="font-bold">{accountId}</span></div>
                                    <div><span className="text-muted-foreground text-xs block mb-0.5">Group Name</span><span className="font-bold">{groupName}</span></div>
                                    <div><span className="text-muted-foreground text-xs block mb-0.5">Recipients</span><span className="font-bold">{contacts.length} contacts</span></div>
                                    <div><span className="text-muted-foreground text-xs block mb-0.5">Media Type</span><span className="font-bold capitalize">{mediaType}</span></div>
                                    <div><span className="text-muted-foreground text-xs block mb-0.5">Delete Group After</span><span className={`font-bold ${deleteAfter ? 'text-destructive' : ''}`}>{deleteAfter ? 'Yes' : 'No'}</span></div>
                                </div>
                                {message && (
                                    <div>
                                        <span className="text-muted-foreground text-xs block mb-1">Message Preview</span>
                                        <div className="bg-background rounded-xl px-4 py-3 text-sm border line-clamp-4">{message}</div>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Navigation */}
                    <div className="flex items-center justify-between mt-8 pt-6 border-t">
                        <Button
                            variant="outline"
                            onClick={() => setStep(s => s - 1)}
                            disabled={step === 0}
                            className="gap-2"
                        >
                            <ChevronLeft className="w-4 h-4" /> Back
                        </Button>

                        {step < STEPS.length - 1 ? (
                            <Button
                                onClick={() => setStep(s => s + 1)}
                                disabled={!canNext()}
                                className="bg-teal-600 hover:bg-teal-700 text-white gap-2"
                            >
                                Next <ChevronRight className="w-4 h-4" />
                            </Button>
                        ) : (
                            <Button
                                onClick={handleLaunch}
                                disabled={launching || !canNext()}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white gap-2"
                            >
                                {launching ? (
                                    <><Loader2 className="w-4 h-4 animate-spin" /> Launching…</>
                                ) : (
                                    <><Rocket className="w-4 h-4" /> Launch Group Campaign</>
                                )}
                            </Button>
                        )}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
