import { useEffect, useState } from 'react';
import { Command } from 'cmdk';
import { Search } from 'lucide-react';
import axios from 'axios';
import { router } from '@inertiajs/react';

export default function CommandPalette() {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);

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

  useEffect(() => {
    if (!query) {
      setResults([]);
      return;
    }

    const timeoutId = setTimeout(() => {
      setLoading(true);
      axios.get(`/search?q=${encodeURIComponent(query)}`)
        .then(res => setResults(res.data))
        .catch(err => console.error(err))
        .finally(() => setLoading(false));
    }, 300);

    return () => clearTimeout(timeoutId);
  }, [query]);

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-start justify-center pt-16 sm:pt-24">
        {/* Backdrop */}
        <div
            className="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"
            onClick={() => setOpen(false)}
        />

        {/* Dialog */}
        <div className="relative w-full max-w-xl transform overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-black/5 transition-all">
            <Command
                className="flex h-full w-full flex-col overflow-hidden bg-white text-gray-900"
                shouldFilter={false}
            >
                <div className="flex items-center border-b border-gray-200 px-4">
                    <Search className="h-5 w-5 text-gray-400" />
                    <Command.Input
                        autoFocus
                        value={query}
                        onValueChange={setQuery}
                        placeholder="Search clients, invoices, services... (Cmd+K)"
                        className="flex-1 border-0 bg-transparent px-4 py-4 text-sm outline-none placeholder:text-gray-400 focus:ring-0"
                    />
                </div>

                <Command.List className="max-h-96 overflow-y-auto p-2">
                    {loading && <div className="p-4 text-center text-sm text-gray-500">Searching...</div>}
                    {!loading && query && results.length === 0 && (
                        <div className="p-4 text-center text-sm text-gray-500">No results found.</div>
                    )}

                    {!loading && results.length > 0 && (
                        <Command.Group heading="Results">
                            {results.map((result: any, i: number) => (
                                <Command.Item
                                    key={`${result.type}-${result.id}-${i}`}
                                    onSelect={() => {
                                        router.visit(result.url);
                                        setOpen(false);
                                    }}
                                    className="flex cursor-pointer select-none items-center rounded-md px-3 py-2 text-sm text-gray-900 aria-selected:bg-gray-100"
                                >
                                    <div className="flex-1">
                                        <span className="font-medium">{result.title}</span>
                                        <span className="ml-2 text-xs text-gray-500 rounded bg-gray-100 px-2 py-0.5">{result.type}</span>
                                    </div>
                                </Command.Item>
                            ))}
                        </Command.Group>
                    )}
                </Command.List>
            </Command>
        </div>
    </div>
  );
}
