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
                    title: 'Settings Updated',
                    description: 'SMTP settings have been successfully saved.',
                });
            },
            onError: () => {
                toast({
                    variant: 'destructive',
                    title: 'Error',
                    description: 'Failed to update SMTP settings. Please check your inputs.',
                });
            }
        });
    };

    return (
        <ERPLayout title="SMTP Settings" workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <div className="max-w-4xl mx-auto px-4 py-10">
                <div className="mb-8">
                    <h1 className="text-2xl font-bold tracking-tight">Email Delivery (SMTP)</h1>
                    <p className="text-muted-foreground mt-1">
                        Configure your SMTP server to send invoices and notifications directly from your own email address.
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Mail className="h-5 w-5 text-primary" /> Sender Identity
                            </CardTitle>
                            <CardDescription>
                                This information will appear as the sender when clients receive your emails.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-6 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="from_name">Sender Name <span className="text-destructive">*</span></Label>
                                <Input
                                    id="from_name"
                                    value={data.from_name}
                                    onChange={e => setData('from_name', e.target.value)}
                                    placeholder="e.g. Acme Corp Billing"
                                />
                                {errors.from_name && <p className="text-xs text-destructive">{errors.from_name}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="from_address">Sender Email <span className="text-destructive">*</span></Label>
                                <Input
                                    id="from_address"
                                    type="email"
                                    value={data.from_address}
                                    onChange={e => setData('from_address', e.target.value)}
                                    placeholder="e.g. billing@acmecorp.com"
                                />
                                {errors.from_address && <p className="text-xs text-destructive">{errors.from_address}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Shield className="h-5 w-5 text-primary" /> Server Credentials
                            </CardTitle>
                            <CardDescription>
                                Enter your SMTP server details. You can find these in your email provider's settings.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="host">SMTP Host <span className="text-destructive">*</span></Label>
                                    <Input
                                        id="host"
                                        value={data.host}
                                        onChange={e => setData('host', e.target.value)}
                                        placeholder="e.g. smtp.gmail.com"
                                    />
                                    {errors.host && <p className="text-xs text-destructive">{errors.host}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="port">SMTP Port <span className="text-destructive">*</span></Label>
                                    <Input
                                        id="port"
                                        type="number"
                                        value={data.port}
                                        onChange={e => setData('port', e.target.value)}
                                        placeholder="e.g. 587"
                                    />
                                    {errors.port && <p className="text-xs text-destructive">{errors.port}</p>}
                                </div>
                            </div>
                            
                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="username">Username <span className="text-destructive">*</span></Label>
                                    <Input
                                        id="username"
                                        value={data.username}
                                        onChange={e => setData('username', e.target.value)}
                                        placeholder="Your SMTP username"
                                    />
                                    {errors.username && <p className="text-xs text-destructive">{errors.username}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="password">Password <span className="text-destructive">*</span></Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        value={data.password}
                                        onChange={e => setData('password', e.target.value)}
                                        placeholder={smtp?.password ? 'Leave blank to keep current password' : 'Your SMTP password'}
                                    />
                                    {errors.password && <p className="text-xs text-destructive">{errors.password}</p>}
                                </div>
                            </div>

                            <div className="space-y-2 md:w-1/2">
                                <Label htmlFor="encryption">Encryption</Label>
                                <select
                                    id="encryption"
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    value={data.encryption}
                                    onChange={e => setData('encryption', e.target.value)}
                                >
                                    <option value="tls">TLS</option>
                                    <option value="ssl">SSL</option>
                                    <option value="">None</option>
                                </select>
                                {errors.encryption && <p className="text-xs text-destructive">{errors.encryption}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-4">
                        <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                            <Save className="mr-2 h-4 w-4" /> Save Settings
                        </Button>
                    </div>
                </form>
            </div>
        </ERPLayout>
    );
}
