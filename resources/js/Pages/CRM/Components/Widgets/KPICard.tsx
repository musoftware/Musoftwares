import React from 'react';
import { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/card';

interface KPICardProps {
    title: string;
    value: string | number;
    trend?: {
        value: number;
        isPositive: boolean;
        label: string;
    };
    icon: LucideIcon;
    colorClass: string;
}

export default function KPICard({ title, value, trend, icon: Icon, colorClass }: KPICardProps) {
    return (
        <Card className="hover:shadow-md transition-shadow">
            <CardContent className="p-5">
                <div className="flex items-start justify-between">
                    <div>
                        <p className="text-sm font-medium text-muted-foreground mb-1">{title}</p>
                        <h3 className="text-2xl font-bold tracking-tight text-foreground">{value}</h3>
                    </div>
                    <div className={`p-3 rounded-lg ${colorClass}`}>
                        <Icon size={20} />
                    </div>
                </div>
                
                {trend && (
                    <div className="mt-4 flex items-center gap-2">
                        <span className={`text-sm font-semibold ${trend.isPositive ? 'text-green-600' : 'text-red-600'}`}>
                            {trend.isPositive ? '+' : '-'}{Math.abs(trend.value)}%
                        </span>
                        <span className="text-sm text-muted-foreground">{trend.label}</span>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
