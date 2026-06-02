import React, { useState, useRef } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { ArrowLeft, FolderOpen, File, FileText, Image as ImageIcon, MoreVertical, Download, Trash2, Edit, Plus, Upload, FileArchive } from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { ConfirmModal, PromptModal } from '@/Components/ui/ConfirmModal';
import { useToast } from '@/Components/ui/use-toast';
import { cn } from '@/lib/utils';

export default function Files({ user, files = [], folders = [], breadcrumbs = [], current_folder = '' }) {
    const { toast } = useToast();
    const fileInputRef = useRef(null);

    // Modal States
    const [isNewFolderOpen, setIsNewFolderOpen] = useState(false);
    const [renameItem, setRenameItem] = useState(null); // { path: string, name: string, type: 'folder' | 'file' }
    const [deleteItem, setDeleteItem] = useState(null); // { path: string, name: string, type: 'folder' | 'file' }
    const [isLoading, setIsLoading] = useState(false);

    // --- Navigation ---
    const navigateTo = (folderPath) => {
        router.get(route('admin.users.files.index', user.id), { folder: folderPath }, { preserveState: true });
    };

    // --- Actions ---
    const handleUploadClick = () => {
        if (fileInputRef.current) {
            fileInputRef.current.click();
        }
    };

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (!file) return;

        setIsLoading(true);
        router.post(route('admin.users.files.upload', user.id), {
            file: file,
            folder: current_folder,
        }, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                toast({ title: 'File uploaded successfully' });
            },
            onError: (errors) => {
                toast({ title: 'Upload failed', description: errors.file || 'An error occurred', variant: 'destructive' });
            },
            onFinish: () => {
                setIsLoading(false);
                if (fileInputRef.current) fileInputRef.current.value = '';
            }
        });
    };

    const handleNewFolder = (folderName) => {
        setIsLoading(true);
        router.post(route('admin.users.files.folder', user.id), {
            name: folderName,
            parent: current_folder,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                toast({ title: 'Folder created' });
                setIsNewFolderOpen(false);
            },
            onFinish: () => setIsLoading(false)
        });
    };

    const handleRename = (newName) => {
        if (!renameItem) return;
        setIsLoading(true);
        router.post(route('admin.users.files.rename', user.id), {
            path: renameItem.path,
            new_name: newName,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                toast({ title: 'Item renamed successfully' });
                setRenameItem(null);
            },
            onFinish: () => setIsLoading(false)
        });
    };

    const handleDelete = () => {
        if (!deleteItem) return;
        setIsLoading(true);
        router.delete(route('admin.users.files.delete', user.id), {
            data: { paths: [deleteItem.path] },
            preserveScroll: true,
            onSuccess: () => {
                toast({ title: 'Item deleted successfully' });
                setDeleteItem(null);
            },
            onFinish: () => setIsLoading(false)
        });
    };

    const handleDownload = (path) => {
        // Construct the URL directly and open it to trigger download
        const url = new URL(route('admin.users.files.download', user.id), window.location.origin);
        url.searchParams.append('paths[]', path);
        window.open(url.toString(), '_blank');
    };

    const handleEdit = (path) => {
        router.get(route('admin.users.files.edit', user.id), { path });
    };

    // --- Helpers ---
    const getFileIcon = (ext) => {
        if (['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'].includes(ext)) return <ImageIcon className="h-8 w-8 text-blue-500" />;
        if (['zip', 'rar', 'tar', 'gz'].includes(ext)) return <FileArchive className="h-8 w-8 text-amber-500" />;
        if (['txt', 'md', 'doc', 'docx', 'pdf'].includes(ext)) return <FileText className="h-8 w-8 text-slate-500" />;
        return <File className="h-8 w-8 text-slate-400" />;
    };

    const isTextFile = (ext) => {
        return ['txt', 'md', 'json', 'env', 'log', 'js', 'jsx', 'ts', 'tsx', 'php', 'html', 'css', 'xml', 'sql', 'sh', 'csv'].includes(ext);
    };

    const formatSize = (bytes) => {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    return (
        <AdminSidebarLayout title={__('general.user_files', 'User Files')} header="User Files">
            <Head title={`Files - ${user.name}`} />

            <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center space-x-4">
                        <Link href={route('admin.users.show', user.id)} className="text-gray-500 hover:text-gray-700">
                            <ArrowLeft className="h-6 w-6" />
                        </Link>
                        <div>
                            <h2 className="text-2xl font-bold text-gray-900">{__('general.files_gallery', 'Files Gallery')}</h2>
                            <p className="text-sm text-gray-500">Manage files and documents for {user.name}</p>
                        </div>
                    </div>
                    
                    <div className="flex items-center gap-2">
                        <Button variant="outline" onClick={() => setIsNewFolderOpen(true)} disabled={isLoading}>
                            <Plus className="h-4 w-4 mr-2" />
                            New Folder
                        </Button>
                        <Button onClick={handleUploadClick} disabled={isLoading}>
                            <Upload className="h-4 w-4 mr-2" />
                            Upload
                        </Button>
                        <input 
                            type="file" 
                            ref={fileInputRef} 
                            className="hidden" 
                            onChange={handleFileChange} 
                        />
                    </div>
                </div>

                <Card className="shadow-sm border-slate-200">
                    <div className="border-b border-slate-200 bg-slate-50 px-4 py-3 flex items-center overflow-x-auto">
                        <div className="flex items-center text-sm font-medium text-slate-600 whitespace-nowrap">
                            {breadcrumbs.map((crumb, idx) => (
                                <React.Fragment key={idx}>
                                    <button 
                                        onClick={() => navigateTo(crumb.path)}
                                        className={cn(
                                            "hover:text-blue-600 transition-colors",
                                            idx === breadcrumbs.length - 1 ? "text-blue-600 font-semibold cursor-default" : ""
                                        )}
                                        disabled={idx === breadcrumbs.length - 1 || isLoading}
                                    >
                                        {crumb.name}
                                    </button>
                                    {idx < breadcrumbs.length - 1 && <span className="mx-2 text-slate-400">/</span>}
                                </React.Fragment>
                            ))}
                        </div>
                    </div>
                    
                    <CardContent className="p-0">
                        {folders.length === 0 && files.length === 0 ? (
                            <div className="p-12 text-center text-slate-500">
                                <FolderOpen className="h-12 w-12 mx-auto text-slate-300 mb-3" />
                                <p>This folder is empty.</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-1 p-4">
                                {/* Folders */}
                                {folders.map((folder) => (
                                    <div 
                                        key={folder.path}
                                        className="group relative flex flex-col items-center p-4 rounded-lg hover:bg-slate-50 border border-transparent hover:border-slate-200 transition-all text-center cursor-pointer"
                                        onDoubleClick={() => navigateTo(folder.path)}
                                    >
                                        <div className="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="icon" className="h-8 w-8" onClick={(e) => e.stopPropagation()}>
                                                        <MoreVertical className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem onClick={(e) => { e.stopPropagation(); handleDownload(folder.path); }}>
                                                        <Download className="h-4 w-4 mr-2" /> Download Zip
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem onClick={(e) => { e.stopPropagation(); setRenameItem({ ...folder, type: 'folder' }); }}>
                                                        <Edit className="h-4 w-4 mr-2" /> Rename
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem className="text-red-600" onClick={(e) => { e.stopPropagation(); setDeleteItem({ ...folder, type: 'folder' }); }}>
                                                        <Trash2 className="h-4 w-4 mr-2" /> Delete
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
                                        <FolderOpen className="h-12 w-12 text-blue-400 mb-2" />
                                        <span className="text-sm font-medium text-slate-700 truncate w-full" title={folder.name}>
                                            {folder.name}
                                        </span>
                                    </div>
                                ))}

                                {/* Files */}
                                {files.map((file) => (
                                    <div 
                                        key={file.path}
                                        className="group relative flex flex-col items-center p-4 rounded-lg hover:bg-slate-50 border border-transparent hover:border-slate-200 transition-all text-center cursor-pointer"
                                        onDoubleClick={() => isTextFile(file.ext) ? handleEdit(file.path) : handleDownload(file.path)}
                                    >
                                        <div className="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="icon" className="h-8 w-8">
                                                        <MoreVertical className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem onClick={() => handleDownload(file.path)}>
                                                        <Download className="h-4 w-4 mr-2" /> Download
                                                    </DropdownMenuItem>
                                                    {isTextFile(file.ext) && (
                                                        <DropdownMenuItem onClick={() => handleEdit(file.path)}>
                                                            <FileText className="h-4 w-4 mr-2" /> Edit in Editor
                                                        </DropdownMenuItem>
                                                    )}
                                                    <DropdownMenuItem onClick={() => setRenameItem({ ...file, type: 'file' })}>
                                                        <Edit className="h-4 w-4 mr-2" /> Rename
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem className="text-red-600" onClick={() => setDeleteItem({ ...file, type: 'file' })}>
                                                        <Trash2 className="h-4 w-4 mr-2" /> Delete
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
                                        <div className="h-12 w-12 flex items-center justify-center mb-2">
                                            {getFileIcon(file.ext)}
                                        </div>
                                        <span className="text-sm font-medium text-slate-700 truncate w-full mb-1" title={file.name}>
                                            {file.name}
                                        </span>
                                        <span className="text-xs text-slate-400">
                                            {formatSize(file.size)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* New Folder Modal */}
            <PromptModal
                isOpen={isNewFolderOpen}
                title="Create New Folder"
                label="Folder Name"
                placeholder="e.g. Documents"
                onConfirm={handleNewFolder}
                onCancel={() => setIsNewFolderOpen(false)}
                loading={isLoading}
            />

            {/* Rename Modal */}
            <PromptModal
                isOpen={!!renameItem}
                title={`Rename ${renameItem?.type === 'folder' ? 'Folder' : 'File'}`}
                label="New Name"
                placeholder={renameItem?.name || ''}
                onConfirm={handleRename}
                onCancel={() => setRenameItem(null)}
                loading={isLoading}
            />

            {/* Delete Confirmation */}
            <ConfirmModal
                isOpen={!!deleteItem}
                title={`Delete ${deleteItem?.type === 'folder' ? 'Folder' : 'File'}`}
                description={`Are you sure you want to permanently delete "${deleteItem?.name}"? This action cannot be undone.`}
                variant="danger"
                confirmLabel="Delete"
                onConfirm={handleDelete}
                onCancel={() => setDeleteItem(null)}
                loading={isLoading}
            />
        </AdminSidebarLayout>
    );
}
