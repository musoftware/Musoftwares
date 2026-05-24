import React, { Suspense, lazy, useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import { runtimeSDK } from '@/lib/runtime-sdk';
import { RuntimeStatusBanner } from '@/Components/Tools/RuntimeStatusBanner';
import {
    Wifi, Shield, Copy, Check, Terminal, ArrowRight,
    Sparkles, Laptop, Smartphone, AlertCircle, RefreshCw, X, Link as LinkIcon, Settings
} from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Label } from '@/Components/ui/label';

// Dynamic import for tool runner components based on the DB field `runner_component`
const components: Record<string, React.ElementType> = {
    'EmailSenderRunner':        lazy(() => import('./EmailSenderRunner')),
    'ScreenshotFeedbackRunner': lazy(() => import('./ScreenshotFeedbackRunner')),
    'WhatsAppSenderRunner':     lazy(() => import('./WhatsAppSenderRunner')),
    'whatsapp-sender-pro':      lazy(() => import('./WhatsAppSenderRunner')),
    'whatsapp-sender':          lazy(() => import('./WhatsAppSenderRunner')),
    'b2b-prospector':           lazy(() => import('./B2BProspectorRunner')),
    'viral-autopsy':            lazy(() => import('./ViralAutopsyRunner')),
    'hook-analyzer':            lazy(() => import('./HookAnalyzerRunner')),
    'format-extractor':         lazy(() => import('./FormatExtractorRunner')),
    'facebook-extractor':       lazy(() => import('./FacebookExtractorRunner')),
    'IPTVDownloaderRunner':     lazy(() => import('./IPTVDownloaderRunner')),
    'iptv-downloader':          lazy(() => import('./IPTVDownloaderRunner')),
    'opensooq':                 lazy(() => import('./OpensooqRunner')),
    'google-maps':              lazy(() => import('./GoogleMapsRunner')),
    'SnapDownloaderRunner':     lazy(() => import('./SnapDownloaderRunner')),
    'tiktok-intelligence':      lazy(() => import('./TikTokIntelligenceRunner')),
    'competitor-tracker':       lazy(() => import('./CompetitorTrackerRunner')),
    'ad-library-monitor':       lazy(() => import('./AdLibraryMonitorRunner')),
    'ugc-creators-search':      lazy(() => import('./UgcCreatorsSearchRunner')),
    'swipe-vault':              lazy(() => import('./SwipeVaultRunner')),
    'DataFilterRunner':         lazy(() => import('./DataFilterRunner')),
    'ContentResearcherRunner':  lazy(() => import('./ContentResearcherRunner')),
    'FacebookPublisher/Runner': lazy(() => import('./FacebookPublisher/Runner')),
    'DomainIntelligenceRunner': lazy(() => import('./DomainIntelligenceRunner')),
    'DomainProspectorRunner':   lazy(() => import('./DomainProspectorRunner')),
    'HarajRunner':              lazy(() => import('./HarajRunner')),
    'WaAiAgentRunner':          lazy(() => import('./WaAiAgentRunner')),
    'WaFunnelEngineRunner':     lazy(() => import('./WaFunnelEngineRunner')),
    'WaWarmupRunner':           lazy(() => import('./WaWarmupRunner')),
};

