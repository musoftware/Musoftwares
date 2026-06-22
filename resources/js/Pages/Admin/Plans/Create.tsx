import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { AsyncCombobox } from '@/Components/ui/AsyncCombobox';
import { Building2, MessageSquare, Zap, Store, Sparkles, Wrench, Check, Layers } from 'lucide-react';
import { toast } from '@/Components/ui/use-toast';
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';

const iconMap: Record<string, React.ElementType> = {
    Building2,
    MessageSquare,
    Zap,
    Store,
    Sparkles,
    Wrench,
    Check,
    Layers,
};

export default function Create({ services }) {
    const { data, setData, post, processing, errors } = useForm({
        user_id: '',
        object: '',
        expires_at: '',
        conflict_resolution: 'extend',
    });

    const [selectedUser, setSelectedUser] = useState<any>(null);

    const handleUserSelect = (user: any) => {
        setData('user_id', user ? String(user.id) : '');
        setSelectedUser(user);
    };

    const handleServiceSelect = (serviceId: string) => {
        setData('object', serviceId);
    };

    const handleDurationClick = (months: number) => {
        let baseDate = new Date();
        
        // Helper to check if user already has an active subscription for the selected service
        const activeSub = selectedUser?.subscriptions?.find(
            (sub: any) => sub.object === data.object && new Date(sub.expires_at) > new Date()
        );

        if (activeSub && data.conflict_resolution === 'extend') {
            const currentExpiry = new Date(activeSub.expires_at);
            if (currentExpiry > baseDate) {
                baseDate = currentExpiry;
            }
        }
        
        baseDate.setMonth(baseDate.getMonth() + months);
        setData('expires_at', baseDate.toISOString().split('T')[0]);
    };

    const handleLifetimeClick = () => {
        const baseDate = new Date();
        baseDate.setFullYear(baseDate.getFullYear() + 100);
        setData('expires_at', baseDate.toISOString().split('T')[0]);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.plans.store'), {
            onSuccess: () => {
                toast({
                    title: __('admin.success'),
                    description: __('admin.subscription_assigned_successfully'),
                });
            },
        });
    };

    // Helper to check if user already has an active subscription for the selected service
    const hasActiveSubscription = selectedUser?.subscriptions?.find(
        (sub: any) => sub.object === data.object && new Date(sub.expires_at) > new Date()
    );

    return (
        <AdminSidebarLayout 
            title={__('admin.add_subscription')} 
            header={__('admin.add_subscription')}
        >
            <Head title={__('admin.add_subscription')} />

            <form onSubmit={handleSubmit} className="space-y-6 w-full max-w-7xl">
                {/* User Selection */}
                <Card>
                    <CardHeader>
                        <CardTitle>{__('admin.select_user')}</CardTitle>
                        <CardDescription>{__('admin.select_user_to_assign_subscription')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="w-full max-w-7xl space-y-2">
                            <Label htmlFor="user_id">{__('admin.user')}</Label>
                            <AsyncCombobox
                                endpoint={route('admin.plans.search-users')}
                                placeholder={__('admin.search_users_placeholder')}
                                value={data.user_id}
                                onChange={(val, option) => handleUserSelect(option)}
                            />
                            {errors.user_id && <p className="text-sm text-red-600">{errors.user_id}</p>}
                        </div>
                    </CardContent>
                </Card>

                {/* Service Selection */}
                <Card>
                    <CardHeader>
                        <CardTitle>{__('admin.select_service')}</CardTitle>
                        <CardDescription>{__('admin.select_service_to_assign')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {services.map((service: any) => {
                                const Icon = iconMap[service.icon] || Layers;
                                const isSelected = data.object === service.id;
                                return (
                                    <div 
                                        key={service.id}
                                        onClick={() => handleServiceSelect(service.id)}
                                        className={`p-4 rounded-xl border-2 cursor-pointer transition-all duration-200 flex flex-col gap-3 ${
                                            isSelected ? 'border-slate-900 bg-slate-100/50' : 'border-gray-200 hover:border-slate-200 hover:bg-gray-50'
                                        }`}
                                    >
                                        <div className="flex items-center gap-3">
                                            <div className={`flex h-10 w-10 items-center justify-center rounded-lg ${isSelected ? 'bg-slate-900 text-white' : 'bg-gray-100 text-gray-600'}`}>
                                                <Icon className="h-5 w-5" />
                                            </div>
                                            <div>
                                                <h3 className={`font-semibold text-sm ${isSelected ? 'text-slate-900' : 'text-gray-900'}`}>
                                                    {service.name}
                                                </h3>
                                                <span className="text-xs text-gray-500 uppercase tracking-wider font-medium">{service.type}</span>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                        {errors.object && <p className="text-sm text-red-600 mt-2">{errors.object}</p>}
                    </CardContent>
                </Card>

                {/* Duration & Conflict Resolution */}
                {data.object && (
                    <Card>
                        <CardHeader>
                            <CardTitle>{__('admin.subscription_duration')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            
                            {hasActiveSubscription && (
                                <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-lg space-y-3">
                                    <p className="text-sm text-yellow-800 font-medium">
                                        {__('admin.user_has_active_subscription', { date: new Date(hasActiveSubscription.expires_at).toLocaleDateString() })}
                                    </p>
                                    <RadioGroup 
                                        value={data.conflict_resolution} 
                                        onValueChange={(val) => setData('conflict_resolution', val)}
                                        className="flex flex-col space-y-2"
                                    >
                                        <div className="flex items-center space-x-2">
                                            <RadioGroupItem value="extend" id="r1" />
                                            <Label htmlFor="r1" className="text-yellow-900 cursor-pointer">{__('admin.extend_expiration_date')}</Label>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <RadioGroupItem value="replace" id="r2" />
                                            <Label htmlFor="r2" className="text-yellow-900 cursor-pointer">{__('admin.replace_expiration_date')}</Label>
                                        </div>
                                    </RadioGroup>
                                </div>
                            )}

                            <div className="flex flex-wrap gap-2">
                                <Button type="button" variant="outline" onClick={() => handleDurationClick(1)}>{__('admin.1_month')}</Button>
                                <Button type="button" variant="outline" onClick={() => handleDurationClick(6)}>{__('admin.6_months')}</Button>
                                <Button type="button" variant="outline" onClick={() => handleDurationClick(12)}>{__('admin.1_year')}</Button>
                                <Button type="button" variant="outline" onClick={handleLifetimeClick}>{__('admin.lifetime')}</Button>
                            </div>

                            <div className="max-w-xs space-y-2">
                                <Label htmlFor="expires_at">{__('admin.expiration_date')}</Label>
                                <Input 
                                    type="date" 
                                    id="expires_at" 
                                    value={data.expires_at} 
                                    onChange={(e) => setData('expires_at', e.target.value)} 
                                />
                                {errors.expires_at && <p className="text-sm text-red-600">{errors.expires_at}</p>}
                            </div>
                        </CardContent>
                    </Card>
                )}

                <div className="flex justify-end gap-3">
                    <Button type="button" variant="ghost" onClick={() => window.history.back()}>
                        {__('general.cancel')}
                    </Button>
                    <Button type="submit" disabled={processing || !data.user_id || !data.object || !data.expires_at}>
                        {__('general.save')}
                    </Button>
                </div>
            </form>
        </AdminSidebarLayout>
    );
}
