import React, { useState } from 'react';
import { X } from 'lucide-react';

interface FolderModalProps {
    isOpen: boolean;
    onClose: () => void;
    folderName: string;
    onRename: (newName: string) => void;
    children: React.ReactNode;
}

export function FolderModal({ isOpen, onClose, folderName, onRename, children }: FolderModalProps) {
    const [isEditing, setIsEditing] = useState(false);
    const [nameVal, setNameVal] = useState(folderName);

    if (!isOpen) return null;

    const handleRenameSubmit = (e?: React.FormEvent) => {
        e?.preventDefault();
        if (nameVal.trim()) {
            onRename(nameVal.trim());
        } else {
            setNameVal(folderName);
        }
        setIsEditing(false);
    };

    return (
        <div 
            className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
            onClick={onClose}
        >
            {/* Folder Container */}
            <div 
                className="w-full max-w-xl bg-white/20 backdrop-blur-xl border border-white/30 rounded-3xl p-6 shadow-2xl animate-in fade-in zoom-in duration-200"
                onClick={(e) => e.stopPropagation()} // Prevent close when clicking inside
            >
                {/* Header (Title) */}
                <div className="flex justify-between items-start mb-6">
                    <div className="flex-1 flex justify-center">
                        {isEditing ? (
                            <form onSubmit={handleRenameSubmit}>
                                <input
                                    autoFocus
                                    type="text"
                                    value={nameVal}
                                    onChange={(e) => setNameVal(e.target.value)}
                                    onBlur={() => handleRenameSubmit()}
                                    className="bg-black/20 text-white border border-white/20 rounded-lg px-3 py-1 text-center font-semibold text-lg outline-none focus:bg-black/30 w-48"
                                />
                            </form>
                        ) : (
                            <h2 
                                className="text-white text-xl font-semibold cursor-pointer hover:bg-white/10 px-4 py-1 rounded-lg transition-colors drop-shadow-md text-center"
                                onClick={() => setIsEditing(true)}
                                title={__('general.click_to_rename')}
                            >
                                {folderName}
                            </h2>
                        )}
                    </div>
                </div>

                {/* Grid Content */}
                <div className="flex flex-wrap gap-4 min-h-[120px] content-start">
                    {children}
                </div>
            </div>
        </div>
    );
}
