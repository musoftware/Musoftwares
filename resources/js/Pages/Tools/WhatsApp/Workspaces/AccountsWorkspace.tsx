import React from 'react';
import { QrCode, RefreshCw, Settings2, Play, Power, Trash2 } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Badge } from '@/Components/ui/badge';

export default function AccountsWorkspace({
    activeQR, qrCountdown, qrSessionId, t,
    newAccountId, setNewAccountId, newProxy, setNewProxy, newHeadless, setNewHeadless,
    daemonConnected, handleConnectSession, handleReconnectSession, sessions, fetchSessions, handleDisconnectSession
}: any) {
    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            {/* QR Overlay for pending connection */}
            {activeQR && (
                <Card className="relative overflow-hidden border-teal-500/20 bg-teal-50/50 dark:bg-teal-950/20 shadow-xl">
                    <CardContent className="flex flex-col md:flex-row items-center gap-8 p-6">
                        <div className="bg-white p-4 rounded-xl shrink-0 shadow-sm border border-slate-200 flex flex-col items-center justify-center">
                            <img src={activeQR} alt="WhatsApp QR Code" className="w-48 h-48 md:w-56 md:h-56" />
                            <div className="flex items-center gap-1.5 mt-3 text-slate-500 text-xs font-bold">
                                <RefreshCw className="w-3.5 h-3.5 animate-spin text-teal-600" />
                                <span>{t.accounts.qrRefreshes} ({qrCountdown}s)</span>
                            </div>
                        </div>

                        <div className="space-y-4 flex-1">
                            <Badge variant="outline" className="text-teal-600 border-teal-200 bg-teal-50 gap-1.5 py-1">
                                <QrCode className="w-3.5 h-3.5" />
                                {t.accounts.qrPendingBadge}
                            </Badge>
                            <h3 className="text-xl font-bold tracking-tight">{t.accounts.qrTitle} ({qrSessionId})</h3>
                            <p className="text-muted-foreground text-sm leading-relaxed">{t.accounts.qrInstructions}</p>
                        </div>
                    </CardContent>
                </Card>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Connect new session column */}
                <Card className="h-fit border-teal-200 shadow-sm relative overflow-hidden">
                    <div className="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-teal-400 to-emerald-500"></div>
                    <CardHeader className="pb-4 border-b bg-teal-50/30">
                        <CardTitle className="text-lg flex items-center gap-2.5">
                            <Settings2 className="w-5 h-5 text-teal-600" />
                            {t.accounts.addAccount}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <form onSubmit={handleConnectSession} className="space-y-5">
                            <div className="space-y-2">
                                <Label htmlFor="accountId" className="font-semibold">{t.accounts.accountId}</Label>
                                <Input
                                    id="accountId"
                                    type="text"
                                    value={newAccountId}
                                    onChange={e => setNewAccountId(e.target.value)}
                                    placeholder={t.accounts.accountIdPlaceholder}
                                    className="border-slate-300 focus-visible:ring-teal-500"
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="proxy" className="font-semibold">{t.accounts.proxy}</Label>
                                <Input
                                    id="proxy"
                                    type="text"
                                    value={newProxy}
                                    onChange={e => setNewProxy(e.target.value)}
                                    placeholder={t.accounts.proxyPlaceholder}
                                    className="border-slate-300 focus-visible:ring-teal-500"
                                />
                            </div>
                            <div className="flex items-center gap-3 py-2 bg-slate-50 p-3 rounded-lg border border-slate-100">
                                <Switch
                                    id="headless-toggle"
                                    checked={newHeadless}
                                    onCheckedChange={setNewHeadless}
                                />
                                <Label htmlFor="headless-toggle" className="cursor-pointer font-medium text-sm">{t.accounts.headless}</Label>
                            </div>
                            <Button
                                type="submit"
                                disabled={!daemonConnected}
                                className="w-full bg-teal-600 hover:bg-teal-700 text-white gap-2 py-6 text-lg font-bold shadow-md hover:shadow-lg transition-all"
                            >
                                <QrCode className="w-5 h-5" />
                                {t.accounts.connect}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Active accounts list column */}
                <div className="lg:col-span-2 space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-xl font-bold tracking-tight">{t.accounts.activeSessions}</h2>
                            <p className="text-sm text-muted-foreground mt-1">{t.accounts.description}</p>
                        </div>
                        <Button
                            variant="outline"
                            size="icon"
                            onClick={fetchSessions}
                        >
                            <RefreshCw className="w-4 h-4" />
                        </Button>
                    </div>

                    {sessions.length === 0 ? (
                        <Card className="border-dashed">
                            <CardContent className="py-16 text-center space-y-4">
                                <QrCode className="w-10 h-10 text-muted-foreground mx-auto" />
                                <div className="max-w-md mx-auto space-y-1">
                                    <h3 className="text-sm font-bold">{t.accounts.title}</h3>
                                    <p className="text-xs text-muted-foreground">{t.accounts.noAccounts}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {sessions.map((s: any) => (
                                <Card key={s.accountId} className="group hover:border-teal-200 transition-all duration-300">
                                    <CardContent className="p-6">
                                        <div className="flex justify-between items-start mb-4">
                                            <div>
                                                <h4 className="font-bold flex items-center gap-2">
                                                    {s.accountId}
                                                </h4>
                                                <div className="flex items-center gap-2 mt-2">
                                                    <Badge variant={
                                                        s.state === 'connected' ? 'default' :
                                                        s.state === 'qr_pending' ? 'secondary' :
                                                        s.state === 'connecting' ? 'outline' :
                                                        s.state === 'banned' ? 'destructive' :
                                                        'secondary'
                                                    } className={
                                                        s.state === 'connected' ? 'bg-emerald-500 hover:bg-emerald-600' :
                                                        s.state === 'qr_pending' ? 'bg-amber-100 text-amber-800' : ''
                                                    }>
                                                        {s.state}
                                                    </Badge>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-1">
                                                {s.state !== 'connected' && s.state !== 'connecting' && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => handleReconnectSession(s.accountId)}
                                                        title="Connect / Show QR"
                                                        className="text-teal-600 hover:text-teal-700 hover:bg-teal-50"
                                                    >
                                                        <Play className="w-4 h-4" />
                                                    </Button>
                                                )}
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => handleDisconnectSession(s.accountId)}
                                                    title={t.accounts.disconnect}
                                                    className="text-muted-foreground hover:text-destructive hover:bg-destructive/10"
                                                >
                                                    <Power className="w-4 h-4" />
                                                </Button>
                                            </div>
                                        </div>

                                        {s.health && (
                                            <div className="pt-4 border-t">
                                                <div className="flex justify-between items-center text-[10px] font-bold text-muted-foreground mb-1.5 uppercase tracking-wider">
                                                    <span>{t.accounts.trustScore}</span>
                                                    <span>{s.health.trustScore}/100</span>
                                                </div>
                                                <div className="w-full bg-secondary h-1.5 rounded-full overflow-hidden">
                                                    <div
                                                        className={`h-full transition-all ${
                                                            s.health.trustScore > 80 ? 'bg-emerald-500' :
                                                            s.health.trustScore > 50 ? 'bg-amber-500' : 'bg-destructive'
                                                        }`}
                                                        style={{ width: `${s.health.trustScore}%` }}
                                                    />
                                                </div>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
