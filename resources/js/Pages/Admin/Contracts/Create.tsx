import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { FileText, ArrowLeft } from 'lucide-react';

export default function Create() {
    return (
        <AdminSidebarLayout title="Create Contract" header="Create Contract">
            <div className="mb-6 flex items-center justify-between">
                <Link href={route('admin.contracts.index')} className="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back to Contracts
                </Link>
            </div>

            <div className="rounded-lg bg-white p-12 text-center shadow">
                <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 mb-4">
                    <FileText className="h-8 w-8 text-blue-600" />
                </div>
                <h3 className="mb-2 text-lg font-medium text-gray-900">Contract Wizard Coming Soon</h3>
                <p className="mb-6 text-sm text-gray-500 max-w-md mx-auto">
                    We are currently building a comprehensive contract creation wizard that will allow you to generate proposals, set terms, and send them directly to clients for signature.
                </p>
                <div className="flex justify-center space-x-4">
                    <Link href={route('admin.calculator.index')}>
                        <Button variant="outline">Go to Price Calculator</Button>
                    </Link>
                    <Link href={route('admin.contracts.index')}>
                        <Button>Back to Contracts Manager</Button>
                    </Link>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
