import React from 'react';
import { __ } from '@/lib/i18n';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Trophy, PhoneCall, Target, CheckCircle2 } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';

interface LeaderboardAgent {
    id: number;
    name: string;
    role: string;
    calls_made: number;
    conversion_rate: number;
    leads_closed: number;
}

export default function LeaderboardTable({ leaderboard = [] }: { leaderboard: LeaderboardAgent[] }) {
    if (!leaderboard || leaderboard.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center h-40 text-sm text-muted-foreground border border-dashed rounded-lg bg-slate-50/50">
                <Trophy className="w-8 h-8 mb-2 text-slate-300" />
                {__('general.no_agent_data_available_for_this_branch')}
            </div>
        );
    }

    const getRankColor = (index: number) => {
        if (index === 0) return "bg-amber-100 text-amber-700 border-amber-200";
        if (index === 1) return "bg-slate-100 text-slate-700 border-slate-200";
        if (index === 2) return "bg-orange-50 text-orange-700 border-orange-200";
        return "bg-slate-50 text-slate-500 border-slate-100";
    };

    return (
        <div className="rounded-md border">
            <Table>
                <TableHeader className="bg-slate-50">
                    <TableRow>
                        <TableHead className="w-12 text-center">#</TableHead>
                        <TableHead>{__('general.agent')}</TableHead>
                        <TableHead className="text-center">{__('general.calls')}</TableHead>
                        <TableHead className="text-center">{__('Closed')}</TableHead>
                        <TableHead className="text-right">{__('general.conversion')}</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {leaderboard.map((agent, index) => (
                        <TableRow key={agent.id} className="group">
                            <TableCell className="text-center font-medium">
                                <Badge variant="outline" className={`w-8 h-8 flex items-center justify-center p-0 rounded-full ${getRankColor(index)}`}>
                                    {index + 1}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <div className="flex items-center gap-3">
                                    <Avatar className="h-8 w-8">
                                        <AvatarImage src={`https://ui-avatars.com/api/?name=${encodeURIComponent(agent.name)}&background=random`} />
                                        <AvatarFallback>{agent.name.substring(0, 2).toUpperCase()}</AvatarFallback>
                                    </Avatar>
                                    <div className="flex flex-col">
                                        <span className="font-medium text-slate-900">{agent.name}</span>
                                        <span className="text-xs text-slate-500">{__(agent.role)}</span>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell className="text-center">
                                <div className="flex items-center justify-center gap-1.5 text-slate-600">
                                    <PhoneCall className="w-3.5 h-3.5 text-blue-500" />
                                    <span className="font-medium">{agent.calls_made}</span>
                                </div>
                            </TableCell>
                            <TableCell className="text-center">
                                <div className="flex items-center justify-center gap-1.5 text-slate-600">
                                    <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" />
                                    <span className="font-medium">{agent.leads_closed}</span>
                                </div>
                            </TableCell>
                            <TableCell className="text-right">
                                <div className="flex items-center justify-end gap-1.5">
                                    <Target className="w-3.5 h-3.5 text-indigo-500" />
                                    <span className="font-bold text-slate-700">{agent.conversion_rate}%</span>
                                </div>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
