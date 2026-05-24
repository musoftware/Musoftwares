import React, { useState, useEffect, useRef } from 'react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { Input } from "@/Components/ui/input";

const getRuntimeHost = () => typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
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

    useEffect(() => {
        loadCountries();
        
        const ws = new WebSocket(getWsUrl());
        wsRef.current = ws;

        ws.onmessage = (e) => {
            try {
                const msg = JSON.parse(e.data);
                // Handle realtime streaming events here later
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
        }
    };

    return (
        <div className="w-full h-full p-6">
            <div className="max-w-7xl mx-auto space-y-6">
                <Tabs defaultValue="search" className="w-full">
                        <TabsList className="mb-4">
                            <TabsTrigger value="search">Search & Extract</TabsTrigger>
                            <TabsTrigger value="results">Live Results ({results.length})</TabsTrigger>
                        </TabsList>
                        
                        <TabsContent value="search">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Search Configuration</CardTitle>
                                    <CardDescription>Configure your lead extraction targets</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">Target Country / Platform</label>
                                            <Select value={selectedCountry} onValueChange={setSelectedCountry}>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select platform..." />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {countries.map(c => (
                                                        <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">Search Keyword</label>
                                            <Input 
                                                placeholder="e.g. iPhone 14 Pro" 
                                                value={keyword}
                                                onChange={e => setKeyword(e.target.value)}
                                            />
                                        </div>
                                    </div>
                                    <div className="pt-4">
                                        <Button 
                                            onClick={startSearch} 
                                            disabled={isRunning || !selectedCountry || !keyword}
                                            className="w-full sm:w-auto"
                                        >
                                            {isRunning ? 'Extraction Running...' : 'Start Extraction Engine'}
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                        
                        <TabsContent value="results">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Extracted Leads</CardTitle>
                                    <CardDescription>Real-time results from the extraction engine</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {results.length === 0 ? (
                                        <div className="text-center text-gray-500 py-8">
                                            No results yet. Start an extraction to see data here.
                                        </div>
                                    ) : (
                                        <div className="space-y-2">
                                            {results.map((r, i) => (
                                                <div key={i} className="p-3 border rounded">
                                                    {r.title} - {r.price}
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

