import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import confetti from 'canvas-confetti';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

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
        <AuthenticatedLayout header="ERP Setup Wizard">
            <Head title="Onboarding" />

            <div className="py-12 px-4 max-w-2xl mx-auto">
                <Card className="shadow-none border bg-card text-card-foreground">
                    <CardContent className="p-8">
                        {/* Progress Bar */}
                        <div className="mb-8 text-center">
                            <p className="text-sm text-muted-foreground mb-3 font-medium">Step {step} of 4</p>
                            <div className="flex justify-center space-x-2">
                                {[1, 2, 3, 4].map((s) => (
                                    <div
                                        key={s}
                                        className={`h-2 w-10 rounded-full transition-colors ${s <= step ? 'bg-primary' : 'bg-muted'}`}
                                    />
                                ))}
                            </div>
                        </div>

                        {/* Step 1 */}
                        {step === 1 && (
                            <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2">
                                <div className="space-y-1">
                                    <h3 className="text-2xl font-bold tracking-tight">Business Setup</h3>
                                    <p className="text-sm text-muted-foreground">Configure your foundational workspace settings.</p>
                                </div>
                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="businessName">Business Name</Label>
                                        <Input id="businessName" placeholder="Acme Inc" />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="baseCurrency">Base Currency</Label>
                                        <select id="baseCurrency" className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                            <option>USD</option>
                                            <option>EUR</option>
                                            <option>GBP</option>
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="timezone">Timezone</Label>
                                        <select id="timezone" className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                            <option>UTC</option>
                                            <option>America/New_York</option>
                                        </select>
                                    </div>
                                </div>
                                <Button onClick={() => setStep(2)} className="w-full h-11 shadow-none">Continue →</Button>
                            </div>
                        )}

                        {/* Step 2 */}
                        {step === 2 && (
                            <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2">
                                <div className="space-y-1">
                                    <h3 className="text-2xl font-bold tracking-tight">Add First Client</h3>
                                    <p className="text-sm text-muted-foreground">Create a profile for your first tenant.</p>
                                </div>
                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="clientName">Client Name</Label>
                                        <Input id="clientName" placeholder="John Doe" />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="clientEmail">Email</Label>
                                        <Input id="clientEmail" type="email" placeholder="john@example.com" />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="clientCurrency">Currency</Label>
                                        <select id="clientCurrency" className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                            <option>USD</option>
                                        </select>
                                    </div>
                                </div>
                                <div className="flex gap-3">
                                    <Button variant="outline" onClick={() => setStep(3)} className="w-1/3">Skip</Button>
                                    <Button onClick={() => setStep(3)} className="w-2/3 shadow-none">Add Client</Button>
                                </div>
                            </div>
                        )}

                        {/* Step 3 */}
                        {step === 3 && (
                            <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2">
                                <div className="space-y-1">
                                    <h3 className="text-2xl font-bold tracking-tight">Create First Invoice</h3>
                                    <p className="text-sm text-muted-foreground">Draft your first billable invoice.</p>
                                </div>
                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="invoiceDesc">Title / Description</Label>
                                        <Input id="invoiceDesc" placeholder="Web Design Services" />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="invoiceAmount">Amount</Label>
                                        <Input id="invoiceAmount" type="number" placeholder="1000.00" />
                                    </div>
                                </div>
                                <div className="flex gap-3">
                                    <Button variant="outline" onClick={() => { setStep(4); handleComplete(); }} className="w-1/3">Skip</Button>
                                    <Button onClick={() => { setStep(4); handleComplete(); }} className="w-2/3 shadow-none">Create Invoice</Button>
                                </div>
                            </div>
                        )}

                        {/* Step 4 */}
                        {step === 4 && (
                            <div className="text-center space-y-6 py-8 animate-in zoom-in-95">
                                <div className="space-y-2">
                                    <h3 className="text-3xl font-bold tracking-tight text-primary">You're all set! 🎉</h3>
                                    <p className="text-muted-foreground">Your business has been successfully configured.</p>
                                </div>
                                <div className="bg-muted p-5 rounded-xl text-left inline-block w-full max-w-sm mx-auto border border-border">
                                    <h4 className="font-semibold text-foreground mb-3 text-sm">Summary</h4>
                                    <ul className="space-y-2 text-sm text-muted-foreground font-medium">
                                        <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-primary" /> Business profile created</li>
                                        <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-primary" /> Base currency configured</li>
                                        <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-primary" /> Settings initialized</li>
                                    </ul>
                                </div>
                                <div className="pt-4">
                                    <Button asChild size="lg" className="px-8 shadow-none">
                                        <Link href="/erp/dashboard">
                                            Go to Dashboard →
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
