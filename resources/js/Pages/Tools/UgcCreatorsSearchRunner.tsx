import React from 'react';
import { Users } from 'lucide-react';
import { Button } from '@/Components/ui/button';

export default function UgcCreatorsSearchRunner({ tool }: any) {
    return (
        <div className="min-h-screen bg-slate-950 font-sans text-slate-100 flex flex-col items-center justify-center p-8">
            <div className="max-w-md text-center space-y-6">
                <div className="w-16 h-16 bg-blue-500/10 border border-blue-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <Users className="w-8 h-8 text-blue-400" />
                </div>
                <h1 className="text-2xl font-bold">Creators Finder</h1>
                <p className="text-sm text-slate-400">
                    Find and connect with UGC creators natively from your browser via the local runtime extension.
                </p>
                <div className="pt-8 border-t border-slate-800">
                    <p className="text-xs text-slate-500 uppercase tracking-widest font-black mb-4">Coming Soon</p>
                    <Button variant="outline" className="bg-slate-900 border-slate-800 text-slate-300 hover:bg-slate-800">
                        View Documentation
                    </Button>
                </div>
            </div>
        </div>
    );
}
