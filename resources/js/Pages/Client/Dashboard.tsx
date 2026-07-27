import React from 'react';
import SciFiDashboard from './Dashboard/SciFiDashboard';
import type { DashboardStats, RecentTransaction, ChartData, UserProject, ActiveTool } from './Dashboard/types';

interface DashboardProps {
    stats?: DashboardStats;
    recentTransactions?: RecentTransaction[];
    chartData?: ChartData[];
    userProjects?: UserProject[];
    activeToolLicenses?: ActiveTool[];
}

export default function Dashboard({ 
    stats, 
    recentTransactions = [],
    chartData = [],
    userProjects = [],
    activeToolLicenses = [],
}: DashboardProps) {
    return (
        <SciFiDashboard
            stats={stats}
            recentTransactions={recentTransactions}
            chartData={chartData}
            userProjects={userProjects}
            activeToolLicenses={activeToolLicenses}
        />
    );
}

