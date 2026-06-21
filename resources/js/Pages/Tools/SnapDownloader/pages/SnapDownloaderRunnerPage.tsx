import React from 'react';
import { Smartphone, Terminal } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { useSnapDownloader } from '../hooks/useSnapDownloader';
import { Sidebar } from '../components/Sidebar';
import { Header } from '../components/Header';
import { MobileNav } from '../components/MobileNav';

// Workspaces
import { NewDownloadWorkspace } from '../components/workspaces/NewDownloadWorkspace';
import { AutomationsWorkspace } from '../components/workspaces/AutomationsWorkspace';
import { ActiveProcessesWorkspace } from '../components/workspaces/ActiveProcessesWorkspace';
import { QueueWorkspace } from '../components/workspaces/QueueWorkspace';
import { FoldersWorkspace } from '../components/workspaces/FoldersWorkspace';
import { HistoryWorkspace } from '../components/workspaces/HistoryWorkspace';

export default function SnapDownloaderRunnerPage() {
    const {
        connected,
        callRPC,
        activeWorkspace,
        setActiveWorkspace,
        activeProcesses,
        queue,
        automations,
        folders,
        history,
        loadAll
    } = useSnapDownloader();

    // ─── Connection Loading Screen ─────────────────────────────────────────
    if (!connected) {
        return (
            <div className="min-h-screen flex items-center justify-center px-4" style={{ background: '#0f1117' }}>
                <div className="text-center space-y-6 w-full max-w-sm p-8 rounded-3xl border border-white/5" style={{ background: 'rgba(255,255,255,0.03)' }}>
                    <div className="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto relative" style={{ background: 'linear-gradient(135deg, #f59e0b, #d97706)' }}>
                        <Smartphone className="w-8 h-8 text-white" />
                        <span className="absolute -top-1 -end-1 w-4 h-4 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center">
                            <span className="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse" />
                        </span>
                    </div>
                    <div>
                        <h3 className="font-bold text-white text-lg">{__('Connecting...')}</h3>
                        <p className="text-xs text-slate-500 mt-2 leading-relaxed mb-4">
                            {__('erp.make_sure_the_musoftware_desktop')}
                        </p>
                        <a
                            href="musoftware://open"
                            className="inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all w-full"
                            style={{ background: 'rgba(245,158,11,0.15)', color: '#f59e0b', border: '1px solid rgba(245,158,11,0.25)' }}
                        >
                            <Terminal className="w-4 h-4" />
                            {__('general.launch_runtime_app')}
                        </a>
                    </div>
                    <div className="h-1 rounded-full overflow-hidden" style={{ background: 'rgba(255,255,255,0.05)' }}>
                        <div className="h-full rounded-full animate-pulse" style={{ width: '60%', background: 'linear-gradient(90deg, #f59e0b, #d97706)' }} />
                    </div>
                </div>
            </div>
        );
    }

    const runningCount = activeProcesses.filter(p => p.status === 'running').length;
    const queuedCount = queue.filter(j => j.status === 'pending').length;

    return (
        <div
            className="flex flex-col md:flex-row antialiased"
            style={{
                background: '#0f1117',
                color: '#e2e8f0',
                fontFamily: "'Inter', system-ui, sans-serif",
                minHeight: '100vh',
            }}
        >
            <Sidebar
                activeWorkspace={activeWorkspace}
                setActiveWorkspace={setActiveWorkspace}
                connected={connected}
                automationsCount={automations.length}
                activeCount={runningCount}
                queueCount={queuedCount}
            />

            <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
                <Header
                    activeWorkspace={activeWorkspace}
                    loadAll={loadAll}
                    activeCount={runningCount}
                />

                <main className="flex-1 overflow-y-auto p-4 sm:p-6 pb-28 md:pb-6">
                    {activeWorkspace === 'new' && (
                        <NewDownloadWorkspace callRPC={callRPC} loadAll={loadAll} setActiveWorkspace={setActiveWorkspace} />
                    )}
                    {activeWorkspace === 'automations' && (
                        <AutomationsWorkspace callRPC={callRPC} loadAll={loadAll} automations={automations} />
                    )}
                    {activeWorkspace === 'active' && (
                        <ActiveProcessesWorkspace activeProcesses={activeProcesses} callRPC={callRPC} loadAll={loadAll} setActiveWorkspace={setActiveWorkspace} />
                    )}
                    {activeWorkspace === 'queue' && (
                        <QueueWorkspace queue={queue} callRPC={callRPC} loadAll={loadAll} />
                    )}
                    {activeWorkspace === 'folders' && (
                        <FoldersWorkspace folders={folders} callRPC={callRPC} />
                    )}
                    {activeWorkspace === 'history' && (
                        <HistoryWorkspace history={history} callRPC={callRPC} setHistory={(h) => {}} />
                    )}
                </main>
            </div>

            <MobileNav
                activeWorkspace={activeWorkspace}
                setActiveWorkspace={setActiveWorkspace}
                automationsCount={automations.length}
                activeCount={runningCount}
                queueCount={queuedCount}
            />
        </div>
    );
}
