import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import { Copy, Search, ShieldAlert, KeyRound } from 'lucide-react';
import { useToast } from '@/Components/ui/use-toast';

interface MatchResult {
    id: string;
    name: string;
    description: string;
    preview: string | null;
}

export default function CipherIdentifier() {
    const [input, setInput] = useState('');
    const [results, setResults] = useState<MatchResult[] | null>(null);
    const { toast } = useToast();

    const analyzeText = () => {
        const text = input.trim();
        if (!text) {
            setResults(null);
            return;
        }

        const matches: Omit<MatchResult, 'id'>[] = [];
        let found = false;

        const addMatch = (name: string, description: string, preview: string | null = null) => {
            matches.push({ name, description, preview });
        };

        // 1. Binary
        if (/^[01\s]+$/.test(text) && text.replace(/\s/g, '').length % 8 === 0 && text.length > 0) {
            addMatch('Binary', 'Detected binary pattern (0s and 1s).', tryBinaryDecode(text));
            found = true;
        }

        // 2. Hexadecimal
        if (/^[0-9A-Fa-f\s]+$/.test(text) && text.replace(/\s/g, '').length % 2 === 0 && text.length > 0) {
             addMatch('Hexadecimal', 'Detected Hex characters (0-9, A-F).', tryHexDecode(text));
             found = true;
        }

        // 3. Base64
        if (/^[A-Za-z0-9+/]+={0,2}$/.test(text) && text.length % 4 === 0 && text.length > 0) {
             try {
                const decoded = atob(text);
                if (/[\x20-\x7E]/.test(decoded)) {
                    addMatch('Base64', 'High confidence. Valid Base64 string.', decoded);
                    found = true;
                }
             } catch(e) { /* ignore */ }
        }

        // 4. Morse Code
        if (/^[.-\s/]+$/.test(text) && text.length > 0) {
            addMatch('Morse Code', 'Detected dots, dashes, and spaces.', tryMorseDecode(text));
            found = true;
        }

        // 5. URL Encoding
        if (/%[0-9A-Fa-f]{2}/.test(text)) {
             try {
                 const decoded = decodeURIComponent(text);
                 if (decoded !== text) {
                     addMatch('URL Encoded', 'Detected URL escape sequences (%).', decoded);
                     found = true;
                 }
             } catch(e){ /* ignore */ }
        }

        // 6. HTML Entities
        if (/&[a-zA-Z0-9#]+;/.test(text)) {
            // A simple DOM parse isn't available in standard React exactly like this without a ref, 
            // but we can use a small regex or leave it as a general match
            addMatch('HTML Entities', 'Detected HTML encoded characters (&...;).', null);
            found = true;
        }

        // 7. A1Z26 Cipher (Numbers to Letters)
        if (/^(\d{1,2}[-\s,]+)+\d{1,2}$/.test(text.trim())) {
            const numbers = text.trim().split(/[-\s,]+/);
            let a1z26Decoded = "";
            let validA1Z26 = true;
            
            for (const num of numbers) {
                const n = parseInt(num);
                if (n < 1 || n > 26) {
                    validA1Z26 = false;
                    break;
                }
                a1z26Decoded += String.fromCharCode(n + 64);
            }
            
            if (validA1Z26 && a1z26Decoded.length > 0) {
                addMatch('A1Z26 Cipher', 'Numbers substituted for letters (1=A, 26=Z).', a1z26Decoded);
                found = true;
            }
        }

        // 8. ROT13
        if (/[A-Za-z]/.test(text)) {
            const rot13 = text.replace(/[a-zA-Z]/g, function(c){
                const base = c <= "Z" ? 90 : 122;
                const next = c.charCodeAt(0) + 13;
                return String.fromCharCode(base >= next ? next : next - 26);
            });
            if (rot13 !== text) {
                addMatch('ROT13', 'Common substitution cipher.', rot13);
                found = true;
            }
        }

        // 9. Reverse
        const reversed = text.split('').reverse().join('');
        if (reversed !== text) {
             addMatch('Reverse Text', 'Simple text reversal.', reversed);
        }

        // 10. Hashes
        const cleanText = text.trim();
        const hexOnly = /^[0-9a-fA-F]+$/.test(cleanText);
        
        if (hexOnly) {
            const len = cleanText.length;
            if (len === 32) {
                addMatch('MD5 Hash', 'Common 128-bit hash. Cannot be simply decoded.');
                found = true;
            } else if (len === 40) {
                addMatch('SHA-1 Hash', '160-bit hash.');
                found = true;
            } else if (len === 56) {
                addMatch('SHA-224 Hash', '224-bit hash from the SHA-2 family.');
                found = true;
            } else if (len === 64) {
                addMatch('SHA-256 Hash', 'Standard 256-bit hash. Very common in modern security.');
                found = true;
            } else if (len === 96) {
                addMatch('SHA-384 Hash', '384-bit hash.');
                found = true;
            } else if (len === 128) {
                addMatch('SHA-512 Hash', '512-bit hash. High security.');
                found = true;
            }
        }

        // 11. Bcrypt
        if (/^\$2[ayb]\$[0-9]{2}\$[A-Za-z0-9./]{53}$/.test(cleanText)) {
            addMatch('Bcrypt Hash', 'Standard password hashing algorithm. Includes salt and cost.');
            found = true;
        }

        // 12. Argon2
        if (cleanText.startsWith('$argon2')) {
            addMatch('Argon2 Hash', 'Modern memory-hard password hashing algorithm.');
            found = true;
        }

        // 13. JWT
        if (/^[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]*$/.test(cleanText)) {
            const parts = cleanText.split('.');
            if (parts.length === 3) {
                try {
                    const fixB64 = (str: string) => {
                        let output = str.replace(/-/g, '+').replace(/_/g, '/');
                        while (output.length % 4) output += '=';
                        return decodeURIComponent(escape(atob(output)));
                    };

                    const header = JSON.parse(fixB64(parts[0]));
                    const payload = JSON.parse(fixB64(parts[1]));
                    
                    const preview = "Header:\n" + JSON.stringify(header, null, 2) + "\n\nPayload:\n" + JSON.stringify(payload, null, 2);
                    addMatch('JSON Web Token (JWT)', 'Standard token format (Header.Payload.Signature).', preview);
                    found = true;
                } catch (e) { /* ignore */ }
            }
        }

        if (!found) {
            addMatch('Unknown / Generic', 'Could not definitely identify a standard encoding. It might be a variant of Vigenère, an obscure hash, or simply random data.');
        }

        setResults(matches.map((m, i) => ({ ...m, id: `match-${i}` })));
    };

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text);
        toast({ title: 'Copied to clipboard', description: 'The decoded text has been copied.' });
    };

    return (
        <ToolsPublicLayout title="Cipher Identifier Online" activeNav="explore">
            <div className="max-w-4xl mx-auto py-12 px-4 sm:px-6">
                <div className="text-center mb-10">
                    <div className="inline-flex items-center justify-center p-3 bg-indigo-100 text-indigo-700 rounded-2xl mb-4">
                        <KeyRound className="w-8 h-8" />
                    </div>
                    <h1 className="text-3xl font-extrabold text-slate-900 sm:text-4xl">Cipher Identifier</h1>
                    <p className="mt-4 text-lg text-slate-600 max-w-2xl mx-auto">
                        Paste your cryptic text below, and we'll attempt to identify the encoding, hash type, or cipher used.
                    </p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div className="lg:col-span-2 space-y-6">
                        <Card className="shadow-sm border-slate-200">
                            <CardHeader>
                                <CardTitle className="text-lg">Cipher / Encoded Text</CardTitle>
                                <CardDescription>Enter the text you want to analyze</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Textarea 
                                    className="font-mono min-h-[160px] text-sm"
                                    placeholder="e.g. SGVsbG8gV29ybGQ="
                                    value={input}
                                    onChange={(e) => setInput(e.target.value)}
                                />
                                <div className="mt-4 flex justify-end">
                                    <Button onClick={analyzeText} size="lg" className="gap-2">
                                        <Search className="w-4 h-4" />
                                        Analyze Text
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        {results && (
                            <div className="space-y-4">
                                <h3 className="text-xl font-bold text-slate-900 border-b pb-2">Analysis Results</h3>
                                {results.map(match => (
                                    <Card key={match.id} className="shadow-sm border-slate-200 overflow-hidden">
                                        <CardHeader className="bg-slate-50 border-b border-slate-100 py-3">
                                            <CardTitle className="text-base text-indigo-700 flex justify-between items-center">
                                                {match.name}
                                                <Badge variant="outline" className="bg-white">{match.name.includes('Hash') ? 'Hash' : 'Cipher/Encoding'}</Badge>
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent className="pt-4">
                                            <p className="text-slate-600 mb-3">{match.description}</p>
                                            {match.preview && (
                                                <div className="bg-slate-900 rounded-md p-3 relative group">
                                                    <div className="absolute right-2 top-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <Button variant="secondary" size="icon" className="h-7 w-7" onClick={() => copyToClipboard(match.preview as string)}>
                                                            <Copy className="h-3.5 w-3.5" />
                                                        </Button>
                                                    </div>
                                                    <pre className="text-xs text-slate-300 whitespace-pre-wrap font-mono break-all pr-8">
                                                        {match.preview}
                                                    </pre>
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="lg:col-span-1">
                        <Card className="bg-slate-50 border-slate-200 shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-base flex items-center gap-2">
                                    <ShieldAlert className="w-4 h-4 text-slate-500" />
                                    Supported Detectors
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ul className="space-y-2 text-sm text-slate-600">
                                    <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 bg-indigo-500 rounded-full" />Base64 & URL Encoding</li>
                                    <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 bg-indigo-500 rounded-full" />Hexadecimal & Binary</li>
                                    <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 bg-indigo-500 rounded-full" />Morse Code</li>
                                    <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 bg-indigo-500 rounded-full" />ROT13 & Caesar</li>
                                    <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 bg-indigo-500 rounded-full" />A1Z26 (Numbers)</li>
                                    <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 bg-indigo-500 rounded-full" />MD5, SHA-1, SHA-256</li>
                                    <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 bg-indigo-500 rounded-full" />Bcrypt & Argon2</li>
                                    <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 bg-indigo-500 rounded-full" />JSON Web Token (JWT)</li>
                                </ul>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </ToolsPublicLayout>
    );
}

function tryBinaryDecode(str: string): string {
    const clean = str.replace(/\s/g, '');
    let output = '';
    for (let i = 0; i < clean.length; i += 8) {
        output += String.fromCharCode(parseInt(clean.substr(i, 8), 2));
    }
    return output;
}

function tryHexDecode(str: string): string {
    const clean = str.replace(/\s/g, '');
    let output = '';
    for (let i = 0; i < clean.length; i += 2) {
        output += String.fromCharCode(parseInt(clean.substr(i, 2), 16));
    }
    return output;
}

function tryMorseDecode(str: string): string {
    const morseMap: Record<string, string> = {
        ".-": "A", "-...": "B", "-.-.": "C", "-..": "D", ".": "E", "..-.": "F",
        "--.": "G", "....": "H", "..": "I", ".---": "J", "-.-": "K", ".-..": "L",
        "--": "M", "-.": "N", "---": "O", ".--.": "P", "--.-": "Q", ".-.": "R",
        "...": "S", "-": "T", "..-": "U", "...-": "V", ".--": "W", "-..-": "X",
        "-.--": "Y", "--..": "Z", "-----": "0", ".----": "1", "..---": "2",
        "...--": "3", "....-": "4", ".....": "5", "-....": "6", "--...": "7",
        "---..": "8", "----.": "9", "/": " "
    };
    return str.split(' ').map(c => morseMap[c] || '?').join('');
}
