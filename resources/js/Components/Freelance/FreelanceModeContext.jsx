import React, { createContext, useContext, useState, useEffect } from 'react';

const FreelanceModeContext = createContext();

export function FreelanceModeProvider({ children }) {
    const [mode, setMode] = useState(() => {
        if (typeof window !== 'undefined') {
            return localStorage.getItem('freelanceMode') || 'client';
        }
        return 'client';
    });

    useEffect(() => {
        if (typeof window !== 'undefined') {
            localStorage.setItem('freelanceMode', mode);
        }
    }, [mode]);

    const toggleMode = () => {
        setMode(prev => prev === 'client' ? 'freelancer' : 'client');
    };

    return (
        <FreelanceModeContext.Provider value={{ mode, setMode, toggleMode }}>
            {children}
        </FreelanceModeContext.Provider>
    );
}

export function useFreelanceMode() {
    return useContext(FreelanceModeContext);
}
