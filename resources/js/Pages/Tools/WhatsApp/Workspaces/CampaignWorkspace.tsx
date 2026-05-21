import React from 'react';
import { Users, MessageSquare, Sparkles, ShieldCheck, Send } from 'lucide-react';

export default function CampaignWorkspace({
    t, contactsText, setContactsText, getParsedRecipients, insertTag,
    messageText, setMessageText, attachmentMode, setAttachmentMode,
    attachmentUrl, setAttachmentUrl, vcardName, setVcardName,
    vcardPhone, setVcardPhone, vcardCompany, setVcardCompany,
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
                <div className="bg-white/60 backdrop-blur-xl border border-white/60 rounded-3xl p-7 shadow-[0_8px_30px_rgb(0,0,0,0.04)] space-y-4 transition-all hover:bg-white/80">
                    <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div className="flex items-center gap-2">
                            <Users className="w-4.5 h-4.5 text-teal-600" />
                            <h3 className="font-bold text-slate-800 text-sm">{t.campaign.contactsLabel}</h3>
                        </div>
                        {getParsedRecipients.length > 0 && (
                            <span className="text-[10px] font-extrabold uppercase px-2.5 py-1 bg-teal-50 border border-teal-200 text-teal-700 rounded-full tracking-wider">
                                {getParsedRecipients.length} {t.campaign.parsedContacts}
                            </span>
                        )}
                    </div>

                    <textarea
                        rows={6}
                        value={contactsText}
                        onChange={e => setContactsText(e.target.value)}
                        placeholder={t.campaign.contactsPlaceholder}
                        className="w-full text-xs border border-slate-200 focus:border-teal-500 rounded-xl p-4 outline-none transition-all resize-none font-mono"
                    />

                    {/* Parsed recipients visual grid preview */}
                    {getParsedRecipients.length > 0 && (
                        <div className="max-h-36 overflow-y-auto border border-slate-100 rounded-xl divide-y divide-slate-50">
                            {getParsedRecipients.slice(0, 10).map((c: any, idx: number) => (
                                <div key={idx} className="p-2.5 flex items-center justify-between text-[11px] font-medium text-slate-600 bg-slate-50/50">
                                    <span className="font-mono text-slate-700 font-bold">{c.phone}</span>
                                    <span className="truncate max-w-[120px]">{c.name || '—'}</span>
                                    <span className="truncate max-w-[120px] text-slate-400">{c.company || '—'}</span>
                                </div>
                            ))}
                            {getParsedRecipients.length > 10 && (
                                <div className="p-2 text-center text-[10px] text-slate-400 font-bold">
                                    + {getParsedRecipients.length - 10} more recipients
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {/* Message editor card */}
                <div className="bg-white/60 backdrop-blur-xl border border-white/60 rounded-3xl p-7 shadow-[0_8px_30px_rgb(0,0,0,0.04)] space-y-4 transition-all hover:bg-white/80">
                    <div className="flex items-center gap-2 border-b border-slate-100 pb-3">
                        <MessageSquare className="w-4.5 h-4.5 text-teal-600" />
                        <h3 className="font-bold text-slate-800 text-sm">{t.campaign.messageLabel}</h3>
                    </div>

                    {/* Personalization key injectors */}
                    <div className="space-y-1.5">
                        <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">{t.campaign.personalizationTags}</span>
                        <div className="flex flex-wrap gap-2">
                            {['{name}', '{phone}', '{company}'].map(tag => (
                                <button
                                    key={tag}
                                    onClick={() => insertTag(tag)}
                                    className="px-2.5 py-1 text-[10px] font-bold text-teal-700 border border-teal-200 bg-teal-50/50 hover:bg-teal-100/50 transition-all rounded-md"
                                >
                                    {tag}
                                </button>
                            ))}
                        </div>
                    </div>

                    <textarea
                        rows={6}
                        value={messageText}
                        onChange={e => setMessageText(e.target.value)}
                        placeholder={t.campaign.messagePlaceholder}
                        className="w-full text-xs border border-slate-200 focus:border-teal-500 rounded-xl p-4 outline-none transition-all resize-none leading-relaxed"
                    />
                </div>
            </div>

            {/* Right Column */}
            <div className="space-y-6">
                {/* Attachment Options card */}
                <div className="bg-white/60 backdrop-blur-xl border border-white/60 rounded-3xl p-7 shadow-[0_8px_30px_rgb(0,0,0,0.04)] space-y-4 transition-all hover:bg-white/80">
                    <div className="flex items-center gap-2 border-b border-slate-100 pb-3">
                        <Sparkles className="w-4.5 h-4.5 text-teal-600" />
                        <h3 className="font-bold text-slate-800 text-sm">{t.campaign.attachmentLabel}</h3>
                    </div>

                    <div className="flex border border-slate-200 rounded-xl overflow-hidden p-1 gap-1">
                        {(['none', 'media', 'vcard'] as const).map(mode => (
                            <button
                                key={mode}
                                type="button"
                                onClick={() => setAttachmentMode(mode)}
                                className={`flex-1 py-1.5 rounded-lg text-xs font-bold transition-all ${attachmentMode === mode ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'}`}
                            >
                                {t.campaign.attachmentModes[mode]}
                            </button>
                        ))}
                    </div>

                    {attachmentMode === 'media' && (
                        <div className="space-y-2.5 animate-in slide-in-from-top-2 duration-300">
                            <label className="block text-[10px] font-bold uppercase tracking-wider text-slate-400">{t.campaign.attachmentUrl}</label>
                            <input
                                type="url"
                                value={attachmentUrl}
                                onChange={e => setAttachmentUrl(e.target.value)}
                                placeholder={t.campaign.attachmentUrlPlaceholder}
                                className="w-full text-xs border border-slate-200 focus:border-teal-500 rounded-xl px-4 py-2.5 outline-none transition-all"
                            />
                        </div>
                    )}

                    {attachmentMode === 'vcard' && (
                        <div className="space-y-3.5 animate-in slide-in-from-top-2 duration-300">
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">{t.campaign.vcardName}</label>
                                    <input
                                        type="text"
                                        value={vcardName}
                                        onChange={e => setVcardName(e.target.value)}
                                        placeholder="John Doe"
                                        className="w-full text-xs border border-slate-200 focus:border-teal-500 rounded-xl px-3 py-2 outline-none transition-all"
                                    />
                                </div>
                                <div>
                                    <label className="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">{t.campaign.vcardPhone}</label>
                                    <input
                                        type="text"
                                        value={vcardPhone}
                                        onChange={e => setVcardPhone(e.target.value)}
                                        placeholder="+123456789"
                                        className="w-full text-xs border border-slate-200 focus:border-teal-500 rounded-xl px-3 py-2 outline-none transition-all"
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">{t.campaign.vcardCompany}</label>
                                <input
                                    type="text"
                                    value={vcardCompany}
                                    onChange={e => setVcardCompany(e.target.value)}
                                    placeholder="Acme Corp"
                                    className="w-full text-xs border border-slate-200 focus:border-teal-500 rounded-xl px-3 py-2 outline-none transition-all"
                                />
                            </div>
                        </div>
                    )}
                </div>

                {/* Safety configuration card */}
                <div className="bg-white/60 backdrop-blur-xl border border-white/60 rounded-3xl p-7 shadow-[0_8px_30px_rgb(0,0,0,0.04)] space-y-4 transition-all hover:bg-white/80">
                    <div className="flex items-center gap-2 border-b border-slate-100 pb-3">
                        <ShieldCheck className="w-4.5 h-4.5 text-teal-600" />
                        <h3 className="font-bold text-slate-800 text-sm">{t.campaign.safetyLabel}</h3>
                    </div>

                    {/* Min/Max WPM speed sliders */}
                    <div className="space-y-2">
                        <div className="flex items-center justify-between text-xs">
                            <span className="font-semibold text-slate-500">{t.campaign.typingSpeed}</span>
                            <span className="font-black text-slate-700">{minWpm} - {maxWpm} WPM</span>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <input
                                type="range"
                                min={30}
                                max={60}
                                value={minWpm}
                                onChange={e => setMinWpm(Number(e.target.value))}
                                className="h-1.5 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-teal-600"
                            />
                            <input
                                type="range"
                                min={61}
                                max={100}
                                value={maxWpm}
                                onChange={e => setMaxWpm(Number(e.target.value))}
                                className="h-1.5 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-teal-600"
                            />
                        </div>
                    </div>

                    {/* Typo Correction percentage slider */}
                    <div className="space-y-2">
                        <div className="flex items-center justify-between text-xs">
                            <span className="font-semibold text-slate-500">{t.campaign.typoChance}</span>
                            <span className="font-black text-slate-700">{typoChance}%</span>
                        </div>
                        <input
                            type="range"
                            min={0}
                            max={15}
                            value={typoChance}
                            onChange={e => setTypoChance(Number(e.target.value))}
                            className="w-full h-1.5 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-teal-600"
                        />
                    </div>

                    {/* Anti ban toggle switches */}
                    <div className="space-y-3.5 pt-2">
                        <div className="flex items-center justify-between">
                            <label className="text-xs font-semibold text-slate-600 select-none">{t.campaign.useSynonyms}</label>
                            <input
                                type="checkbox"
                                checked={useSynonyms}
                                onChange={e => setUseSynonyms(e.target.checked)}
                                className="w-4 h-4 text-teal-650 accent-teal-600 border-slate-350 rounded focus:ring-teal-500"
                            />
                        </div>
                        <div className="flex items-center justify-between">
                            <label className="text-xs font-semibold text-slate-600 select-none">{t.campaign.bellCurve}</label>
                            <input
                                type="checkbox"
                                checked={bellCurve}
                                onChange={e => setBellCurve(e.target.checked)}
                                className="w-4 h-4 text-teal-650 accent-teal-600 border-slate-350 rounded focus:ring-teal-500"
                            />
                        </div>
                        <div className="flex items-center justify-between">
                            <label className="text-xs font-semibold text-slate-600 select-none">{t.campaign.trackDelivery}</label>
                            <input
                                type="checkbox"
                                checked={trackDelivery}
                                onChange={e => setTrackDelivery(e.target.checked)}
                                className="w-4 h-4 text-teal-650 accent-teal-600 border-slate-350 rounded focus:ring-teal-500"
                            />
                        </div>
                        <div className="flex items-center justify-between">
                            <label className="text-xs font-semibold text-slate-600 select-none">{t.campaign.stopOnBlock}</label>
                            <input
                                type="checkbox"
                                checked={stopOnBlock}
                                onChange={e => setStopOnBlock(e.target.checked)}
                                className="w-4 h-4 text-teal-650 accent-teal-600 border-slate-350 rounded focus:ring-teal-500"
                            />
                        </div>
                    </div>

                    {stopOnBlock && (
                        <div className="space-y-2 pt-1 animate-in slide-in-from-top-1 duration-200">
                            <div className="flex items-center justify-between text-[11px]">
                                <span className="font-semibold text-slate-450">{t.campaign.maxBlockRate}</span>
                                <span className="font-black text-rose-600">{maxBlockRate}%</span>
                            </div>
                            <input
                                type="range"
                                min={2}
                                max={20}
                                value={maxBlockRate}
                                onChange={e => setMaxBlockRate(Number(e.target.value))}
                                className="w-full h-1.5 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-rose-600"
                            />
                        </div>
                    )}
                </div>

                {/* Select dispatch accounts configuration */}
                <div className="space-y-4">
                    <div className="flex flex-col md:flex-row items-stretch md:items-center gap-3">
                        <input
                            type="text"
                            value={campaignName}
                            onChange={e => setCampaignName(e.target.value)}
                            placeholder="Enter Campaign Identifier Name"
                            className="flex-1 text-xs border border-slate-200 focus:border-teal-500 rounded-xl px-4 py-2.5 outline-none transition-all bg-white"
                        />
                        <select
                            value={selectedAccount}
                            onChange={e => setSelectedAccount(e.target.value)}
                            className="text-xs border border-slate-200 rounded-xl px-3 py-2.5 outline-none bg-white font-bold text-slate-600"
                        >
                            <option value="">Select Routing Session</option>
                            {sessions.filter((s: any) => s.state === 'connected').map((s: any) => (
                                <option key={s.accountId} value={s.accountId}>{s.accountId}</option>
                            ))}
                        </select>
                    </div>

                    <button
                        onClick={handleLaunchCampaign}
                        disabled={isCampaignRunning}
                        className="w-full py-4 bg-gradient-to-r from-teal-500 to-emerald-600 hover:from-teal-400 hover:to-emerald-500 text-white rounded-2xl text-sm font-extrabold transition-all shadow-[0_8px_20px_rgb(20,184,166,0.3)] hover:shadow-[0_12px_25px_rgb(20,184,166,0.4)] active:scale-95 flex items-center justify-center gap-2.5 disabled:opacity-50"
                    >
                        <Send className="w-4 h-4" />
                        {t.campaign.launchButton}
                    </button>
                </div>
            </div>
        </div>
    );
}
