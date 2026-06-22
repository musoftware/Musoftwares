import React from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import { Head } from '@inertiajs/react';
import PipelineBoard from '../Components/Kanban/PipelineBoard';
import { __ } from '@/lib/i18n';

export default function PipelinesIndex() {
    return (
        <CrmLayout title={__('crm.pipelines')} activeMenu="pipelines">
            <Head title={__('crm.pipelines')} />
            <div className="p-6 h-[calc(100vh-100px)] flex flex-col">
                <div className="mb-6 flex justify-end gap-4 items-center shrink-0">
                    <h1 className="text-2xl font-bold">{__('crm.pipelines')}</h1>
                </div>

                <div className="flex-1 overflow-hidden">
                    <PipelineBoard />
                </div>
            </div>
        </CrmLayout>
    );
}
