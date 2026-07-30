import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { MapPin, Calendar, DollarSign, Phone, Mail, MessageCircle, ArrowLeft, ExternalLink, ShieldAlert } from 'lucide-react';

interface ListingDetail {
    id: number;
    title: string;
    description: string;
    price: number;
    currency: string;
    city: string;
    phone: string;
    email: string;
    images: string[];
    original_url: string;
    created_at: string;
}

interface ShowProps {
    listing: ListingDetail;
}

export default function Show({ listing }: ShowProps) {
    const [activeImageIdx, setActiveImageIdx] = useState(0);

    // Clean phone number for WhatsApp API link
    const cleanPhone = listing.phone.replace(/[^0-9]/g, '');
    const whatsappUrl = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(
        `السلام عليكم، أنا مهتم بالإعلان الوظيفي الذي نشرته: "${listing.title}" على منصة Musoftwares.`
    )}`;

    return (
        <PublicLayout>
            <Head>
                <title>{`${listing.title} | Musoftwares Jobs`}</title>
                <meta name="description" content={listing.description.substring(0, 160)} />
            </Head>

            <div className="min-h-screen bg-slate-950 text-slate-100 py-12 px-4 sm:px-6 lg:px-8">
                {/* Back Link */}
                <div className="max-w-5xl mx-auto mb-8">
                    <Link
                        href="/listing"
                        className="inline-flex items-center gap-2 text-sm font-semibold text-slate-400 hover:text-slate-100 transition-colors"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        العودة لقائمة الوظائف
                    </Link>
                </div>

                <div className="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Main Content Area */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Job Details Card */}
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 md:p-8 backdrop-blur-xl shadow-xl">
                            <div className="flex flex-wrap justify-between items-start gap-4 mb-6 pb-6 border-b border-slate-800">
                                <div>
                                    <h1 className="text-2xl md:text-3xl font-extrabold text-slate-100 mb-3 leading-tight">
                                        {listing.title}
                                    </h1>
                                    <div className="flex flex-wrap items-center gap-4 text-xs text-slate-400">
                                        {listing.city && (
                                            <span className="flex items-center gap-1">
                                                <MapPin className="h-4 w-4 text-slate-500" />
                                                {listing.city}
                                            </span>
                                        )}
                                        <span className="flex items-center gap-1">
                                            <Calendar className="h-4 w-4 text-slate-500" />
                                            نُشر {listing.created_at}
                                        </span>
                                    </div>
                                </div>

                                <div className="text-right">
                                    <span className="inline-block text-xl font-extrabold text-emerald-400 bg-emerald-950/30 border border-emerald-900/60 px-4 py-2 rounded-xl">
                                        {listing.price > 0 ? `${listing.price} ${listing.currency}` : 'قابل للتفاوض / غير محدد'}
                                    </span>
                                </div>
                            </div>

                            {/* Images Carousel / Gallery */}
                            {listing.images && listing.images.length > 0 && (
                                <div className="mb-8 space-y-4">
                                    <div className="relative aspect-video w-full bg-slate-950 border border-slate-850 rounded-2xl overflow-hidden shadow-lg">
                                        <img
                                            src={listing.images[activeImageIdx]}
                                            alt={`${listing.title} - ${activeImageIdx + 1}`}
                                            className="w-full h-full object-contain object-center"
                                        />
                                    </div>

                                    {listing.images.length > 1 && (
                                        <div className="flex gap-2 overflow-x-auto pb-2 scrollbar-thin">
                                            {listing.images.map((img, idx) => (
                                                <button
                                                    key={idx}
                                                    onClick={() => setActiveImageIdx(idx)}
                                                    className={`relative w-20 aspect-video rounded-lg border overflow-hidden shrink-0 transition-all ${
                                                        idx === activeImageIdx
                                                            ? 'border-blue-500 scale-95 ring-2 ring-blue-500/20'
                                                            : 'border-slate-800 hover:border-slate-600'
                                                    }`}
                                                >
                                                    <img src={img} alt="" className="w-full h-full object-cover" />
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Description */}
                            <div className="space-y-4">
                                <h3 className="text-lg font-bold text-slate-200">وصف الوظيفة والمتطلبات:</h3>
                                <div className="text-slate-350 text-base leading-relaxed whitespace-pre-wrap text-right font-sans">
                                    {listing.description}
                                </div>
                            </div>
                        </div>

                        {/* Import Notice */}
                        <div className="bg-blue-950/20 border border-blue-900/40 rounded-2xl p-6 flex gap-4 items-start shadow-md">
                            <ShieldAlert className="h-6 w-6 text-blue-400 shrink-0 mt-0.5" />
                            <div>
                                <h4 className="font-bold text-blue-300 mb-1">إشعار الأمان وحقوق النشر</h4>
                                <p className="text-xs text-blue-400/80 leading-relaxed">
                                    هذه الوظيفة تم استيرادها تلقائيًا من موقع الوسيط لخدمتكم وتسهيل التصفح.
                                    تم إرسال بريد إلكتروني تلقائي لصاحب الإعلان يتضمن بيانات حسابه لتفعيل إعلانه وتعديله أو حذفه في أي وقت.
                                </p>
                                <a
                                    href={listing.original_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1 text-xs text-blue-300 hover:text-blue-200 mt-2 font-semibold transition-colors"
                                >
                                    عرض الإعلان الأصلي على الوسيط
                                    <ExternalLink className="h-3 w-3" />
                                </a>
                            </div>
                        </div>
                    </div>

                    {/* Contact Sidebar */}
                    <div className="space-y-6">
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-xl shadow-xl sticky top-6">
                            <h3 className="text-lg font-bold text-slate-200 mb-6 pb-4 border-b border-slate-800 text-center">
                                معلومات التواصل المباشر
                            </h3>

                            <div className="space-y-4">
                                {/* Call Button */}
                                <a
                                    href={`tel:${listing.phone}`}
                                    className="flex items-center justify-center gap-3 w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-xl shadow-lg hover:shadow-indigo-500/25 transition-all duration-300"
                                >
                                    <Phone className="h-5 w-5" />
                                    اتصال هاتفي
                                </a>

                                {/* WhatsApp Button */}
                                <a
                                    href={whatsappUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="flex items-center justify-center gap-3 w-full py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold rounded-xl shadow-lg hover:shadow-emerald-500/25 transition-all duration-300"
                                >
                                    <MessageCircle className="h-5 w-5" />
                                    مراسلة عبر واتساب
                                </a>

                                {/* Email Button */}
                                <a
                                    href={`mailto:${listing.email}?subject=${encodeURIComponent(
                                        `التقديم على وظيفة: ${listing.title}`
                                    )}`}
                                    className="flex items-center justify-center gap-3 w-full py-4 bg-slate-800 hover:bg-slate-700 text-slate-100 font-bold rounded-xl border border-slate-700 transition-all duration-300"
                                >
                                    <Mail className="h-5 w-5" />
                                    أرسل سيرة ذاتية (إيميل)
                                </a>
                            </div>

                            {/* Contact Details Card */}
                            <div className="mt-8 pt-6 border-t border-slate-800 space-y-3 text-sm text-slate-400">
                                <div className="flex justify-between items-center">
                                    <span className="text-slate-500">رقم الهاتف:</span>
                                    <span className="font-semibold text-slate-300">{listing.phone}</span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-slate-500">البريد الإلكتروني:</span>
                                    <span className="font-semibold text-slate-300 truncate max-w-[160px]" title={listing.email}>
                                        {listing.email}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
