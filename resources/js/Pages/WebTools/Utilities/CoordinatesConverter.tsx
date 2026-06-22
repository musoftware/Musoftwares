import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import WebToolsLayout from '@/Layouts/WebToolsLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Copy, MapPin, ArrowRightLeft } from 'lucide-react';
import { useToast } from '@/Components/ui/use-toast';
import { __ } from '@/lib/i18n';

export default function CoordinatesConverter() {
    const { toast } = useToast();

    // DD to DMS State
    const [ddLat, setDdLat] = useState('');
    const [ddLng, setDdLng] = useState('');
    const [dmsResult, setDmsResult] = useState<{ lat: string; lng: string; combined: string } | null>(null);

    // DMS to DD State
    const [dmsLatDeg, setDmsLatDeg] = useState('');
    const [dmsLatMin, setDmsLatMin] = useState('');
    const [dmsLatSec, setDmsLatSec] = useState('');
    const [dmsLatDir, setDmsLatDir] = useState('N');
    const [dmsLngDeg, setDmsLngDeg] = useState('');
    const [dmsLngMin, setDmsLngMin] = useState('');
    const [dmsLngSec, setDmsLngSec] = useState('');
    const [dmsLngDir, setDmsLngDir] = useState('E');
    const [ddResult, setDdResult] = useState<string | null>(null);

    const toDMS = (coordinate: number, type: 'lat' | 'lng') => {
        const absolute = Math.abs(coordinate);
        const degrees = Math.floor(absolute);
        const minutesNotTruncated = (absolute - degrees) * 60;
        const minutes = Math.floor(minutesNotTruncated);
        const seconds = ((minutesNotTruncated - minutes) * 60).toFixed(4);

        let direction = "";
        if (type === 'lat') {
            direction = coordinate >= 0 ? "N" : "S";
        } else {
            direction = coordinate >= 0 ? "E" : "W";
        }

        return `${degrees}° ${minutes}' ${parseFloat(seconds)}" ${direction}`;
    };

    const convertDDtoDMS = () => {
        const lat = parseFloat(ddLat);
        const lng = parseFloat(ddLng);

        if (isNaN(lat) || isNaN(lng)) {
            toast({ title: 'Invalid Input', description: 'Please enter valid decimal coordinates.', variant: 'destructive' });
            return;
        }

        const resLat = toDMS(lat, 'lat');
        const resLng = toDMS(lng, 'lng');

        setDmsResult({
            lat: resLat,
            lng: resLng,
            combined: `${resLat}, ${resLng}`
        });
    };

    const convertDMStoDD = () => {
        const latDeg = parseFloat(dmsLatDeg) || 0;
        const latMin = parseFloat(dmsLatMin) || 0;
        const latSec = parseFloat(dmsLatSec) || 0;
        
        const lngDeg = parseFloat(dmsLngDeg) || 0;
        const lngMin = parseFloat(dmsLngMin) || 0;
        const lngSec = parseFloat(dmsLngSec) || 0;

        let latDD = latDeg + (latMin / 60) + (latSec / 3600);
        if (dmsLatDir === 'S') latDD = latDD * -1;

        let lngDD = lngDeg + (lngMin / 60) + (lngSec / 3600);
        if (dmsLngDir === 'W') lngDD = lngDD * -1;

        setDdResult(`${latDD.toFixed(6)}, ${lngDD.toFixed(6)}`);
    };

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text);
        toast({ title: 'Copied to clipboard', description: 'The text has been copied.' });
    };

    return (
        <WebToolsLayout title="Decimal to Degrees Converter (DMS)" activeNav="explore">
            <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6">
                <div className="text-center mb-10">
                    <div className="inline-flex items-center justify-center p-3 bg-blue-100 text-blue-700 rounded-2xl mb-4">
                        <MapPin className="w-8 h-8" />
                    </div>
                    <h1 className="text-3xl font-extrabold text-slate-900 sm:text-4xl">{__('general.decimal_to_degrees_converter')}</h1>
                    <p className="mt-4 text-lg text-slate-600 max-w-7xl mx-auto">
                        Convert GPS coordinates between Decimal Degrees (DD) and Degrees, Minutes, Seconds (DMS).
                    </p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div className="lg:col-span-2 space-y-6">
                        <Card className="shadow-sm border-slate-200">
                            <CardContent className="pt-6">
                                <Tabs defaultValue="dd-to-dms" className="w-full">
                                    <TabsList className="grid w-full grid-cols-2 mb-6">
                                        <TabsTrigger value="dd-to-dms">{__('general.decimal_to_dms')}</TabsTrigger>
                                        <TabsTrigger value="dms-to-dd">{__('general.dms_to_decimal')}</TabsTrigger>
                                    </TabsList>
                                    
                                    <TabsContent value="dd-to-dms">
                                        <div className="space-y-4">
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div className="space-y-2">
                                                    <Label htmlFor="ddLat">Latitude (Decimal)</Label>
                                                    <Input 
                                                        id="ddLat" 
                                                        type="number" 
                                                        placeholder="e.g. 40.7128" 
                                                        value={ddLat} 
                                                        onChange={(e) => setDdLat(e.target.value)} 
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="ddLng">Longitude (Decimal)</Label>
                                                    <Input 
                                                        id="ddLng" 
                                                        type="number" 
                                                        placeholder="e.g. -74.0060" 
                                                        value={ddLng} 
                                                        onChange={(e) => setDdLng(e.target.value)} 
                                                    />
                                                </div>
                                            </div>
                                            
                                            <Button onClick={convertDDtoDMS} className="w-full gap-2">
                                                <ArrowRightLeft className="w-4 h-4" />
                                                {__('general.convert_to_dms')}</Button>

                                            {dmsResult && (
                                                <div className="mt-6 p-4 bg-slate-50 border border-slate-100 rounded-lg">
                                                    <h3 className="text-sm font-semibold text-slate-500 mb-4 uppercase tracking-wider">{__('general.result')}</h3>
                                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                                        <div>
                                                            <div className="text-xs text-slate-400 mb-1">{__('general.latitude')}</div>
                                                            <div className="text-lg font-semibold text-slate-800">{dmsResult.lat}</div>
                                                        </div>
                                                        <div>
                                                            <div className="text-xs text-slate-400 mb-1">{__('general.longitude')}</div>
                                                            <div className="text-lg font-semibold text-slate-800">{dmsResult.lng}</div>
                                                        </div>
                                                    </div>
                                                    <div className="pt-4 border-t border-slate-200">
                                                        <div className="text-xs text-slate-400 mb-1">{__('general.combined')}</div>
                                                        <div className="flex items-center justify-between bg-white p-2 rounded border border-slate-200">
                                                            <code className="text-sm font-mono text-slate-800">{dmsResult.combined}</code>
                                                            <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => copyToClipboard(dmsResult.combined)}>
                                                                <Copy className="h-4 w-4 text-slate-500" />
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </TabsContent>

                                    <TabsContent value="dms-to-dd">
                                        <div className="space-y-6">
                                            {/* Latitude Input */}
                                            <div className="space-y-2">
                                                <Label>{__('general.latitude')}</Label>
                                                <div className="flex gap-2">
                                                    <Input type="number" placeholder="Deg" value={dmsLatDeg} onChange={(e) => setDmsLatDeg(e.target.value)} className="flex-1" />
                                                    <div className="flex items-center text-slate-400 font-serif">°</div>
                                                    <Input type="number" placeholder="Min" value={dmsLatMin} onChange={(e) => setDmsLatMin(e.target.value)} className="flex-1" />
                                                    <div className="flex items-center text-slate-400 font-serif">'</div>
                                                    <Input type="number" placeholder="Sec" value={dmsLatSec} onChange={(e) => setDmsLatSec(e.target.value)} className="flex-1" />
                                                    <div className="flex items-center text-slate-400 font-serif">"</div>
                                                    <Select value={dmsLatDir} onValueChange={(val) => setDmsLatDir(val || '')}>
                                                        <SelectTrigger className="w-[80px]">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="N">N</SelectItem>
                                                            <SelectItem value="S">S</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            </div>

                                            {/* Longitude Input */}
                                            <div className="space-y-2">
                                                <Label>{__('general.longitude')}</Label>
                                                <div className="flex gap-2">
                                                    <Input type="number" placeholder="Deg" value={dmsLngDeg} onChange={(e) => setDmsLngDeg(e.target.value)} className="flex-1" />
                                                    <div className="flex items-center text-slate-400 font-serif">°</div>
                                                    <Input type="number" placeholder="Min" value={dmsLngMin} onChange={(e) => setDmsLngMin(e.target.value)} className="flex-1" />
                                                    <div className="flex items-center text-slate-400 font-serif">'</div>
                                                    <Input type="number" placeholder="Sec" value={dmsLngSec} onChange={(e) => setDmsLngSec(e.target.value)} className="flex-1" />
                                                    <div className="flex items-center text-slate-400 font-serif">"</div>
                                                    <Select value={dmsLngDir} onValueChange={(val) => setDmsLngDir(val || '')}>
                                                        <SelectTrigger className="w-[80px]">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="E">E</SelectItem>
                                                            <SelectItem value="W">W</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            </div>

                                            <Button onClick={convertDMStoDD} className="w-full gap-2">
                                                <ArrowRightLeft className="w-4 h-4" />
                                                {__('general.convert_to_decimal')}</Button>

                                            {ddResult && (
                                                <div className="mt-6 p-4 bg-slate-50 border border-slate-100 rounded-lg">
                                                    <h3 className="text-sm font-semibold text-slate-500 mb-4 uppercase tracking-wider">{__('general.result')}</h3>
                                                    <div className="flex items-center justify-between bg-white p-3 rounded-md border border-slate-200">
                                                        <code className="text-lg font-mono text-slate-800">{ddResult}</code>
                                                        <Button variant="ghost" size="icon" onClick={() => copyToClipboard(ddResult)}>
                                                            <Copy className="h-4 w-4 text-slate-500" />
                                                        </Button>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </TabsContent>
                                </Tabs>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="lg:col-span-1">
                        <Card className="bg-slate-50 border-slate-200 shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-base flex items-center gap-2">
                                    <MapPin className="w-4 h-4 text-slate-500" />
                                    {__('general.about_formats')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4 text-sm text-slate-600">
                                    <div>
                                        <strong className="block text-slate-800 mb-1">Decimal Degrees (DD)</strong>
                                        Expresses latitude and longitude as decimal fractions (e.g., 40.7128°). Standard for digital mapping and APIs like Google Maps.
                                    </div>
                                    <div className="pt-3 border-t border-slate-200">
                                        <strong className="block text-slate-800 mb-1">Degrees, Minutes, Seconds (DMS)</strong>
                                        Uses the sexagesimal system (e.g., 40° 42' 46" N). Common in traditional navigation, nautical charts, and legacy systems.
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </WebToolsLayout>
    );
}
