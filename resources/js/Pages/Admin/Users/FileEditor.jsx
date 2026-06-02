import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import Editor from '@monaco-editor/react';
import { ArrowLeft, Save } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { useToast } from '@/Components/ui/use-toast';

export default function FileEditor({ user, file }) {
    const { toast } = useToast();
    const [content, setContent] = useState(file.content);
    const [isSaving, setIsSaving] = useState(false);

    // Determine language based on extension
    const getLanguage = (filename) => {
        const ext = filename.split('.').pop().toLowerCase();
        switch (ext) {
            case 'js':
            case 'jsx':
                return 'javascript';
            case 'ts':
            case 'tsx':
                return 'typescript';
            case 'json':
                return 'json';
            case 'html':
                return 'html';
            case 'css':
                return 'css';
            case 'php':
                return 'php';
            case 'md':
                return 'markdown';
            case 'sql':
                return 'sql';
            case 'xml':
                return 'xml';
            case 'sh':
            case 'env':
                return 'shell';
            default:
                return 'plaintext';
        }
    };

    const handleSave = () => {
        setIsSaving(true);
        router.post(route('admin.users.files.updateContent', user.id), {
            path: file.path,
            content: content
        }, {
            preserveScroll: true,
            onSuccess: () => {
                toast({ title: __('general.file_saved_successfully', 'File saved successfully.') });
            },
            onError: () => {
                toast({ title: 'Failed to save', variant: 'destructive' });
            },
            onFinish: () => setIsSaving(false)
        });
    };

    return (
        <AdminSidebarLayout title={__('general.file_editor', 'File Editor')} header="File Editor">
            <Head title={`Editing ${file.name} - ${user.name}`} />

            <div className="flex flex-col h-[calc(100vh-100px)]">
                {/* Toolbar */}
                <div className="flex items-center justify-between px-6 py-4 bg-white border-b border-slate-200 shadow-sm">
                    <div className="flex items-center space-x-4">
                        <Link 
                            href={route('admin.users.files.index', { userId: user.id, folder: file.folder_id })} 
                            className="text-slate-500 hover:text-slate-700 transition-colors"
                        >
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <div>
                            <h2 className="text-lg font-semibold text-slate-800">{file.name}</h2>
                            <p className="text-xs text-slate-500">{user.name}</p>
                        </div>
                    </div>
                    
                    <div>
                        <Button onClick={handleSave} disabled={isSaving || content === file.content}>
                            <Save className="h-4 w-4 mr-2" />
                            {isSaving ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </div>
                </div>

                {/* Editor Container */}
                <div className="flex-1 w-full bg-slate-900">
                    <Editor
                        height="100%"
                        language={getLanguage(file.name)}
                        theme="vs-dark"
                        value={content}
                        onChange={(value) => setContent(value || '')}
                        options={{
                            minimap: { enabled: false },
                            fontSize: 14,
                            wordWrap: 'on',
                            scrollBeyondLastLine: false,
                            automaticLayout: true,
                        }}
                    />
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
