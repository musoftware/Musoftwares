import React from 'react';
import V8Dashboard from './Dashboard/V8Dashboard';
import type { DashboardStats } from './Dashboard/types';

interface DashboardProps {
    stats?: DashboardStats;
}

export default function Dashboard({ stats }: DashboardProps) {
    return <V8Dashboard stats={stats} />;
}

