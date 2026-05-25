import React, { useState, useEffect } from 'react';
import { Loader2, AlertCircle, ShieldCheck } from 'lucide-react';

interface Props {
    installingPlugin: boolean;
    loginRequired: boolean;
    setLoginRequired: (val: boolean) => void;
    locale?: 'en' | 'ar';
}

export function RuntimePluginModals({ installingPlugin, loginRequired, setLoginRequired, locale = 'en' }: Props) {
    const [status, setStatus] = useState<'idle' | 'loading' | 'redirecting' | 'error'>('idle');
    const [errorMsg, setErrorMsg] = useState<string>('');
    const [retryTrigger, setRetryTrigger] = useState<number>(0);

    useEffect(() => {
        if (!loginRequired) {
            setStatus('idle');
            return;
        }

        let isMounted = true;

        const initiateAuthRedirect = async () => {
            if (isMounted) {
                setStatus('loading');
                setErrorMsg('');
            }
            try {
                const host = typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
                const response = await fetch(`http://${host}:18400/auth/start`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error(locale === 'ar' ? 'فشل بدء عملية تسجيل الدخول من الرن تايم.' : 'Failed to initiate login handshake from runtime.');
                }

                const data = await response.json();
                if (data && data.url) {
                    if (isMounted) {
                        setStatus('redirecting');
                    }
                    // Wait briefly for smooth UX before redirect
                    setTimeout(() => {
                        if (isMounted) {
                            window.location.href = data.url;
                        }
                    }, 1200);
                } else {
                    throw new Error(locale === 'ar' ? 'لم يتم إرجاع رابط الربط من الرن تايم.' : 'No connection URL returned by runtime.');
                }
            } catch (err: any) {
                console.error('Auth redirect error:', err);
                if (isMounted) {
                    setStatus('error');
                    setErrorMsg(err.message || (locale === 'ar' ? 'فشل الاتصال بالرن تايم. تأكد أنه يعمل بالخلفية.' : 'Could not contact the local runtime agent. Make sure it is running in the background.'));
                }
            }
        };

        initiateAuthRedirect();

        return () => {
            isMounted = false;
        };
    }, [loginRequired, locale, retryTrigger]);

    return (
        <>
            {/* ── Plugin Installing Modal ────────────────────────────── */}
            {installingPlugin && (
                <div className="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 animate-in fade-in duration-200">
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
                <div className="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 animate-in fade-in duration-200">
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl p-8 max-w-sm w-full text-center shadow-2xl flex flex-col items-center">
                        {status === 'loading' && (
                            <>
                                <div className="h-12 w-12 rounded-full bg-indigo-500/10 flex items-center justify-center mb-4">
                                    <Loader2 className="h-6 w-6 text-indigo-500 animate-spin" />
                                </div>
                                <h3 className="text-xl font-bold text-white mb-2">
                                    {locale === 'ar' ? 'جاري التحقق من الاتصال...' : 'Connecting to Agent...'}
                                </h3>
                                <p className="text-slate-400 text-sm leading-relaxed">
                                    {locale === 'ar' 
                                        ? 'جاري توليد رمز الربط الآمن من وكيل الرن تايم المحلي...' 
                                        : 'Generating secure link code from local runtime agent...'}
                                </p>
                            </>
                        )}

                        {status === 'redirecting' && (
                            <>
                                <div className="h-12 w-12 rounded-full bg-emerald-500/10 flex items-center justify-center mb-4">
                                    <ShieldCheck className="h-6 w-6 text-emerald-500 animate-pulse" />
                                </div>
                                <h3 className="text-xl font-bold text-white mb-2">
                                    {locale === 'ar' ? 'تم إنشاء رمز الربط!' : 'Link Code Generated!'}
                                </h3>
                                <p className="text-slate-400 text-sm leading-relaxed">
                                    {locale === 'ar'
                                        ? 'جاري توجيهك الآن لصفحة ربط الحساب الآمنة...'
                                        : 'Redirecting you to the secure device authentication page...'}
                                </p>
                                <div className="w-16 h-1.5 bg-emerald-950 rounded-full overflow-hidden mt-4 relative">
                                    <div className="h-full bg-emerald-500 rounded-full w-1/2 animate-[shimmer_1.5s_infinite] absolute left-0" style={{
                                        animation: 'shimmer 1.5s infinite ease-in-out'
                                    }} />
                                </div>
                                <style dangerouslySetInnerHTML={{__html: `
                                    @keyframes shimmer {
                                        0% { left: -50%; }
                                        100% { left: 100%; }
                                    }
                                `}} />
                            </>
                        )}

                        {status === 'error' && (
                            <>
                                <div className="h-12 w-12 rounded-full bg-rose-500/10 flex items-center justify-center mb-4">
                                    <AlertCircle className="h-6 w-6 text-rose-500" />
                                </div>
                                <h3 className="text-xl font-bold text-white mb-2">
                                    {locale === 'ar' ? 'فشل الاتصال بالرن تايم' : 'Connection Failed'}
                                </h3>
                                <p className="text-slate-400 text-sm leading-relaxed mb-6">
                                    {errorMsg}
                                </p>
                                <div className="flex gap-3 w-full">
                                    <button
                                        onClick={() => setLoginRequired(false)}
                                        className="flex-1 px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl font-semibold text-sm transition-all"
                                    >
                                        {locale === 'ar' ? 'إلغاء' : 'Cancel'}
                                    </button>
                                    <button
                                        onClick={() => setRetryTrigger(prev => prev + 1)}
                                        className="flex-1 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-semibold text-sm transition-all shadow-lg shadow-indigo-500/20"
                                    >
                                        {locale === 'ar' ? 'إعادة المحاولة' : 'Retry'}
                                    </button>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            )}
        </>
    );
}
