import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { formatMoney } from '@/lib/utils';
import {
    Sparkles,
    CheckCircle2,
    Lock,
    ArrowRight,
    TrendingUp,
    Calculator,
    PieChart,
    Loader2
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { useToast } from '@/Components/ui/use-toast';

export default function UpgradePreview() {
    const [isUpgrading, setIsUpgrading] = useState(false);
    const { toast } = useToast();

    // Simulated upgrade pipeline
    const handleUpgradeSimulate = () => {
        setIsUpgrading(true);
        setTimeout(() => {
            // Write simulated activation into sessionStorage so AuthenticatedLayout and other pages reflect the active ERP state instantly!
            sessionStorage.setItem('is_subscribed_erp', 'true');
            setIsUpgrading(false);
            
            toast({
                title: "Premium ERP Workspace Unlocked!",
                description: "Your organization now has active estimates, expense tracking, and reports.",
            });

            // Redirect back to dashboard where ERP is now fully active
            router.visit(route('dashboard'));
        }, 1500);
    };
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('overview');

    return (
        <ERPLayout title={__('general.erp_workspace_premium_upgrade')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="max-w-[1000px] mx-auto px-4 py-8 space-y-8 font-sans text-sm">
                
                {/* Visual upgrade hero card */}
                <Card className="border-primary/20 bg-muted/10 shadow-none overflow-hidden relative">
                    <div className="absolute top-0 right-0 translate-x-12 -translate-y-12 opacity-5 pointer-events-none">
                        <Calculator className="h-64 w-64 text-primary" />
                    </div>

                    <CardContent className="p-8 md:p-10 relative z-10 max-w-2xl space-y-6">
                        <Badge variant="secondary" className="bg-primary/10 text-primary border-primary/20 font-semibold gap-1.5 px-3 py-1">
                            <Sparkles className="h-3.5 w-3.5" />{__('general.premium_erp_extension_module')}</Badge>
                        <div className="space-y-2">
                            <h1 className="text-2xl md:text-3xl font-bold tracking-tight text-foreground leading-tight">{__('general.consolidate_invoicing_estimations_profit_ledger_into_one_operational_workspace')}</h1>
                            <p className="text-muted-foreground leading-relaxed">{__('general.upgrade_your_client_account_to_unlock_professional_estimates_automated_recurring_business_invoices_employee_expense_reporting_and_active_ledger_profit_distribution_charts')}</p>
                        </div>
                        
                        <div className="pt-2">
                            <Button 
                                onClick={handleUpgradeSimulate}
                                disabled={isUpgrading}
                                className="shadow-none flex items-center gap-2 group h-11 px-8 transition-all"
                            >
                                {isUpgrading ? "Checking License..." : "Activate ERP Capability"}
                                {!isUpgrading && <ArrowRight className="h-4 w-4 group-hover:translate-x-1 transition-transform" />}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Capabilities grid with locked screen visual mockups */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Capability 1: Advanced Estimations */}
                    <Card className="shadow-none border-border group overflow-hidden">
                        <CardHeader className="pb-3 relative">
                            <div className="absolute top-6 right-6 text-muted-foreground opacity-30 group-hover:opacity-60 transition-opacity">
                                <Lock className="h-5 w-5" />
                            </div>
                            <CardTitle className="text-base flex items-center gap-2">
                                <Calculator className="h-4 w-4 text-primary" />{__('general.dynamic_estimations_engine')}</CardTitle>
                            <CardDescription className="text-xs leading-normal pt-1">{__('general.build_interactive_itemized_estimates_and_automatically_convert_them_into_billing_invoices_upon_client_signature_approval')}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {/* Blurred Mockup Visual */}
                            <div className="rounded-lg border border-border bg-muted/30 p-4 select-none filter blur-[1.5px] opacity-70 pointer-events-none font-mono text-[10px] space-y-3">
                                <div className="flex justify-between border-b border-border pb-2">
                                    <span className="font-semibold text-foreground">Estimate #EST-021</span>
                                    <span className="bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded font-medium">Draft</span>
                                </div>
                                <div className="space-y-1.5 text-muted-foreground">
                                    <div className="flex justify-between"><span>{__('general.core_refactoring')}</span><span>{formatMoney(1200, 'USD')}</span></div>
                                    <div className="flex justify-between"><span>{__('general.database_migration')}</span><span>{formatMoney(650, 'USD')}</span></div>
                                </div>
                                <div className="text-right font-bold pt-2 border-t border-border text-foreground">Total: {formatMoney(1850, 'USD')}</div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Capability 2: Ledger Expenses & Cash Flow Reports */}
                    <Card className="shadow-none border-border group overflow-hidden">
                        <CardHeader className="pb-3 relative">
                            <div className="absolute top-6 right-6 text-muted-foreground opacity-30 group-hover:opacity-60 transition-opacity">
                                <Lock className="h-5 w-5" />
                            </div>
                            <CardTitle className="text-base flex items-center gap-2">
                                <PieChart className="h-4 w-4 text-primary" />{__('general.automated_expense_tracker')}</CardTitle>
                            <CardDescription className="text-xs leading-normal pt-1">{__('general.log_operational_costs_vendor_receipts_server_costs_and_compile_automatic_profit_loss_charts_for_annual_tax_schedules')}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {/* Blurred chart visual */}
                            <div className="rounded-lg border border-border bg-muted/30 p-4 select-none filter blur-[1.5px] opacity-70 pointer-events-none font-mono text-[10px] space-y-3">
                                <div className="flex justify-between items-center">
                                    <span className="font-semibold text-foreground">{__('general.profit_loss_ledger')}</span>
                                    <span className="text-emerald-600 font-bold flex items-center"><TrendingUp className="h-3 w-3 mr-1" /> +14.5%</span>
                                </div>
                                <div className="flex justify-between text-muted-foreground">
                                    <span>Net Sales: {formatMoney(12450, 'USD')}</span>
                                    <span>Expenses: {formatMoney(3210, 'USD')}</span>
                                </div>
                                <div className="h-2 w-full bg-border rounded-full overflow-hidden flex">
                                    <div className="h-full bg-emerald-500 w-2/3" />
                                    <div className="h-full bg-rose-400 w-1/3" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Feature Comparison Capability Table */}
                <Card className="shadow-none overflow-hidden">
                    <CardHeader className="bg-muted/30 border-b pb-4">
                        <CardTitle className="text-xs font-bold uppercase tracking-wider text-muted-foreground">{__('general.capability_mapping_comparison')}</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/10">
                                        <TableHead className="pl-6 w-[40%]">Feature</TableHead>
                                        <TableHead className="text-center w-[30%]">{__('general.standard_client_account')}</TableHead>
                                        <TableHead className="text-center w-[30%] bg-primary/5 text-primary font-semibold">{__('general.premium_erp_workspace')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow>
                                        <TableCell className="pl-6 font-medium">{__('general.receive_invoices_pay')}</TableCell>
                                        <TableCell className="text-center"><CheckCircle2 className="h-4 w-4 text-emerald-500 mx-auto" /></TableCell>
                                        <TableCell className="text-center bg-primary/5"><CheckCircle2 className="h-4 w-4 text-emerald-500 mx-auto" /></TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell className="pl-6 font-medium">{__('general.estimates_creation_signatures')}</TableCell>
                                        <TableCell className="text-center text-muted-foreground"><Lock className="h-4 w-4 mx-auto opacity-50" /></TableCell>
                                        <TableCell className="text-center bg-primary/5"><CheckCircle2 className="h-4 w-4 text-emerald-500 mx-auto" /></TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell className="pl-6 font-medium">{__('general.client_expense_ledger_tracking')}</TableCell>
                                        <TableCell className="text-center text-muted-foreground"><Lock className="h-4 w-4 mx-auto opacity-50" /></TableCell>
                                        <TableCell className="text-center bg-primary/5"><CheckCircle2 className="h-4 w-4 text-emerald-500 mx-auto" /></TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell className="pl-6 font-medium">{__('general.annual_profit_loss_reports')}</TableCell>
                                        <TableCell className="text-center text-muted-foreground"><Lock className="h-4 w-4 mx-auto opacity-50" /></TableCell>
                                        <TableCell className="text-center bg-primary/5"><CheckCircle2 className="h-4 w-4 text-emerald-500 mx-auto" /></TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell className="pl-6 font-medium">{__('general.recurring_auto_invoicing_automation')}</TableCell>
                                        <TableCell className="text-center text-muted-foreground"><Lock className="h-4 w-4 mx-auto opacity-50" /></TableCell>
                                        <TableCell className="text-center bg-primary/5"><CheckCircle2 className="h-4 w-4 text-emerald-500 mx-auto" /></TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

                {/* Bottom interactive Upgrade CTA banner */}
                <Card className="shadow-none border-primary/20 bg-primary/5">
                    <CardContent className="p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div className="space-y-1">
                            <span className="font-semibold text-foreground block">{__('general.ready_to_unlock_fully_unified_operations')}</span>
                            <p className="text-xs text-muted-foreground leading-snug">{__('general.activate_your_premium_erp_workspace_license_today_simulates_check_clearance_instantly_in_sandbox_mode')}</p>
                        </div>
                        <Button 
                            onClick={handleUpgradeSimulate}
                            disabled={isUpgrading}
                            className="shadow-none whitespace-nowrap"
                        >
                            {isUpgrading ? <><Loader2 className="mr-2 h-4 w-4 animate-spin" />{__('general.checking')}</> : "Upgrade Now"}
                        </Button>
                    </CardContent>
                </Card>
            </div>
            
            {/* Simulated progress overlay */}
            <AnimatePresence>
                {isUpgrading && (
                    <motion.div 
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="fixed inset-0 z-50 bg-background/80 backdrop-blur-sm flex items-center justify-center p-4"
                    >
                        <motion.div 
                            initial={{ scale: 0.95, y: 20 }}
                            animate={{ scale: 1, y: 0 }}
                            exit={{ scale: 0.95, y: 20 }}
                            className="bg-card rounded-xl p-8 max-w-sm w-full border shadow-lg text-center space-y-4"
                        >
                            <div className="h-12 w-12 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto animate-spin">
                                <Loader2 className="h-6 w-6" />
                            </div>
                            <div className="space-y-1">
                                <h3 className="font-semibold text-card-foreground text-base">{__('general.processing_license_setup')}</h3>
                                <p className="text-muted-foreground text-xs leading-normal">{__('general.provisioning_estimates_tables_recurring_models_and_dashboard_metrics_trackers')}</p>
                            </div>
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>
        </ERPLayout>
    );
}
