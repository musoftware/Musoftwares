import React, { createContext, useContext, useState, useEffect } from 'react';

const MarketplaceModeContext = createContext();

export function MarketplaceModeProvider({ children }) {
    const [mode, setMode] = useState(() => {
        if (typeof window !== 'undefined') {
            return localStorage.getItem('marketplaceMode') || 'client';
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

export function useMarketplaceMode() {
    return useContext(MarketplaceModeContext);
}
