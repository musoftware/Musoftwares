import React, { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { Play, Pause, List, CheckCircle, Clock, Plus, Upload, User, HardDrive } from 'lucide-react';
import { useRuntimeWS } from '@/hooks/useRuntimeWS';
import { RuntimePluginModals } from '@/Components/Tools/RuntimePluginModals';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';

export default function Runner({ tool, userPlan }: { tool: any; userPlan: any }) {
    const { connected: isConnected, callRPC: sendRpc, installingPlugin, loginRequired, setLoginRequired } = useRuntimeWS('facebook-publisher');
    const [activeTab, setActiveTab] = useState('queue');
    const [accounts, setAccounts] = useState<any[]>([]);
    const [queues, setQueues] = useState<any[]>([]);

    useEffect(() => {
        if (isConnected) {
            refreshData();
        }
    }, [isConnected]);

    const refreshData = async () => {
        try {
            const accRes = await sendRpc('get_accounts', {}) as { accounts: any[] };
            setAccounts(accRes.accounts || []);
            
            const qRes = await sendRpc('get_queues', {}) as { queues: any[] };
            setQueues(qRes.queues || []);
        } catch (e) {
            console.error(e);
        }
    };

    const handleAddAccount = async () => {
        const name = prompt("Enter Account Name (Profile):");
        if (name) {
            await sendRpc('add_account', { name, profile_path: `Profile_${Date.now()}` });
            refreshData();
        }
    };

    const handleAddJob = async () => {
        const pageId = prompt("Enter Page ID:");
        const action = prompt("Enter Action (Reel, Story, VideoPost):", "Reel");
        const filePath = prompt("Enter File Path to Video/Image:");

        if (pageId && action && filePath) {
            await sendRpc('add_queue', { page_id: pageId, action_type: action, file_path: filePath });
            refreshData();
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={tool.title} />
            <RuntimePluginModals 
                installingPlugin={installingPlugin} 
                loginRequired={loginRequired} 
                setLoginRequired={setLoginRequired} 
            />

            <div className="flex h-[calc(100vh-4rem)]">
                {/* Sidebar */}
                <div className="w-64 border-r bg-muted/30 p-4 space-y-4">
                    <div className="flex items-center space-x-3 mb-6">
                        <div className="h-10 w-10 bg-primary/10 rounded-xl flex items-center justify-center">
                            <Upload className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h2 className="font-semibold text-sm">Auto Publisher</h2>
                            <p className="text-xs text-muted-foreground">Background Engine</p>
                        </div>
                    </div>

                    <div className="space-y-1">
                        <Button 
                            variant={activeTab === 'queue' ? 'secondary' : 'ghost'} 
                            className="w-full justify-start"
                            onClick={() => setActiveTab('queue')}
                        >
                            <List className="mr-2 h-4 w-4" />
                            Publishing Queue
                        </Button>
                        <Button 
                            variant={activeTab === 'accounts' ? 'secondary' : 'ghost'} 
                            className="w-full justify-start"
                            onClick={() => setActiveTab('accounts')}
                        >
                            <User className="mr-2 h-4 w-4" />
                            Accounts & Pages
                        </Button>
                        <Button 
                            variant={activeTab === 'media' ? 'secondary' : 'ghost'} 
                            className="w-full justify-start"
                            onClick={() => setActiveTab('media')}
                        >
                            <HardDrive className="mr-2 h-4 w-4" />
                            Local Media Library
                        </Button>
                    </div>

                    <div className="mt-8 pt-8 border-t">
                        <div className="rounded-lg bg-card p-3 shadow-sm border">
                            <div className="flex items-center space-x-2 text-sm font-medium mb-1">
                                <div className={`h-2 w-2 rounded-full ${isConnected ? 'bg-green-500' : 'bg-red-500'}`} />
                                <span>{isConnected ? 'Runtime Connected' : 'Disconnected'}</span>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Engine is {isConnected ? 'listening for jobs' : 'offline'}.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Main Workspace */}
                <div className="flex-1 overflow-auto bg-background p-8">
                    <div className="max-w-5xl mx-auto space-y-6">
                        
                        {activeTab === 'queue' && (
                            <div className="space-y-6">
                                <div className="flex justify-between items-center">
                                    <div>
                                        <h1 className="text-2xl font-bold tracking-tight">Publishing Queue</h1>
                                        <p className="text-muted-foreground">Monitor and manage background automation jobs.</p>
                                    </div>
                                    <Button onClick={handleAddJob} disabled={!isConnected}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Queue New Post
                                    </Button>
                                </div>

                                <Card>
                                    <CardContent className="p-0">
                                        <div className="border rounded-md">
                                            <table className="w-full text-sm">
                                                <thead>
                                                    <tr className="border-b bg-muted/50 text-left">
                                                        <th className="font-medium p-4">ID</th>
                                                        <th className="font-medium p-4">Page ID</th>
                                                        <th className="font-medium p-4">Type</th>
                                                        <th className="font-medium p-4">File</th>
                                                        <th className="font-medium p-4">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {queues.length === 0 ? (
                                                        <tr>
                                                            <td colSpan={5} className="p-8 text-center text-muted-foreground">
                                                                No publishing jobs in queue.
                                                            </td>
                                                        </tr>
                                                    ) : (
                                                        queues.map(q => (
                                                            <tr key={q.id} className="border-b last:border-0">
                                                                <td className="p-4">#{q.id}</td>
                                                                <td className="p-4">{q.page_id}</td>
                                                                <td className="p-4">{q.action_type}</td>
                                                                <td className="p-4 text-muted-foreground truncate max-w-[200px]" title={q.file_path}>
                                                                    {q.file_path}
                                                                </td>
                                                                <td className="p-4">
                                                                    <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                                                                        q.status === 'completed' ? 'bg-green-100 text-green-700' :
                                                                        q.status === 'processing' ? 'bg-blue-100 text-blue-700' :
                                                                        q.status === 'failed' ? 'bg-red-100 text-red-700' :
                                                                        'bg-yellow-100 text-yellow-700'
                                                                    }`}>
                                                                        {q.status}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        ))
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        )}

                        {activeTab === 'accounts' && (
                            <div className="space-y-6">
                                <div className="flex justify-between items-center">
                                    <div>
                                        <h1 className="text-2xl font-bold tracking-tight">Facebook Accounts</h1>
                                        <p className="text-muted-foreground">Manage connected profiles and isolated browser sessions.</p>
                                    </div>
                                    <Button onClick={handleAddAccount} disabled={!isConnected}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Add Account
                                    </Button>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {accounts.length === 0 ? (
                                        <div className="col-span-full p-8 text-center border rounded-lg bg-muted/10 text-muted-foreground">
                                            No accounts configured yet.
                                        </div>
                                    ) : (
                                        accounts.map(acc => (
                                            <Card key={acc.id}>
                                                <CardHeader className="pb-2">
                                                    <CardTitle className="text-base">{acc.name}</CardTitle>
                                                    <CardDescription>ID: {acc.id}</CardDescription>
                                                </CardHeader>
                                                <CardContent>
                                                    <div className="text-xs text-muted-foreground mb-4">
                                                        Profile: {acc.profile_path}
                                                    </div>
                                                    <Button variant="outline" size="sm" className="w-full">
                                                        Manage Pages
                                                    </Button>
                                                </CardContent>
                                            </Card>
                                        ))
                                    )}
                                </div>
                            </div>
                        )}

                        {activeTab === 'media' && (
                            <div className="space-y-6">
                                <div>
                                    <h1 className="text-2xl font-bold tracking-tight">Local Media Library</h1>
                                    <p className="text-muted-foreground">Manage files that the local engine will use to publish.</p>
                                </div>
                                <div className="border border-dashed rounded-lg p-12 text-center text-muted-foreground">
                                    <HardDrive className="h-10 w-10 mx-auto mb-4 opacity-50" />
                                    <p>Media Library integration coming soon.</p>
                                    <p className="text-sm mt-1">For now, provide absolute paths when queueing posts.</p>
                                </div>
                            </div>
                        )}

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
