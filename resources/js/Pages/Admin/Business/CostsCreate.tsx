import React from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { 
    Card, 
    CardContent, 
    CardHeader, 
    CardTitle,
    CardDescription,
    CardFooter
} from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
import { Loader2, ArrowLeft, Receipt, DollarSign, FileText, Calendar, Building2, User } from 'lucide-react';

export default function CostsCreate() {
    const { users, projects, currencies, businessCurrency } = usePage<any>().props;

    const { data, setData, post, processing, errors, reset } = useForm({
        amount: '',
        currency_id: businessCurrency?.id || '',
        reason: '',
        created_at: new Date().toISOString().split('T')[0],
        user_id: '',
        project_id: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.costs.store'), {
            onSuccess: () => reset(),
        });
    };

    return (
        <AdminSidebarLayout 
            title={__('admin.add_cost') || "Add Direct Cost"} 
            header={__('admin.add_cost') || "Add Direct Cost"}
        >
            <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center">
                    <Button 
                        variant="ghost" 
                        size="sm" 
                        onClick={() => router.visit(route('admin.costs.index'))}
                        className="me-4 hover:bg-slate-100"
                    >
                        <ArrowLeft className="w-4 h-4 me-2" />
                        {__('general.back') || "Back"}
                    </Button>
                </div>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardHeader className="pb-4 border-b border-slate-100 bg-slate-50/50 rounded-t-xl">
                        <div className="flex items-center gap-3">
                            <div className="p-2.5 bg-rose-100 text-rose-600 rounded-xl shadow-sm">
                                <Receipt className="w-5 h-5" />
                            </div>
                            <div>
                                <CardTitle className="text-xl font-bold text-slate-900">
                                    {__('admin.add_direct_cost') || "Add Direct Cost"}
                                </CardTitle>
                                <CardDescription className="text-sm mt-1">
                                    {__('admin.add_direct_cost_description') || "Record a new operational or business expense."}
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>

                    <form onSubmit={handleSubmit}>
                        <CardContent className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                
                                {/* Amount */}
                                <div className="space-y-2">
                                    <Label className="text-slate-700 font-semibold flex items-center gap-2">
                                        <DollarSign className="w-4 h-4 text-slate-400" />
                                        {__('general.amount') || "Amount"} <span className="text-rose-500">*</span>
                                    </Label>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={data.amount}
                                        onChange={(e) => setData('amount', e.target.value)}
                                        placeholder="0.00"
                                        className="h-11 font-mono text-base bg-white"
                                        required
                                    />
                                    {errors.amount && <p className="text-xs text-rose-500 mt-1">{errors.amount}</p>}
                                </div>

                                {/* Currency */}
                                <div className="space-y-2">
                                    <Label className="text-slate-700 font-semibold flex items-center gap-2">
                                        <DollarSign className="w-4 h-4 text-slate-400" />
                                        {__('general.currency') || "Currency"} <span className="text-rose-500">*</span>
                                    </Label>
                                    <PremiumCombobox
                                        value={data.currency_id}
                                        onChange={(val) => setData('currency_id', val as string)}
                                        options={currencies.map((c: any) => ({
                                            value: c.id,
                                            label: `${c.currency} (${c.symbol})`
                                        }))}
                                        placeholder={__('general.select_currency') || "Select Currency..."}
                                    />
                                    {errors.currency_id && <p className="text-xs text-rose-500 mt-1">{errors.currency_id}</p>}
                                </div>

                                {/* Reason */}
                                <div className="space-y-2 md:col-span-2">
                                    <Label className="text-slate-700 font-semibold flex items-center gap-2">
                                        <FileText className="w-4 h-4 text-slate-400" />
                                        {__('general.reason') || "Reason / Description"} <span className="text-rose-500">*</span>
                                    </Label>
                                    <Input
                                        value={data.reason}
                                        onChange={(e) => setData('reason', e.target.value)}
                                        placeholder={__('admin.cost_reason_placeholder') || "e.g., Server Hosting, Marketing Campaign"}
                                        className="h-11 bg-white"
                                        required
                                    />
                                    {errors.reason && <p className="text-xs text-rose-500 mt-1">{errors.reason}</p>}
                                </div>

                                {/* Date */}
                                <div className="space-y-2 md:col-span-2 lg:col-span-1">
                                    <Label className="text-slate-700 font-semibold flex items-center gap-2">
                                        <Calendar className="w-4 h-4 text-slate-400" />
                                        {__('general.date') || "Date"}
                                    </Label>
                                    <Input
                                        type="date"
                                        value={data.created_at}
                                        onChange={(e) => setData('created_at', e.target.value)}
                                        className="h-11 bg-white"
                                    />
                                    {errors.created_at && <p className="text-xs text-rose-500 mt-1">{errors.created_at}</p>}
                                </div>

                                {/* Spacer for layout on large screens */}
                                <div className="hidden lg:block"></div>

                                <div className="md:col-span-2 border-t border-slate-100 pt-6 mt-2">
                                    <h3 className="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
                                        <Building2 className="w-4 h-4 text-slate-400" />
                                        {__('admin.association_optional') || "Association (Optional)"}
                                    </h3>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        {/* Client / User */}
                                        <div className="space-y-2">
                                            <Label className="text-slate-700 text-sm flex items-center gap-2">
                                                <User className="w-4 h-4 text-slate-400" />
                                                {__('admin.client_user') || "Client / User"}
                                            </Label>
                                            <PremiumCombobox
                                                value={data.user_id}
                                                onChange={(val) => setData('user_id', val as string)}
                                                options={users}
                                                placeholder={__('admin.search_client') || "Search Client..."}
                                            />
                                            {errors.user_id && <p className="text-xs text-rose-500 mt-1">{errors.user_id}</p>}
                                        </div>

                                        {/* Project */}
                                        <div className="space-y-2">
                                            <Label className="text-slate-700 text-sm flex items-center gap-2">
                                                <Building2 className="w-4 h-4 text-slate-400" />
                                                {__('admin.project') || "Project"}
                                            </Label>
                                            <PremiumCombobox
                                                value={data.project_id}
                                                onChange={(val) => setData('project_id', val as string)}
                                                options={projects}
                                                placeholder={__('admin.search_project') || "Search Project..."}
                                            />
                                            {errors.project_id && <p className="text-xs text-rose-500 mt-1">{errors.project_id}</p>}
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </CardContent>
                        <CardFooter className="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3 rounded-b-xl">
                            <Button 
                                type="button"
                                variant="outline" 
                                onClick={() => router.visit(route('admin.costs.index'))}
                            >
                                {__('general.cancel') || "Cancel"}
                            </Button>
                            <Button type="submit" disabled={processing} className="bg-rose-600 hover:bg-rose-700 text-white min-w-[120px]">
                                {processing ? (
                                    <>
                                        <Loader2 className="me-2 h-4 w-4 animate-spin" />
                                        {__('general.saving') || "Saving..."}
                                    </>
                                ) : (
                                    __('general.save') || "Save Cost"
                                )}
                            </Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}
