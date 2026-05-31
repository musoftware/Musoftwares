import React from 'react';
import { Head, router } from '@inertiajs/react';
import CrmLayout from '@/Layouts/CrmLayout';
import { __ } from '@/lib/i18n';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/Components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Settings as SettingsIcon } from 'lucide-react';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';

export default function SettingsIndex({ api_token, webhook_url }) {
    return (
        <CrmLayout title={__('CRM Settings')} activeMenu="settings">
            <ModulePageHeader 
                title={__('CRM Settings & Integrations')}
                description={__('Manage your workspace settings and integrations.')}
                icon={SettingsIcon}
                module="CRM"
            />
            <div className="flex-1 space-y-4 px-8 pb-8">
                <Tabs defaultValue="integrations" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="general">{__('General Settings')}</TabsTrigger>
                        <TabsTrigger value="integrations">{__('Integrations & API')}</TabsTrigger>
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

                </Tabs>
            </div>
        </CrmLayout>
    );
}
