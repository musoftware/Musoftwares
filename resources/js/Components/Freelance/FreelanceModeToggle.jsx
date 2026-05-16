import React from 'react';
import { useFreelanceMode } from './FreelanceModeContext';

export default function FreelanceModeToggle() {
    const { mode, setMode } = useFreelanceMode();

    return (
        <div className="flex justify-center mb-6">
            <div className="relative inline-flex bg-gray-100 p-1 rounded-full shadow-inner">
                {/* Sliding background indicator */}
                <div
                    className={`absolute top-1 bottom-1 w-1/2 bg-indigo-600 rounded-full transition-transform duration-300 ease-in-out shadow`}
                    style={{
                        transform: mode === 'client' ? 'translateX(0)' : 'translateX(100%)'
                    }}
                ></div>

                <button
                    onClick={() => setMode('client')}
                    className={`relative w-40 py-2 text-sm font-medium rounded-full z-10 transition-colors duration-300 ${
                        mode === 'client' ? 'text-white' : 'text-gray-600 hover:text-gray-900'
                    }`}
                >
                    👤 Client
                </button>
                <button
                    onClick={() => setMode('freelancer')}
                    className={`relative w-40 py-2 text-sm font-medium rounded-full z-10 transition-colors duration-300 ${
                        mode === 'freelancer' ? 'text-white' : 'text-gray-600 hover:text-gray-900'
                    }`}
                >
                    💻 Freelancer
                </button>
            </div>
        </div>
    );
}
