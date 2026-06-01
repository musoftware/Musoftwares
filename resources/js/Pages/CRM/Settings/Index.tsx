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
        <CrmLayout title={__('general.crm_settings')} activeMenu="settings">
            <ModulePageHeader 
                title={__('general.crm_settings_integrations')}
                description={__('general.manage_your_workspace_settings_and_integrations')}
                icon={SettingsIcon}
                module="CRM"
            />
            <div className="flex-1 space-y-4 px-8 pb-8">
                <Tabs defaultValue="integrations" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="general">{__('general.general_settings')}</TabsTrigger>
                        <TabsTrigger value="integrations">{__('general.integrations_api')}</TabsTrigger>
                    </TabsList>

                    <TabsContent value="general" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>{__('general.general_settings')}</CardTitle>
                                <CardDescription>{__('general.basic_configuration_for_your_crm_workspace')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label>{__('general.workspace_name')}</Label>
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
                                <CardTitle>{__('general.api_access_webhooks')}</CardTitle>
                                <CardDescription>{__('general.connect_your_crm_with_zapier_make_or_custom_apps')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label>{__('general.api_token')}</Label>
                                    <div className="flex gap-2">
                                        <Input readOnly value={api_token} className="font-mono text-sm" />
                                        <Button variant="outline" onClick={() => navigator.clipboard.writeText(api_token)}>
                                            {__('Copy')}
                                        </Button>
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label>{__('general.incoming_webhook_url')}</Label>
                                    <div className="flex gap-2">
                                        <Input readOnly value={webhook_url} className="font-mono text-sm" />
                                        <Button variant="outline" onClick={() => navigator.clipboard.writeText(webhook_url)}>
                                            {__('Copy')}
                                        </Button>
                                    </div>
                                    <p className="text-xs text-slate-500 mt-1">{__('general.send_post_requests_to_this_url_to_create_leads')}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                </Tabs>
            </div>
        </CrmLayout>
    );
}
