import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import confetti from 'canvas-confetti';

export default function Onboarding() {
    const [step, setStep] = useState(1);

    const handleComplete = () => {
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
        });
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">ERP Setup Wizard</h2>}
        >
            <Head title="Onboarding" />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">
                        {/* Progress Bar */}
                        <div className="mb-8 text-center">
                            <p className="text-sm text-gray-500 mb-2">Step {step} of 4</p>
                            <div className="flex justify-center space-x-2">
                                {[1, 2, 3, 4].map((s) => (
                                    <div
                                        key={s}
                                        className={`h-2 w-8 rounded-full ${s <= step ? 'bg-indigo-600' : 'bg-gray-200'}`}
                                    />
                                ))}
                            </div>
                        </div>

                        {/* Step 1 */}
                        {step === 1 && (
                            <div className="space-y-6">
                                <h3 className="text-2xl font-bold font-sora">Business Setup</h3>
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Business Name</label>
                                        <input type="text" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Acme Inc" />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Base Currency</label>
                                        <select className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option>USD</option>
                                            <option>EUR</option>
                                            <option>GBP</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Timezone</label>
                                        <select className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option>UTC</option>
                                            <option>America/New_York</option>
                                        </select>
                                    </div>
                                </div>
                                <button onClick={() => setStep(2)} className="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">Continue →</button>
                            </div>
                        )}

                        {/* Step 2 */}
                        {step === 2 && (
                            <div className="space-y-6">
                                <h3 className="text-2xl font-bold font-sora">Add First Client</h3>
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Client Name</label>
                                        <input type="text" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="John Doe" />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="john@example.com" />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Currency</label>
                                        <select className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option>USD</option>
                                        </select>
                                    </div>
                                </div>
                                <div className="flex space-x-4">
                                    <button onClick={() => setStep(3)} className="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">Add Client</button>
                                    <button onClick={() => setStep(3)} className="flex-1 bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition">Skip for now</button>
                                </div>
                            </div>
                        )}

                        {/* Step 3 */}
                        {step === 3 && (
                            <div className="space-y-6">
                                <h3 className="text-2xl font-bold font-sora">Create First Invoice</h3>
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Title / Description</label>
                                        <input type="text" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Web Design Services" />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Amount</label>
                                        <input type="number" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="1000.00" />
                                    </div>
                                </div>
                                <div className="flex space-x-4">
                                    <button onClick={() => { setStep(4); handleComplete(); }} className="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">Create Invoice</button>
                                    <button onClick={() => { setStep(4); handleComplete(); }} className="flex-1 bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition">Skip for now</button>
                                </div>
                            </div>
                        )}

                        {/* Step 4 */}
                        {step === 4 && (
                            <div className="text-center space-y-6 py-8">
                                <h3 className="text-3xl font-bold font-sora text-indigo-600">You're all set! 🎉</h3>
                                <p className="text-gray-600">Your business has been successfully configured.</p>
                                <div className="bg-gray-50 p-4 rounded-lg text-left inline-block w-full max-w-sm mx-auto">
                                    <h4 className="font-semibold mb-2">Summary</h4>
                                    <ul className="space-y-2 text-sm text-gray-600">
                                        <li>✓ Business profile created</li>
                                        <li>✓ Base currency configured</li>
                                        <li>✓ Settings initialized</li>
                                    </ul>
                                </div>
                                <div>
                                    <Link href="/erp/dashboard" className="inline-block bg-indigo-600 text-white px-8 py-3 rounded-lg hover:bg-indigo-700 transition font-medium">
                                        Go to Dashboard →
                                    </Link>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
