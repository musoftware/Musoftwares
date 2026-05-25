import React from 'react';
import { Loader2, AlertCircle } from 'lucide-react';

interface Props {
    installingPlugin: boolean;
    loginRequired: boolean;
    setLoginRequired: (val: boolean) => void;
    locale?: 'en' | 'ar';
}

export function RuntimePluginModals({ installingPlugin, loginRequired, setLoginRequired, locale = 'en' }: Props) {
    return (
        <>
            {/* ── Plugin Installing Modal ────────────────────────────── */}
            {installingPlugin && (
                <div className="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-8 max-w-sm w-full text-center shadow-2xl flex flex-col items-center">
                        <div className="h-12 w-12 rounded-full bg-blue-500/10 flex items-center justify-center mb-4">
                            <Loader2 className="h-6 w-6 text-blue-500 animate-spin" />
                        </div>
                        <h3 className="text-xl font-semibold text-white mb-2">
                            {locale === 'ar' ? 'جاري تحميل الأداة...' : 'Downloading Extension...'}
                        </h3>
                        <p className="text-slate-400 text-sm">
                            {locale === 'ar' ? 'الرجاء الانتظار، يتم الآن تثبيت وتهيئة الأداة المطلوبة تلقائياً في الخلفية.' : 'Please wait, the required extension is being installed automatically in the background.'}
                        </p>
                    </div>
                </div>
            )}

            {/* ── Login Required Modal ───────────────────────────────── */}
            {loginRequired && (
                <div className="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-8 max-w-sm w-full text-center shadow-2xl flex flex-col items-center">
                        <div className="h-12 w-12 rounded-full bg-amber-500/10 flex items-center justify-center mb-4">
                            <AlertCircle className="h-6 w-6 text-amber-500" />
                        </div>
                        <h3 className="text-xl font-semibold text-white mb-2">
                            {locale === 'ar' ? 'تسجيل الدخول مطلوب' : 'Login Required'}
                        </h3>
                        <p className="text-slate-400 text-sm mb-6">
                            {locale === 'ar' ? 'الأداة غير مثبتة وحسابك غير متصل بالرن تايم. لقد فتحنا صفحة تسجيل الدخول، يرجى تسجيل الدخول للبدء.' : 'The extension is not installed and your account is not connected to the runtime. We have opened the login page, please log in to start.'}
                        </p>
                        <button
                            onClick={() => setLoginRequired(false)}
                            className="px-6 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg font-medium transition-colors"
                        >
                            {locale === 'ar' ? 'حسناً، فهمت' : 'Okay'}
                        </button>
                    </div>
                </div>
            )}
        </>
    );
}
