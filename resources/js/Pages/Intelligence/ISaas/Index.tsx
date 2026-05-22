import React, { useState } from 'react';
import axios from 'axios';
import { Head, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { AlertCircle, UploadCloud, Download } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from "@/Components/ui/alert";
import { useToast } from "@/Components/ui/use-toast";

export default function ISaasIndex() {
    const { toast } = useToast();
    const { flash } = usePage().props as any;
    const { data, setData, post, processing, errors, reset } = useForm({
        file: null as File | null,
    });

    const submit = async (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!data.file) {
            toast({
                title: "File required",
                description: "Please select a file to upload first.",
                variant: "destructive"
            });
            return;
        }

        const formData = new FormData();
        formData.append('file', data.file);

        try {
            const response = await axios.post(route('fbmb.process'), formData, {
                responseType: 'blob'
            });

            // Handle file download
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', 'isaas_results.csv');
            document.body.appendChild(link);
            link.click();
            link.remove();
            
            toast({
                title: "Success",
                description: "File processed and downloaded successfully.",
            });
            reset('file');
        } catch (error: any) {
            let message = "An error occurred while processing your file.";
            if (error.response && error.response.data && error.response.data instanceof Blob) {
                // Try to parse the blob error
                try {
                    const text = await error.response.data.text();
                    const json = JSON.parse(text);
                    if (json.message) message = json.message;
                } catch(e) {}
            }
            toast({
                title: "Error processing file",
                description: message,
                variant: "destructive"
            });
        }
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">iSAAS Database Lookup</h2>}
        >
            <Head title="iSAAS Database Lookup" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold tracking-tight text-slate-900">iSAAS Database Lookup</h1>
                        <p className="text-sm text-slate-500 mt-2">
                            Upload a CSV or TXT file containing Facebook IDs to find their corresponding Mobile Numbers. 
                        </p>
                        <p className="text-sm font-semibold text-indigo-600 mt-1">
                            Note: 1 Point (Credit) will be deducted per successful match.
                        </p>
                    </div>

                    {(flash?.error || errors.file) && (
                        <Alert variant="destructive" className="mb-6">
                            <AlertCircle className="h-4 w-4" />
                            <AlertTitle>Error</AlertTitle>
                            <AlertDescription>
                                {flash?.error || errors.file}
                            </AlertDescription>
                        </Alert>
                    )}

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
                        <div className="p-6 bg-white border-b border-slate-200">
                            <form onSubmit={submit} className="space-y-6">
                                <div>
                                    <Label htmlFor="file" className="text-base">Upload File (TXT, CSV)</Label>
                                    <div className="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors">
                                        <div className="space-y-1 text-center">
                                            <UploadCloud className="mx-auto h-12 w-12 text-slate-400" />
                                            <div className="flex text-sm text-slate-600 justify-center">
                                                <label
                                                    htmlFor="file-upload"
                                                    className="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500"
                                                >
                                                    <span>Upload a file</span>
                                                    <Input
                                                        id="file-upload"
                                                        name="file"
                                                        type="file"
                                                        className="sr-only"
                                                        accept=".txt,.csv"
                                                        onChange={(e) => setData('file', e.target.files ? e.target.files[0] : null)}
                                                    />
                                                </label>
                                                <p className="pl-1">or drag and drop</p>
                                            </div>
                                            <p className="text-xs text-slate-500">
                                                {data.file ? (
                                                    <span className="font-semibold text-indigo-600">Selected: {data.file.name}</span>
                                                ) : (
                                                    "TXT, CSV up to 10MB"
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-center justify-end">
                                    <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                                        <Download className="w-4 h-4 mr-2" />
                                        {processing ? 'Processing...' : 'Process and Lookup'}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
