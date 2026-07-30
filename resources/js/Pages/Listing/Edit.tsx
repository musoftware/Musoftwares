import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ArrowLeft, Save, AlertCircle } from 'lucide-react';

interface ListingEditData {
    id: number;
    title: string;
    description: string;
    price: number;
    city: string;
    status: string;
}

interface EditProps {
    listing: ListingEditData;
}

export default function Edit({ listing }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        title: listing.title,
        description: listing.description,
        price: listing.price,
        city: listing.city || '',
        status: listing.status,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/listing/${listing.id}`);
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between w-full">
                    <Link
                        href="/listing/dashboard"
                        className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-400 hover:text-slate-100 transition-colors"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        إلغاء والرجوع
                    </Link>
                    <h2 className="font-semibold text-xl text-slate-100 leading-tight text-right font-sans">
                        تعديل الإعلان الوظيفي
                    </h2>
                </div>
            }
        >
            <Head>
                <title>تعديل الإعلان: {listing.title}</title>
            </Head>

            <div className="py-12 bg-slate-950 min-h-screen text-slate-100 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto">
                    
                    {/* Full Width Form Card */}
                    <div className="w-full bg-slate-900/50 border border-slate-800/80 rounded-2xl p-6 md:p-8 backdrop-blur-xl shadow-2xl">
                        <form onSubmit={handleSubmit} className="space-y-6 text-right">
                            
                            {/* Validation Summary Error Alert */}
                            {Object.keys(errors).length > 0 && (
                                <div className="bg-rose-950/20 border border-rose-900/50 rounded-xl p-4 flex gap-3 items-start text-rose-400">
                                    <AlertCircle className="h-5 w-5 shrink-0 mt-0.5" />
                                    <div>
                                        <h5 className="font-bold mb-1">يرجى تصحيح الأخطاء التالية:</h5>
                                        <ul className="list-disc list-inside text-xs space-y-1">
                                            {Object.entries(errors).map(([key, val]) => (
                                                <li key={key}>{val}</li>
                                            ))}
                                        </ul>
                                    </div>
                                </div>
                            )}

                            {/* Title */}
                            <div className="space-y-2">
                                <label className="block text-sm font-bold text-slate-350" htmlFor="title">
                                    عنوان الإعلان الوظيفي <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:border-blue-500 text-right transition-colors"
                                    required
                                />
                            </div>

                            {/* Description */}
                            <div className="space-y-2">
                                <label className="block text-sm font-bold text-slate-350" htmlFor="description">
                                    وصف الوظيفة والمتطلبات <span className="text-rose-500">*</span>
                                </label>
                                <textarea
                                    id="description"
                                    rows={8}
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:border-blue-500 text-right transition-colors font-sans"
                                    required
                                />
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                {/* Price */}
                                <div className="space-y-2">
                                    <label className="block text-sm font-bold text-slate-350" htmlFor="price">
                                        الميزانية / المرتب الشهري (ج.م)
                                    </label>
                                    <input
                                        id="price"
                                        type="number"
                                        min="0"
                                        value={data.price}
                                        onChange={(e) => setData('price', parseFloat(e.target.value) || 0)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:border-blue-500 text-right transition-colors"
                                    />
                                    <p className="text-xs text-slate-500">أدخل 0 لتظهر كـ "قابل للتفاوض / غير محدد"</p>
                                </div>

                                {/* City */}
                                <div className="space-y-2">
                                    <label className="block text-sm font-bold text-slate-350" htmlFor="city">
                                        المدينة / المنطقة
                                    </label>
                                    <input
                                        id="city"
                                        type="text"
                                        value={data.city}
                                        onChange={(e) => setData('city', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:border-blue-500 text-right transition-colors"
                                    />
                                </div>

                                {/* Status */}
                                <div className="space-y-2">
                                    <label className="block text-sm font-bold text-slate-350" htmlFor="status">
                                        حالة الإعلان
                                    </label>
                                    <select
                                        id="status"
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:border-blue-500 text-right transition-colors"
                                    >
                                        <option value="active">نشط (يظهر للعامة)</option>
                                        <option value="draft">مسودة (مخفي)</option>
                                        <option value="archived">مؤرشف (مغلق)</option>
                                    </select>
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="pt-6 border-t border-slate-850 flex items-center justify-end gap-4">
                                <Link
                                    href="/listing/dashboard"
                                    className="px-6 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold rounded-xl transition-all duration-200"
                                >
                                    إلغاء
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 disabled:opacity-50 text-white font-bold rounded-xl shadow-lg hover:shadow-indigo-500/20 flex items-center gap-2 transition-all duration-300"
                                >
                                    <Save className="h-5 w-5" />
                                    حفظ التعديلات
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
