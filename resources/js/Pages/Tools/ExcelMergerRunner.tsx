import React, { useState, useCallback } from 'react';
import { useDropzone } from 'react-dropzone';
import { read, utils, writeFile } from 'xlsx';
import {
    FileSpreadsheet, Download, Trash2, 
    FileIcon, AlertCircle, CheckCircle, RefreshCw
} from 'lucide-react';
import { Button } from '@/Components/ui/button';

export default function ExcelMergerRunner({ tool, subscription }: any) {
    const [files, setFiles] = useState<File[]>([]);
    const [status, setStatus] = useState<'idle' | 'running' | 'done' | 'error'>('idle');
    const [progressMsg, setProgressMsg] = useState('');
    const [errorMsg, setError] = useState('');
    
    // Config
    const [deduplicate, setDeduplicate] = useState(true);
    const [extractPhones, setExtractPhones] = useState(false);

    const onDrop = useCallback((acceptedFiles: File[]) => {
        const excelFiles = acceptedFiles.filter(f => 
            f.name.endsWith('.xlsx') || f.name.endsWith('.xls') || f.name.endsWith('.csv')
        );
        setFiles(prev => [...prev, ...excelFiles]);
        setStatus('idle');
    }, []);

    const { getRootProps, getInputProps, isDragActive } = useDropzone({ onDrop, accept: {
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': ['.xlsx'],
        'application/vnd.ms-excel': ['.xls'],
        'text/csv': ['.csv']
    }});

    const removeFile = (index: number) => {
        setFiles(prev => prev.filter((_, i) => i !== index));
    };

    const extractEgyptianPhone = (str: string) => {
        if (!str) return '';
        const s = String(str).replace(/\D/g, '');
        if (s.startsWith('01') && s.length === 11) return s;
        if (s.startsWith('201') && s.length === 12) return s.substring(1);
        if (s.startsWith('1') && s.length === 10) return '0' + s;
        return '';
    };

    const handleMerge = async () => {
        if (files.length === 0) return;
        setStatus('running');
        setProgressMsg('Reading files...');
        setError('');

        try {
            let combinedData: any[] = [];
            let headers: string[] = [];

            for (let i = 0; i < files.length; i++) {
                setProgressMsg(`Processing file ${i + 1} of ${files.length}...`);
                const file = files[i];
                const arrayBuffer = await file.arrayBuffer();
                const workbook = read(arrayBuffer, { type: 'array' });
                
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];
                
                const jsonData = utils.sheet_to_json(worksheet, { header: 1, defval: '' }) as any[][];
                
                if (jsonData.length === 0) continue;

                if (i === 0) {
                    headers = jsonData[0] || [];
                    if (extractPhones) headers.push('Extracted Phone');
                }

                const startRow = i === 0 ? 0 : 1;
                
                for (let r = startRow; r < jsonData.length; r++) {
                    const row = jsonData[r];
                    if (row.filter(c => c !== '').length === 0) continue;

                    let phone = '';
                    if (extractPhones) {
                        for (let c = 0; c < row.length; c++) {
                            const p = extractEgyptianPhone(row[c]);
                            if (p) phone = p;
                        }
                        row.push(phone);
                    }
                    combinedData.push(row);
                }
            }

            setProgressMsg('Deduplicating and formatting...');

            if (deduplicate) {
                const seen = new Set();
                const uniqueData = [];
                for (const row of combinedData) {
                    const str = JSON.stringify(row);
                    if (!seen.has(str)) {
                        seen.add(str);
                        uniqueData.push(row);
                    }
                }
                combinedData = uniqueData;
            }

            setProgressMsg('Generating combined file...');
            
            const newWs = utils.aoa_to_sheet(combinedData);
            const newWb = utils.book_new();
            utils.book_append_sheet(newWb, newWs, "Merged Data");
            
            writeFile(newWb, "Merged_Output.xlsx");
            
            setStatus('done');
            setProgressMsg(`Success! Merged ${files.length} files into ${combinedData.length} rows.`);

        } catch (err: any) {
            console.error(err);
            setStatus('error');
            setError('An error occurred while merging files: ' + err.message);
        }
    };

    return (
        <div className="flex h-screen bg-slate-50 overflow-hidden font-sans antialiased text-slate-900">
            {/* Sidebar */}
            <div className="w-80 bg-white border-r border-slate-200 shadow-[2px_0_10px_rgba(0,0,0,0.02)] flex flex-col z-10 overflow-y-auto">
                <div className="px-6 py-8">
                    <div className="w-12 h-12 bg-indigo-500 rounded-2xl flex items-center justify-center shadow-inner mb-6 shadow-indigo-200">
                        <FileSpreadsheet className="w-6 h-6 text-white" />
                    </div>
                    <h1 className="text-xl font-black tracking-tight text-slate-900 mb-2">Excel Merger</h1>
                    <p className="text-sm font-medium text-slate-500 mb-8 leading-relaxed">
                        Fast, local, privacy-first Excel and CSV file merger. Your files never leave your browser.
                    </p>

                    <div className="space-y-6">
                        <div className="space-y-4">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500 ml-1">Merge Settings</h3>
                            
                            <label className="flex items-start gap-3 cursor-pointer group p-3 bg-slate-50 rounded-xl border border-slate-100 hover:border-indigo-200 transition-colors">
                                <div className="relative flex items-center justify-center mt-0.5">
                                    <input 
                                        type="checkbox" 
                                        checked={deduplicate}
                                        onChange={(e) => setDeduplicate(e.target.checked)}
                                        className="peer sr-only" 
                                    />
                                    <div className="w-5 h-5 rounded border border-slate-300 bg-white peer-checked:bg-indigo-500 peer-checked:border-indigo-500 transition-colors"></div>
                                    <CheckCircle className="w-3.5 h-3.5 text-white absolute opacity-0 peer-checked:opacity-100 transition-opacity" />
                                </div>
                                <div className="flex flex-col">
                                    <span className="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">Deduplicate Rows</span>
                                    <span className="text-xs font-medium text-slate-500 leading-relaxed mt-0.5">Remove identical rows across all files.</span>
                                </div>
                            </label>

                            <label className="flex items-start gap-3 cursor-pointer group p-3 bg-slate-50 rounded-xl border border-slate-100 hover:border-indigo-200 transition-colors">
                                <div className="relative flex items-center justify-center mt-0.5">
                                    <input 
                                        type="checkbox" 
                                        checked={extractPhones}
                                        onChange={(e) => setExtractPhones(e.target.checked)}
                                        className="peer sr-only" 
                                    />
                                    <div className="w-5 h-5 rounded border border-slate-300 bg-white peer-checked:bg-indigo-500 peer-checked:border-indigo-500 transition-colors"></div>
                                    <CheckCircle className="w-3.5 h-3.5 text-white absolute opacity-0 peer-checked:opacity-100 transition-opacity" />
                                </div>
                                <div className="flex flex-col">
                                    <span className="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">Extract Phone Numbers</span>
                                    <span className="text-xs font-medium text-slate-500 leading-relaxed mt-0.5">Scan rows for Egyptian phone numbers and append.</span>
                                </div>
                            </label>
                        </div>

                        {status === 'running' ? (
                            <Button 
                                disabled
                                className="w-full h-12 rounded-xl bg-slate-100 text-slate-400 font-bold shadow-none gap-2 mt-4"
                            >
                                <RefreshCw className="w-4 h-4 animate-spin" />
                                Merging...
                            </Button>
                        ) : (
                            <Button 
                                onClick={handleMerge}
                                disabled={files.length === 0}
                                className="w-full h-12 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-lg shadow-indigo-500/25 gap-2 mt-4 transition-all disabled:opacity-50"
                            >
                                <FileSpreadsheet className="w-4 h-4 fill-current" />
                                Merge & Download
                            </Button>
                        )}

                        {status === 'error' && (
                            <div className="flex items-start gap-2.5 bg-rose-50 border border-rose-200 rounded-xl p-3">
                                <AlertCircle className="w-4 h-4 text-rose-500 mt-0.5 shrink-0" />
                                <p className="text-xs text-rose-700 font-medium leading-relaxed">{errorMsg}</p>
                            </div>
                        )}

                        {status === 'done' && (
                            <div className="flex items-start gap-2.5 bg-emerald-50 border border-emerald-200 rounded-xl p-3">
                                <CheckCircle className="w-4 h-4 text-emerald-500 mt-0.5 shrink-0" />
                                <p className="text-xs text-emerald-700 font-medium leading-relaxed">
                                    {progressMsg}
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Main Workspace Area */}
            <div className="flex-1 flex flex-col min-w-0 bg-slate-50 p-8 overflow-y-auto">
                <div className="max-w-4xl w-full mx-auto space-y-6">
                    
                    <div 
                        {...getRootProps()} 
                        className={`border-2 border-dashed rounded-3xl p-12 text-center cursor-pointer transition-all duration-200 ${
                            isDragActive ? 'border-indigo-500 bg-indigo-50' : 'border-slate-300 bg-white hover:border-indigo-400 hover:bg-slate-50'
                        }`}
                    >
                        <input {...getInputProps()} />
                        <div className="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <Download className="w-8 h-8 text-indigo-600" />
                        </div>
                        <h3 className="text-lg font-black text-slate-800 mb-2">
                            {isDragActive ? 'Drop files here...' : 'Drag & drop Excel or CSV files'}
                        </h3>
                        <p className="text-sm font-medium text-slate-500">
                            or click to browse from your computer. Files are processed entirely locally.
                        </p>
                    </div>

                    {files.length > 0 && (
                        <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-500">
                            <div className="bg-slate-50 border-b border-slate-200 px-6 py-4 flex justify-between items-center">
                                <h2 className="text-sm font-black text-slate-900">
                                    Pending Files ({files.length})
                                </h2>
                                <Button 
                                    onClick={() => { setFiles([]); setStatus('idle'); }}
                                    variant="ghost" 
                                    size="sm" 
                                    className="text-rose-500 hover:text-rose-600 hover:bg-rose-50 h-8 font-bold"
                                >
                                    Clear All
                                </Button>
                            </div>
                            <div className="divide-y divide-slate-100">
                                {files.map((f, i) => (
                                    <div key={i} className="flex items-center justify-between px-6 py-4 hover:bg-slate-50/50 transition-colors">
                                        <div className="flex items-center gap-4 min-w-0">
                                            <div className="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center shrink-0">
                                                <FileIcon className="w-5 h-5 text-slate-500" />
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <p className="text-sm font-bold text-slate-800 truncate">{f.name}</p>
                                                <p className="text-xs font-medium text-slate-500">{(f.size / 1024).toFixed(1)} KB</p>
                                            </div>
                                        </div>
                                        <button 
                                            onClick={() => removeFile(i)}
                                            className="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-colors ml-4 shrink-0"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
