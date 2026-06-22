import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import WebToolsLayout from '@/Layouts/WebToolsLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Checkbox } from '@/Components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Input } from '@/Components/ui/input';
import { Copy, Lock, RotateCcw, Code2 } from 'lucide-react';
import { useToast } from '@/Components/ui/use-toast';
import { __ } from '@/lib/i18n';

declare global {
    interface Window {
        JavaScriptObfuscator: any;
    }
}

export default function JsObfuscator() {
    const { toast } = useToast();
    const [isLoaded, setIsLoaded] = useState(false);
    
    useEffect(() => {
        const script = document.createElement('script');
        script.src = "https://cdn.jsdelivr.net/npm/javascript-obfuscator/dist/index.browser.js";
        script.async = true;
        script.onload = () => setIsLoaded(true);
        document.body.appendChild(script);
        return () => {
            document.body.removeChild(script);
        };
    }, []);

    const [inputCode, setInputCode] = useState('// Example code\nfunction hello(name) {\n    console.log("Hello, " + name + "!");\n}\n\nhello("World");');
    const [outputCode, setOutputCode] = useState('');
    const [preset, setPreset] = useState('default');

    // General
    const [compact, setCompact] = useState(true);
    const [simplify, setSimplify] = useState(true);
    const [target, setTarget] = useState('browser');
    const [seed, setSeed] = useState(0);

    // String Array
    const [stringArray, setStringArray] = useState(true);
    const [stringArrayThreshold, setStringArrayThreshold] = useState([0.75]);
    const [rotateStringArray, setRotateStringArray] = useState(true);
    const [shuffleStringArray, setShuffleStringArray] = useState(true);
    const [stringArrayEncoding, setStringArrayEncoding] = useState('none');

    // Control Flow
    const [controlFlowFlattening, setControlFlowFlattening] = useState(false);
    const [controlFlowFlatteningThreshold, setControlFlowFlatteningThreshold] = useState([0.75]);
    const [deadCodeInjection, setDeadCodeInjection] = useState(false);
    const [deadCodeInjectionThreshold, setDeadCodeInjectionThreshold] = useState([0.4]);

    // Protection
    const [selfDefending, setSelfDefending] = useState(false);
    const [debugProtection, setDebugProtection] = useState(false);
    const [disableConsoleOutput, setDisableConsoleOutput] = useState(false);

    // Identifiers
    const [identifierNamesGenerator, setIdentifierNamesGenerator] = useState('hexadecimal');
    const [identifiersPrefix, setIdentifiersPrefix] = useState('');
    const [renameGlobals, setRenameGlobals] = useState(false);

    // Other
    const [numbersToExpressions, setNumbersToExpressions] = useState(false);
    const [splitStrings, setSplitStrings] = useState(false);
    const [transformObjectKeys, setTransformObjectKeys] = useState(false);
    const [unicodeEscapeSequence, setUnicodeEscapeSequence] = useState(false);

    const handlePresetChange = (val: string) => {
        setPreset(val);
        if (val === 'low') {
            setCompact(true);
            setControlFlowFlattening(false);
            setDeadCodeInjection(false);
            setDebugProtection(false);
            setSelfDefending(false);
            setStringArray(true);
            setStringArrayEncoding('none');
        } else if (val === 'medium') {
            setCompact(true);
            setControlFlowFlattening(true);
            setControlFlowFlatteningThreshold([0.75]);
            setDeadCodeInjection(true);
            setDeadCodeInjectionThreshold([0.4]);
            setSelfDefending(true);
            setStringArray(true);
            setStringArrayEncoding('base64');
        } else if (val === 'high') {
            setCompact(true);
            setControlFlowFlattening(true);
            setControlFlowFlatteningThreshold([1]);
            setDeadCodeInjection(true);
            setDeadCodeInjectionThreshold([1]);
            setDebugProtection(true);
            setSelfDefending(true);
            setStringArray(true);
            setStringArrayEncoding('rc4');
            setNumbersToExpressions(true);
            setSplitStrings(true);
        } else {
            resetOptions();
        }
    };

    const resetOptions = () => {
        setPreset('default');
        setCompact(true);
        setSimplify(true);
        setTarget('browser');
        setSeed(0);
        setStringArray(true);
        setStringArrayThreshold([0.75]);
        setRotateStringArray(true);
        setShuffleStringArray(true);
        setStringArrayEncoding('none');
        setControlFlowFlattening(false);
        setControlFlowFlatteningThreshold([0.75]);
        setDeadCodeInjection(false);
        setDeadCodeInjectionThreshold([0.4]);
        setSelfDefending(false);
        setDebugProtection(false);
        setDisableConsoleOutput(false);
        setIdentifierNamesGenerator('hexadecimal');
        setIdentifiersPrefix('');
        setRenameGlobals(false);
        setNumbersToExpressions(false);
        setSplitStrings(false);
        setTransformObjectKeys(false);
        setUnicodeEscapeSequence(false);
    };

    const obfuscateCode = () => {
        if (!isLoaded || !window.JavaScriptObfuscator) {
            toast({ title: 'Loading...', description: 'Obfuscator engine is still loading. Please wait a moment.', variant: 'destructive' });
            return;
        }

        if (!inputCode.trim()) {
            toast({ title: 'Missing Input', description: 'Please enter some JavaScript code to obfuscate!', variant: 'destructive' });
            return;
        }

        try {
            const options = {
                compact,
                controlFlowFlattening,
                controlFlowFlatteningThreshold: controlFlowFlatteningThreshold[0],
                deadCodeInjection,
                deadCodeInjectionThreshold: deadCodeInjectionThreshold[0],
                debugProtection,
                disableConsoleOutput,
                identifierNamesGenerator,
                identifiersPrefix,
                numbersToExpressions,
                renameGlobals,
                rotateStringArray,
                seed,
                selfDefending,
                shuffleStringArray,
                simplify,
                splitStrings,
                stringArray,
                stringArrayEncoding: stringArrayEncoding === 'none' ? [] : [stringArrayEncoding],
                stringArrayThreshold: stringArrayThreshold[0],
                target,
                transformObjectKeys,
                unicodeEscapeSequence
            };

            const obfuscationResult = window.JavaScriptObfuscator.obfuscate(inputCode, options);
            setOutputCode(obfuscationResult.getObfuscatedCode());
            toast({ title: 'Success', description: 'Code successfully obfuscated.' });
        } catch (error: any) {
            setOutputCode(`Error: ${error.message}`);
            toast({ title: 'Error', description: 'Failed to obfuscate code.', variant: 'destructive' });
        }
    };

    const copyOutput = () => {
        if (!outputCode) {
            toast({ title: 'Empty', description: 'No obfuscated code to copy.', variant: 'destructive' });
            return;
        }
        navigator.clipboard.writeText(outputCode);
        toast({ title: 'Copied', description: 'Obfuscated code copied to clipboard!' });
    };

    return (
        <WebToolsLayout title={__('general.javascript_obfuscator_online')} activeNav="explore">
            <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6">
                <div className="text-center mb-10">
                    <div className="inline-flex items-center justify-center p-3 bg-red-100 text-red-700 rounded-2xl mb-4">
                        <Code2 className="w-8 h-8" />
                    </div>
                    <h1 className="text-3xl font-extrabold text-slate-900 sm:text-4xl">{__('general.javascript_obfuscator')}</h1>
                    <p className="mt-4 text-lg text-slate-600 max-w-7xl mx-auto">
                        {__('general.protect_your_javascript_code_with_advanc')}</p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div className="space-y-2">
                        <Label htmlFor="inputCode">{__('general.input_code')}</Label>
                        <Textarea 
                            id="inputCode"
                            className="font-mono h-64 text-sm resize-y" 
                            value={inputCode} 
                            onChange={(e) => setInputCode(e.target.value)} 
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="outputCode">{__('general.output_code')}</Label>
                        <Textarea 
                            id="outputCode"
                            className="font-mono h-64 text-sm bg-slate-50 resize-y" 
                            readOnly 
                            value={outputCode} 
                        />
                    </div>
                </div>

                <div className="flex flex-wrap items-center justify-center gap-3 mb-10">
                    <Button onClick={obfuscateCode} disabled={!isLoaded} size="lg" className="gap-2 bg-slate-900 hover:bg-slate-800">
                        <Lock className="w-4 h-4" />
                        {__('general.obfuscate_code')}</Button>
                    <Button onClick={copyOutput} variant="outline" size="lg" className="gap-2">
                        <Copy className="w-4 h-4" />
                        {__('general.copy_output')}</Button>
                    <Button onClick={resetOptions} variant="secondary" size="lg" className="gap-2">
                        <RotateCcw className="w-4 h-4" />
                        {__('general.reset_options')}</Button>
                </div>

                <div className="flex justify-center mb-10">
                    <div className="w-full max-w-md">
                        <Label htmlFor="preset">{__('general.quick_preset')}</Label>
                        <Select value={preset} onValueChange={(val) => handlePresetChange(val || '')}>
                            <SelectTrigger id="preset">
                                <SelectValue placeholder={__('general.select_a_preset')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="default">{__('general.default')}</SelectItem>
                                <SelectItem value="low">{__('general.low_obfuscation')}</SelectItem>
                                <SelectItem value="medium">{__('general.medium_obfuscation')}</SelectItem>
                                <SelectItem value="high">{__('general.high_obfuscation')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {/* General Options */}
                    <Card className="shadow-sm border-slate-200">
                        <CardHeader className="pb-3 border-b border-slate-100">
                            <CardTitle className="text-base">{__('general.general')}</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-4 space-y-4">
                            <div className="flex items-center space-x-2">
                                <Checkbox id="compact" checked={compact} onCheckedChange={(c) => setCompact(c as boolean)} />
                                <Label htmlFor="compact">{__('general.compact_code')}</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox id="simplify" checked={simplify} onCheckedChange={(c) => setSimplify(c as boolean)} />
                                <Label htmlFor="simplify">{__('general.simplify')}</Label>
                            </div>
                            <div className="space-y-1">
                                <Label className="text-xs text-slate-500">{__('general.target')}</Label>
                                <Select value={target} onValueChange={(val) => setTarget(val || '')}>
                                    <SelectTrigger className="h-8"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="browser">{__('general.browser')}</SelectItem>
                                        <SelectItem value="browser-no-eval">{__('general.browser_no_eval')}</SelectItem>
                                        <SelectItem value="node">{__('general.node')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1">
                                <Label className="text-xs text-slate-500">{__('general.seed')}</Label>
                                <Input type="number" value={seed} onChange={(e) => setSeed(parseInt(e.target.value) || 0)} className="h-8" />
                            </div>
                        </CardContent>
                    </Card>

                    {/* String Array */}
                    <Card className="shadow-sm border-slate-200">
                        <CardHeader className="pb-3 border-b border-slate-100">
                            <CardTitle className="text-base">{__('general.string_array')}</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-4 space-y-4">
                            <div className="flex items-center space-x-2">
                                <Checkbox id="stringArray" checked={stringArray} onCheckedChange={(c) => setStringArray(c as boolean)} />
                                <Label htmlFor="stringArray">{__('general.enable_string_array')}</Label>
                            </div>
                            <div className="space-y-2">
                                <div className="flex justify-between">
                                    <Label className="text-xs text-slate-500">{__('general.threshold')}</Label>
                                    <span className="text-xs font-semibold text-slate-700">{stringArrayThreshold[0]}</span>
                                </div>
                                <input type="range" min={0} max={1} step={0.05} value={stringArrayThreshold[0]} onChange={e => setStringArrayThreshold([parseFloat(e.target.value)])} className="w-full accent-indigo-600" />
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox id="rotate" checked={rotateStringArray} onCheckedChange={(c) => setRotateStringArray(c as boolean)} />
                                <Label htmlFor="rotate">{__('general.rotate')}</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox id="shuffle" checked={shuffleStringArray} onCheckedChange={(c) => setShuffleStringArray(c as boolean)} />
                                <Label htmlFor="shuffle">{__('general.shuffle')}</Label>
                            </div>
                            <div className="space-y-1">
                                <Label className="text-xs text-slate-500">{__('general.encoding')}</Label>
                                <Select value={stringArrayEncoding} onValueChange={(val) => setStringArrayEncoding(val || '')}>
                                    <SelectTrigger className="h-8"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">{__('general.none')}</SelectItem>
                                        <SelectItem value="base64">{__('general.base64')}</SelectItem>
                                        <SelectItem value="rc4">RC4</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Control Flow */}
                    <Card className="shadow-sm border-slate-200">
                        <CardHeader className="pb-3 border-b border-slate-100">
                            <CardTitle className="text-base">{__('general.control_flow')}</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-4 space-y-4">
                            <div className="flex items-center space-x-2">
                                <Checkbox id="cff" checked={controlFlowFlattening} onCheckedChange={(c) => setControlFlowFlattening(c as boolean)} />
                                <Label htmlFor="cff">{__('general.enable_flattening')}</Label>
                            </div>
                            <div className="space-y-2">
                                <div className="flex justify-between">
                                    <Label className="text-xs text-slate-500">{__('general.flattening_threshold')}</Label>
                                    <span className="text-xs font-semibold text-slate-700">{controlFlowFlatteningThreshold[0]}</span>
                                </div>
                                <input type="range" min={0} max={1} step={0.05} value={controlFlowFlatteningThreshold[0]} onChange={e => setControlFlowFlatteningThreshold([parseFloat(e.target.value)])} className="w-full accent-indigo-600" />
                            </div>
                            <div className="flex items-center space-x-2 pt-2 border-t border-slate-100">
                                <Checkbox id="dci" checked={deadCodeInjection} onCheckedChange={(c) => setDeadCodeInjection(c as boolean)} />
                                <Label htmlFor="dci">{__('general.dead_code_injection')}</Label>
                            </div>
                            <div className="space-y-2">
                                <div className="flex justify-between">
                                    <Label className="text-xs text-slate-500">{__('general.dead_code_threshold')}</Label>
                                    <span className="text-xs font-semibold text-slate-700">{deadCodeInjectionThreshold[0]}</span>
                                </div>
                                <input type="range" min={0} max={1} step={0.05} value={deadCodeInjectionThreshold[0]} onChange={e => setDeadCodeInjectionThreshold([parseFloat(e.target.value)])} className="w-full accent-indigo-600" />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Protection */}
                    <Card className="shadow-sm border-slate-200">
                        <CardHeader className="pb-3 border-b border-slate-100">
                            <CardTitle className="text-base">{__('general.protection')}</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-4 space-y-4">
                            <div className="flex items-center space-x-2">
                                <Checkbox id="sd" checked={selfDefending} onCheckedChange={(c) => setSelfDefending(c as boolean)} />
                                <Label htmlFor="sd">{__('general.self_defending')}</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox id="dp" checked={debugProtection} onCheckedChange={(c) => setDebugProtection(c as boolean)} />
                                <Label htmlFor="dp">{__('general.debug_protection')}</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox id="dco" checked={disableConsoleOutput} onCheckedChange={(c) => setDisableConsoleOutput(c as boolean)} />
                                <Label htmlFor="dco">{__('general.disable_console_output')}</Label>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Identifiers */}
                    <Card className="shadow-sm border-slate-200">
                        <CardHeader className="pb-3 border-b border-slate-100">
                            <CardTitle className="text-base">{__('general.identifiers')}</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-4 space-y-4">
                            <div className="space-y-1">
                                <Label className="text-xs text-slate-500">{__('general.names_generator')}</Label>
                                <Select value={identifierNamesGenerator} onValueChange={(val) => setIdentifierNamesGenerator(val || '')}>
                                    <SelectTrigger className="h-8"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="hexadecimal">{__('general.hexadecimal')}</SelectItem>
                                        <SelectItem value="mangled">{__('general.mangled')}</SelectItem>
                                        <SelectItem value="mangled-shuffled">{__('general.mangled_shuffled')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1">
                                <Label className="text-xs text-slate-500">Identifiers Prefix (Optional)</Label>
                                <Input value={identifiersPrefix} onChange={(e) => setIdentifiersPrefix(e.target.value)} className="h-8" />
                            </div>
                            <div className="flex items-center space-x-2 pt-2">
                                <Checkbox id="rg" checked={renameGlobals} onCheckedChange={(c) => setRenameGlobals(c as boolean)} />
                                <Label htmlFor="rg">{__('general.rename_globals')}</Label>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Other */}
                    <Card className="shadow-sm border-slate-200">
                        <CardHeader className="pb-3 border-b border-slate-100">
                            <CardTitle className="text-base">{__('general.other')}</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-4 space-y-4">
                            <div className="flex items-center space-x-2">
                                <Checkbox id="nte" checked={numbersToExpressions} onCheckedChange={(c) => setNumbersToExpressions(c as boolean)} />
                                <Label htmlFor="nte">{__('general.numbers_to_expressions')}</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox id="ss" checked={splitStrings} onCheckedChange={(c) => setSplitStrings(c as boolean)} />
                                <Label htmlFor="ss">{__('general.split_strings')}</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox id="tok" checked={transformObjectKeys} onCheckedChange={(c) => setTransformObjectKeys(c as boolean)} />
                                <Label htmlFor="tok">{__('general.transform_object_keys')}</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox id="ues" checked={unicodeEscapeSequence} onCheckedChange={(c) => setUnicodeEscapeSequence(c as boolean)} />
                                <Label htmlFor="ues">{__('general.unicode_escape_sequence')}</Label>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </WebToolsLayout>
    );
}
