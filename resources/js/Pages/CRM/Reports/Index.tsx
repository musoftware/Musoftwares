import React, { useState } from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import { __ } from '@/lib/i18n';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Input } from '@/Components/ui/input';
import { FileSpreadsheet, Download, Activity, Users, DollarSign, Target } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import KPICard from '../Components/Widgets/KPICard';

export default function ReportsIndex({ kpis }: { kpis: any }) {
    const { data, setData, post, processing, errors } = useForm({
        report_type: 'sales_performance',
        start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
        end_date: new Date().toISOString().split('T')[0],
    });

    const handleExport = (e: React.FormEvent) => {
        e.preventDefault();
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = route('crm.reports.export');
        
        const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }

        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'report_type';
        typeInput.value = data.report_type;
        form.appendChild(typeInput);

        const startInput = document.createElement('input');
        startInput.type = 'hidden';
        startInput.name = 'start_date';
        startInput.value = data.start_date;
        form.appendChild(startInput);

        const endInput = document.createElement('input');
        endInput.type = 'hidden';
        endInput.name = 'end_date';
        endInput.value = data.end_date;
        form.appendChild(endInput);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    };

    return (
        <CrmLayout title={__('reports.department_reports') ?? 'Department Reports'} activeMenu="reports">
            <div className="flex flex-col h-full gap-6 p-8 pt-6">
                
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">{__('reports.department_reports') ?? 'Department Reports'}</h1>
                        <p className="text-sm text-muted-foreground mt-1">{__('reports.export_description') ?? 'Export activity, performance, and metrics for your department.'}</p>
                    </div>
                </div>

                {/* KPI Bar */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <KPICard 
                        title={__('reports.total_leads') ?? 'Total Leads'} 
                        value={kpis?.total_leads ?? 0} 
                        icon={Users} 
                        colorClass="bg-blue-100 text-blue-600" 
                    />
                    <KPICard 
                        title={__('reports.total_customers') ?? 'Total Customers'} 
                        value={kpis?.total_customers ?? 0} 
                        icon={Target} 
                        colorClass="bg-purple-100 text-purple-600" 
                    />
                    <KPICard 
                        title={__('reports.conversion_rate') ?? 'Conversion Rate'} 
                        value={`${kpis?.conversion_rate ?? 0}%`} 
                        icon={Activity} 
                        colorClass="bg-emerald-100 text-emerald-600" 
                    />
                    <KPICard 
                        title={__('reports.total_value') ?? 'Total Value'} 
                        value={`$${kpis?.total_value ?? 0}`} 
                        icon={DollarSign} 
                        colorClass="bg-amber-100 text-amber-600" 
                    />
                </div>

                {/* Main Content Area */}
                <div className="flex-1 flex gap-6 min-h-0">
                    <Card className="flex-1 flex flex-col overflow-hidden shadow-sm">
                        <CardHeader className="p-4 border-b bg-muted/50 space-y-0">
                            <CardTitle className="font-semibold flex items-center gap-2">
                                <FileSpreadsheet size={18} className="text-emerald-500" />
                                {__('reports.generate_export') ?? 'Generate Export'}
                            </CardTitle>
                            <CardDescription>
                                {__('reports.select_report_description') ?? 'Select the report type and date range to export data as CSV.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-6 overflow-y-auto">
                            <form onSubmit={handleExport} className="space-y-6 max-w-xl">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">{__('reports.report_type') ?? 'Report Type'}</label>
                                    <Select 
                                        value={data.report_type} 
                                        onValueChange={(val) => setData('report_type', val)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder={__('reports.select_report_type') ?? 'Select Report Type'} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="sales_performance">{__('reports.sales_performance') ?? 'Sales Performance'}</SelectItem>
                                            <SelectItem value="lead_sources">{__('reports.lead_sources') ?? 'Lead Sources'}</SelectItem>
                                            <SelectItem value="support_resolutions">{__('reports.support_resolutions') ?? 'Support Resolutions'}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.report_type && <p className="text-xs text-red-500">{errors.report_type}</p>}
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">{__('general.start_date') ?? 'Start Date'}</label>
                                        <Input 
                                            type="date" 
                                            value={data.start_date}
                                            onChange={e => setData('start_date', e.target.value)}
                                        />
                                        {errors.start_date && <p className="text-xs text-red-500">{errors.start_date}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">{__('general.end_date') ?? 'End Date'}</label>
                                        <Input 
                                            type="date" 
                                            value={data.end_date}
                                            onChange={e => setData('end_date', e.target.value)}
                                        />
                                        {errors.end_date && <p className="text-xs text-red-500">{errors.end_date}</p>}
                                    </div>
                                </div>

                                <Button type="submit" className="w-full sm:w-auto flex items-center gap-2">
                                    <Download size={16} />
                                    {__('reports.download_csv') ?? 'Download CSV'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </CrmLayout>
    );
}
