import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { CheckCircle, Copy, Download, ExternalLink, Smartphone, Settings } from 'lucide-react';
import { Input } from '@/Components/ui/input';
import { useState } from 'react';
import { toast } from 'sonner';

interface InstallProps {
    androidAppUrl: string;
    macrodroidUrl: string;
    macrodroidToken: string;
}

export default function Install({ androidAppUrl, macrodroidUrl, macrodroidToken }: InstallProps) {
    const [copied, setCopied] = useState(false);

    const copyToClipboard = () => {
        navigator.clipboard.writeText(macrodroidUrl);
        setCopied(true);
        toast.success('Copied to clipboard');
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <AuthenticatedLayout>
            <Head title={__('admin.install_app')} />

            <div className="container mx-auto p-4 md:p-6 lg:p-8 max-w-4xl space-y-6">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{__('admin.install_app')}</h1>
                        <p className="text-muted-foreground mt-1">
                            {__('admin.choose_installation_method')}
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={route('sms-payment-gateway.index')}>
                            {__('general.back_to_dashboard')}
                        </Link>
                    </Button>
                </div>

                <Tabs defaultValue="android" className="w-full">
                    <TabsList className="grid w-full grid-cols-2">
                        <TabsTrigger value="android" className="flex gap-2">
                            <Smartphone className="w-4 h-4" />
                            {__('admin.official_android_app')}
                        </TabsTrigger>
                        <TabsTrigger value="macrodroid" className="flex gap-2">
                            <Settings className="w-4 h-4" />
                            {__('admin.macrodroid_alternative')}
                        </TabsTrigger>
                    </TabsList>
                    
                    <TabsContent value="android" className="mt-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>{__('admin.official_android_app')}</CardTitle>
                                <CardDescription>
                                    {__('admin.official_android_app_desc')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="aspect-video w-full rounded-xl overflow-hidden border bg-muted shadow-sm">
                                    <iframe 
                                        width="100%" 
                                        height="100%" 
                                        src="https://www.youtube.com/embed/TfwTupWceeo" 
                                        title={__('admin.installation_video')} 
                                        frameBorder="0" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowFullScreen
                                    ></iframe>
                                </div>

                                <div className="p-6 bg-primary/5 rounded-xl border flex flex-col items-center text-center space-y-4">
                                    <div className="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center">
                                        <Download className="w-8 h-8 text-primary" />
                                    </div>
                                    <div>
                                        <h3 className="font-semibold text-lg">{__('admin.download_apk')}</h3>
                                        <p className="text-muted-foreground text-sm max-w-md mx-auto mt-1">
                                            {__('admin.download_apk_desc')}
                                        </p>
                                    </div>
                                    <Button asChild size="lg" className="mt-2">
                                        <a href={androidAppUrl} target="_blank" rel="noopener noreferrer">
                                            <Download className="w-4 h-4 mr-2" />
                                            {__('admin.download')}
                                        </a>
                                    </Button>
                                </div>

                                <div className="space-y-4">
                                    <h3 className="font-semibold">{__('admin.installation_steps')}</h3>
                                    <ol className="list-decimal list-inside space-y-2 text-sm text-muted-foreground">
                                        <li>{__('admin.step_download_apk')}</li>
                                        <li>{__('admin.step_allow_unknown_sources')}</li>
                                        <li>{__('admin.step_install_apk')}</li>
                                        <li>{__('admin.step_grant_permissions')}</li>
                                        <li>{__('admin.step_scan_qr')}</li>
                                    </ol>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="macrodroid" className="mt-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>{__('admin.macrodroid_alternative')}</CardTitle>
                                <CardDescription>
                                    {__('admin.macrodroid_alternative_desc')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="space-y-2">
                                    <h3 className="text-sm font-medium">{__('admin.your_secure_webhook')}</h3>
                                    <div className="flex items-center gap-2">
                                        <Input readOnly value={macrodroidUrl} className="font-mono text-xs bg-muted/50" />
                                        <Button variant="outline" size="icon" onClick={copyToClipboard}>
                                            {copied ? <CheckCircle className="w-4 h-4 text-green-500" /> : <Copy className="w-4 h-4" />}
                                        </Button>
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {__('admin.webhook_secret_warning')}
                                    </p>
                                </div>

                                <div className="space-y-4">
                                    <h3 className="font-semibold">{__('admin.installation_steps')}</h3>
                                    <div className="space-y-6">
                                        <div className="flex gap-4">
                                            <div className="flex-none flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary font-bold">1</div>
                                            <div className="space-y-1 pt-1">
                                                <h4 className="font-medium text-sm">{__('admin.download_macrodroid')}</h4>
                                                <p className="text-sm text-muted-foreground">
                                                    {__('admin.download_macrodroid_desc')}
                                                </p>
                                                <Button variant="link" className="p-0 h-auto text-sm" asChild>
                                                    <a href="https://play.google.com/store/apps/details?id=com.arlosoft.macrodroid" target="_blank" rel="noopener noreferrer">
                                                        {__('admin.open_in_playstore')} <ExternalLink className="w-3 h-3 ml-1" />
                                                    </a>
                                                </Button>
                                            </div>
                                        </div>

                                        <div className="flex gap-4">
                                            <div className="flex-none flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary font-bold">2</div>
                                            <div className="space-y-1 pt-1">
                                                <h4 className="font-medium text-sm">{__('admin.create_macro_trigger')}</h4>
                                                <p className="text-sm text-muted-foreground">
                                                    {__('admin.create_macro_trigger_desc')}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex gap-4">
                                            <div className="flex-none flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary font-bold">3</div>
                                            <div className="space-y-1 pt-1">
                                                <h4 className="font-medium text-sm">{__('admin.create_macro_action')}</h4>
                                                <p className="text-sm text-muted-foreground">
                                                    {__('admin.create_macro_action_desc')}
                                                </p>
                                                <div className="bg-muted p-3 rounded-md text-xs font-mono space-y-1 mt-2">
                                                    <div><strong>URL:</strong> {macrodroidUrl}</div>
                                                    <div><strong>Method:</strong> POST</div>
                                                    <div><strong>Content-Type:</strong> application/x-www-form-urlencoded</div>
                                                </div>
                                                <div className="bg-muted p-3 rounded-md text-xs mt-2 space-y-2">
                                                    <p className="font-semibold">{__('admin.add_these_parameters')}:</p>
                                                    <div className="grid grid-cols-2 gap-2">
                                                        <div className="font-mono bg-background p-1.5 rounded border">sender</div>
                                                        <div className="font-mono bg-background p-1.5 rounded border">[sms_number]</div>
                                                        <div className="font-mono bg-background p-1.5 rounded border">message</div>
                                                        <div className="font-mono bg-background p-1.5 rounded border">[sms_message]</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div className="flex gap-4">
                                            <div className="flex-none flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary font-bold">4</div>
                                            <div className="space-y-1 pt-1">
                                                <h4 className="font-medium text-sm">{__('admin.save_and_test')}</h4>
                                                <p className="text-sm text-muted-foreground">
                                                    {__('admin.save_and_test_desc')}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AuthenticatedLayout>
    );
}
