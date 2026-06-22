import React from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Mail, Shield, Save } from 'lucide-react';
import { useToast } from '@/Components/ui/use-toast';
import { __ } from '@/lib/i18n';

export default function Smtp({ smtp }) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('settings');
    const { toast } = useToast();

    const { data, setData, put, processing, errors } = useForm({
        host: smtp?.host || '',
        port: smtp?.port || '',
        username: smtp?.username || '',
        password: smtp?.password || '',
        encryption: smtp?.encryption || 'tls',
        from_address: smtp?.from_address || '',
        from_name: smtp?.from_name || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('erp.settings.smtp.update'), {
            onSuccess: () => {
                toast({
                    title: __('general.settings_updated'),
                    description: __('general.smtp_settings_have_been_successfully_saved'),
                });
            },
            onError: () => {
                toast({
                    variant: 'destructive',
                    title: __('general.error'),
                    description: __('general.failed_to_update_smtp_settings'),
                });
            }
        });
    };

    return (
        <ERPLayout title={__('general.smtp_settings')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <div className="max-w-7xl w-full mx-auto px-4 py-10">
                <div className="mb-8">
                    <h1 className="text-2xl font-bold tracking-tight">{__('general.email_delivery_smtp')}</h1>
                    <p className="text-muted-foreground mt-1">{__('general.configure_your_smtp_server_to_send_invoices_and_notifications_directly_from_your_own_email_address')}</p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Mail className="h-5 w-5 text-primary" />{__('general.sender_identity')}</CardTitle>
                            <CardDescription>{__('general.this_information_will_appear_as_the_sender_when_clients_receive_your_emails')}</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-6 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="from_name">{__('general.sender_name')}<span className="text-destructive">*</span></Label>
                                <Input
                                    id="from_name"
                                    value={data.from_name}
                                    onChange={e => setData('from_name', e.target.value)}
                                    placeholder={__('general.e_g_acme_corp_billing')}
                                />
                                {errors.from_name && <p className="text-xs text-destructive">{errors.from_name}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="from_address">{__('general.sender_email')}<span className="text-destructive">*</span></Label>
                                <Input
                                    id="from_address"
                                    type="email"
                                    value={data.from_address}
                                    onChange={e => setData('from_address', e.target.value)}
                                    placeholder={__('general.e_g_billing_acmecorp_com')}
                                />
                                {errors.from_address && <p className="text-xs text-destructive">{errors.from_address}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Shield className="h-5 w-5 text-primary" />{__('general.server_credentials')}</CardTitle>
                            <CardDescription>{__('general.enter_your_smtp_server_details_you_can_find_these_in_your_email_provider_s_settings')}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="host">{__('general.smtp_host')}<span className="text-destructive">*</span></Label>
                                    <Input
                                        id="host"
                                        value={data.host}
                                        onChange={e => setData('host', e.target.value)}
                                        placeholder={__('general.e_g_smtp_gmail_com')}
                                    />
                                    {errors.host && <p className="text-xs text-destructive">{errors.host}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="port">{__('general.smtp_port')}<span className="text-destructive">*</span></Label>
                                    <Input
                                        id="port"
                                        type="number"
                                        value={data.port}
                                        onChange={e => setData('port', e.target.value)}
                                        placeholder={__('general.e_g_587')}
                                    />
                                    {errors.port && <p className="text-xs text-destructive">{errors.port}</p>}
                                </div>
                            </div>
                            
                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="username">{__('general.username')} <span className="text-destructive">*</span></Label>
                                    <Input
                                        id="username"
                                        value={data.username}
                                        onChange={e => setData('username', e.target.value)}
                                        placeholder={__('general.your_smtp_username')}
                                    />
                                    {errors.username && <p className="text-xs text-destructive">{errors.username}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="password">{__('general.password')} <span className="text-destructive">*</span></Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        value={data.password}
                                        onChange={e => setData('password', e.target.value)}
                                        placeholder={smtp?.password ? __('general.leave_blank_to_keep_current_password') : __('general.your_smtp_password')}
                                    />
                                    {errors.password && <p className="text-xs text-destructive">{errors.password}</p>}
                                </div>
                            </div>

                            <div className="space-y-2 md:w-1/2">
                                <Label htmlFor="encryption">{__('general.encryption')}</Label>
                                <select
                                    id="encryption"
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    value={data.encryption}
                                    onChange={e => setData('encryption', e.target.value)}
                                >
                                    <option value="tls">TLS</option>
                                    <option value="ssl">SSL</option>
                                    <option value="">{__('general.none')}</option>
                                </select>
                                {errors.encryption && <p className="text-xs text-destructive">{errors.encryption}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-4">
                        <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                            <Save className="me-2 h-4 w-4" />{__('general.save_settings')}</Button>
                    </div>
                </form>
            </div>
        </ERPLayout>
    );
}
