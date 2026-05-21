import React, { useState, useEffect } from 'react';
import {
    QrCode, RefreshCw, Settings2, Play, Power, Trash2,
    Pencil, Check, X, Activity, Phone,
    AlertTriangle, Loader2, ShieldCheck, ShieldAlert, Wifi, WifiOff
} from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Badge } from '@/Components/ui/badge';
import { Avatar, AvatarImage, AvatarFallback, AvatarBadge } from '@/Components/ui/avatar';
import { Separator } from '@/Components/ui/separator';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/Components/ui/tooltip';
import { StatusBadge } from '@/Components/ui/StatusBadge';

// ── Tooltip Button helper ─────────────────────────────────────────────────────
function TipButton({ tip, children, ...props }: any) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button {...props}>{children}</Button>
            </TooltipTrigger>
            <TooltipContent side="left"><p>{tip}</p></TooltipContent>
        </Tooltip>
    );
}

// ── Session Card ──────────────────────────────────────────────────────────────
function SessionCard({
    s, t,
    onReconnect, onDisconnect, onDelete, onRename, onCheckStatus, onGetPhoto
}: any) {
    const [renaming, setRenaming]     = useState(false);
    const [nameInput, setNameInput]   = useState(s.displayName || s.accountId);
    const [statusInfo, setStatusInfo] = useState<{ status: string; phoneNumber?: string } | null>(null);
    const [statusLoading, setStatusLoading] = useState(false);
    const [photo, setPhoto]           = useState<string | null>(null);
    const [deleteConfirm, setDeleteConfirm] = useState(false);

    // Auto-fetch profile photo when session is connected
    useEffect(() => {
        if (s.state === 'connected') {
            onGetPhoto(s.accountId)
                .then((url: string | null) => { if (url) setPhoto(url); })
                .catch(() => {});
        } else {
            setPhoto(null);
        }
    }, [s.state, s.accountId]);

    const handleRenameSubmit = () => {
        if (!nameInput.trim()) return;
        onRename(s.accountId, nameInput.trim());
        setRenaming(false);
    };

    const handleCheckStatus = async () => {
        setStatusLoading(true);
        const res = await onCheckStatus(s.accountId);
        setStatusInfo(res);
        setStatusLoading(false);
    };

    const displayLabel = s.displayName || s.accountId;

    return (
        <Card className="group overflow-hidden transition-all duration-200 hover:shadow-md hover:border-border/80">
            <CardContent className="p-0">
                {/* ── Header ── */}
                <div className="flex items-start gap-3 p-4">
                    {/* Avatar */}
                    <Avatar size="lg" className="shrink-0">
                        <AvatarImage src={photo ?? undefined} alt={displayLabel} />
                        <AvatarFallback className="bg-primary/10 text-primary font-bold">
                            {displayLabel.charAt(0).toUpperCase()}
                        </AvatarFallback>
                        {s.state === 'connected' && (
                            <AvatarBadge className="bg-emerald-500" />
                        )}
                    </Avatar>

                    {/* Name + Status */}
                    <div className="flex-1 min-w-0 space-y-1">
                        {renaming ? (
                            <div className="flex items-center gap-1.5">
                                <Input
                                    value={nameInput}
                                    onChange={e => setNameInput(e.target.value)}
                                    onKeyDown={e => {
                                        if (e.key === 'Enter')  handleRenameSubmit();
                                        if (e.key === 'Escape') setRenaming(false);
                                    }}
                                    className="h-7 text-sm"
                                    autoFocus
                                />
                                <Button size="icon" variant="ghost" onClick={handleRenameSubmit} className="size-7 shrink-0 text-emerald-600">
                                    <Check className="size-3.5" />
                                </Button>
                                <Button size="icon" variant="ghost" onClick={() => setRenaming(false)} className="size-7 shrink-0 text-muted-foreground">
                                    <X className="size-3.5" />
                                </Button>
                            </div>
                        ) : (
                            <div className="flex items-center gap-1.5 min-w-0">
                                <p className="font-semibold text-sm truncate leading-tight">{displayLabel}</p>
                                <button
                                    onClick={() => { setNameInput(displayLabel); setRenaming(true); }}
                                    className="opacity-0 group-hover:opacity-60 hover:!opacity-100 transition-opacity text-muted-foreground hover:text-foreground shrink-0"
                                    aria-label={t.accounts.rename}
                                >
                                    <Pencil className="size-3" />
                                </button>
                            </div>
                        )}

                        <div className="flex items-center gap-2 flex-wrap">
                            <StatusBadge status={s.state} size="sm" />
                            <span className="inline-flex items-center gap-1.5 text-[11px] text-muted-foreground font-mono bg-muted/40 px-2.5 py-0.5 rounded-full border border-muted/60">
                                <Phone className="size-3 text-muted-foreground/70" />
                                {s.phoneNumber || statusInfo?.phoneNumber || '—'}
                            </span>
                        </div>
                    </div>

                    {/* Action icons */}
                    <TooltipProvider delayDuration={300}>
                        <div className="flex items-center gap-0.5 shrink-0">
                            {s.state !== 'connected' && s.state !== 'connecting' && (
                                <TipButton
                                    tip={t.accounts.reconnect}
                                    variant="ghost" size="icon"
                                    onClick={() => onReconnect(s.accountId)}
                                    className="size-8 text-primary hover:bg-primary/10"
                                >
                                    <Play className="size-3.5" />
                                </TipButton>
                            )}

                            <TipButton
                                tip={t.accounts.disconnect}
                                variant="ghost" size="icon"
                                onClick={() => onDisconnect(s.accountId)}
                                className="size-8 text-muted-foreground hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/30"
                            >
                                <Power className="size-3.5" />
                            </TipButton>

                            {deleteConfirm ? (
                                <>
                                    <Button size="icon" variant="ghost" onClick={() => onDelete(s.accountId)}
                                        className="size-8 text-destructive hover:bg-destructive/10">
                                        <Check className="size-3.5" />
                                    </Button>
                                    <Button size="icon" variant="ghost" onClick={() => setDeleteConfirm(false)}
                                        className="size-8 text-muted-foreground hover:bg-muted">
                                        <X className="size-3.5" />
                                    </Button>
                                </>
                            ) : (
                                <TipButton
                                    tip={t.accounts.deleteSession}
                                    variant="ghost" size="icon"
                                    onClick={() => setDeleteConfirm(true)}
                                    className="size-8 text-muted-foreground hover:text-destructive hover:bg-destructive/10"
                                >
                                    <Trash2 className="size-3.5" />
                                </TipButton>
                            )}
                        </div>
                    </TooltipProvider>
                </div>

                <Separator />

                {/* ── Status check result ── */}
                {statusInfo && (
                    <>
                        <div className="flex items-center gap-2 px-4 py-2.5 bg-muted/40 text-xs">
                            {statusInfo.status === 'connected'
                                ? <ShieldCheck className="size-4 text-emerald-500 shrink-0" />
                                : <ShieldAlert className="size-4 text-amber-500 shrink-0" />
                            }
                            <span className="text-muted-foreground">
                                {t.accounts.checkStatusResult}:{' '}
                                <span className="font-semibold text-foreground">{statusInfo.status}</span>
                            </span>
                            {statusInfo.phoneNumber && (
                                <span className="ml-auto text-muted-foreground">{statusInfo.phoneNumber}</span>
                            )}
                        </div>
                        <Separator />
                    </>
                )}

                {/* ── Health bar ── */}
                {s.health && (
                    <>
                        <div className="px-4 py-3 space-y-1.5">
                            <div className="flex justify-between text-[10px] font-medium text-muted-foreground uppercase tracking-wider">
                                <span>{t.accounts.trustScore}</span>
                                <span>{s.health.trustScore}/100</span>
                            </div>
                            <div className="w-full bg-secondary h-1 rounded-full overflow-hidden">
                                <div
                                    className={`h-full transition-all duration-500 ${
                                        s.health.trustScore > 80 ? 'bg-emerald-500' :
                                        s.health.trustScore > 50 ? 'bg-amber-500'   : 'bg-destructive'
                                    }`}
                                    style={{ width: `${s.health.trustScore}%` }}
                                />
                            </div>
                        </div>
                        <Separator />
                    </>
                )}

                {/* ── Footer toolbar ── */}
                <div className="flex">
                    <Button
                        variant="ghost"
                        size="sm"
                        className="flex-1 gap-1.5 rounded-none h-9 text-xs text-muted-foreground hover:text-foreground hover:bg-muted/60 border-r"
                        onClick={handleCheckStatus}
                        disabled={statusLoading}
                    >
                        {statusLoading
                            ? <Loader2 className="size-3 animate-spin" />
                            : <Activity className="size-3" />
                        }
                        {t.accounts.checkStatus}
                    </Button>

                    <Button
                        variant="ghost"
                        size="sm"
                        className="flex-1 gap-1.5 rounded-none h-9 text-xs text-muted-foreground hover:text-foreground hover:bg-muted/60"
                        onClick={() => { setNameInput(displayLabel); setRenaming(true); }}
                    >
                        <Pencil className="size-3" />
                        {t.accounts.rename}
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

// ── Main Export ────────────────────────────────────────────────────────────────
export default function AccountsWorkspace({
    activeQR, qrCountdown, qrSessionId, t,
    newAccountId, setNewAccountId, newProxy, setNewProxy, newHeadless, setNewHeadless,
    daemonConnected, handleConnectSession, handleReconnectSession, sessions, fetchSessions,
    handleDisconnectSession, handleDeleteSession, handleRenameSession,
    handleCheckStatus, handleGetProfilePhoto
}: any) {
    return (
        <div className="space-y-6 animate-in fade-in duration-300">

            {/* ── QR Scan Card ── */}
            {activeQR && (
                <Card className="border-primary/20 bg-primary/5">
                    <CardContent className="flex flex-col md:flex-row items-center gap-6 p-6">
                        <div className="bg-background p-3 rounded-xl shrink-0 shadow-sm border flex flex-col items-center">
                            <img src={activeQR} alt="WhatsApp QR Code" className="w-48 h-48 md:w-52 md:h-52" />
                            <div className="flex items-center gap-1.5 mt-2.5 text-muted-foreground text-xs font-medium">
                                <RefreshCw className="size-3.5 animate-spin text-primary" />
                                <span>{t.accounts.qrRefreshes} ({qrCountdown}s)</span>
                            </div>
                        </div>
                        <div className="space-y-3 flex-1">
                            <Badge variant="outline" className="gap-1.5">
                                <QrCode className="size-3.5" />
                                {t.accounts.qrPendingBadge}
                            </Badge>
                            <h3 className="text-xl font-semibold tracking-tight">
                                {t.accounts.qrTitle}{' '}
                                <span className="text-muted-foreground font-normal">({qrSessionId})</span>
                            </h3>
                            <p className="text-muted-foreground text-sm leading-relaxed">{t.accounts.qrInstructions}</p>
                        </div>
                    </CardContent>
                </Card>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* ── Connect Form ── */}
                <Card className="h-fit">
                    <CardHeader className="pb-4 border-b">
                        <CardTitle className="text-base flex items-center gap-2">
                            <Settings2 className="size-4 text-primary" />
                            {t.accounts.addAccount}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-5">
                        <form onSubmit={handleConnectSession} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="accountId">{t.accounts.accountId}</Label>
                                <Input
                                    id="accountId"
                                    value={newAccountId}
                                    onChange={e => setNewAccountId(e.target.value)}
                                    placeholder={t.accounts.accountIdPlaceholder}
                                    required
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="proxy">{t.accounts.proxy}</Label>
                                <Input
                                    id="proxy"
                                    value={newProxy}
                                    onChange={e => setNewProxy(e.target.value)}
                                    placeholder={t.accounts.proxyPlaceholder}
                                />
                            </div>

                            <div className="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-muted/50 border">
                                <Switch
                                    id="headless-toggle"
                                    checked={newHeadless}
                                    onCheckedChange={setNewHeadless}
                                />
                                <Label htmlFor="headless-toggle" className="cursor-pointer text-sm">
                                    {t.accounts.headless}
                                </Label>
                            </div>

                            <Button
                                type="submit"
                                disabled={!daemonConnected}
                                className="w-full gap-2"
                            >
                                <QrCode className="size-4" />
                                {t.accounts.connect}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* ── Sessions List ── */}
                <div className="lg:col-span-2 space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-base font-semibold tracking-tight">{t.accounts.activeSessions}</h2>
                            <p className="text-xs text-muted-foreground mt-0.5">{t.accounts.description}</p>
                        </div>
                        <Button variant="outline" size="icon" onClick={fetchSessions} className="size-8">
                            <RefreshCw className="size-3.5" />
                        </Button>
                    </div>

                    {sessions.length === 0 ? (
                        <Card className="border-dashed">
                            <CardContent className="py-14 text-center space-y-3">
                                <div className="size-10 rounded-full bg-muted flex items-center justify-center mx-auto">
                                    <QrCode className="size-5 text-muted-foreground" />
                                </div>
                                <div className="space-y-1">
                                    <p className="text-sm font-medium">{t.accounts.title}</p>
                                    <p className="text-xs text-muted-foreground">{t.accounts.noAccounts}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {sessions.map((s: any) => (
                                <SessionCard
                                    key={s.accountId}
                                    s={s}
                                    t={t}
                                    onReconnect={handleReconnectSession}
                                    onDisconnect={handleDisconnectSession}
                                    onDelete={handleDeleteSession}
                                    onRename={handleRenameSession}
                                    onCheckStatus={handleCheckStatus}
                                    onGetPhoto={handleGetProfilePhoto}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
