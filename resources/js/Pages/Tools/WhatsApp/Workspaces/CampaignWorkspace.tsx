import React from 'react';
import { Users, MessageSquare, Sparkles, ShieldCheck, Send } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';

export default function CampaignWorkspace({
    t, contactsText, setContactsText, getParsedRecipients,
    templates, selectedTemplateId, setSelectedTemplateId,
    minWpm, setMinWpm, maxWpm, setMaxWpm, typoChance, setTypoChance,
    useSynonyms, setUseSynonyms, bellCurve, setBellCurve,
    trackDelivery, setTrackDelivery, stopOnBlock, setStopOnBlock, maxBlockRate, setMaxBlockRate,
    campaignName, setCampaignName, selectedAccount, setSelectedAccount, sessions,
    handleLaunchCampaign, isCampaignRunning
}: any) {
    return (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 animate-in fade-in duration-300">
            {/* Left Column */}
            <div className="space-y-6">
                {/* Contacts Parser card */}
                <Card>
                    <CardHeader className="pb-4 border-b">
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-base flex items-center gap-2">
                                <Users className="w-4.5 h-4.5 text-teal-600" />
                                {t.campaign.contactsLabel}
                            </CardTitle>
                            {getParsedRecipients.length > 0 && (
                                <Badge variant="secondary" className="bg-teal-50 text-teal-700 hover:bg-teal-100">
                                    {getParsedRecipients.length} {t.campaign.parsedContacts}
                                </Badge>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6 space-y-4">
                        <Textarea
                            rows={6}
                            value={contactsText}
                            onChange={e => setContactsText(e.target.value)}
                            placeholder={t.campaign.contactsPlaceholder}
                            className="font-mono text-xs resize-none"
                        />

                        {/* Parsed recipients visual grid preview */}
                        {getParsedRecipients.length > 0 && (
                            <div className="max-h-36 overflow-y-auto border rounded-xl divide-y">
                                {getParsedRecipients.slice(0, 10).map((c: any, idx: number) => (
                                    <div key={idx} className="p-2.5 flex items-center justify-between text-[11px] font-medium bg-muted/50">
                                        <span className="font-mono font-bold">{c.phone}</span>
                                        <span className="truncate max-w-[120px]">{c.name || '—'}</span>
                                        <span className="truncate max-w-[120px] text-muted-foreground">{c.company || '—'}</span>
                                    </div>
                                ))}
                                {getParsedRecipients.length > 10 && (
                                    <div className="p-2 text-center text-[10px] text-muted-foreground font-bold bg-muted/20">
                                        + {getParsedRecipients.length - 10} more recipients
                                    </div>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Template Selector card */}
                <Card>
                    <CardHeader className="pb-4 border-b">
                        <CardTitle className="text-base flex items-center gap-2">
                            <MessageSquare className="w-4.5 h-4.5 text-teal-600" />
                            Select Message Template
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6 space-y-4">
                        {templates.length === 0 ? (
                            <div className="text-sm text-muted-foreground bg-muted p-4 rounded-xl text-center">
                                No templates found. Please create one in the Templates tab first!
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {templates.map((tpl: any) => (
                                    <label key={tpl.id} className={`flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all ${selectedTemplateId === tpl.id ? 'border-teal-500 bg-teal-50/50 dark:bg-teal-950/20 shadow-sm' : 'hover:border-teal-300'}`}>
                                        <input
                                            type="radio"
                                            name="template_select"
                                            checked={selectedTemplateId === tpl.id}
                                            onChange={() => setSelectedTemplateId(tpl.id)}
                                            className="mt-1 text-teal-600 focus:ring-teal-500"
                                        />
                                        <div className="flex-1 min-w-0">
                                            <h4 className="font-bold text-sm">{tpl.name}</h4>
                                            <p className="text-xs text-muted-foreground line-clamp-2 mt-0.5">{tpl.message}</p>
                                            {tpl.media_url && (
                                                <Badge variant="outline" className="mt-2 gap-1 bg-teal-50/50 text-teal-700">
                                                    <Sparkles className="w-3 h-3" />
                                                    {tpl.media_type} Attached
                                                </Badge>
                                            )}
                                        </div>
                                    </label>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Safety configuration card */}
                <Card>
                    <CardHeader className="pb-4 border-b">
                        <CardTitle className="text-base flex items-center gap-2">
                            <ShieldCheck className="w-4.5 h-4.5 text-teal-600" />
                            {t.campaign.safetyLabel}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6 space-y-6">
                        {/* Min/Max WPM speed sliders */}
                        <div className="space-y-2">
                            <div className="flex items-center justify-between text-xs">
                                <Label>{t.campaign.typingSpeed}</Label>
                                <span className="font-black">{minWpm} - {maxWpm} WPM</span>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <Input
                                    type="range"
                                    min={30}
                                    max={60}
                                    value={minWpm}
                                    onChange={e => setMinWpm(Number(e.target.value))}
                                    className="p-0 h-auto border-none cursor-pointer"
                                />
                                <Input
                                    type="range"
                                    min={61}
                                    max={100}
                                    value={maxWpm}
                                    onChange={e => setMaxWpm(Number(e.target.value))}
                                    className="p-0 h-auto border-none cursor-pointer"
                                />
                            </div>
                        </div>

                        {/* Typo Correction percentage slider */}
                        <div className="space-y-2">
                            <div className="flex items-center justify-between text-xs">
                                <Label>{t.campaign.typoChance}</Label>
                                <span className="font-black">{typoChance}%</span>
                            </div>
                            <Input
                                type="range"
                                min={0}
                                max={15}
                                value={typoChance}
                                onChange={e => setTypoChance(Number(e.target.value))}
                                className="p-0 h-auto border-none cursor-pointer"
                            />
                        </div>

                        {/* Anti ban toggle switches */}
                        <div className="space-y-4 pt-2">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="useSynonyms" className="cursor-pointer">{t.campaign.useSynonyms}</Label>
                                <Switch
                                    id="useSynonyms"
                                    checked={useSynonyms}
                                    onCheckedChange={setUseSynonyms}
                                />
                            </div>
                            <div className="flex items-center justify-between">
                                <Label htmlFor="bellCurve" className="cursor-pointer">{t.campaign.bellCurve}</Label>
                                <Switch
                                    id="bellCurve"
                                    checked={bellCurve}
                                    onCheckedChange={setBellCurve}
                                />
                            </div>
                            <div className="flex items-center justify-between">
                                <Label htmlFor="trackDelivery" className="cursor-pointer">{t.campaign.trackDelivery}</Label>
                                <Switch
                                    id="trackDelivery"
                                    checked={trackDelivery}
                                    onCheckedChange={setTrackDelivery}
                                />
                            </div>
                            <div className="flex items-center justify-between">
                                <Label htmlFor="stopOnBlock" className="cursor-pointer text-destructive">{t.campaign.stopOnBlock}</Label>
                                <Switch
                                    id="stopOnBlock"
                                    checked={stopOnBlock}
                                    onCheckedChange={setStopOnBlock}
                                />
                            </div>
                        </div>

                        {stopOnBlock && (
                            <div className="space-y-2 pt-1 animate-in slide-in-from-top-1 duration-200">
                                <div className="flex items-center justify-between text-[11px]">
                                    <Label className="text-destructive">{t.campaign.maxBlockRate}</Label>
                                    <span className="font-black text-destructive">{maxBlockRate}%</span>
                                </div>
                                <Input
                                    type="range"
                                    min={2}
                                    max={20}
                                    value={maxBlockRate}
                                    onChange={e => setMaxBlockRate(Number(e.target.value))}
                                    className="p-0 h-auto border-none cursor-pointer accent-destructive"
                                />
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Select dispatch accounts configuration */}
                <div className="space-y-4">
                    <div className="flex flex-col md:flex-row items-stretch md:items-center gap-3">
                        <Input
                            type="text"
                            value={campaignName}
                            onChange={e => setCampaignName(e.target.value)}
                            placeholder="Enter Campaign Identifier Name"
                            className="flex-1"
                        />
                        <select
                            value={selectedAccount}
                            onChange={e => setSelectedAccount(e.target.value)}
                            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:w-auto font-medium"
                        >
                            <option value="">Select Routing Session</option>
                            {sessions.filter((s: any) => s.state === 'connected').map((s: any) => (
                                <option key={s.accountId} value={s.accountId}>{s.accountId}</option>
                            ))}
                        </select>
                    </div>

                    <Button
                        onClick={handleLaunchCampaign}
                        disabled={isCampaignRunning}
                        size="lg"
                        className="w-full bg-teal-600 hover:bg-teal-700 text-white gap-2"
                    >
                        <Send className="w-4 h-4" />
                        {t.campaign.launchButton}
                    </Button>
                </div>
            </div>
        </div>
    );
}
