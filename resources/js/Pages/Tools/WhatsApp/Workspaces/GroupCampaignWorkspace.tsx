import React, { useState } from 'react';
import { CheckCircle2, Zap, FileText, Image, Video, FileArchive, Mic, ChevronLeft, ChevronRight, Loader2, Rocket } from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';

export default function GroupCampaignWorkspace({ callRPC, sessions, t, locale }: any) {
    const isRtl = locale === 'ar';
    const STEPS = [
        t.groupCampaign.step1,
        t.groupCampaign.step2,
        t.groupCampaign.step3,
        t.groupCampaign.step4
    ];

    const MEDIA_TYPES = [
        { value: 'text',     label: isRtl ? 'نص' : 'Text',    icon: FileText },
        { value: 'image',    label: isRtl ? 'صورة' : 'Image',   icon: Image },
        { value: 'video',    label: isRtl ? 'فيديو' : 'Video',   icon: Video },
        { value: 'document', label: isRtl ? 'مستند' : 'Doc',     icon: FileArchive },
        { value: 'audio',    label: isRtl ? 'صوت' : 'Audio',    icon: Mic },
    ];

    function StepIndicator({ currentStep }: { currentStep: number }) {
        return (
            <div className="flex items-center gap-0 mb-8 select-none">
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
                    <h2 className="text-2xl font-black">{t.groupCampaign.launchedTitle}</h2>
                    <p className="text-muted-foreground mt-2 text-sm">{t.groupCampaign.launchedSub.replace('contacts', `${contacts.length} ${t.groupCampaign.validContacts}`)}</p>
                    <p className="text-xs font-mono text-muted-foreground/70 mt-2">{launched.campaignId}</p>
                </div>
                <Button onClick={() => { setLaunched(null); setStep(0); setGroupName(''); setContactsText(''); setMessage(''); }} size="lg">
                    {t.groupCampaign.startAnotherBtn}
                </Button>
            </div>
        );
    }

    return (
        <div className="space-y-6 animate-in fade-in duration-300 text-start">
            <div>
                <h2 className="text-xl font-bold tracking-tight text-start">{t.groupCampaign.title}</h2>
                <p className="text-sm text-muted-foreground mt-1 text-start">{t.groupCampaign.subtitle}</p>
            </div>

            <Card>
                <CardContent className="p-8">
                    <StepIndicator currentStep={step} />

                    {/* Step 0: Account & Group */}
                    {step === 0 && (
                        <div className="space-y-5 animate-in fade-in duration-200 text-start">
                            <div className="space-y-2">
                                <Label>{t.groupCampaign.accountLabel}</Label>
                                {connectedSessions.length === 0 ? (
                                    <div className="text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-start">
                                        ⚠️ {t.groupCampaign.noAccountWarning}
                                    </div>
                                ) : (
                                    <select value={accountId} onChange={e => setAccountId(e.target.value)} className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 font-medium text-start">
                                        {connectedSessions.map((s: any) => (
                                            <option key={s.accountId} value={s.accountId}>{s.accountId}{s.phone ? ` (+${s.phone})` : ''}</option>
                                        ))}
                                    </select>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label>{t.groupCampaign.groupNameLabel}</Label>
                                <Input value={groupName} onChange={e => setGroupName(e.target.value)} placeholder={t.groupCampaign.groupNamePlaceholder} className="text-start" />
                            </div>

                            <div className="flex items-center gap-3 p-4 bg-muted/50 rounded-xl border cursor-pointer hover:bg-muted transition-colors text-start" onClick={() => setDeleteAfter(!deleteAfter)}>
                                <Switch checked={deleteAfter} onCheckedChange={setDeleteAfter} />
                                <div>
                                    <span className="font-bold text-sm">{t.groupCampaign.deleteAfterLabel}</span>
                                    <p className="text-xs text-muted-foreground mt-0.5">{t.groupCampaign.deleteAfterSub}</p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Step 1: Contacts */}
                    {step === 1 && (
                        <div className="space-y-4 animate-in fade-in duration-200 text-start">
                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label>{t.groupCampaign.contactsLabel}</Label>
                                    {contacts.length > 0 && (
                                        <span className="text-xs font-bold text-teal-600 bg-teal-50 px-2.5 py-1 rounded-full">{contacts.length} {t.groupCampaign.validContacts}</span>
                                    )}
                                </div>
                                <Textarea
                                    value={contactsText}
                                    onChange={e => setContactsText(e.target.value)}
                                    placeholder={t.groupCampaign.contactsPlaceholder}
                                    rows={12}
                                    className="font-mono text-xs resize-none text-start"
                                />
                                <p className="text-[10px] text-muted-foreground text-start">{t.groupCampaign.limitWarning}</p>
                            </div>

                            {contacts.length > 0 && (
                                <div className="max-h-40 overflow-y-auto border rounded-xl divide-y">
                                    {contacts.slice(0, 8).map((c, i) => (
                                        <div key={i} className="flex items-center gap-3 px-4 py-2.5 bg-muted/50 text-start">
                                            <div className="w-6 h-6 rounded-full bg-teal-600 flex items-center justify-center text-white text-[10px] font-bold">{(c.name || c.phone)[0]}</div>
                                            <span className="text-sm font-mono">{c.phone}</span>
                                            {c.name && <span className="text-xs text-muted-foreground">{c.name}</span>}
                                        </div>
                                    ))}
                                    {contacts.length > 8 && <div className="px-4 py-2 text-xs text-muted-foreground text-center bg-muted/20">+ {contacts.length - 8} {t.groupCampaign.moreContacts}</div>}
                                </div>
                            )}
                        </div>
                    )}

                    {/* Step 2: Compose */}
                    {step === 2 && (
                        <div className="space-y-5 animate-in fade-in duration-200 text-start">
                            <div className="space-y-2">
                                <Label>{t.groupCampaign.msgTypeLabel}</Label>
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
                                    <Label>{t.groupCampaign.mediaUrlLabel}</Label>
                                    <Input value={mediaUrl} onChange={e => setMediaUrl(e.target.value)} placeholder={__('general.https_example_com_promo_jpg')} className="font-mono text-start" />
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label>{t.groupCampaign.captionLabel}</Label>
                                <Textarea value={message} onChange={e => setMessage(e.target.value)} placeholder={t.groupCampaign.captionPlaceholder} rows={6} className="resize-none text-start" />
                            </div>
                        </div>
                    )}

                    {/* Step 3: Review & Launch */}
                    {step === 3 && (
                        <div className="space-y-5 animate-in fade-in duration-200 text-start">
                            <div className="bg-teal-50/50 dark:bg-teal-950/20 border border-teal-200/60 rounded-2xl p-6 space-y-4 text-start">
                                <h3 className="font-bold text-sm uppercase tracking-wider">{t.groupCampaign.summaryTitle}</h3>
                                <div className="grid grid-cols-2 gap-4 text-sm text-start">
                                    <div><span className="text-muted-foreground text-xs block mb-0.5">{t.groupCampaign.colAccount}</span><span className="font-bold">{accountId}</span></div>
                                    <div><span className="text-muted-foreground text-xs block mb-0.5">{t.groupCampaign.colGroupName}</span><span className="font-bold">{groupName}</span></div>
                                    <div><span className="text-muted-foreground text-xs block mb-0.5">{t.groupCampaign.colRecipients}</span><span className="font-bold">{contacts.length} {t.groupCampaign.validContacts}</span></div>
                                    <div><span className="text-muted-foreground text-xs block mb-0.5">{t.groupCampaign.colMediaType}</span><span className="font-bold capitalize">{mediaType}</span></div>
                                    <div><span className="text-muted-foreground text-xs block mb-0.5">{t.groupCampaign.colDeleteAfter}</span><span className={`font-bold ${deleteAfter ? 'text-destructive' : ''}`}>{deleteAfter ? t.groupCampaign.yes : t.groupCampaign.no}</span></div>
                                </div>
                                {message && (
                                    <div className="text-start">
                                        <span className="text-muted-foreground text-xs block mb-1">{t.groupCampaign.msgPreview}</span>
                                        <div className="bg-background rounded-xl px-4 py-3 text-sm border line-clamp-4 text-start">{message}</div>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Navigation */}
                    <div className="flex items-center justify-between mt-8 pt-6 border-t select-none">
                        <Button
                            variant="outline"
                            onClick={() => setStep(s => s - 1)}
                            disabled={step === 0}
                            className="gap-2"
                        >
                            <ChevronLeft className="w-4 h-4 rtl:rotate-180" /> {t.groupCampaign.backBtn}
                        </Button>

                        {step < STEPS.length - 1 ? (
                            <Button
                                onClick={() => setStep(s => s + 1)}
                                disabled={!canNext()}
                                className="bg-teal-600 hover:bg-teal-700 text-white gap-2 font-bold"
                            >
                                {t.groupCampaign.nextBtn} <ChevronRight className="w-4 h-4 rtl:rotate-180" />
                            </Button>
                        ) : (
                            <Button
                                onClick={handleLaunch}
                                disabled={launching || !canNext()}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white gap-2 font-bold"
                            >
                                {launching ? (
                                    <><Loader2 className="w-4 h-4 animate-spin" /> {t.groupCampaign.launchingBtn}</>
                                ) : (
                                    <><Rocket className="w-4 h-4" /> {t.groupCampaign.launchBtn}</>
                                )}
                            </Button>
                        )}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
