import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';

export default function Education({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title="Education Software Solutions - musoftware" />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <h1 className="text-5xl sm:text-7xl font-extrabold text-slate-900 tracking-tight mb-8">
                            Digital Campus Platforms
                        </h1>
                        <p className="text-xl text-slate-600 font-light mb-12 max-w-2xl leading-relaxed">
                            Comprehensive systems for schools and universities. Centralize student information, manage admissions, and facilitate e-learning environments.
                        </p>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <a href="mailto:sales@musoftwares.com">
                                <Button size="lg" className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-10 h-14 text-base font-semibold transition-all">
                                    Discuss Education Needs
                                </Button>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section className="py-24 bg-slate-50 border-t border-slate-100">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-12">
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">Student Information System</h3>
                            <p className="text-slate-600 font-light">Manage academic records, enrollment, and grading in one unified database.</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">E-Learning Portals</h3>
                            <p className="text-slate-600 font-light">Interactive platforms for assignments, virtual classrooms, and course material distribution.</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">Administration & HR</h3>
                            <p className="text-slate-600 font-light">Automate faculty payroll, resource scheduling, and institutional reporting.</p>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
