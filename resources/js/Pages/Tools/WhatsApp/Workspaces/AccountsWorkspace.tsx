import React from 'react';
import { QrCode, RefreshCw, Settings2, Play, Power, Trash2 } from 'lucide-react';

export default function AccountsWorkspace({
    activeQR, qrCountdown, qrSessionId, t,
    newAccountId, setNewAccountId, newProxy, setNewProxy, newHeadless, setNewHeadless,
    daemonConnected, handleConnectSession, handleReconnectSession, sessions, fetchSessions, handleDisconnectSession
}: any) {
    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            {/* QR Overlay for pending connection */}
            {activeQR && (
                <div className="bg-gradient-to-tr from-slate-950 to-slate-900 border border-slate-800 rounded-3xl p-6 text-white flex flex-col md:flex-row items-center gap-8 shadow-2xl relative overflow-hidden">
                    <div className="absolute top-0 right-0 w-64 h-64 bg-teal-500/10 rounded-full blur-3xl -z-10" />

                    <div className="bg-white p-4 rounded-2xl shrink-0 shadow-lg border border-slate-100 flex flex-col items-center justify-center">
                        <img src={activeQR} alt="WhatsApp QR Code" className="w-48 h-48 md:w-56 md:h-56" />
                        <div className="flex items-center gap-1.5 mt-3 text-slate-500 text-xs font-bold">
                            <RefreshCw className="w-3.5 h-3.5 animate-spin text-teal-600" />
                            <span>{t.accounts.qrRefreshes} ({qrCountdown}s)</span>
                        </div>
                    </div>

                    <div className="space-y-4 flex-1">
                        <div className="flex items-center gap-2 text-xs font-bold bg-teal-500/10 text-teal-400 px-3 py-1.5 rounded-full border border-teal-500/20 w-fit">
                            <QrCode className="w-3.5 h-3.5" />
                            <span>{t.accounts.qrPendingBadge}</span>
                        </div>
                        <h3 className="text-xl font-bold tracking-tight">{t.accounts.qrTitle} ({qrSessionId})</h3>
                        <p className="text-slate-400 text-sm leading-relaxed">{t.accounts.qrInstructions}</p>
                    </div>
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Connect new session column */}
                <div className="bg-white/60 backdrop-blur-xl border border-white/60 rounded-3xl p-7 shadow-[0_8px_30px_rgb(0,0,0,0.04)] h-fit hover:bg-white/80 transition-all">
                    <div className="flex items-center gap-2.5 pb-4 border-b border-slate-100 mb-4">
                        <Settings2 className="w-5 h-5 text-teal-600" />
                        <h3 className="font-bold text-slate-800 text-sm">{t.accounts.addAccount}</h3>
                    </div>

                    <form onSubmit={handleConnectSession} className="space-y-4">
                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">{t.accounts.accountId}</label>
                            <input
                                type="text"
                                value={newAccountId}
                                onChange={e => setNewAccountId(e.target.value)}
                                placeholder={t.accounts.accountIdPlaceholder}
                                className="w-full text-sm border border-slate-200 focus:border-teal-500 rounded-xl px-4 py-2.5 outline-none transition-all"
                                required
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">{t.accounts.proxy}</label>
                            <input
                                type="text"
                                value={newProxy}
                                onChange={e => setNewProxy(e.target.value)}
                                placeholder={t.accounts.proxyPlaceholder}
                                className="w-full text-sm border border-slate-200 focus:border-teal-500 rounded-xl px-4 py-2.5 outline-none transition-all"
                            />
                        </div>
                        <div className="flex items-center gap-3 py-1">
                            <input
                                id="headless-toggle"
                                type="checkbox"
                                checked={newHeadless}
                                onChange={e => setNewHeadless(e.target.checked)}
                                className="w-4.5 h-4.5 text-teal-600 focus:ring-teal-500 border-slate-300 rounded"
                            />
                            <label htmlFor="headless-toggle" className="text-xs font-semibold text-slate-600 select-none cursor-pointer">{t.accounts.headless}</label>
                        </div>
                        <button
                            type="submit"
                            disabled={!daemonConnected}
                            className="w-full py-3.5 bg-gradient-to-r from-teal-500 to-emerald-600 hover:from-teal-400 hover:to-emerald-500 text-white rounded-2xl text-sm font-extrabold transition-all shadow-[0_8px_20px_rgb(20,184,166,0.3)] hover:shadow-[0_12px_25px_rgb(20,184,166,0.4)] flex items-center justify-center gap-2 active:scale-95 disabled:opacity-50 disabled:shadow-none"
                        >
                            <Play className="w-3.5 h-3.5" />
                            {t.accounts.connect}
                        </button>
                    </form>
                </div>

                {/* Active accounts list column */}
                <div className="lg:col-span-2 space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-xl font-bold tracking-tight text-slate-850">{t.accounts.activeSessions}</h2>
                            <p className="text-xs text-slate-400 mt-1">{t.accounts.description}</p>
                        </div>
                        <button
                            onClick={fetchSessions}
                            className="p-2 hover:bg-slate-100 border border-slate-200 text-slate-500 rounded-xl transition-all active:scale-95"
                        >
                            <RefreshCw className="w-4 h-4" />
                        </button>
                    </div>

                    {sessions.length === 0 ? (
                        <div className="py-16 text-center border border-dashed border-slate-300 rounded-2xl bg-white space-y-4 px-6">
                            <QrCode className="w-10 h-10 text-slate-300 mx-auto" />
                            <div className="max-w-md mx-auto space-y-1">
                                <h3 className="text-sm font-bold text-slate-700">{t.accounts.title}</h3>
                                <p className="text-xs text-slate-400">{t.accounts.noAccounts}</p>
                            </div>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {sessions.map((s: any) => (
                                <div key={s.accountId} className="bg-white/60 backdrop-blur-xl border border-white/60 rounded-3xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.03)] relative overflow-hidden group hover:bg-white/90 hover:-translate-y-1 transition-all duration-300">
                                    <div className="flex justify-between items-start mb-4">
                                        <div>
                                            <h4 className="font-extrabold text-slate-800 flex items-center gap-2">
                                                {s.accountId}
                                            </h4>
                                            <div className="flex items-center gap-2 mt-2">
                                                <span className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider ${
                                                    s.state === 'connected' ? 'bg-emerald-50 text-emerald-600' :
                                                    s.state === 'qr_pending' ? 'bg-amber-50 text-amber-600' :
                                                    s.state === 'connecting' ? 'bg-blue-50 text-blue-600' :
                                                    s.state === 'banned' ? 'bg-rose-50 text-rose-600' :
                                                    'bg-slate-100 text-slate-500'
                                                }`}>
                                                    {s.state}
                                                </span>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-1">
                                            {s.state !== 'connected' && s.state !== 'connecting' && (
                                                <button
                                                    onClick={() => handleReconnectSession(s.accountId)}
                                                    className="text-teal-500 hover:text-teal-600 p-1.5 hover:bg-teal-50 rounded-lg transition-all"
                                                    title="Connect / Show QR"
                                                >
                                                    <Play className="w-4 h-4" />
                                                </button>
                                            )}
                                            <button
                                                onClick={() => handleDisconnectSession(s.accountId)}
                                                className="text-slate-400 hover:text-rose-500 p-1.5 hover:bg-rose-50 rounded-lg transition-all"
                                                title={t.accounts.disconnect}
                                            >
                                                <Power className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>

                                    {s.health && (
                                        <div className="pt-4 border-t border-slate-100">
                                            <div className="flex justify-between items-center text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider">
                                                <span>{t.accounts.trustScore}</span>
                                                <span>{s.health.trustScore}/100</span>
                                            </div>
                                            <div className="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                                <div
                                                    className={`h-full transition-all ${
                                                        s.health.trustScore > 80 ? 'bg-emerald-500' :
                                                        s.health.trustScore > 50 ? 'bg-amber-500' : 'bg-rose-500'
                                                    }`}
                                                    style={{ width: `${s.health.trustScore}%` }}
                                                />
                                            </div>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
