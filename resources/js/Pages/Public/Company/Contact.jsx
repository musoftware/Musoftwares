import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { MapPin, Mail, Phone } from 'lucide-react';

export default function Contact({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title="Contact Sales - musoftware" />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <h1 className="text-5xl sm:text-7xl font-extrabold text-slate-900 tracking-tight mb-8">
                            Get In Touch
                        </h1>
                        <p className="text-xl text-slate-600 font-light mb-12 max-w-2xl leading-relaxed">
                            Speak directly with our engineering and sales teams to architect the right infrastructure for your business.
                        </p>
                    </div>
                </div>
            </section>

            <section className="py-24 bg-slate-50 border-t border-slate-100">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-12">
                        <div className="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm flex flex-col items-start text-left">
                            <div className="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center mb-6">
                                <Mail className="h-6 w-6 text-slate-900" />
                            </div>
                            <h3 className="text-lg font-bold text-slate-900 mb-2">Sales Inquiries</h3>
                            <p className="text-slate-600 font-light mb-6 flex-1">Email our solutions team for pricing, architecture reviews, and platform demos.</p>
                            <a href="mailto:admin@musoftwares.com" className="text-slate-900 font-semibold hover:underline">admin@musoftwares.com</a>
                        </div>
                        
                        <div className="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm flex flex-col items-start text-left">
                            <div className="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center mb-6">
                                <Phone className="h-6 w-6 text-slate-900" />
                            </div>
                            <h3 className="text-lg font-bold text-slate-900 mb-2">Technical Support</h3>
                            <p className="text-slate-600 font-light mb-6 flex-1">For existing enterprise clients requiring critical infrastructure support.</p>
                            <div className="flex flex-col space-y-2 mt-auto">
                                <a href="tel:+201015218548" className="text-slate-900 font-semibold hover:underline">Call: +201015218548</a>
                                <a href="https://wa.me/201015218548" target="_blank" rel="noreferrer" className="text-slate-900 font-semibold hover:underline">WhatsApp: +201015218548</a>
                            </div>
                        </div>

                        <div className="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm flex flex-col items-start text-left">
                            <div className="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center mb-6">
                                <MapPin className="h-6 w-6 text-slate-900" />
                            </div>
                            <h3 className="text-lg font-bold text-slate-900 mb-2">Headquarters</h3>
                            <p className="text-slate-600 font-light mb-6 flex-1">Visit our main engineering office for scheduled consultations.</p>
                            <span className="text-slate-900 font-semibold">Cairo, Egypt</span>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
