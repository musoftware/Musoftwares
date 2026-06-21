import React, { useState } from 'react';
import { Button } from '@/Components/ui/button';
import { Plus, UserPlus, FileText, CheckSquare, Send, X } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

export function FloatingQuickAdd() {
    const [isOpen, setIsOpen] = useState(false);

    const toggleOpen = () => setIsOpen(!isOpen);

    const actions = [
        { icon: <UserPlus className="h-5 w-5" />, label: 'New Lead', onClick: () => { /* open new lead modal */ } },
        { icon: <CheckSquare className="h-5 w-5" />, label: 'New Task', onClick: () => { /* open new task modal */ } },
        { icon: <Send className="h-5 w-5" />, label: 'Send Campaign', onClick: () => { /* open new campaign modal */ } },
        { icon: <FileText className="h-5 w-5" />, label: 'Add Note', onClick: () => { /* open quick note modal */ } },
    ];

    return (
        <div className="fixed bottom-8 end-8 z-50">
            <AnimatePresence>
                {isOpen && (
                    <motion.div
                        initial={{ opacity: 0, y: 20, scale: 0.9 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        exit={{ opacity: 0, y: 20, scale: 0.9 }}
                        className="absolute bottom-16 end-0 mb-4 flex flex-col items-end gap-3"
                    >
                        {actions.map((action, index) => (
                            <motion.div
                                key={action.label}
                                initial={{ opacity: 0, x: 20 }}
                                animate={{ opacity: 1, x: 0 }}
                                transition={{ delay: index * 0.05 }}
                                className="flex items-center gap-3"
                            >
                                <span className="bg-white px-3 py-1.5 rounded-lg shadow-sm text-sm font-medium text-slate-700 whitespace-nowrap">
                                    {action.label}
                                </span>
                                <Button
                                    variant="secondary"
                                    size="icon"
                                    className="h-12 w-12 rounded-full shadow-lg bg-white hover:bg-slate-50 border border-slate-100 text-slate-700"
                                    onClick={() => {
                                        action.onClick();
                                        setIsOpen(false);
                                    }}
                                >
                                    {action.icon}
                                </Button>
                            </motion.div>
                        ))}
                    </motion.div>
                )}
            </AnimatePresence>

            <motion.button
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
                className={`h-14 w-14 rounded-full shadow-xl flex items-center justify-center text-white transition-colors duration-200 ${
                    isOpen ? 'bg-slate-800' : 'bg-indigo-600 hover:bg-indigo-700'
                }`}
                onClick={toggleOpen}
            >
                <motion.div
                    animate={{ rotate: isOpen ? 45 : 0 }}
                    transition={{ type: 'spring', stiffness: 260, damping: 20 }}
                >
                    <Plus className="h-6 w-6" />
                </motion.div>
            </motion.button>
        </div>
    );
}
