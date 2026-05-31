import React from 'react';
import { Card } from '@/Components/ui/card';
import { cn } from '@/lib/utils';

interface FreelanceCardProps extends React.ComponentProps<typeof Card> {
    interactive?: boolean;
}

export function FreelanceCard({ children, className, interactive = false, ...props }: FreelanceCardProps) {
    return (
        <Card 
            className={cn(
                'shadow-sm border-slate-200 overflow-hidden bg-white',
                interactive && 'hover:border-indigo-200 transition-colors cursor-pointer group',
                className
            )} 
            {...props}
        >
            {children}
        </Card>
    );
}
