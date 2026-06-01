import React, { useState, useEffect, useRef } from 'react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { Input } from "@/Components/ui/input";
import { __ } from '@/lib/i18n';

const getRuntimeHost = () => typeof window !== 'undefined' ? ((window as any).MUSOFTWARE_RUNTIME_HOST || '127.0.0.1') : '127.0.0.1';
const getRuntimeHttp = () => `http://${getRuntimeHost()}:18400`;
const getWsUrl       = () => `ws://${getRuntimeHost()}:18401/ws`;

export default function OlxB2CFinderRunner({ auth, tool }: any) {
    const [countries, setCountries] = useState<any[]>([]);
    const [selectedCountry, setSelectedCountry] = useState<string>('');
    const [keyword, setKeyword] = useState<string>('');
    const [isRunning, setIsRunning] = useState(false);
    const [results, setResults] = useState<any[]>([]);
    const wsRef = useRef<WebSocket | null>(null);

    const loadCountries = async () => {
        try {
            const res = await fetch(`${getRuntimeHttp()}/plugins/olx-b2c-finder/rpc`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_countries', params: {} })
            });
            const data = await res.json();
            if (data.countries) {
                setCountries(data.countries);
            }
        } catch (e) {
            console.error('Failed to load countries:', e);
        }
    };

    const loadLeads = async () => {
        try {
            const res = await fetch(`${getRuntimeHttp()}/plugins/olx-b2c-finder/rpc`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_leads', params: { limit: 100 } })
            });
            const data = await res.json();
            if (data.leads) {
                setResults(data.leads);
            }
        } catch (e) {
            console.error('Failed to load leads:', e);
        }
    };

    useEffect(() => {
        loadCountries();
        loadLeads();
        
        const ws = new WebSocket(getWsUrl());
        wsRef.current = ws;

        ws.onmessage = (e) => {
            try {
                const msg = JSON.parse(e.data);
                if (msg.event === 'prospecting.lead.extracted') {
                    const lead = msg.data.lead;
                    setResults(prev => {
                        if (prev.some(x => x.id === lead.id || x.dedupe_hash === lead.dedupe_hash)) {
                            return prev;
                        }
                        return [lead, ...prev];
                    });
                } else if (msg.event === 'olx.search.stopped' || msg.event === 'olx.extraction.stop_requested') {
                    setIsRunning(false);
                } else if (msg.event === 'olx.search.started') {
                    setIsRunning(true);
                }
            } catch (_) {}
        };
        
        return () => ws.close();
    }, []);

    const startSearch = async () => {
        if (!selectedCountry || !keyword) return;
        setIsRunning(true);
        
        try {
            const res = await fetch(`${getRuntimeHttp()}/plugins/olx-b2c-finder/rpc`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'start_search', 
                    params: { country: selectedCountry, keyword } 
                })
            });
            const data = await res.json();
            console.log('Search started', data);
        } catch (e) {
            console.error('Failed to start search', e);
            setIsRunning(false);
        }
    };

    const stopSearch = async () => {
        setIsRunning(false);
        try {
            await fetch(`${getRuntimeHttp()}/plugins/olx-b2c-finder/rpc`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'stop_search', 
                    params: {} 
                })
            });
        } catch (e) {
            console.error('Failed to stop search', e);
        }
    };

    return (
        <div className="w-full h-full p-6">
            <div className="max-w-7xl mx-auto space-y-6">
                <Tabs defaultValue="search" className="w-full">
                        <TabsList className="mb-4">
                            <TabsTrigger value="search">{__('general.search_extract')}</TabsTrigger>
                            <TabsTrigger value="results">Live Results ({results.length})</TabsTrigger>
                        </TabsList>
                        
                        <TabsContent value="search">
                            <Card>
                                <CardHeader>
                                    <CardTitle>{__('general.search_configuration')}</CardTitle>
                                    <CardDescription>{__('general.configure_your_lead_extraction_targets')}</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">{__('general.target_country_platform')}</label>
                                            <Select value={selectedCountry} onValueChange={setSelectedCountry} disabled={isRunning}>
                                                <SelectTrigger>
                                                    <SelectValue placeholder={__('general.select_platform')} />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {countries.map(c => (
                                                        <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">{__('general.search_keyword')}</label>
                                            <Input 
                                                placeholder={__('general.e_g_iphone_14_pro')} 
                                                value={keyword}
                                                onChange={e => setKeyword(e.target.value)}
                                                disabled={isRunning}
                                            />
                                        </div>
                                    </div>
                                    <div className="flex gap-3 pt-4">
                                        {!isRunning ? (
                                            <Button 
                                                onClick={startSearch} 
                                                disabled={!selectedCountry || !keyword}
                                                className="w-full sm:w-auto bg-blue-600 hover:bg-blue-700"
                                            >{__('general.start_extraction_engine')}</Button>
                                        ) : (
                                            <Button 
                                                onClick={stopSearch} 
                                                className="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white"
                                            >{__('general.stop_extraction_engine')}</Button>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                        
                        <TabsContent value="results">
                            <Card>
                                <CardHeader>
                                    <CardTitle>{__('general.extracted_leads')}</CardTitle>
                                    <CardDescription>{__('general.real_time_results_from_the_extraction_engine')}</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {results.length === 0 ? (
                                        <div className="text-center text-gray-500 py-8">{__('general.no_results_yet_start_an_extraction_to_see_data_here')}</div>
                                    ) : (
                                        <div className="space-y-2 max-h-[500px] overflow-y-auto pr-2">
                                            {results.map((r, i) => (
                                                <div key={r.id || i} className="p-4 border rounded-xl bg-slate-900/40 border-slate-800/80 flex items-center justify-between hover:bg-slate-900/60 transition duration-200">
                                                    <div className="space-y-1">
                                                        <div className="font-medium text-slate-100">{r.listing_title || r.title}</div>
                                                        <div className="text-xs text-slate-400 flex items-center gap-3 flex-wrap">
                                                            <span className="bg-slate-800 px-2 py-0.5 rounded text-slate-300">👤 {r.name || 'Seller'}</span>
                                                            {r.phone && <span className="bg-blue-900/30 text-blue-300 px-2 py-0.5 rounded">📞 {r.phone}</span>}
                                                            {r.region && <span className="bg-slate-800 px-2 py-0.5 rounded text-slate-300">📍 {r.region}</span>}
                                                            <span className="text-emerald-400 font-semibold">{r.price}</span>
                                                        </div>
                                                    </div>
                                                    {r.url && (
                                                        <a 
                                                            href={r.url} 
                                                            target="_blank" 
                                                            rel="noopener noreferrer"
                                                            className="text-xs text-blue-400 hover:underline hover:text-blue-300 bg-blue-950/40 px-3 py-1.5 rounded-lg border border-blue-900/50"
                                                        >
                                                            Open Listing ↗
                                                        </a>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
    );
}

