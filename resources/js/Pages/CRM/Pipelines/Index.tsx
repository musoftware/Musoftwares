import React from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { Head } from '@inertiajs/react';
import { KanbanBoard } from '../Components/KanbanBoard';
import { __ } from '@/lib/i18n';

export default function PipelinesIndex({ pipelines }: { pipelines: any[] }) {
    return (
        <ERPLayout title="Pipelines">
            <Head title={__('crm.pipelines')} />
            <div className="p-6">
                <div className="mb-6 flex justify-between items-center">
                    <h1 className="text-2xl font-bold">{__('crm.pipelines')}</h1>
                    {/* Action button to create pipeline */}
                </div>

                <div className="mt-4">
                    {pipelines.length === 0 ? (
                        <div className="text-center py-10 bg-white rounded shadow">
                            <p className="text-gray-500">{__('crm.no_pipelines_found')}</p>
                        </div>
                    ) : (
                        pipelines.map((pipeline) => (
                            <div key={pipeline.id} className="mb-10">
                                <h2 className="text-xl font-semibold mb-4">{pipeline.name}</h2>
                                <KanbanBoard pipeline={pipeline} />
                            </div>
                        ))
                    )}
                </div>
            </div>
        </ERPLayout>
    );
}
