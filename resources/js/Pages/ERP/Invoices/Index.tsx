import React, { useState, useEffect, useRef } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import axios from 'axios';
import AdminNotesPanel from '@/Components/AdminNotesPanel';

export default function Index({ invoices }: any) {
    const { auth } = usePage().props;
    const [timer, setTimer] = useState(0);
    const [timerState, setTimerState] = useState('stopped');
    const intervalRef = useRef<any>(null);
    const pollingIntervalRef = useRef<any>(null);

    const fetchTimerState = async () => {
        // Assume itemId = 1 for the scope of this invoice demo
        const itemId = 1;
        try {
            const res = await axios.get(`/api/timer/${itemId}`);
            setTimer(res.data.duration_seconds || 0);
            setTimerState(res.data.stopped_at ? 'stopped' : 'running');
        } catch (err) {
            console.error("Failed to fetch timer state", err);
        }
    };

    useEffect(() => {
        fetchTimerState();

        // Sync with server every 30s using polling instead of WebSockets
        pollingIntervalRef.current = setInterval(() => {
            fetchTimerState();
        }, 30000);

        return () => {
            if (pollingIntervalRef.current) clearInterval(pollingIntervalRef.current);
        };
    }, []);

    useEffect(() => {
        if (timerState === 'running') {
            intervalRef.current = setInterval(() => {
                setTimer((prev) => prev + 1);
            }, 1000);
        } else {
            if (intervalRef.current) clearInterval(intervalRef.current);
        }

        return () => {
            if (intervalRef.current) clearInterval(intervalRef.current);
        };
    }, [timerState]);

    const formatTime = (seconds: number) => {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Invoices
                </h2>
            }
        >
            <Head title="Invoices" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6 flex justify-between items-center">
                        <h3 className="text-lg font-bold">Active Timer</h3>
                        <div className="flex items-center gap-4">
                            <span className="text-sm text-gray-500">Status: {timerState}</span>
                            <span className="text-2xl font-mono bg-gray-100 px-4 py-2 rounded-lg">
                                {formatTime(timer)}
                            </span>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 className="text-lg font-bold mb-4">Invoices List</h3>
                        <ul>
                            {invoices?.data?.map((invoice: any) => (
                                <li key={invoice.id} className="py-2 border-b">
                                    Invoice #{invoice.number} - {invoice.client?.name}
                                </li>
                            )) || <li className="py-2 text-gray-500">No invoices available</li>}
                        </ul>
                    </div>

                    <div className="mt-8">
                        <AdminNotesPanel noteableType="invoice" noteableId={1} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
