import React, { Suspense, lazy, useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import { runtimeSDK } from '@/lib/runtime-sdk';
import {
    Wifi, Shield, Copy, Check, Terminal, ArrowRight,
    Sparkles, Laptop, Smartphone, AlertCircle, RefreshCw, X, Link as LinkIcon, Settings
} from 'lucide-react';

// Dynamic import for tool runner components based on the DB field `runner_component`
const components: Record<string, React.ElementType> = {
    'ScreenshotFeedbackRunner': lazy(() => import('./ScreenshotFeedbackRunner')),
    'WhatsAppSenderRunner':     lazy(() => import('./WhatsAppSenderRunner')),
    'whatsapp-sender-pro':      lazy(() => import('./WhatsAppSenderRunner')),
    'whatsapp-sender':          lazy(() => import('./WhatsAppSenderRunner')),
    'b2b-prospector':           lazy(() => import('./B2BProspectorRunner')),
    'viral-autopsy':            lazy(() => import('./ViralAutopsyRunner')),
    'hook-analyzer':            lazy(() => import('./HookAnalyzerRunner')),
    'format-extractor':         lazy(() => import('./FormatExtractorRunner')),
    'IPTVDownloaderRunner':     lazy(() => import('./IPTVDownloaderRunner')),
    'iptv-downloader':          lazy(() => import('./IPTVDownloaderRunner')),
    'opensooq':                 lazy(() => import('./OpensooqRunner')),
    'google-maps':              lazy(() => import('./GoogleMapsRunner')),
    'SnapDownloaderRunner':     lazy(() => import('./SnapDownloaderRunner')),
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
        <div className="min-h-screen bg-slate-900 text-slate-100 flex flex-col items-center justify-center p-4 md:p-8 font-sans relative overflow-hidden">
            {/* Background elements */}
            <div className="absolute top-[-20%] left-[-20%] w-[60%] h-[60%] rounded-full bg-indigo-600/10 blur-[120px] pointer-events-none" />
            <div className="absolute bottom-[-20%] right-[-20%] w-[60%] h-[60%] rounded-full bg-emerald-600/10 blur-[120px] pointer-events-none" />

            <div className="w-full max-w-lg bg-slate-850/40 backdrop-blur-xl border border-slate-700/50 rounded-3xl p-6 md:p-8 shadow-2xl space-y-8 relative z-10">
                
                {/* Header Section */}
                <div className="text-center space-y-3">
                    <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold">
                        <Smartphone className="w-3.5 h-3.5 animate-pulse" />
                        <span>Remote Control Mode</span>
                    </div>
                    
                    <h1 className="text-2xl md:text-3xl font-black bg-gradient-to-r from-white via-slate-100 to-slate-350 bg-clip-text text-transparent tracking-tight">
                        Link to Windows PC
                    </h1>
                    <p className="text-xs md:text-sm text-slate-400 max-w-sm mx-auto leading-relaxed">
                        Android devices cannot run local code tools directly. Link this device securely to the Musoftware Runtime running on your PC.
                    </p>
                </div>

                {/* Device Sync Visualizer */}
                <div className="bg-slate-950/40 border border-slate-800 rounded-2xl p-5 flex items-center justify-around relative overflow-hidden">
                    <div className="flex flex-col items-center gap-1.5 z-10">
                        <div className="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shadow-lg shadow-indigo-500/5">
                            <Laptop className="w-6 h-6" />
                        </div>
                        <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Windows PC</span>
                    </div>

                    {/* Linking indicator */}
                    <div className="flex-1 max-w-[120px] flex flex-col items-center justify-center px-4 relative">
                        <div className="w-full h-0.5 bg-slate-700 relative">
                            {testing ? (
                                <div className="absolute inset-0 bg-indigo-500 animate-pulse" />
                            ) : success ? (
                                <div className="absolute inset-0 bg-emerald-500" />
                            ) : (
                                <div className="absolute inset-0 bg-indigo-500/30 animate-pulse" />
                            )}
                        </div>
                        <span className="text-[9px] font-extrabold text-slate-500 uppercase tracking-widest mt-2 block">
                            {testing ? 'Verifying...' : success ? 'Linked' : 'Wi-Fi Link'}
                        </span>
                    </div>

                    <div className="flex flex-col items-center gap-1.5 z-10">
                        <div className="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shadow-lg shadow-emerald-500/5">
                            <Smartphone className="w-6 h-6" />
                        </div>
                        <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Android App</span>
                    </div>
                </div>

                {/* Steps Section */}
                <div className="space-y-6">
                    <h2 className="text-xs font-extrabold uppercase tracking-widest text-slate-400">Setup Walkthrough</h2>
                    
                    <div className="space-y-5">
                        {/* Step 1 */}
                        <div className="flex items-start gap-4">
                            <div className="w-6 h-6 rounded-lg bg-slate-800/80 flex items-center justify-center text-xs font-bold text-slate-300 shrink-0 border border-slate-750">
                                1
                            </div>
                            <div className="space-y-1">
                                <h3 className="text-sm font-bold text-slate-200">Connect to Same Wi-Fi</h3>
                                <p className="text-xs text-slate-400 leading-relaxed">
                                    Your PC and Android device must reside on the exact same local wireless or Ethernet network.
                                </p>
                            </div>
                        </div>

                        {/* Step 2 */}
                        <div className="flex items-start gap-4">
                            <div className="w-6 h-6 rounded-lg bg-slate-800/80 flex items-center justify-center text-xs font-bold text-slate-300 shrink-0 border border-slate-750">
                                2
                            </div>
                            <div className="space-y-2.5 w-full">
                                <h3 className="text-sm font-bold text-slate-200">Open Windows Firewall Exception</h3>
                                <p className="text-xs text-slate-400 leading-relaxed">
                                    Windows blocks incoming local traffic by default. Run **PowerShell as Administrator** on your PC and paste the following exception rule:
                                </p>
                                
                                <div className="bg-slate-950/90 rounded-xl p-3.5 border border-slate-800 font-mono text-[10px] text-indigo-300 relative group max-w-full overflow-hidden">
                                    <div className="overflow-x-auto whitespace-pre pr-8 select-all scrollbar-thin">
                                        {fwCommand}
                                    </div>
                                    <button 
                                        onClick={handleCopy}
                                        type="button"
                                        className="absolute right-2.5 top-2.5 p-1.5 rounded-lg bg-slate-850 hover:bg-slate-800 border border-slate-750 hover:border-slate-700 text-slate-400 hover:text-white transition-all active:scale-95"
                                        title="Copy PowerShell command"
                                    >
                                        {copied ? <Check className="w-3.5 h-3.5 text-emerald-500" /> : <Copy className="w-3.5 h-3.5" />}
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* Step 3 */}
                        <div className="flex items-start gap-4">
                            <div className="w-6 h-6 rounded-lg bg-slate-800/80 flex items-center justify-center text-xs font-bold text-slate-300 shrink-0 border border-slate-750">
                                3
                            </div>
                            <div className="space-y-3 w-full">
                                <h3 className="text-sm font-bold text-slate-200 font-sans">Enter Windows Host IP</h3>
                                <p className="text-xs text-slate-400 leading-relaxed font-sans">
                                    Type the local IP address of your Windows PC. The `musoftware-runtime` printed this address in your command line on startup.
                                </p>
                                
                                <form onSubmit={handleLink} className="space-y-3">
                                    <div className="flex gap-2">
                                        <div className="relative flex-1">
                                            <Wifi className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
                                            <input 
                                                type="text" 
                                                placeholder="e.g. 192.168.1.15" 
                                                value={ipInput}
                                                onChange={e => setIpInput(e.target.value)}
                                                disabled={testing || success}
                                                className="w-full bg-slate-950/60 border border-slate-750 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-10 pr-4 py-2.5 text-xs outline-none transition-all placeholder:text-slate-750 font-mono text-white"
                                            />
                                        </div>
                                        
                                        <button 
                                            type="submit"
                                            disabled={testing || success || !ipInput.trim()}
                                            className="px-4 bg-indigo-650 hover:bg-indigo-600 active:bg-indigo-700 disabled:bg-slate-800 text-white rounded-xl text-[10px] font-black uppercase tracking-wider hover:shadow-lg hover:shadow-indigo-600/20 active:scale-95 transition-all disabled:opacity-40 disabled:pointer-events-none flex items-center gap-1.5 shrink-0"
                                        >
                                            {testing ? <RefreshCw className="w-3.5 h-3.5 animate-spin" /> : <LinkIcon className="w-3.5 h-3.5" />}
                                            <span>Link PC</span>
                                        </button>
                                    </div>
                                </form>

                                {/* Success message */}
                                {success && (
                                    <div className="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-xs text-emerald-400 flex items-center gap-2 animate-in fade-in duration-300">
                                        <Check className="w-4 h-4 text-emerald-500 shrink-0" />
                                        <span className="font-semibold">Linked Successfully! Launching runner...</span>
                                    </div>
                                )}

                                {/* Error Message */}
                                {errorMsg && (
                                    <div className="p-3.5 bg-rose-500/10 border border-rose-500/20 rounded-xl text-xs text-rose-450 flex items-start gap-2.5 animate-in fade-in duration-300">
                                        <AlertCircle className="w-4.5 h-4.5 text-rose-500 shrink-0 mt-0.5" />
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

            </div>
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
                <button
                    onClick={() => setIsOpen(true)}
                    className="flex items-center gap-2 px-3.5 py-2 bg-slate-900/90 hover:bg-slate-950 backdrop-blur-md border border-slate-700/50 rounded-full shadow-lg hover:shadow-indigo-500/10 hover:border-slate-600 transition-all text-xs font-bold text-slate-100 group"
                >
                    <span className="relative flex h-2 w-2">
                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span className="text-[10px] tracking-wide text-slate-300">Linked PC: <span className="font-mono text-emerald-400 group-hover:text-emerald-300 transition-colors">{host}</span></span>
                    <Settings className="w-3.5 h-3.5 text-slate-500 group-hover:text-slate-300 transition-colors ml-0.5" />
                </button>
            </div>

            {/* Glassmorphic Settings Dialog Overlay */}
            {isOpen && (
                <div className="fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm animate-in fade-in duration-200">
                    <div className="w-full max-w-sm bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-2xl space-y-5 relative text-slate-200">
                        <button 
                            onClick={() => setIsOpen(false)}
                            className="absolute top-4 right-4 p-1 rounded-lg text-slate-500 hover:text-white hover:bg-slate-800 transition-all"
                        >
                            <X className="w-4 h-4" />
                        </button>

                        <div className="space-y-1">
                            <h3 className="font-extrabold text-slate-100 text-sm flex items-center gap-1.5">
                                <Laptop className="w-4 h-4 text-indigo-400" />
                                <span>Linked PC Status</span>
                            </h3>
                            <p className="text-xs text-slate-500">
                                Update the connection details or unlink this Android controller.
                            </p>
                        </div>

                        <form onSubmit={handleSave} className="space-y-4">
                            <div className="space-y-2">
                                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">PC Local IP Address</label>
                                <div className="relative">
                                    <Wifi className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-650" />
                                    <input 
                                        type="text" 
                                        placeholder="e.g. 192.168.1.15" 
                                        value={ipInput}
                                        onChange={e => setIpInput(e.target.value)}
                                        disabled={testing || success}
                                        className="w-full bg-slate-950/65 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-9 pr-4 py-2.5 text-xs outline-none transition-all font-mono text-white"
                                    />
                                </div>
                            </div>

                            {success && (
                                <p className="text-[11px] font-bold text-emerald-400 animate-pulse text-center">Connection Verified! Reloading...</p>
                            )}

                            {errorMsg && (
                                <p className="text-[11px] font-bold text-rose-450 text-center">{errorMsg}</p>
                            )}

                            <div className="flex gap-2.5 pt-2 border-t border-slate-850">
                                <button 
                                    type="button"
                                    onClick={onDisconnect}
                                    className="flex-1 py-2 bg-rose-500/10 hover:bg-rose-550 text-rose-400 hover:text-white border border-rose-500/20 hover:border-rose-550 rounded-xl text-xs font-bold transition-all active:scale-95 text-center"
                                >
                                    Disconnect PC
                                </button>
                                
                                <button 
                                    type="submit"
                                    disabled={testing || success || !ipInput.trim()}
                                    className="flex-1 py-2 bg-indigo-650 hover:bg-indigo-600 text-white rounded-xl text-xs font-bold transition-all active:scale-95 disabled:opacity-40 disabled:pointer-events-none flex items-center justify-center gap-1"
                                >
                                    {testing && <RefreshCw className="w-3 h-3 animate-spin" />}
                                    <span>Update IP</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </>
    );
}

export default function Runner({ tool, subscription, runtimePort, pluginSlug }: any) {
    const Component = components[tool.runner_component];
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
            <div className="min-h-screen bg-slate-550 flex items-center justify-center p-8 font-sans">
                <div className="text-center max-w-md">
                    <h1 className="text-2xl font-bold text-slate-900 mb-2">Component Not Found</h1>
                    <p className="text-slate-500">The interface for {tool.title} could not be loaded because the component "{tool.runner_component}" does not exist.</p>
                </div>
            </div>
        );
    }

    if (isMobile && status === 'checking') {
        return (
            <div className="min-h-screen bg-slate-900 flex items-center justify-center text-slate-200">
                <div className="text-center space-y-4">
                    <div className="w-8 h-8 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto" />
                    <p className="text-xs font-bold text-slate-400 uppercase tracking-widest animate-pulse">Checking Local PC Connection...</p>
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
        <>
            <Head title={tool.title} />
            
            {isMobile && status === 'online' && (
                <FloatingBadge host={currentHost} onDisconnect={handleDisconnect} />
            )}

            <Suspense fallback={
                <div className="min-h-screen bg-slate-50 flex items-center justify-center">
                    <div className="w-8 h-8 border-2 border-slate-900 border-t-transparent rounded-full animate-spin" />
                </div>
            }>
                <Component tool={tool} subscription={subscription} runtimePort={runtimePort} pluginSlug={pluginSlug} />
            </Suspense>
        </>
    );
}
