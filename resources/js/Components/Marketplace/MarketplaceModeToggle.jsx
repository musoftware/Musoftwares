import React from 'react';
import { useMarketplaceMode } from './MarketplaceModeContext';

export default function MarketplaceModeToggle() {
    const { mode, setMode } = useMarketplaceMode();

    return (
        <div className="inline-flex items-center bg-slate-100 p-0.5 rounded-lg border border-slate-200/50 select-none">
            <button
                onClick={() => setMode('client')}
                className={`px-2.5 py-1 text-xs font-medium rounded-md transition-all duration-200 ${
                    mode === 'client'
                        ? 'bg-white text-slate-900 shadow-sm font-semibold'
                        : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50/50'
                }`}
            >
                Client
            </button>
            <button
                onClick={() => setMode('seller')}
                className={`px-2.5 py-1 text-xs font-medium rounded-md transition-all duration-200 ${
                    mode === 'seller'
                        ? 'bg-white text-slate-900 shadow-sm font-semibold'
                        : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50/50'
                }`}
            >
                Seller
            </button>
        </div>
    );
}
