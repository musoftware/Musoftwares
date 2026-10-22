import { getLoadedLocale } from '@/lib/i18n';

/** Picks the Arabic or English text for the admin pages of the software store. */
export const t = (en: string, ar: string): string => (getLoadedLocale() === 'ar' ? ar : en);

export const SECTION_LABELS: Record<string, [string, string]> = {
    overview: ['Read this first', 'اقرأ ده الأول'],
    details: ['Full details', 'التفاصيل الكاملة'],
    faq: ['Common questions', 'الأسئلة الشائعة'],
    problems: ['Problems', 'المشاكل'],
    stat: ['Big number', 'الرقم الكبير'],
    showcase: ['Screen and features', 'الشاشة والمميزات'],
    pillars: ['Main features', 'المميزات الأساسية'],
    video: ['Video title', 'عنوان الفيديو'],
    steps: ['Steps', 'الخطوات'],
    metrics: ['Numbers', 'الأرقام'],
    before_after: ['Before and after', 'قبل وبعد'],
    gallery: ['Pictures title', 'عنوان الصور'],
    plans: ['Price plans block', 'قسم الباقات'],
    cta: ['Last box (buy)', 'صندوق الشراء الأخير'],
};

export const FIELD_LABELS: Record<string, [string, string]> = {
    kicker: ['Small title above', 'عنوان صغير فوق'],
    headline: ['Main title', 'العنوان الرئيسي'],
    subheadline: ['Short description', 'وصف قصير'],
    title: ['Title', 'العنوان'],
    body: ['Text', 'النص'],
    icon: ['Icon', 'الأيقونة'],
    value: ['Big number', 'الرقم'],
    label: ['Label', 'الوصف'],
    note: ['Note under the plans', 'ملحوظة تحت الباقات'],
    button: ['Button text', 'نص الزرار'],
    included: ['Included in every plan', 'موجود في كل الباقات'],
    items: ['Items', 'العناصر'],
    paragraphs: ['Paragraphs', 'الفقرات'],
    list_title: ['List title', 'عنوان القايمة'],
    list: ['List', 'القايمة'],
    q: ['Question', 'السؤال'],
    a: ['Answer', 'الإجابة'],
    period: ['What the price covers (example: per year)', 'السعر عن إيه (مثلا: في السنة)'],
    before_title: ['Left box title', 'عنوان الصندوق الأول'],
    after_title: ['Right box title', 'عنوان الصندوق التاني'],
    before: ['Left box points', 'نقاط الصندوق الأول'],
    after: ['Right box points', 'نقاط الصندوق التاني'],
    b: ['Short title', 'عنوان قصير'],
    t: ['Text', 'النص'],
};

export const ICON_NAMES = [
    'wrench', 'box', 'calculator', 'search', 'users', 'phone', 'mail', 'send', 'shield',
    'clock', 'chart', 'file', 'lock', 'database', 'zap', 'message', 'refresh', 'check',
];

export const labelOf = (map: Record<string, [string, string]>, key: string): string => {
    const pair = map[key];
    return pair ? t(pair[0], pair[1]) : key.replace(/_/g, ' ');
};
