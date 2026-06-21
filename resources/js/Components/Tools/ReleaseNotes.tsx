import React, { useState } from 'react';
import { ChevronDown, ChevronUp } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';

interface ToolVersion {
    version: string;
    changelog: string;
    is_latest: boolean;
    is_beta: boolean;
    file_size: string;
    released_at: string;
}

interface ReleaseNotesProps {
    versions: ToolVersion[];
    defaultExpanded?: string | null;
}

export function ReleaseNotes({ versions, defaultExpanded }: ReleaseNotesProps) {
    const [expanded, setExpanded] = useState<string | null>(
        defaultExpanded !== undefined ? defaultExpanded : (versions[0]?.version ?? null)
    );

    if (!versions || versions.length === 0) return null;

    return (
        <div className="space-y-2">
            {versions.map(v => {
                const isOpen = expanded === v.version;
                return (
                    <div
                        key={v.version}
                        className="border border-slate-200 rounded-xl overflow-hidden transition-all"
                    >
                        <button
                            className="w-full flex items-center justify-between px-4 py-3.5 text-start hover:bg-slate-50/80 transition-colors"
                            onClick={() => setExpanded(isOpen ? null : v.version)}
                        >
                            <div className="flex items-center gap-2.5 min-w-0">
                                <code className="font-mono text-sm font-semibold text-slate-800">v{v.version}</code>
                                {v.is_latest && (
                                    <Badge className="bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-50 text-[11px] px-2 py-0 font-medium">
                                        Latest
                                    </Badge>
                                )}
                                {v.is_beta && (
                                    <Badge className="bg-orange-50 text-orange-700 border border-orange-200 hover:bg-orange-50 text-[11px] px-2 py-0 font-medium">
                                        Beta
                                    </Badge>
                                )}
                                <span className="text-xs text-slate-400 hidden sm:block">{v.released_at}</span>
                                {v.file_size && (
                                    <span className="text-xs text-slate-400 hidden md:block">· {v.file_size}</span>
                                )}
                            </div>
                            {isOpen
                                ? <ChevronUp className="h-4 w-4 text-slate-400 flex-shrink-0" />
                                : <ChevronDown className="h-4 w-4 text-slate-400 flex-shrink-0" />
                            }
                        </button>

                        {isOpen && v.changelog && (
                            <div className="px-4 pb-4 pt-0 border-t border-slate-100">
                                <p className="text-xs text-slate-400 mt-3 mb-2 sm:hidden">{v.released_at}</p>
                                <div className="text-sm text-slate-600 leading-relaxed whitespace-pre-wrap mt-3">
                                    {v.changelog}
                                </div>
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
