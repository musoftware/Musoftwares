import React from 'react';
import { Head } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { MonitorSmartphone } from 'lucide-react';

const __ = (key: string) => key;

export default function Index() {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('pos');

    return (
        <ERPLayout
            title={__('POS System')}
            workspaceName={workspaceName}
            tenantId={tenantId}
            menuItems={menuItems}
            lockedAddons={lockedAddons}
        >
            <Head title={__('POS System')} />
            
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <MonitorSmartphone className="h-16 w-16 text-gray-400 mb-6" />
                                <h3 className="text-xl font-medium text-gray-900 mb-2">
                                    {__('POS System Initialized')}
                                </h3>
                                <p className="text-sm text-gray-500 max-w-md">
                                    {__('The POS system module has been activated successfully. Checkout capabilities, barcode scanning, and receipt management will be added here in the next phase.')}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
