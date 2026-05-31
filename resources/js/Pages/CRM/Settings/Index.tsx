import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import CrmLayout from '@/Layouts/CrmLayout';
import { __ } from '@/lib/i18n';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/Components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Smartphone, Zap, Settings as SettingsIcon, Plus, QrCode, PowerOff } from 'lucide-react';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import axios from 'axios';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';

export default function SettingsIndex({ whatsapp_accounts = [], api_token, webhook_url }) {
    const [isAddingWa, setIsAddingWa] = useState(false);
    const [waName, setWaName] = useState('');
    const [qrCode, setQrCode] = useState(null);
    const [isConnecting, setIsConnecting] = useState(false);

    const handleAddWhatsApp = async (e) => {
        e.preventDefault();
        try {
            const res = await axios.post(route('crm.whatsapp.accounts.store'), { name: waName });
            setIsAddingWa(false);
            setWaName('');
            router.reload();
        } catch (error) {
            alert(__('crm.error_adding_whatsapp_account'));
        }
    };

    const handleConnect = async (accountId) => {
        setIsConnecting(true);
        try {
            await axios.post(route('crm.whatsapp.accounts.connect', accountId));
            const qrRes = await axios.get(route('crm.whatsapp.accounts.qr', accountId));
            if (qrRes.data.qr_code) {
                setQrCode(qrRes.data.qr_code);
            }
        } catch (error) {
            alert(__('crm.error_connecting_whatsapp'));
        } finally {
            setIsConnecting(false);
        }
    };

    const handleDisconnect = async (accountId) => {
        if (!confirm(__('crm.confirm_disconnect_whatsapp'))) return;
        try {
            await axios.post(route('crm.whatsapp.accounts.disconnect', accountId));
            router.reload();
        } catch (error) {
            alert(__('crm.error_disconnecting_whatsapp'));
        }
    };

    const handleDelete = async (accountId) => {
        if (!confirm(__('crm.confirm_delete_whatsapp'))) return;
        try {
            await axios.delete(route('crm.whatsapp.accounts.destroy', accountId));
            router.reload();
        } catch (error) {
            alert(__('crm.error_deleting_whatsapp'));
        }
    };

    return (
        <CrmLayout title={__('CRM Settings')} activeMenu="settings">
            <ModulePageHeader 
                title={__('CRM Settings & Integrations')}
                description={__('Manage your workspace settings, integrations, and WhatsApp connections.')}
                icon={SettingsIcon}
                module="CRM"
            />
            <div className="flex-1 space-y-4 px-8 pb-8">
                <Tabs defaultValue="integrations" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="general">{__('General Settings')}</TabsTrigger>
                        <TabsTrigger value="integrations">{__('Integrations & API')}</TabsTrigger>
                        <TabsTrigger value="whatsapp">{__('WhatsApp Accounts')}</TabsTrigger>
                    </TabsList>

                    <TabsContent value="general" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>{__('General Settings')}</CardTitle>
                                <CardDescription>{__('Basic configuration for your CRM workspace.')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label>{__('Workspace Name')}</Label>
                                    <Input defaultValue="CRM Workspace" disabled />
                                </div>
                            </CardContent>
                            <CardFooter>
                                <Button>{__('Save Changes')}</Button>
                            </CardFooter>
                        </Card>
                    </TabsContent>

                    <TabsContent value="integrations" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>{__('API Access & Webhooks')}</CardTitle>
                                <CardDescription>{__('Connect your CRM with Zapier, Make, or custom apps.')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label>{__('API Token')}</Label>
                                    <div className="flex gap-2">
                                        <Input readOnly value={api_token} className="font-mono text-sm" />
                                        <Button variant="outline" onClick={() => navigator.clipboard.writeText(api_token)}>
                                            {__('Copy')}
                                        </Button>
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label>{__('Incoming Webhook URL')}</Label>
                                    <div className="flex gap-2">
                                        <Input readOnly value={webhook_url} className="font-mono text-sm" />
                                        <Button variant="outline" onClick={() => navigator.clipboard.writeText(webhook_url)}>
                                            {__('Copy')}
                                        </Button>
                                    </div>
                                    <p className="text-xs text-slate-500 mt-1">{__('Send POST requests to this URL to create leads.')}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="whatsapp" className="space-y-4">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0">
                                <div>
                                    <CardTitle>{__('WhatsApp Business Accounts')}</CardTitle>
                                    <CardDescription>{__('Connect WhatsApp accounts to use the Inbox and Broadcast Campaigns.')}</CardDescription>
                                </div>
                                <Dialog open={isAddingWa} onOpenChange={setIsAddingWa}>
                                    <DialogTrigger asChild>
                                        <Button><Plus className="w-4 h-4 mr-2" /> {__('Add Account')}</Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <form onSubmit={handleAddWhatsApp}>
                                            <DialogHeader>
                                                <DialogTitle>{__('Add WhatsApp Account')}</DialogTitle>
                                                <DialogDescription>{__('Give this account a name to identify it later.')}</DialogDescription>
                                            </DialogHeader>
                                            <div className="space-y-4 py-4">
                                                <div className="space-y-2">
                                                    <Label>{__('Account Name')}</Label>
                                                    <Input required value={waName} onChange={e => setWaName(e.target.value)} placeholder={__('e.g. Sales Support')} />
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <Button type="submit">{__('Add Account')}</Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                            </CardHeader>
                            <CardContent>
                                {whatsapp_accounts.length === 0 ? (
                                    <div className="text-center py-8">
                                        <Smartphone className="w-12 h-12 text-slate-300 mx-auto mb-3" />
                                        <h3 className="text-lg font-medium text-slate-900">{__('No WhatsApp Accounts')}</h3>
                                        <p className="text-slate-500">{__('Connect your first WhatsApp account to start sending messages.')}</p>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {whatsapp_accounts.map(account => (
                                            <div key={account.id} className="flex items-center justify-between p-4 border rounded-lg bg-slate-50">
                                                <div className="flex items-center gap-4">
                                                    <div className={`w-10 h-10 rounded-full flex items-center justify-center ${account.is_connected ? 'bg-green-100 text-green-600' : 'bg-slate-200 text-slate-500'}`}>
                                                        <Smartphone className="w-5 h-5" />
                                                    </div>
                                                    <div>
                                                        <h4 className="font-semibold text-slate-900">{account.name}</h4>
                                                        <p className="text-sm text-slate-500">
                                                            {account.is_connected ? <span className="text-green-600 font-medium">{__('Connected')}</span> : __('Disconnected')}
                                                            {account.phone_number && ` • ${account.phone_number}`}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    {!account.is_connected ? (
                                                        <Dialog>
                                                            <DialogTrigger asChild>
                                                                <Button variant="outline" size="sm" onClick={() => handleConnect(account.id)}>
                                                                    <QrCode className="w-4 h-4 mr-2" /> {__('Scan QR')}
                                                                </Button>
                                                            </DialogTrigger>
                                                            <DialogContent>
                                                                <DialogHeader>
                                                                    <DialogTitle>{__('Scan WhatsApp QR Code')}</DialogTitle>
                                                                    <DialogDescription>{__('Open WhatsApp on your phone, go to Linked Devices, and scan this code.')}</DialogDescription>
                                                                </DialogHeader>
                                                                <div className="flex justify-center p-6 bg-white rounded-lg">
                                                                    {isConnecting && !qrCode ? (
                                                                        <div className="animate-pulse flex items-center gap-2 text-indigo-600">
                                                                            {__('Generating QR Code...')}
                                                                        </div>
                                                                    ) : qrCode ? (
                                                                        <img src={qrCode} alt="WhatsApp QR Code" className="w-64 h-64 border p-2 rounded-lg" />
                                                                    ) : (
                                                                        <div className="text-slate-500">{__('Failed to load QR code')}</div>
                                                                    )}
                                                                </div>
                                                            </DialogContent>
                                                        </Dialog>
                                                    ) : (
                                                        <Button variant="outline" size="sm" className="text-orange-600 hover:text-orange-700 hover:bg-orange-50" onClick={() => handleDisconnect(account.id)}>
                                                            <PowerOff className="w-4 h-4 mr-2" /> {__('Disconnect')}
                                                        </Button>
                                                    )}
                                                    <Button variant="ghost" size="sm" className="text-red-600 hover:bg-red-50" onClick={() => handleDelete(account.id)}>
                                                        {__('Delete')}
                                                    </Button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </CrmLayout>
    );
}
