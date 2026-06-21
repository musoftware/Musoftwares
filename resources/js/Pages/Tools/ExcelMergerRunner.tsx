import React, { useState, useRef, useMemo } from 'react';
import { read, utils, writeFile } from 'xlsx';
import { useDropzone } from 'react-dropzone';
import {
    UploadCloud, Download, Save, RefreshCw, Filter, 
    XCircle, CheckCircle, Trash2, Plus, Minus,
    ChevronLeft, ChevronRight, Wand2, Layers, SplitSquareHorizontal,
    FileSpreadsheet, FileIcon, Settings
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';

type FilterRule = 'contains' | 'equal' | 'not_equal' | 'must_exist' | 'start_with' | 'end_with';

interface FieldFilterState {
    checked: boolean;
    rule: FilterRule;
    keywords: string[];
}

export type TransformOperation = {
    id: string;
    type: 'replace' | 'prefix' | 'suffix' | 'double';
    column: string;
    text1: string; // find text or addition text
    text2?: string; // replace text
};

const processInChunks = async <T, R>(
    items: T[], 
    processor: (chunk: T[]) => R[], 
    onProgress?: (percent: number) => void,
    chunkSize = 5000
): Promise<R[]> => {
    const results: R[] = [];
    for (let i = 0; i < items.length; i += chunkSize) {
        const chunk = items.slice(i, i + chunkSize);
        const chunkResult = processor(chunk);
        for(let j=0; j<chunkResult.length; j++) results.push(chunkResult[j]);
        if (onProgress) {
            onProgress(Math.floor((Math.min(i + chunkSize, items.length) / items.length) * 100));
        }
        await new Promise(resolve => setTimeout(resolve, 0));
    }
    return results;
};

export default function ExcelMergerRunner() {
    const [status, setStatus] = useState<'idle' | 'running' | 'done' | 'error'>('idle');
    const [progressMsg, setProgressMsg] = useState('');
    const [progressPercent, setProgressPercent] = useState(0);

    const [csvEncoding, setCsvEncoding] = useState('utf-8');

    const [originalData, setOriginalData] = useState<any[][]>([]);
    const [filteredData, setFilteredData] = useState<any[][]>([]);
    const [headers, setHeaders] = useState<string[]>([]);
    
    const [fieldFilters, setFieldFilters] = useState<Record<string, FieldFilterState>>({});
    const [transformations, setTransformations] = useState<TransformOperation[]>([]);
    
    // Transform State
    const [replaceFindText, setReplaceFindText] = useState('');
    const [replaceWithText, setReplaceWithText] = useState('');
    const [replaceColumn, setReplaceColumn] = useState('');

    const [additionText, setAdditionText] = useState('');
    const [additionPos, setAdditionPos] = useState<'Left' | 'Right' | 'Double'>('Left');
    const [additionColumn, setAdditionColumn] = useState('');

    // Pagination
    const [page, setPage] = useState(1);
    const rowsPerPage = 100;
    const fileInputRef = useRef<HTMLInputElement>(null);

    // Modals State
    const [mergeModalOpen, setMergeModalOpen] = useState(false);
    const [splitModalOpen, setSplitModalOpen] = useState(false);
    
    // Merge Modal State
    const [mergeFiles, setMergeFiles] = useState<File[]>([]);
    const [splitRowsCount, setSplitRowsCount] = useState(10000);

    const handleFileUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setStatus('running');
        setProgressMsg('Reading file...');
        setProgressPercent(10);

        try {
            const arrayBuffer = await file.arrayBuffer();
            let workbook;
            
            if (file.name.toLowerCase().endsWith('.csv')) {
                const decoder = new TextDecoder(csvEncoding);
                const csvString = decoder.decode(arrayBuffer);
                workbook = read(csvString, { type: 'string' });
            } else {
                workbook = read(arrayBuffer, { type: 'array' });
            }
            
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            
            setProgressPercent(50);
            setProgressMsg('Parsing data...');

            const jsonData = utils.sheet_to_json(worksheet, { header: 1, defval: '' }) as any[][];
            
            if (jsonData.length > 0) {
                const rawHeaders = jsonData[0].map(String);
                
                const uniqueHeaders: string[] = [];
                rawHeaders.forEach((h, i) => {
                    let newH = h || `Column ${i+1}`;
                    let counter = 1;
                    while(uniqueHeaders.includes(newH)) {
                        newH = `${h}_${counter}`;
                        counter++;
                    }
                    uniqueHeaders.push(newH);
                });

                setHeaders(uniqueHeaders);
                
                const initFilters: Record<string, FieldFilterState> = {};
                uniqueHeaders.forEach(h => {
                    initFilters[h] = { checked: true, rule: 'contains', keywords: [] };
                });
                setFieldFilters(initFilters);

                const dataRows = jsonData.slice(1);
                setOriginalData(dataRows);
                setFilteredData(dataRows);
                setTransformations([]);
                setPage(1);
            }
            
            setStatus('idle');
            setProgressPercent(100);
            setProgressMsg(`Loaded ${jsonData.length - 1} rows.`);
        } catch (error) {
            console.error(error);
            setStatus('error');
            setProgressMsg('Failed to read file.');
        }
        
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    const updateFilter = (header: string, updates: Partial<FieldFilterState>) => {
        setFieldFilters(prev => ({
            ...prev,
            [header]: { ...prev[header], ...updates }
        }));
    };

    const applyPipeline = async (currentData = originalData, currentFilters = fieldFilters, currentTransforms = transformations) => {
        setStatus('running');
        setProgressMsg('Applying transformations...');
        setProgressPercent(0);

        let data = currentData;

        // 1. Transformations
        if (currentTransforms.length > 0) {
            const colIndices = currentTransforms.map(t => headers.indexOf(t.column));
            data = await processInChunks(currentData, (chunk) => {
                return chunk.map(row => {
                    const newRow = [...row];
                    for (let i = 0; i < currentTransforms.length; i++) {
                        const t = currentTransforms[i];
                        const colIdx = colIndices[i];
                        if (colIdx === -1) continue;
                        
                        const val = String(newRow[colIdx] || '');
                        if (t.type === 'replace') {
                            newRow[colIdx] = val.split(t.text1).join(t.text2 || '');
                        } else if (t.type === 'prefix') {
                            newRow[colIdx] = t.text1 + val;
                        } else if (t.type === 'suffix') {
                            newRow[colIdx] = val + t.text1;
                        } else if (t.type === 'double') {
                            newRow[colIdx] = t.text1 + val + t.text1;
                        }
                    }
                    return newRow;
                });
            }, setProgressPercent, 2000);
        }

        setProgressMsg('Applying filters...');
        setProgressPercent(0);

        // 2. Filters
        const filtered = await processInChunks(data, (chunk) => {
            return chunk.filter((row) => {
                for (let i = 0; i < headers.length; i++) {
                    const header = headers[i];
                    const filter = currentFilters[header];
                    if (!filter) continue;
                    const cellValue = String(row[i] || '');

                    let match = false;
                    
                    if (filter.rule === 'must_exist') {
                        match = cellValue.trim() !== '';
                    } else if (filter.keywords.length === 0) {
                        match = true;
                    } else if (filter.rule === 'contains') {
                        match = filter.keywords.some(kw => cellValue.toLowerCase().includes(kw.toLowerCase()));
                    } else if (filter.rule === 'equal') {
                        match = filter.keywords.some(kw => cellValue.toLowerCase() === kw.toLowerCase());
                    } else if (filter.rule === 'not_equal') {
                        match = true;
                        for (const kw of filter.keywords) {
                            if (cellValue.toLowerCase() === kw.toLowerCase()) {
                                match = false;
                                break;
                            }
                        }
                    } else if (filter.rule === 'start_with') {
                        match = filter.keywords.some(kw => cellValue.toLowerCase().startsWith(kw.toLowerCase()));
                    } else if (filter.rule === 'end_with') {
                        match = filter.keywords.some(kw => cellValue.toLowerCase().endsWith(kw.toLowerCase()));
                    }

                    if (!match) return false;
                }
                return true;
            });
        }, setProgressPercent, 2000);

        setFilteredData(filtered);
        setPage(1);
        setStatus('idle');
        setProgressPercent(100);
        setProgressMsg(`Completed. ${filtered.length} rows remaining.`);
    };

    const handleApplyFilter = () => {
        applyPipeline(originalData, fieldFilters, transformations);
    };

    const handleClearFilter = () => {
        const resetFilters = { ...fieldFilters };
        Object.keys(resetFilters).forEach(h => resetFilters[h].keywords = []);
        setFieldFilters(resetFilters);
        applyPipeline(originalData, resetFilters, transformations);
    };

    const handleClearAll = () => {
        setOriginalData([]);
        setFilteredData([]);
        setHeaders([]);
        setFieldFilters({});
        setTransformations([]);
        setProgressPercent(0);
        setProgressMsg('All data cleared.');
        setStatus('idle');
    };

    const handleDistinct = async () => {
        setStatus('running');
        setProgressMsg('Removing duplicates...');
        setProgressPercent(0);
        
        const seen = new Set();
        
        const unique = await processInChunks(filteredData, (chunk) => {
            const chunkUnique: any[][] = [];
            chunk.forEach(row => {
                const key = JSON.stringify(row);
                if (!seen.has(key)) {
                    seen.add(key);
                    chunkUnique.push(row);
                }
            });
            return chunkUnique;
        }, setProgressPercent, 5000);

        setFilteredData(unique);
        setPage(1);
        setStatus('idle');
        setProgressMsg(`Removed duplicates. ${unique.length} rows remaining.`);
    };

    const addTransformation = (t: Omit<TransformOperation, 'id'>) => {
        const newTransforms = [...transformations, { ...t, id: Math.random().toString(36).substring(7) }];
        setTransformations(newTransforms);
        applyPipeline(originalData, fieldFilters, newTransforms);
    };

    const removeTransformation = (id: string) => {
        const newTransforms = transformations.filter(t => t.id !== id);
        setTransformations(newTransforms);
        applyPipeline(originalData, fieldFilters, newTransforms);
    };

    const applyReplace = () => {
        if (!replaceColumn || !replaceFindText) return;
        addTransformation({
            type: 'replace',
            column: replaceColumn,
            text1: replaceFindText,
            text2: replaceWithText
        });
        setReplaceFindText('');
        setReplaceWithText('');
    };

    const applyAddition = () => {
        if (!additionColumn || !additionText) return;
        addTransformation({
            type: additionPos.toLowerCase() as any,
            column: additionColumn,
            text1: additionText
        });
        setAdditionText('');
    };

    const exportData = (onlyCheckedColumns: boolean) => {
        const indicesToKeep = headers.map((h, i) => {
            if (onlyCheckedColumns && !fieldFilters[h].checked) return -1;
            return i;
        }).filter(i => i !== -1);

        const exportHeaders = indicesToKeep.map(i => headers[i]);
        const exportRows = filteredData.map(row => indicesToKeep.map(i => row[i]));

        const finalAOA = [exportHeaders, ...exportRows];
        const newWs = utils.aoa_to_sheet(finalAOA);
        const newWb = utils.book_new();
        utils.book_append_sheet(newWb, newWs, "Data");
        
        writeFile(newWb, "Exported_Data.xlsx");
    };

    // Sub-Forms (Modals) Logic
    const onMergeDrop = (acceptedFiles: File[]) => {
        const excelFiles = acceptedFiles.filter(f => f.name.endsWith('.xlsx') || f.name.endsWith('.xls') || f.name.endsWith('.csv'));
        setMergeFiles(prev => [...prev, ...excelFiles]);
    };
    
    const { getRootProps: getMergeProps, getInputProps: getMergeInputProps, isDragActive: mergeActive } = useDropzone({ 
        onDrop: onMergeDrop, 
        accept: {
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': ['.xlsx'],
            'application/vnd.ms-excel': ['.xls'],
            'text/csv': ['.csv']
        }
    });

    const executeMerge = async () => {
        if (mergeFiles.length === 0) return;
        setStatus('running');
        setProgressMsg('Merging files...');
        setMergeModalOpen(false);

        try {
            let combinedRows: any[][] = [];
            let firstFileHeaders: string[] = [];

            for (let i = 0; i < mergeFiles.length; i++) {
                setProgressMsg(`Merging file ${i + 1} of ${mergeFiles.length}...`);
                const file = mergeFiles[i];
                const arrayBuffer = await file.arrayBuffer();
                let workbook;
                
                if (file.name.toLowerCase().endsWith('.csv')) {
                    const decoder = new TextDecoder(csvEncoding);
                    workbook = read(decoder.decode(arrayBuffer), { type: 'string' });
                } else {
                    workbook = read(arrayBuffer, { type: 'array' });
                }
                
                const worksheet = workbook.Sheets[workbook.SheetNames[0]];
                const jsonData = utils.sheet_to_json(worksheet, { header: 1, defval: '' }) as any[][];
                
                if (jsonData.length === 0) continue;

                if (i === 0) {
                    firstFileHeaders = jsonData[0].map(String);
                    // Ensure unique headers
                    const uniqueHeaders: string[] = [];
                    firstFileHeaders.forEach((h, idx) => {
                        let newH = h || `Column ${idx+1}`;
                        let counter = 1;
                        while(uniqueHeaders.includes(newH)) {
                            newH = `${h}_${counter}`;
                            counter++;
                        }
                        uniqueHeaders.push(newH);
                    });
                    setHeaders(uniqueHeaders);
                    
                    const initFilters: Record<string, FieldFilterState> = {};
                    uniqueHeaders.forEach(h => {
                        initFilters[h] = { checked: true, rule: 'contains', keywords: [] };
                    });
                    setFieldFilters(initFilters);
                    
                    const dataRows = jsonData.slice(1).filter(r => r.some(c => c !== ''));
                    combinedRows = [...combinedRows, ...dataRows];
                } else {
                    const currentFileHeaders = jsonData[0].map(String);
                    const dataRows = jsonData.slice(1).filter(r => r.some(c => c !== ''));
                    
                    // Map headers to primary file
                    const mappedRows = dataRows.map(row => {
                        const mappedRow = new Array(firstFileHeaders.length).fill('');
                        currentFileHeaders.forEach((h, colIdx) => {
                            const primaryIdx = firstFileHeaders.indexOf(h);
                            if (primaryIdx !== -1) {
                                mappedRow[primaryIdx] = row[colIdx];
                            }
                        });
                        return mappedRow;
                    });
                    
                    combinedRows = [...combinedRows, ...mappedRows];
                }
            }

            setOriginalData(combinedRows);
            setTransformations([]);
            setFieldFilters(prev => {
                const resetFilters = { ...prev };
                Object.keys(resetFilters).forEach(h => resetFilters[h].keywords = []);
                return resetFilters;
            });
            applyPipeline(combinedRows, {}, []); // will reset filters properly
            setPage(1);
            setMergeFiles([]);
        } catch (e) {
            console.error(e);
            setStatus('error');
            setProgressMsg('Error merging files.');
        }
    };

    const executeSplit = async () => {
        if (splitRowsCount < 1 || filteredData.length === 0) return;
        setStatus('running');
        setSplitModalOpen(false);

        const indicesToKeep = headers.map((h, i) => fieldFilters[h].checked ? i : -1).filter(i => i !== -1);
        const exportHeaders = indicesToKeep.map(i => headers[i]);
        const exportRows = filteredData.map(row => indicesToKeep.map(i => row[i]));

        const totalRows = exportRows.length; 
        const chunks = Math.ceil(totalRows / splitRowsCount);
        setProgressMsg(`Splitting into ${chunks} files...`);

        try {
            for (let c = 0; c < chunks; c++) {
                const start = c * splitRowsCount;
                const end = Math.min(start + splitRowsCount, exportRows.length);
                const chunkData = [exportHeaders, ...exportRows.slice(start, end)];
                
                const newWs = utils.aoa_to_sheet(chunkData);
                const newWb = utils.book_new();
                utils.book_append_sheet(newWb, newWs, "Data");
                
                writeFile(newWb, `Split_Output_Part_${c+1}.xlsx`);
                
                await new Promise(r => setTimeout(r, 800));
            }
            setStatus('idle');
            setProgressMsg(`Success! Split data into ${chunks} files.`);
        } catch (e) {
            console.error(e);
            setStatus('error');
            setProgressMsg('Error splitting files.');
        }
    };

    const currentTableData = useMemo(() => {
        const start = (page - 1) * rowsPerPage;
        return filteredData.slice(start, start + rowsPerPage);
    }, [filteredData, page]);

    const totalPages = Math.ceil(filteredData.length / rowsPerPage) || 1;

    return (
        <div className="flex flex-col h-screen bg-slate-50 font-sans antialiased relative">
            {/* Top Bar */}
            <div className="bg-white border-b border-slate-200 px-4 py-3 flex items-center justify-between shadow-sm z-20 shrink-0">
                <div className="flex items-center gap-2 overflow-x-auto pe-4">
                    <input type="file" ref={fileInputRef} onChange={handleFileUpload} className="hidden" accept=".xlsx,.xls,.csv" />
                    
                    <select value={csvEncoding} onChange={(e) => setCsvEncoding(e.target.value)} className="h-8 text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200 rounded-md px-2 outline-none">
                        <option value="utf-8">UTF-8</option>
                        <option value="windows-1256">Windows-1256 (Arabic)</option>
                    </select>

                    <Button onClick={() => fileInputRef.current?.click()} size="sm" className="bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold border border-indigo-200 gap-1.5 shrink-0">
                        <UploadCloud className="w-4 h-4" /> Upload
                    </Button>

                    <div className="h-6 w-px bg-slate-200 mx-2 shrink-0" />

                    <Button onClick={handleApplyFilter} disabled={headers.length === 0} size="sm" className="bg-slate-800 text-white hover:bg-slate-700 font-bold gap-1.5 shrink-0">
                        <Filter className="w-4 h-4" /> Apply Pipeline
                    </Button>
                    <Button onClick={handleClearFilter} disabled={headers.length === 0} variant="outline" size="sm" className="font-bold gap-1.5 shrink-0">
                        <RefreshCw className="w-4 h-4" /> Clear Filters
                    </Button>
                    <Button onClick={handleDistinct} disabled={headers.length === 0} variant="outline" size="sm" className="font-bold shrink-0">
                        Distinct
                    </Button>
                    <Button onClick={handleClearAll} disabled={headers.length === 0} size="sm" className="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-bold shrink-0">
                        Clear All
                    </Button>

                    <div className="h-6 w-px bg-slate-200 mx-2 shrink-0" />

                    <Button onClick={() => exportData(false)} disabled={headers.length === 0} size="sm" className="bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold border border-emerald-200 gap-1.5 shrink-0">
                        <Save className="w-4 h-4" /> Save To File
                    </Button>
                    <Button onClick={() => exportData(true)} disabled={headers.length === 0} size="sm" className="bg-emerald-600 text-white hover:bg-emerald-700 font-bold gap-1.5 shrink-0">
                        <Download className="w-4 h-4" /> Save Checked
                    </Button>
                </div>

                <div className="flex items-center gap-4 shrink-0 ps-4 border-s border-slate-200">
                    <div className="w-48 h-2.5 bg-slate-100 rounded-full overflow-hidden shrink-0">
                        <div 
                            className="h-full bg-indigo-500 transition-all duration-300" 
                            style={{ width: `${progressPercent}%` }} 
                        />
                    </div>
                    <span className="text-xs font-bold text-slate-500 shrink-0 min-w-[100px] text-end">
                        Count = {filteredData.length}
                    </span>
                </div>
            </div>

            <div className="flex-1 flex overflow-hidden">
                {/* Left Panel */}
                <div className="w-80 bg-slate-50 border-e border-slate-200 flex flex-col z-10 shrink-0">
                    <div className="flex-1 overflow-y-auto p-4 space-y-4">
                        {headers.length === 0 ? (
                            <div className="text-center p-8 text-slate-400 font-medium text-sm">
                                Please upload a file to view and configure field filters.
                            </div>
                        ) : (
                            headers.map((h, i) => {
                                const filter = fieldFilters[h];
                                if (!filter) return null;
                                return (
                                    <div key={i} className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                                        <div className="bg-indigo-600 text-white px-3 py-2 flex items-center justify-between">
                                            <div className="flex items-center gap-2 min-w-0">
                                                <input 
                                                    type="checkbox" 
                                                    checked={filter.checked}
                                                    onChange={e => updateFilter(h, { checked: e.target.checked })}
                                                    className="rounded border-indigo-400 text-indigo-900 focus:ring-0 shrink-0"
                                                />
                                                <span className="text-xs font-bold truncate">{h}</span>
                                            </div>
                                        </div>
                                        <div className="p-3 space-y-3">
                                            <div className="grid grid-cols-2 gap-y-2 gap-x-1">
                                                {(['contains', 'equal', 'not_equal', 'must_exist', 'start_with', 'end_with'] as FilterRule[]).map(rule => (
                                                    <label key={rule} className="flex items-center gap-1.5 cursor-pointer">
                                                        <input 
                                                            type="radio" 
                                                            name={`rule-${h}`} 
                                                            checked={filter.rule === rule}
                                                            onChange={() => updateFilter(h, { rule })}
                                                            className="w-3.5 h-3.5 text-indigo-600 border-slate-300 focus:ring-indigo-500"
                                                        />
                                                        <span className="text-[10px] font-bold text-slate-700 uppercase tracking-tight">
                                                            {rule.replace('_', ' ')}
                                                        </span>
                                                    </label>
                                                ))}
                                            </div>
                                            
                                            <div className="flex items-center gap-1">
                                                <input 
                                                    type="text" 
                                                    id={`input-${h}`}
                                                    placeholder="Add keyword..."
                                                    className="flex-1 text-xs border border-slate-200 rounded-md p-1.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
                                                    onKeyDown={(e) => {
                                                        if (e.key === 'Enter') {
                                                            const val = e.currentTarget.value.trim();
                                                            if (val && !filter.keywords.includes(val)) {
                                                                updateFilter(h, { keywords: [...filter.keywords, val] });
                                                                e.currentTarget.value = '';
                                                            }
                                                        }
                                                    }}
                                                />
                                                <button 
                                                    onClick={() => {
                                                        const el = document.getElementById(`input-${h}`) as HTMLInputElement;
                                                        const val = el?.value.trim();
                                                        if (val && !filter.keywords.includes(val)) {
                                                            updateFilter(h, { keywords: [...filter.keywords, val] });
                                                            el.value = '';
                                                        }
                                                    }}
                                                    className="w-7 h-7 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-md flex items-center justify-center shrink-0 transition-colors"
                                                >
                                                    <Plus className="w-4 h-4" />
                                                </button>
                                                <button 
                                                    onClick={() => updateFilter(h, { keywords: [] })}
                                                    className="w-7 h-7 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-md flex items-center justify-center shrink-0 transition-colors"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </div>

                                            {filter.keywords.length > 0 && (
                                                <div className="h-24 overflow-y-auto border border-slate-100 rounded-md bg-slate-50 p-1 space-y-1">
                                                    {filter.keywords.map((kw, idx) => (
                                                        <div key={idx} className="flex justify-between items-center text-xs bg-white border border-slate-100 px-2 py-1 rounded">
                                                            <span className="truncate">{kw}</span>
                                                            <button 
                                                                onClick={() => {
                                                                    const newKw = [...filter.keywords];
                                                                    newKw.splice(idx, 1);
                                                                    updateFilter(h, { keywords: newKw });
                                                                }}
                                                                className="text-slate-400 hover:text-rose-500 ms-2"
                                                            >
                                                                <Minus className="w-3 h-3" />
                                                            </button>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                );
                            })
                        )}
                    </div>

                    <div className="p-4 bg-white border-t border-slate-200 shrink-0 space-y-4">
                        {transformations.length > 0 && (
                            <div className="space-y-2">
                                <h4 className="text-xs font-black text-emerald-700 uppercase flex items-center gap-1">
                                    <Settings className="w-3.5 h-3.5" /> Active Transformations
                                </h4>
                                <div className="space-y-1 max-h-24 overflow-y-auto border border-emerald-100 rounded p-1 bg-emerald-50/50">
                                    {transformations.map(t => (
                                        <div key={t.id} className="flex items-center justify-between bg-white text-[10px] p-1.5 rounded border border-emerald-100">
                                            <span className="truncate font-medium text-emerald-800">
                                                [{t.column}] {t.type} {t.text1} {t.text2 ? `-> ${t.text2}` : ''}
                                            </span>
                                            <button onClick={() => removeTransformation(t.id)} className="text-rose-400 hover:text-rose-600 ms-2 shrink-0">
                                                <Minus className="w-3 h-3" />
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        <div className="space-y-2 pt-3 border-t border-slate-100">
                            <h4 className="text-xs font-black text-slate-800 uppercase flex items-center gap-1">
                                <Wand2 className="w-3.5 h-3.5" /> Find & Replace
                            </h4>
                            <div className="flex gap-2">
                                <input type="text" placeholder="Find..." value={replaceFindText} onChange={e => setReplaceFindText(e.target.value)} className="flex-1 min-w-0 text-xs border border-slate-200 rounded p-1.5" />
                                <input type="text" placeholder="Replace..." value={replaceWithText} onChange={e => setReplaceWithText(e.target.value)} className="flex-1 min-w-0 text-xs border border-slate-200 rounded p-1.5" />
                            </div>
                            <div className="flex gap-2">
                                <select value={replaceColumn} onChange={e => setReplaceColumn(e.target.value)} className="flex-1 text-xs border border-slate-200 rounded p-1.5 bg-white">
                                    <option value="">Column...</option>
                                    {headers.map(h => <option key={h} value={h}>{h}</option>)}
                                </select>
                                <Button onClick={applyReplace} disabled={!replaceColumn || !replaceFindText} size="sm" className="h-auto py-1 bg-indigo-600 hover:bg-indigo-700 text-[11px]">Replace</Button>
                            </div>
                        </div>

                        <div className="space-y-2 pt-3 border-t border-slate-100">
                            <h4 className="text-xs font-black text-slate-800 uppercase flex items-center gap-1">
                                <Plus className="w-3.5 h-3.5" /> Addition (Prefix/Suffix)
                            </h4>
                            <div className="flex gap-2">
                                <input type="text" placeholder="Text..." value={additionText} onChange={e => setAdditionText(e.target.value)} className="flex-1 min-w-0 text-xs border border-slate-200 rounded p-1.5" />
                                <select value={additionPos} onChange={e => setAdditionPos(e.target.value as any)} className="w-20 text-xs border border-slate-200 rounded p-1.5 bg-white shrink-0">
                                    <option value="Left">Left</option>
                                    <option value="Right">Right</option>
                                    <option value="Double">Double</option>
                                </select>
                            </div>
                            <div className="flex gap-2">
                                <select value={additionColumn} onChange={e => setAdditionColumn(e.target.value)} className="flex-1 text-xs border border-slate-200 rounded p-1.5 bg-white">
                                    <option value="">Column...</option>
                                    {headers.map(h => <option key={h} value={h}>{h}</option>)}
                                </select>
                                <Button onClick={applyAddition} disabled={!additionColumn || !additionText} size="sm" className="h-auto py-1 bg-indigo-600 hover:bg-indigo-700 text-[11px]">Add</Button>
                            </div>
                        </div>

                        <div className="space-y-2 pt-3 border-t border-slate-100">
                            <h4 className="text-xs font-black text-slate-800 uppercase flex items-center gap-1">
                                <Layers className="w-3.5 h-3.5" /> More Functions
                            </h4>
                            <div className="flex gap-2">
                                <Button onClick={() => setMergeModalOpen(true)} size="sm" className="flex-1 bg-slate-800 hover:bg-slate-700 text-white font-bold h-auto py-2 gap-1.5">
                                    <Layers className="w-4 h-4" /> Merge
                                </Button>
                                <Button onClick={() => setSplitModalOpen(true)} disabled={headers.length === 0} size="sm" className="flex-1 bg-slate-800 hover:bg-slate-700 text-white font-bold h-auto py-2 gap-1.5">
                                    <SplitSquareHorizontal className="w-4 h-4" /> Split
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Right Area (Data Grid) */}
                <div className="flex-1 bg-white flex flex-col min-w-0">
                    <div className="flex-1 overflow-auto bg-white">
                        {headers.length === 0 ? (
                            <div className="h-full flex flex-col items-center justify-center text-slate-400">
                                <FileSpreadsheet className="w-16 h-16 text-slate-200 mb-4" />
                                <p className="font-bold text-lg text-slate-300">No Data Loaded</p>
                                <p className="text-sm">Click "Upload" to select an Excel or CSV file.</p>
                            </div>
                        ) : (
                            <table className="w-full text-start border-collapse text-xs whitespace-nowrap">
                                <thead>
                                    <tr>
                                        <th className="sticky top-0 start-0 z-20 bg-slate-100 border-b border-e border-slate-200 px-3 py-2 font-bold text-slate-500 w-12 text-center shadow-[0_1px_0_#e2e8f0]">#</th>
                                        {headers.map((h, i) => (
                                            <th key={i} className="sticky top-0 z-10 bg-slate-100 border-b border-e border-slate-200 px-3 py-2 font-bold text-slate-700 shadow-[0_1px_0_#e2e8f0]">
                                                {h}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {currentTableData.map((row, rIdx) => {
                                        const globalIdx = (page - 1) * rowsPerPage + rIdx + 1;
                                        return (
                                            <tr key={rIdx} className="hover:bg-slate-50 transition-colors">
                                                <td className="sticky start-0 z-10 bg-white border-b border-e border-slate-200 px-3 py-1.5 text-center text-slate-400 font-medium group-hover:bg-slate-50">
                                                    {globalIdx}
                                                </td>
                                                {headers.map((_, cIdx) => (
                                                    <td key={cIdx} className="border-b border-e border-slate-100 px-3 py-1.5 text-slate-600 max-w-xs truncate" title={String(row[cIdx] || '')}>
                                                        {row[cIdx] !== undefined ? String(row[cIdx]) : ''}
                                                    </td>
                                                ))}
                                            </tr>
                                        );
                                    })}
                                    {currentTableData.length === 0 && (
                                        <tr>
                                            <td colSpan={headers.length + 1} className="text-center py-8 text-slate-400 font-medium border-b border-slate-100">
                                                No rows match the current filters.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        )}
                    </div>

                    {/* Pagination Bar */}
                    {headers.length > 0 && (
                        <div className="bg-white border-t border-slate-200 p-2 flex items-center justify-between shrink-0 shadow-[0_-4px_10px_rgba(0,0,0,0.02)] z-10">
                            <span className="text-xs font-bold text-slate-500 ms-2">
                                Showing {(page - 1) * rowsPerPage + 1} to {Math.min(page * rowsPerPage, filteredData.length)} of {filteredData.length} entries
                            </span>
                            <div className="flex items-center gap-1">
                                <Button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1} variant="outline" size="sm" className="h-8 px-2 text-slate-600">
                                    <ChevronLeft className="w-4 h-4" />
                                </Button>
                                <span className="text-xs font-bold text-slate-700 px-3 py-1 bg-slate-100 rounded-md">
                                    Page {page} of {totalPages}
                                </span>
                                <Button onClick={() => setPage(p => Math.min(totalPages, p + 1))} disabled={page === totalPages} variant="outline" size="sm" className="h-8 px-2 text-slate-600">
                                    <ChevronRight className="w-4 h-4" />
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Merge Sub-Form Modal */}
            {mergeModalOpen && (
                <div className="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-200">
                    <div className="bg-white rounded-2xl shadow-xl w-full max-w-2xl flex flex-col max-h-[90vh] overflow-hidden animate-in zoom-in-95 duration-200">
                        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                            <h3 className="text-lg font-black text-slate-900 flex items-center gap-2">
                                <Layers className="w-5 h-5 text-indigo-600" /> Merge Files
                            </h3>
                            <button onClick={() => setMergeModalOpen(false)} className="text-slate-400 hover:text-slate-700">
                                <XCircle className="w-6 h-6" />
                            </button>
                        </div>
                        <div className="p-6 overflow-y-auto flex-1 space-y-4">
                            <div 
                                {...getMergeProps()} 
                                className={`border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all duration-200 ${
                                    mergeActive ? 'border-indigo-500 bg-indigo-50' : 'border-slate-300 bg-white hover:border-indigo-400 hover:bg-slate-50'
                                }`}
                            >
                                <input {...getMergeInputProps()} />
                                <div className="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <Download className="w-6 h-6 text-indigo-600" />
                                </div>
                                <h3 className="text-sm font-bold text-slate-800 mb-1">
                                    {mergeActive ? 'Drop files here...' : 'Drag & drop Excel or CSV files to merge'}
                                </h3>
                                <p className="text-xs font-medium text-slate-500">Files will be merged vertically. Columns will be correctly mapped by Header Name.</p>
                            </div>

                            {mergeFiles.length > 0 && (
                                <div className="space-y-2">
                                    <h4 className="text-xs font-bold text-slate-500 uppercase">Selected Files ({mergeFiles.length})</h4>
                                    <div className="max-h-40 overflow-y-auto space-y-1">
                                        {mergeFiles.map((f, i) => (
                                            <div key={i} className="flex items-center justify-between bg-slate-50 px-3 py-2 rounded-lg border border-slate-100">
                                                <div className="flex items-center gap-2">
                                                    <FileIcon className="w-4 h-4 text-slate-400" />
                                                    <span className="text-xs font-bold text-slate-700">{f.name}</span>
                                                </div>
                                                <button onClick={() => setMergeFiles(prev => prev.filter((_, idx) => idx !== i))} className="text-rose-400 hover:text-rose-600">
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                    <Button onClick={() => setMergeFiles([])} variant="ghost" size="sm" className="text-rose-500 h-6 px-2 text-xs">Clear all</Button>
                                </div>
                            )}
                        </div>
                        <div className="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-2">
                            <Button onClick={() => setMergeModalOpen(false)} variant="outline">Cancel</Button>
                            <Button onClick={executeMerge} disabled={mergeFiles.length === 0} className="bg-indigo-600 hover:bg-indigo-700 text-white gap-1.5">
                                <Layers className="w-4 h-4" /> Merge & Load
                            </Button>
                        </div>
                    </div>
                </div>
            )}

            {/* Split Sub-Form Modal */}
            {splitModalOpen && (
                <div className="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-200">
                    <div className="bg-white rounded-2xl shadow-xl w-full max-w-sm flex flex-col overflow-hidden animate-in zoom-in-95 duration-200">
                        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                            <h3 className="text-lg font-black text-slate-900 flex items-center gap-2">
                                <SplitSquareHorizontal className="w-5 h-5 text-indigo-600" /> Split Data
                            </h3>
                            <button onClick={() => setSplitModalOpen(false)} className="text-slate-400 hover:text-slate-700">
                                <XCircle className="w-6 h-6" />
                            </button>
                        </div>
                        <div className="p-6 space-y-4">
                            <p className="text-sm text-slate-500 font-medium">
                                The current filtered dataset ({filteredData.length} rows) will be divided into multiple smaller Excel files.
                            </p>
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">Max Rows Per File</label>
                                <input 
                                    type="number" 
                                    min="1"
                                    value={splitRowsCount}
                                    onChange={e => setSplitRowsCount(parseInt(e.target.value) || 1000)}
                                    className="w-full text-sm border-slate-200 rounded-lg p-2.5 outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                />
                            </div>
                            <div className="bg-indigo-50 text-indigo-700 text-xs p-3 rounded-lg font-medium border border-indigo-100">
                                Expected Output: <strong className="font-black">{Math.ceil(filteredData.length / splitRowsCount) || 0}</strong> files
                            </div>
                        </div>
                        <div className="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-2">
                            <Button onClick={() => setSplitModalOpen(false)} variant="outline">Cancel</Button>
                            <Button onClick={executeSplit} disabled={filteredData.length === 0} className="bg-indigo-600 hover:bg-indigo-700 text-white gap-1.5">
                                <Download className="w-4 h-4" /> Download Parts
                            </Button>
                        </div>
                    </div>
                </div>
            )}

            {/* Status Toast Overlay */}
            {progressMsg && status !== 'idle' && (
                <div className="fixed bottom-6 end-6 bg-slate-800 text-white px-4 py-3 rounded-xl shadow-xl flex items-center gap-3 animate-in slide-in-from-bottom-4 z-50">
                    {status === 'running' && <RefreshCw className="w-4 h-4 animate-spin text-indigo-400" />}
                    {status === 'done' && <CheckCircle className="w-4 h-4 text-emerald-400" />}
                    {status === 'error' && <XCircle className="w-4 h-4 text-rose-400" />}
                    <span className="text-sm font-bold">{progressMsg}</span>
                </div>
            )}
        </div>
    );
}
