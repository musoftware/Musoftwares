import React, { useState, useEffect } from 'react';
import { BarChart3, Send, Users, MessageSquare, Bot, Contact, Clock, CheckCircle2, XCircle, Play, CalendarClock, TrendingUp, Inbox, ArrowUpRight } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';

export default function DashboardWorkspace({ t, locale, callRPC, daemonConnected, setActiveTab }: any) {
    const isRtl = locale === 'ar';
    const [stats, setStats] = useState<any>(null);
    const [loading, setLoading] = useState(true);

    const fetchStats = async () => {
        if (!daemonConnected) return;
        setLoading(true);
        try {
            const res: any = await callRPC('getDashboardStats', {});
            setStats(res);
        } catch (err: any) {
            console.error('Dashboard stats error:', err);
        }
        setLoading(false);
    };

    useEffect(() => {
        if (daemonConnected) fetchStats();
        // Auto-refresh every 30s
        const interval = setInterval(() => { if (daemonConnected) fetchStats(); }, 30000);
        return () => clearInterval(interval);
    }, [daemonConnected]);

    const deliveryRate = stats?.messages?.total > 0
        ? Math.round(((stats.messages.delivered || 0) + (stats.messages.read_count || 0) + (stats.messages.replied || 0)) / stats.messages.total * 100)
        : 0;

    const replyRate = stats?.messages?.total > 0
        ? Math.round((stats.messages.replied || 0) / stats.messages.total * 100)
        : 0;

    const STATUS_COLORS: Record<string, string> = {
        running: 'bg-blue-500', processing: 'bg-blue-500', completed: 'bg-emerald-500',
        failed: 'bg-red-500', paused: 'bg-amber-500', scheduled: 'bg-orange-500',
        created: 'bg-slate-400', stopped: 'bg-slate-400'
    };

    if (loading && !stats) {
        return (
            <div className="flex items-center justify-center py-20">
                <div className="flex flex-col items-center gap-3">
                    <span className="size-8 border-3 border-teal-500 border-t-transparent rounded-full animate-spin" />
                    <p className="text-sm text-muted-foreground">{isRtl ? 'جارٍ تحميل الإحصائيات...' : 'Loading dashboard...'}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            {/* Header */}
            <div className="text-start">
                <h2 className="text-xl font-bold tracking-tight flex items-center gap-2">
                    <div className="size-9 rounded-xl bg-gradient-to-br from-teal-100 to-emerald-100 dark:from-teal-950/40 dark:to-emerald-950/40 flex items-center justify-center">
                        <BarChart3 className="w-5 h-5 text-teal-600" />
                    </div>
                    {isRtl ? 'لوحة التحكم' : 'Dashboard'}
                </h2>
                <p className="text-sm text-muted-foreground mt-1">
                    {isRtl ? 'نظرة عامة على أداء أعمالك' : 'Overview of your business performance'}
                </p>
            </div>

            {/* Main Stats Grid */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {/* Accounts */}
                <Card className="rounded-2xl hover:shadow-md transition-shadow cursor-pointer group" onClick={() => setActiveTab?.('accounts')}>
                    <CardContent className="p-4 text-center">
                        <div className="size-10 rounded-xl bg-blue-50 dark:bg-blue-950/30 flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                            <Users className="w-5 h-5 text-blue-600" />
                        </div>
                        <div className="text-2xl font-black text-blue-600">{stats?.sessions?.connected || 0}<span className="text-base text-muted-foreground font-medium">/{stats?.sessions?.total || 0}</span></div>
                        <p className="text-[11px] text-muted-foreground font-medium mt-0.5">{isRtl ? 'حسابات متصلة' : 'Connected Accounts'}</p>
                    </CardContent>
                </Card>

                {/* Messages Sent */}
                <Card className="rounded-2xl hover:shadow-md transition-shadow">
                    <CardContent className="p-4 text-center">
                        <div className="size-10 rounded-xl bg-teal-50 dark:bg-teal-950/30 flex items-center justify-center mx-auto mb-2">
                            <Send className="w-5 h-5 text-teal-600" />
                        </div>
                        <div className="text-2xl font-black text-teal-600">{((stats?.messages?.sent || 0) + (stats?.messages?.delivered || 0) + (stats?.messages?.read_count || 0) + (stats?.messages?.replied || 0)).toLocaleString()}</div>
                        <p className="text-[11px] text-muted-foreground font-medium mt-0.5">{isRtl ? 'رسائل مرسلة' : 'Messages Sent'}</p>
                    </CardContent>
                </Card>

                {/* Delivery Rate */}
                <Card className="rounded-2xl hover:shadow-md transition-shadow">
                    <CardContent className="p-4 text-center">
                        <div className="size-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 flex items-center justify-center mx-auto mb-2">
                            <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                        </div>
                        <div className="text-2xl font-black text-emerald-600">{deliveryRate}%</div>
                        <p className="text-[11px] text-muted-foreground font-medium mt-0.5">{isRtl ? 'معدل التوصيل' : 'Delivery Rate'}</p>
                    </CardContent>
                </Card>

                {/* Reply Rate */}
                <Card className="rounded-2xl hover:shadow-md transition-shadow">
                    <CardContent className="p-4 text-center">
                        <div className="size-10 rounded-xl bg-violet-50 dark:bg-violet-950/30 flex items-center justify-center mx-auto mb-2">
                            <MessageSquare className="w-5 h-5 text-violet-600" />
                        </div>
                        <div className="text-2xl font-black text-violet-600">{replyRate}%</div>
                        <p className="text-[11px] text-muted-foreground font-medium mt-0.5">{isRtl ? 'معدل الرد' : 'Reply Rate'}</p>
                    </CardContent>
                </Card>
            </div>

            {/* Secondary Stats */}
            <div className="grid grid-cols-2 lg:grid-cols-5 gap-3">
                <Card className="rounded-2xl cursor-pointer hover:shadow-sm transition-shadow" onClick={() => setActiveTab?.('history')}>
                    <CardContent className="p-3 flex items-center gap-3">
                        <div className="size-9 rounded-lg bg-blue-50/80 dark:bg-blue-950/20 flex items-center justify-center shrink-0">
                            <TrendingUp className="w-4 h-4 text-blue-600" />
                        </div>
                        <div className="text-start min-w-0">
                            <div className="text-lg font-black">{stats?.campaigns?.total || 0}</div>
                            <p className="text-[10px] text-muted-foreground font-medium truncate">{isRtl ? 'إجمالي الحملات' : 'Total Campaigns'}</p>
                        </div>
                    </CardContent>
                </Card>
                <Card className="rounded-2xl cursor-pointer hover:shadow-sm transition-shadow" onClick={() => setActiveTab?.('contacts')}>
                    <CardContent className="p-3 flex items-center gap-3">
                        <div className="size-9 rounded-lg bg-sky-50/80 dark:bg-sky-950/20 flex items-center justify-center shrink-0">
                            <Contact className="w-4 h-4 text-sky-600" />
                        </div>
                        <div className="text-start min-w-0">
                            <div className="text-lg font-black">{stats?.contacts || 0}</div>
                            <p className="text-[10px] text-muted-foreground font-medium truncate">{isRtl ? 'جهات الاتصال' : 'Contacts'}</p>
                        </div>
                    </CardContent>
                </Card>
                <Card className="rounded-2xl cursor-pointer hover:shadow-sm transition-shadow" onClick={() => setActiveTab?.('auto-reply')}>
                    <CardContent className="p-3 flex items-center gap-3">
                        <div className="size-9 rounded-lg bg-violet-50/80 dark:bg-violet-950/20 flex items-center justify-center shrink-0">
                            <Bot className="w-4 h-4 text-violet-600" />
                        </div>
                        <div className="text-start min-w-0">
                            <div className="text-lg font-black">{stats?.autoReply?.activeRules || 0}</div>
                            <p className="text-[10px] text-muted-foreground font-medium truncate">{isRtl ? 'قواعد رد تلقائي' : 'Auto-Replies'}</p>
                        </div>
                    </CardContent>
                </Card>
                <Card className="rounded-2xl cursor-pointer hover:shadow-sm transition-shadow" onClick={() => setActiveTab?.('inbox')}>
                    <CardContent className="p-3 flex items-center gap-3">
                        <div className="size-9 rounded-lg bg-amber-50/80 dark:bg-amber-950/20 flex items-center justify-center shrink-0">
                            <Inbox className="w-4 h-4 text-amber-600" />
                        </div>
                        <div className="text-start min-w-0">
                            <div className="text-lg font-black">{stats?.unreadInbox || 0}</div>
                            <p className="text-[10px] text-muted-foreground font-medium truncate">{isRtl ? 'رسائل غير مقروءة' : 'Unread Messages'}</p>
                        </div>
                    </CardContent>
                </Card>
                <Card className="rounded-2xl">
                    <CardContent className="p-3 flex items-center gap-3">
                        <div className="size-9 rounded-lg bg-red-50/80 dark:bg-red-950/20 flex items-center justify-center shrink-0">
                            <XCircle className="w-4 h-4 text-red-600" />
                        </div>
                        <div className="text-start min-w-0">
                            <div className="text-lg font-black">{stats?.messages?.failed || 0}</div>
                            <p className="text-[10px] text-muted-foreground font-medium truncate">{isRtl ? 'رسائل فاشلة' : 'Failed Messages'}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Message Funnel */}
            <Card className="rounded-2xl text-start">
                <CardHeader className="pb-4 border-b">
                    <CardTitle className="text-base flex items-center gap-2">
                        <Send className="w-4 h-4 text-teal-600" />
                        {isRtl ? 'مسار الرسائل' : 'Message Funnel'}
                    </CardTitle>
                </CardHeader>
                <CardContent className="pt-6">
                    <div className="space-y-3">
                        {[
                            { label: isRtl ? 'مرسلة' : 'Sent', value: stats?.messages?.sent || 0, color: 'bg-blue-500', max: stats?.messages?.total || 1 },
                            { label: isRtl ? 'تم التوصيل' : 'Delivered', value: stats?.messages?.delivered || 0, color: 'bg-teal-500', max: stats?.messages?.total || 1 },
                            { label: isRtl ? 'مقروءة' : 'Read', value: stats?.messages?.read_count || 0, color: 'bg-emerald-500', max: stats?.messages?.total || 1 },
                            { label: isRtl ? 'تم الرد' : 'Replied', value: stats?.messages?.replied || 0, color: 'bg-violet-500', max: stats?.messages?.total || 1 },
                            { label: isRtl ? 'فشلت' : 'Failed', value: stats?.messages?.failed || 0, color: 'bg-red-500', max: stats?.messages?.total || 1 },
                        ].map((item, i) => (
                            <div key={i} className="flex items-center gap-3">
                                <span className="text-xs font-bold w-20 text-end shrink-0">{item.label}</span>
                                <div className="flex-1 h-6 bg-muted/30 rounded-full overflow-hidden relative">
                                    <div
                                        className={`h-full ${item.color} rounded-full transition-all duration-700 ease-out`}
                                        style={{ width: `${Math.max(item.value / item.max * 100, item.value > 0 ? 2 : 0)}%` }}
                                    />
                                    <span className="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-foreground/80">
                                        {item.value.toLocaleString()}
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>

            {/* Recent Campaigns */}
            <Card className="rounded-2xl text-start">
                <CardHeader className="pb-4 border-b flex flex-row items-center justify-between">
                    <CardTitle className="text-base flex items-center gap-2">
                        <Clock className="w-4 h-4 text-muted-foreground" />
                        {isRtl ? 'آخر الحملات' : 'Recent Campaigns'}
                    </CardTitle>
                    <button onClick={() => setActiveTab?.('history')} className="text-xs text-teal-600 font-bold flex items-center gap-0.5 hover:underline">
                        {isRtl ? 'عرض الكل' : 'View All'} <ArrowUpRight className="w-3 h-3" />
                    </button>
                </CardHeader>
                <CardContent className="pt-4">
                    <div className="space-y-2">
                        {(stats?.recentCampaigns || []).map((c: any) => {
                            const progress = c.total_contacts > 0 ? Math.round((c.sent_count / c.total_contacts) * 100) : 0;
                            return (
                                <div key={c.id} className="flex items-center gap-3 p-3 rounded-xl hover:bg-muted/30 transition-colors">
                                    <div className={`size-2.5 rounded-full shrink-0 ${STATUS_COLORS[c.status] || 'bg-slate-400'}`} />
                                    <div className="flex-1 min-w-0">
                                        <p className="text-xs font-bold truncate">{c.name}</p>
                                        <p className="text-[10px] text-muted-foreground">
                                            {c.sent_count}/{c.total_contacts} · {new Date(c.created_at).toLocaleDateString()}
                                        </p>
                                    </div>
                                    <div className="text-end shrink-0">
                                        <Badge variant="secondary" className="text-[9px] font-bold px-1.5 py-0 h-4">
                                            {c.schedule_status === 'scheduled' ? (isRtl ? 'مجدولة' : 'Scheduled') : c.status}
                                        </Badge>
                                    </div>
                                    <div className="w-16 h-1.5 bg-muted rounded-full overflow-hidden shrink-0">
                                        <div className={`h-full rounded-full ${STATUS_COLORS[c.status] || 'bg-slate-400'}`} style={{ width: `${progress}%` }} />
                                    </div>
                                </div>
                            );
                        })}
                        {(!stats?.recentCampaigns || stats.recentCampaigns.length === 0) && (
                            <div className="text-center py-8">
                                <p className="text-xs text-muted-foreground">{isRtl ? 'لا توجد حملات بعد' : 'No campaigns yet'}</p>
                            </div>
                        )}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
