import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ArrowLeft, FolderOpen } from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/card';

export default function Files({ client }) {
    return (
        <AdminLayout>
            <Head title={`Files - ${client.name}`} />

            <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link href={`/admin/users/${client.id}`} className="text-gray-500 hover:text-gray-700">
                            <ArrowLeft className="h-6 w-6" />
                        </Link>
                        <div>
                            <h2 className="text-2xl font-bold text-gray-900">Files Gallery</h2>
                            <p className="text-sm text-gray-500">Manage files and documents for {client.name}</p>
                        </div>
                    </div>
                </div>

                <Card>
                    <CardContent className="p-12 text-center">
                        <div className="w-16 h-16 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <FolderOpen size={32} />
                        </div>
                        <h3 className="text-xl font-bold text-gray-900 mb-2">Media Manager Upgrade in Progress</h3>
                        <p className="text-gray-500 max-w-md mx-auto">
                            The advanced file explorer is currently being migrated to our new React architecture. 
                            File management capabilities for this user will be available shortly.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
