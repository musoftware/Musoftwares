import { Command } from 'cmdk';
import { useEffect, useState } from 'react';

// A minimal placeholder for the cmdk command palette
export function CommandPalette() {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        const down = (e: KeyboardEvent) => {
            if (e.key === 'k' && (e.metaKey || e.ctrlKey)) {
                e.preventDefault();
                setOpen((open) => !open);
            }
        };
        document.addEventListener('keydown', down);
        return () => document.removeEventListener('keydown', down);
    }, []);

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-start justify-center bg-background/80 pt-32 backdrop-blur-sm">
            <div className="w-full max-w-2xl overflow-hidden rounded-xl border border-border bg-card shadow-2xl">
                <Command>
                    <Command.Input
                        placeholder="Search invoices, services, users..."
                        className="w-full border-b border-border bg-transparent p-4 text-foreground outline-none placeholder:text-muted-foreground"
                    />
                    <Command.List className="max-h-96 overflow-y-auto p-2">
                        <Command.Empty className="p-4 text-center text-sm text-muted-foreground">
                            No results found.
                        </Command.Empty>

                        <Command.Group
                            heading="Suggestions"
                            className="px-2 py-1.5 text-xs font-semibold text-muted-foreground"
                        >
                            <Command.Item className="mb-1 cursor-pointer rounded-md px-2 py-2 text-sm text-foreground hover:bg-muted">
                                Dashboard
                            </Command.Item>
                            <Command.Item className="mb-1 cursor-pointer rounded-md px-2 py-2 text-sm text-foreground hover:bg-muted">
                                Create Invoice
                            </Command.Item>
                            <Command.Item className="cursor-pointer rounded-md px-2 py-2 text-sm text-foreground hover:bg-muted">
                                Settings
                            </Command.Item>
                        </Command.Group>
                    </Command.List>
                </Command>
            </div>
        </div>
    );
}
