import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { MetricCard } from '@/Components/ui/MetricCard';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { formatMoney } from '@/lib/utils';
import {
  Heart, Wallet, TrendingUp, TrendingDown, Users,
  Plus, Minus, Search, Settings, ArrowLeftRight } from
'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter } from
"@/Components/ui/dialog";
import Pagination from '@/Components/Pagination';
import { Avatar, AvatarFallback, AvatarImage } from "@/Components/ui/avatar";
import { toast } from 'sonner';
import { __, getLoadedLocale } from '@/lib/i18n';

interface CharityCounterProps {
  charityCounters: any;
  filters: any;
  stats: {
    totalBalance: number;
    totalReceived: number;
    totalSpent: number;
    totalUsers: number;
  };
}

export default function CharityCounterIndex({ charityCounters, filters, stats }: CharityCounterProps) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);
    const [isSubtractModalOpen, setIsSubtractModalOpen] = useState(false);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('admin.charity-counter.index'), { search: searchQuery }, { preserveState: true });
    };

    const clearSearch = () => {
        setSearchQuery('');
        router.get(route('admin.charity-counter.index'));
    };

  const addForm = useForm({
    amount: '',
    description: ''
  });

  const subtractForm = useForm({
    amount: '',
    description: ''
  });

  const handleAddSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    addForm.post(route('admin.charity-counter.add-amount'), {
      onSuccess: () => {
        setIsAddModalOpen(false);
        addForm.reset();
        toast.success(__('admin.charity_counter_add_success'));
      },
      onError: () => {
        toast.error(__('admin.charity_counter_add_failed'));
      }
    });
  };

  const handleSubtractSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    subtractForm.post(route('admin.charity-counter.subtract-amount'), {
      onSuccess: () => {
        setIsSubtractModalOpen(false);
        subtractForm.reset();
        toast.success(__('admin.charity_counter_subtract_success'));
      },
      onError: () => {
        toast.error(__('admin.charity_counter_subtract_failed'));
      }
    });
  };

  return (
    <AdminSidebarLayout>
            <Head title={__('admin.charity_counter_page_title')} />

            <div className="space-y-6">
                {/* Header Section */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-end gap-4 gap-4">
                    <div className="me-auto">
                        <h1 className="text-2xl font-bold tracking-tight">{__('admin.charity_counter_heading')}</h1>
                        <p className="text-muted-foreground mt-1">
                            {__('admin.charity_counter_subtitle')}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" className="text-destructive hover:bg-destructive/10" onClick={() => setIsSubtractModalOpen(true)}>
                            <Minus className="w-4 h-4 me-2" />
                            {__('admin.charity_counter_subtract_button')}
                        </Button>
                        <Button onClick={() => setIsAddModalOpen(true)}>
                            <Plus className="w-4 h-4 me-2" />
                            {__('admin.charity_counter_add_button')}
                        </Button>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <MetricCard
            label={__('admin.charity_counter_current_balance')}
            value={formatMoney(stats.totalBalance)}
            icon={Wallet}
            className="border-primary/20" />
          
                    <MetricCard
            label={__('admin.charity_counter_total_donations')}
            value={formatMoney(stats.totalReceived)}
            icon={TrendingUp} />
          
                    <MetricCard
            label={__('admin.charity_counter_total_spent')}
            value={formatMoney(stats.totalSpent)}
            icon={TrendingDown} />
          
                    <MetricCard
            label={__('admin.charity_counter_donors_count')}
            value={stats.totalUsers.toString()}
            icon={Users} />
          
                </div>

                {/* Global Management Section */}
                <Card className="border-primary/20 shadow-sm">
                    <CardHeader className="pb-3 border-b bg-muted/30">
                        <div className="flex items-center gap-2">
                            <Settings className="w-5 h-5 text-primary" />
                            <CardTitle className="text-lg">{__('admin.charity_counter_manage_title')}</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent className="p-6">
                        <div className="grid md:grid-cols-2 gap-6 items-center">
                            <div className="bg-primary/10 rounded-xl p-6 border border-primary/20 text-center md:text-end">
                                <div className="flex items-center justify-center md:justify-start gap-2 text-primary font-semibold mb-2">
                                    <Heart className="w-5 h-5" />
                                    <span>{__('admin.charity_counter_current_balance')}</span>
                                </div>
                                <div className="text-4xl font-bold text-primary mb-1">
                                    {formatMoney(stats.totalBalance)}
                                </div>
                                <p className="text-sm text-primary/80">{__('admin.charity_counter_balance_hint')}</p>
                            </div>
                            
                            <div className="flex flex-col sm:flex-row gap-3 h-full justify-center md:justify-end">
                                <Button
                  size="lg"
                  className="flex-1 sm:flex-none h-16 text-lg"
                  onClick={() => setIsAddModalOpen(true)}>
                  
                                    <Plus className="w-5 h-5 me-2" />
                                    {__('admin.charity_counter_add_amount_title')}
                                </Button>
                                <Button
                  size="lg"
                  variant="outline"
                  className="flex-1 sm:flex-none h-16 text-lg border-destructive text-destructive hover:bg-destructive/10"
                  onClick={() => setIsSubtractModalOpen(true)}>
                  
                                    <Minus className="w-5 h-5 me-2" />
                                    {__('admin.charity_counter_subtract_button')}
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Users List */}
                <Card>
                    <CardHeader className="flex flex-col sm:flex-row justify-end gap-4 sm:items-center gap-4 border-b pb-4">
                        <div className="me-auto flex items-center gap-2">
                            <Users className="w-5 h-5 text-muted-foreground" />
                            <CardTitle>{__('admin.charity_counter_user_donations')}</CardTitle>
                        </div>
                        
                        <form onSubmit={handleSearch} className="flex gap-2 max-w-sm w-full">
                            <div className="relative flex-1">
                                <Search className="absolute end-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                <Input
                  placeholder={__('admin.charity_counter_search_placeholder')}
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="ps-3 pe-9" />
                
                            </div>
                            <Button type="submit" variant="secondary">{__('general.search')}</Button>
                        </form>
                    </CardHeader>
                    
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-end">
                                <thead className="bg-muted/50 border-b">
                                    <tr>
                                        <th className="py-3 px-4 font-semibold text-muted-foreground">{__('general.user')}</th>
                                        <th className="py-3 px-4 font-semibold text-muted-foreground">{__('common.email')}</th>
                                        <th className="py-3 px-4 font-semibold text-muted-foreground">{__('admin.charity_counter_total_donations')}</th>
                                        <th className="py-3 px-4 font-semibold text-muted-foreground">{__('admin.charity_counter_last_donation_date')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(charityCounters.data as any).length > 0 ?
                  (charityCounters.data as any).map((counter: any) =>
                  <tr key={counter.id} className="border-b last:border-0 hover:bg-muted/20 transition-colors">
                                                <td className="py-3 px-4">
                                                    <div className="flex items-center gap-3">
                                                        <Avatar className="w-8 h-8">
                                                            <AvatarImage src={`https://www.gravatar.com/avatar/${btoa(counter.user.email)}?s=40&d=mp`} />
                                                            <AvatarFallback>{counter.user.name.substring(0, 2).toUpperCase()}</AvatarFallback>
                                                        </Avatar>
                                                        <span className="font-medium">{counter.user.name}</span>
                                                    </div>
                                                </td>
                                                <td className="py-3 px-4 text-muted-foreground">{counter.user.email}</td>
                                                <td className="py-3 px-4 font-bold text-success">{formatMoney(counter.total_received)}</td>
                                                <td className="py-3 px-4 text-muted-foreground">
                                                    {new Date(counter.updated_at).toLocaleDateString(getLoadedLocale() ?? 'en', { year: 'numeric', month: 'short', day: 'numeric' })}
                                                </td>
                                            </tr>
                  ) :

                  <tr>
                                            <td colSpan={4} className="py-12 text-center text-muted-foreground">
                                                <div className="flex flex-col items-center justify-center">
                                                    <Wallet className="w-12 h-12 mb-3 text-muted-foreground/30" />
                                                    <p>{__('admin.charity_counter_no_donations')}</p>
                                                </div>
                                            </td>
                                        </tr>
                  }
                                </tbody>
                            </table>
                        </div>
                        
                        {charityCounters.links && charityCounters.links.length > 3 &&
            <div className="p-4 border-t">
                                <Pagination links={charityCounters.links} />
                            </div>
            }
                    </CardContent>
                </Card>
            </div>

            {/* Add Modal */}
            <Dialog open={isAddModalOpen} onOpenChange={setIsAddModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{__('admin.charity_counter_add_amount_title')}</DialogTitle>
                        <DialogDescription>
                            {__('admin.charity_counter_add_description')}
                        </DialogDescription>
                    </DialogHeader>
                    
                    <form onSubmit={handleAddSubmit} className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="add-amount">{__('general.amount')}</Label>
                            <Input
                id="add-amount"
                type="number"
                step="0.01"
                min="0.01"
                value={addForm.data.amount}
                onChange={(e) => addForm.setData('amount', e.target.value)}
                required />
              
                            {addForm.errors.amount && <p className="text-sm text-destructive">{addForm.errors.amount}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="add-desc">{__('admin.charity_counter_add_reason_label')}</Label>
                            <Textarea
                id="add-desc"
                value={addForm.data.description}
                onChange={(e) => addForm.setData('description', e.target.value)}
                placeholder={__('admin.charity_counter_add_reason_placeholder')}
                required />
              
                            {addForm.errors.description && <p className="text-sm text-destructive">{addForm.errors.description}</p>}
                        </div>
                        
                        <DialogFooter className="mt-6">
                            <Button type="button" variant="ghost" onClick={() => setIsAddModalOpen(false)}>{__('general.cancel')}</Button>
                            <Button type="submit" disabled={addForm.processing}>
                                {__('admin.charity_counter_add_submit')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Subtract Modal */}
            <Dialog open={isSubtractModalOpen} onOpenChange={setIsSubtractModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{__('admin.charity_counter_subtract_title')}</DialogTitle>
                        <DialogDescription>
                            {__('admin.charity_counter_subtract_description')}
                        </DialogDescription>
                    </DialogHeader>
                    
                    <form onSubmit={handleSubtractSubmit} className="space-y-4 py-4">
                        <div className="p-3 bg-muted rounded-md mb-4 flex justify-end gap-4 items-center">
                            <span className="me-auto text-sm font-medium">{__('admin.charity_counter_current_balance_label')}</span>
                            <span className="font-bold text-primary">{formatMoney(stats.totalBalance)}</span>
                        </div>
                        
                        <div className="space-y-2">
                            <Label htmlFor="sub-amount">{__('admin.charity_counter_subtract_amount_label')}</Label>
                            <Input
                id="sub-amount"
                type="number"
                step="0.01"
                min="0.01"
                max={stats.totalBalance}
                value={subtractForm.data.amount}
                onChange={(e) => subtractForm.setData('amount', e.target.value)}
                required />
              
                            {subtractForm.errors.amount && <p className="text-sm text-destructive">{subtractForm.errors.amount}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="sub-desc">{__('admin.charity_counter_subtract_reason_label')}</Label>
                            <Textarea
                id="sub-desc"
                value={subtractForm.data.description}
                onChange={(e) => subtractForm.setData('description', e.target.value)}
                placeholder={__('admin.charity_counter_subtract_reason_placeholder')}
                required />
              
                            {subtractForm.errors.description && <p className="text-sm text-destructive">{subtractForm.errors.description}</p>}
                        </div>
                        
                        <DialogFooter className="mt-6">
                            <Button type="button" variant="ghost" onClick={() => setIsSubtractModalOpen(false)}>{__('general.cancel')}</Button>
                            <Button type="submit" variant="destructive" disabled={subtractForm.processing}>
                                {__('admin.charity_counter_subtract_submit')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>);

}