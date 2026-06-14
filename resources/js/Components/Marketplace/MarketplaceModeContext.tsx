import React, { createContext, useContext, useState, useEffect } from 'react';

export type MarketplaceMode = 'client' | 'seller';

interface MarketplaceModeContextType {
    mode: MarketplaceMode;
    setMode: (mode: MarketplaceMode) => void;
    toggleMode: () => void;
}

const MarketplaceModeContext = createContext<MarketplaceModeContextType | undefined>(undefined);

export function MarketplaceModeProvider({ children }: { children: React.ReactNode }) {
    const [mode, setMode] = useState<MarketplaceMode>(() => {
        if (typeof window !== 'undefined') {
            const savedMode = localStorage.getItem('marketplaceMode');
            return (savedMode === 'seller' || savedMode === 'client') ? savedMode : 'client';
        }
        return 'client';
    });

    useEffect(() => {
        if (typeof window !== 'undefined') {
            localStorage.setItem('marketplaceMode', mode);
        }
    }, [mode]);

    const toggleMode = () => {
        setMode(prev => prev === 'client' ? 'seller' : 'client');
    };

    return (
        <MarketplaceModeContext.Provider value={{ mode, setMode, toggleMode }}>
            {children}
        </MarketplaceModeContext.Provider>
    );
}

export function useMarketplaceMode(): MarketplaceModeContextType {
    const context = useContext(MarketplaceModeContext);
    if (context === undefined) {
        throw new Error('useMarketplaceMode must be used within a MarketplaceModeProvider');
    }
    return context;
}