function SetupWizard({ currentHost, onLinked }: { currentHost: string; onLinked: (ip: string) => void }) {
    const [ipInput, setIpInput] = useState(currentHost === '127.0.0.1' ? '' : currentHost);
    const [testing, setTesting] = useState(false);
    const [copied, setCopied] = useState(false);
    const [errorMsg, setErrorMsg] = useState<string | null>(null);
    const [success, setSuccess] = useState(false);

    const fwCommand = 'New-NetFirewallRule -DisplayName "Musoftware Runtime" -Direction Inbound -LocalPort 18400,18401 -Protocol TCP -Action Allow';

    const handleCopy = () => {
        navigator.clipboard.writeText(fwCommand);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const handleLink = async (e: React.FormEvent) => {
        e.preventDefault();
        setErrorMsg(null);
        setTesting(true);

        const cleanHost = ipInput.trim().replace(/^https?:\/\//i, '').split(':')[0];
        if (!cleanHost) {
            setErrorMsg('Please enter a valid IP address.');
            setTesting(false);
            return;
        }

        runtimeSDK.setHost(cleanHost);
        const ok = await runtimeSDK.ping();

        if (ok) {
            setSuccess(true);
            setTimeout(() => {
                onLinked(cleanHost);
            }, 1000);
        } else {
            setErrorMsg(`Could not connect to Musoftware Runtime at ${cleanHost}. Ensure both devices are on the same Wi-Fi, the firewall rule is open, and the desktop runtime is active.`);
            setTesting(false);
        }
    };

    return (
        <div className="min-h-screen bg-background text-foreground flex flex-col items-center justify-center p-4 md:p-8 font-sans relative overflow-hidden">
            {/* Background elements */}
            <div className="absolute top-[-20%] left-[-20%] w-[60%] h-[60%] rounded-full bg-primary/5 blur-[120px] pointer-events-none" />
            <div className="absolute bottom-[-20%] right-[-20%] w-[60%] h-[60%] rounded-full bg-emerald-500/5 blur-[120px] pointer-events-none" />

            <Card className="w-full max-w-lg shadow-xl relative z-10 border-muted">
                <CardHeader className="text-center space-y-3 pb-6 border-b border-border/50">
                    <div className="flex justify-center">
                        <Badge variant="secondary" className="gap-1.5 font-semibold">
                            <Smartphone className="w-3.5 h-3.5 animate-pulse text-primary" />
                            Remote Control Mode
                        </Badge>
                    </div>
                    <CardTitle className="text-2xl md:text-3xl font-black tracking-tight">
                        Link to Windows PC
                    </CardTitle>
                    <CardDescription className="text-xs md:text-sm max-w-sm mx-auto leading-relaxed">
                        Android devices cannot run local code tools directly. Link this device securely to the Musoftware Runtime running on your PC.
                    </CardDescription>
                </CardHeader>

                <CardContent className="space-y-8 pt-6">
                    {/* Device Sync Visualizer */}
                    <div className="bg-secondary/30 border border-border rounded-2xl p-5 flex items-center justify-around relative overflow-hidden">
                        <div className="flex flex-col items-center gap-1.5 z-10">
                            <div className="w-12 h-12 rounded-2xl bg-background border flex items-center justify-center text-primary shadow-sm">
                                <Laptop className="w-6 h-6" />
                            </div>
                            <span className="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Windows PC</span>
                        </div>

                        {/* Linking indicator */}
                        <div className="flex-1 max-w-[120px] flex flex-col items-center justify-center px-4 relative">
                            <div className="w-full h-0.5 bg-border relative overflow-hidden">
                                {testing ? (
                                    <div className="absolute inset-0 bg-primary animate-pulse" />
                                ) : success ? (
                                    <div className="absolute inset-0 bg-emerald-500" />
                                ) : (
                                    <div className="absolute inset-0 bg-primary/30 animate-pulse" />
                                )}
                            </div>
                            <span className="text-[9px] font-extrabold text-muted-foreground uppercase tracking-widest mt-2 block">
                                {testing ? 'Verifying...' : success ? 'Linked' : 'Wi-Fi Link'}
                            </span>
                        </div>

                        <div className="flex flex-col items-center gap-1.5 z-10">
                            <div className="w-12 h-12 rounded-2xl bg-background border flex items-center justify-center text-emerald-500 shadow-sm">
                                <Smartphone className="w-6 h-6" />
                            </div>
                            <span className="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Android App</span>
                        </div>
                    </div>

                    {/* Steps Section */}
                    <div className="space-y-6">
                        <h2 className="text-xs font-extrabold uppercase tracking-widest text-muted-foreground">Setup Walkthrough</h2>
                        
                        <div className="space-y-5">
                            {/* Step 1 */}
                            <div className="flex items-start gap-4">
                                <div className="w-6 h-6 rounded-lg bg-secondary flex items-center justify-center text-xs font-bold text-foreground shrink-0 border">
                                    1
                                </div>
                                <div className="space-y-1">
                                    <h3 className="text-sm font-bold">Connect to Same Wi-Fi</h3>
                                    <p className="text-xs text-muted-foreground leading-relaxed">
                                        Your PC and Android device must reside on the exact same local wireless or Ethernet network.
                                    </p>
                                </div>
                            </div>

                            {/* Step 2 */}
                            <div className="flex items-start gap-4">
                                <div className="w-6 h-6 rounded-lg bg-secondary flex items-center justify-center text-xs font-bold text-foreground shrink-0 border">
                                    2
                                </div>
                                <div className="space-y-2.5 w-full min-w-0">
                                    <h3 className="text-sm font-bold">Open Windows Firewall Exception</h3>
                                    <p className="text-xs text-muted-foreground leading-relaxed">
                                        Windows blocks incoming local traffic by default. Run **PowerShell as Administrator** on your PC and paste the following exception rule:
                                    </p>
                                    
                                    <div className="bg-muted/50 rounded-xl p-3.5 border font-mono text-[10px] relative group overflow-hidden flex items-center">
                                        <div className="overflow-x-auto whitespace-pre pr-8 select-all flex-1">
                                            {fwCommand}
                                        </div>
                                        <Button 
                                            variant="ghost"
                                            size="icon"
                                            onClick={handleCopy}
                                            className="absolute right-2 top-1/2 -translate-y-1/2 h-7 w-7 bg-background/80 hover:bg-background shadow-sm"
                                            title="Copy PowerShell command"
                                        >
                                            {copied ? <Check className="w-3.5 h-3.5 text-emerald-500" /> : <Copy className="w-3.5 h-3.5" />}
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            {/* Step 3 */}
                            <div className="flex items-start gap-4">
                                <div className="w-6 h-6 rounded-lg bg-secondary flex items-center justify-center text-xs font-bold text-foreground shrink-0 border">
                                    3
                                </div>
                                <div className="space-y-3 w-full">
                                    <h3 className="text-sm font-bold">Enter Windows Host IP</h3>
                                    <p className="text-xs text-muted-foreground leading-relaxed">
                                        Type the local IP address of your Windows PC. The `musoftware-runtime` printed this address in your command line on startup.
                                    </p>
                                    
                                    <form onSubmit={handleLink} className="space-y-3">
                                        <div className="flex gap-2">
                                            <div className="relative flex-1">
                                                <Wifi className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                                <Input 
                                                    type="text" 
                                                    placeholder="e.g. 192.168.1.15" 
                                                    value={ipInput}
                                                    onChange={e => setIpInput(e.target.value)}
                                                    disabled={testing || success}
                                                    className="pl-10 font-mono"
                                                />
                                            </div>
                                            
                                            <Button 
                                                type="submit"
                                                disabled={testing || success || !ipInput.trim()}
                                                className="shrink-0 gap-1.5 uppercase text-[10px] tracking-wider font-black"
                                            >
                                                {testing ? <RefreshCw className="w-3.5 h-3.5 animate-spin" /> : <LinkIcon className="w-3.5 h-3.5" />}
                                                <span>Link PC</span>
                                            </Button>
                                        </div>
                                    </form>

                                    {/* Success message */}
                                    {success && (
                                        <div className="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-2 animate-in fade-in duration-300">
                                            <Check className="w-4 h-4 shrink-0" />
                                            <span className="font-semibold">Linked Successfully! Launching runner...</span>
                                        </div>
                                    )}

                                    {/* Error Message */}
                                    {errorMsg && (
                                        <div className="p-3.5 bg-destructive/10 border border-destructive/20 rounded-xl text-xs text-destructive flex items-start gap-2.5 animate-in fade-in duration-300">
                                            <AlertCircle className="w-4.5 h-4.5 shrink-0 mt-0.5" />
                                            <div className="space-y-1">
                                                <p className="font-bold">Connection Verification Failed</p>
                                                <p className="text-[11px] opacity-95 leading-relaxed">{errorMsg}</p>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

function FloatingBadge({ host, onDisconnect }: { host: string; onDisconnect: () => void }) {
    const [isOpen, setIsOpen] = useState(false);
    const [ipInput, setIpInput] = useState(host);
    const [testing, setTesting] = useState(false);
    const [errorMsg, setErrorMsg] = useState<string | null>(null);
    const [success, setSuccess] = useState(false);

    const handleSave = async (e: React.FormEvent) => {
        e.preventDefault();
        setErrorMsg(null);
        setTesting(true);

        const cleanHost = ipInput.trim().replace(/^https?:\/\//i, '').split(':')[0];
        if (!cleanHost) {
            setErrorMsg('Please enter a valid IP address.');
            setTesting(false);
            return;
        }

        runtimeSDK.setHost(cleanHost);
        const ok = await runtimeSDK.ping();

        if (ok) {
            setSuccess(true);
            setTimeout(() => {
                setIsOpen(false);
                window.location.reload();
            }, 1000);
        } else {
            setErrorMsg(`Could not reach PC at ${cleanHost}`);
            setTesting(false);
        }
    };

    return (
        <>
            {/* Floating Indicator */}
            <div className="fixed top-3 right-3 z-[9999] animate-in fade-in duration-300">
                <Button
                    variant="outline"
                    onClick={() => setIsOpen(true)}
                    className="flex items-center gap-2 rounded-full shadow-lg text-xs font-bold bg-background/90 backdrop-blur-md"
                >
                    <span className="relative flex h-2 w-2">
                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span className="text-[10px] tracking-wide text-muted-foreground">Linked PC: <span className="font-mono text-emerald-600 dark:text-emerald-400">{host}</span></span>
                    <Settings className="w-3.5 h-3.5 text-muted-foreground ml-0.5" />
                </Button>
            </div>

            {/* Glassmorphic Settings Dialog Overlay */}
            {isOpen && (
                <div className="fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-background/80 backdrop-blur-sm animate-in fade-in duration-200">
                    <Card className="w-full max-w-sm shadow-2xl relative">
                        <Button 
                            variant="ghost" 
                            size="icon"
                            onClick={() => setIsOpen(false)}
                            className="absolute top-2 right-2 h-8 w-8"
                        >
                            <X className="w-4 h-4" />
                        </Button>

                        <CardHeader className="pb-4">
                            <CardTitle className="text-sm flex items-center gap-1.5">
                                <Laptop className="w-4 h-4 text-primary" />
                                <span>Linked PC Status</span>
                            </CardTitle>
                            <CardDescription className="text-xs">
                                Update the connection details or unlink this Android controller.
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <form onSubmit={handleSave} className="space-y-4">
                                <div className="space-y-2">
                                    <Label className="text-[10px] font-bold uppercase tracking-wider">PC Local IP Address</Label>
                                    <div className="relative">
                                        <Wifi className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                        <Input 
                                            type="text" 
                                            placeholder="e.g. 192.168.1.15" 
                                            value={ipInput}
                                            onChange={e => setIpInput(e.target.value)}
                                            disabled={testing || success}
                                            className="pl-9 font-mono"
                                        />
                                    </div>
                                </div>

                                {success && (
                                    <p className="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 animate-pulse text-center">Connection Verified! Reloading...</p>
                                )}

                                {errorMsg && (
                                    <p className="text-[11px] font-bold text-destructive text-center">{errorMsg}</p>
                                )}

                                <div className="flex gap-2.5 pt-2 border-t">
                                    <Button 
                                        type="button"
                                        variant="destructive"
                                        onClick={onDisconnect}
                                        className="flex-1 text-xs font-bold"
                                    >
                                        Disconnect PC
                                    </Button>
                                    
                                    <Button 
                                        type="submit"
                                        disabled={testing || success || !ipInput.trim()}
                                        className="flex-1 text-xs font-bold gap-1"
                                    >
                                        {testing && <RefreshCw className="w-3 h-3 animate-spin" />}
                                        <span>Update IP</span>
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            )}
        </>
    );
}

export default function Runner({ tool, subscription, runtimePort, pluginSlug }: any) {
    const runnerKey = tool.runner_component?.trim();
    const Component = components[runnerKey];
    console.log("Runner Debug:", { runner_component: tool.runner_component, runnerKey, keys: Object.keys(components) });
    const [isMobile, setIsMobile] = useState(false);
    const [status, setStatus] = useState<'checking' | 'online' | 'offline'>('checking');
    const [currentHost, setCurrentHost] = useState('127.0.0.1');

    const checkConnectivity = async () => {
        setStatus('checking');
        const host = typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
        
        const isLocalDeveloper = typeof window !== 'undefined' && (window.location.hostname === '127.0.0.1' || window.location.hostname === 'localhost');
        if (host === '127.0.0.1' && !isLocalDeveloper) {
            setStatus('offline');
            return;
        }
        
        const ok = await runtimeSDK.ping();
        if (ok) {
            setStatus('online');
        } else {
            setStatus('offline');
        }
    };

    useEffect(() => {
        if (typeof window !== 'undefined') {
            const mobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            setIsMobile(mobile);
            const host = window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1';
            setCurrentHost(host);
            
            if (mobile) {
                checkConnectivity();
            } else {
                setStatus('online');
            }
        }
    }, []);

    const handleLinked = (ip: string) => {
        setCurrentHost(ip);
        setStatus('online');
        window.location.reload();
    };

    const handleDisconnect = () => {
        runtimeSDK.setHost('127.0.0.1');
        setCurrentHost('127.0.0.1');
        setStatus('offline');
        window.location.reload();
    };

    if (!Component) {
        return (
            <div className="min-h-screen bg-background flex items-center justify-center p-8 font-sans">
                <div className="text-center max-w-md">
                    <h1 className="text-2xl font-bold text-foreground mb-2">Component Not Found</h1>
                    <p className="text-muted-foreground">The interface for {tool.title} could not be loaded because the component "{tool.runner_component}" does not exist.</p>
                </div>
            </div>
        );
    }

    if (isMobile && status === 'checking') {
        return (
            <div className="min-h-screen bg-background flex items-center justify-center text-foreground">
                <div className="text-center space-y-4">
                    <div className="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin mx-auto" />
                    <p className="text-xs font-bold text-muted-foreground uppercase tracking-widest animate-pulse">Checking Local PC Connection...</p>
                </div>
            </div>
        );
    }

    if (isMobile && status === 'offline') {
        return (
            <>
                <Head title={`Link PC to Control — ${tool.title}`} />
                <SetupWizard currentHost={currentHost} onLinked={handleLinked} />
            </>
        );
    }

    return (
        <div className="flex flex-col min-h-screen w-full bg-background text-foreground">
            <Head title={tool.title} />
            
            <RuntimeStatusBanner toolSlug={pluginSlug} />
            
            {isMobile && status === 'online' && (
                <FloatingBadge host={currentHost} onDisconnect={handleDisconnect} />
            )}

            <div className="flex-1 w-full">
                <Suspense fallback={
                    <div className="min-h-screen bg-background flex items-center justify-center">
                        <div className="w-8 h-8 border-2 border-foreground border-t-transparent rounded-full animate-spin" />
                    </div>
                }>
                    <Component tool={tool} subscription={subscription} runtimePort={runtimePort} pluginSlug={pluginSlug} />
                </Suspense>
            </div>
        </div>
    );
}
