import React from 'react';
import { useForm } from '@inertiajs/react';
import { t } from '@/Components/Admin/SoftwareStore/t';

type ReleaseMode = 'each' | 'batch' | 'manual';

const MODES: [ReleaseMode, [string, string], [string, string]][] = [
    ['each', ['After every change', 'بعد كل تعديل'], ['Each finished change goes to GitHub and the server at once.', 'كل تعديل يخلص يترفع على GitHub والسيرفر على طول.']],
    ['batch', ['In batches', 'على دفعات'], ['Changes wait, then go together once enough are waiting or the customer goes quiet.', 'التعديلات تستنى، وتترفع مع بعض لما يتجمع عدد معين أو العميل يسكت فترة.']],
    ['manual', ['Only when I press "Release now"', 'لما أدوس "ارفع دلوقتي" بس'], ['Nothing goes out until you release it from the chat page.', 'مفيش حاجة تترفع غير لما ترفعها بنفسك من صفحة الشات.']],
];

const field = 'w-full rounded-lg border px-3 py-2 text-sm';

/** The owner brings in an existing project (a Drive zip of its folder) for a customer to keep changing in the chat. */
export default function ImportProject() {
    const form = useForm({
        title: '', description: '', import_url: '', locale: 'ar',
        release_mode: 'batch' as ReleaseMode, release_after_changes: 3, release_quiet_minutes: 60, deploy_enabled: true,
    });
    const error = (name: keyof typeof form.data) => form.errors[name] && <p className="text-sm text-red-700">{form.errors[name]}</p>;
    const submit = (event: React.FormEvent) => { event.preventDefault(); form.post('/admin/builder/import'); };

    return (
        <details className="rounded-xl border bg-white p-4">
            <summary className="cursor-pointer font-semibold">{t('Import a project for a customer', 'ارفع مشروع لعميل')}</summary>
            <form onSubmit={submit} className="mt-3 grid gap-3">
                <p className="text-sm text-slate-500">{t('The worker downloads it, then you get a chat link to send to the customer. He asks for changes himself; fixes are free like our delivered projects, new ideas are paid.', 'الـ worker بيحمّله، وبعدها بيوصلك لينك شات تبعته للعميل. هو يطلب التعديلات بنفسه؛ الإصلاحات مجانية زي مشاريعنا والأفكار الجديدة بفلوس.')}</p>
                <input className={field} placeholder={t('Project name', 'اسم المشروع')} value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} dir="auto" required />
                {error('title')}
                <textarea className={field} rows={3} placeholder={t('What the project is and who uses it (the chat reads this)', 'المشروع ده إيه ومين بيستخدمه (الشات بيقرا ده)')} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} dir="auto" required />
                {error('description')}
                <input className={field} placeholder={t('GitHub repo link, or a Google Drive link to the .zip', 'لينك الـ repo على GitHub، أو لينك Google Drive لملف .zip')} value={form.data.import_url} onChange={(e) => form.setData('import_url', e.target.value)} dir="ltr" required />
                <p className="text-xs text-slate-500">{t('GitHub: paste https://github.com/owner/repo. The build PC clones it with your own token, so your token must be allowed to reach that repo.', 'GitHub: اكتب https://github.com/owner/repo. جهاز البناء بيسحبه بالـ token بتاعك، فلازم الـ token يكون مسموح له بالـ repo ده.')}</p>
                <p className="text-xs text-slate-500">{t('Drive: share it as "Anyone with the link". Zip the whole project folder with its .git folder: its GitHub repo, SSH and deploy files are read from it, stay on the build PC, and are never shown to the customer.', 'Drive: شاركه "أي حد معاه اللينك". اضغط مجلد المشروع كله ومعاه مجلد .git: الـ GitHub والـ SSH وملفات الـ deploy بتتقري منه، وبتفضل على جهاز البناء ومبتظهرش للعميل.')}</p>
                {error('import_url')}
                <select className={field} value={form.data.locale} onChange={(e) => form.setData('locale', e.target.value)} aria-label={t('Customer language', 'لغة العميل')}>
                    <option value="ar">{t('Customer speaks Arabic', 'العميل بيتكلم عربي')}</option>
                    <option value="en">{t('Customer speaks English', 'العميل بيتكلم إنجليزي')}</option>
                </select>
                <ReleaseRules form={form} />
                <button type="submit" disabled={form.processing} className="justify-self-start rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
                    {t('Import', 'ارفع المشروع')}
                </button>
            </form>
        </details>
    );
}

type ImportForm = ReturnType<typeof useForm<{
    title: string; description: string; import_url: string; locale: string;
    release_mode: ReleaseMode; release_after_changes: number; release_quiet_minutes: number; deploy_enabled: boolean;
}>>;

/** When the changes go out (a branch on GitHub for review, then the deploy). */
function ReleaseRules({ form }: { form: ImportForm }) {
    return (
        <fieldset className="grid gap-2 rounded-lg border p-3">
            <legend className="px-1 text-sm font-semibold">{t('When the changes go out', 'التعديلات تترفع إمتى')}</legend>
            {MODES.map(([mode, label, note]) => (
                <label key={mode} className="flex gap-2 text-sm">
                    <input type="radio" name="release_mode" checked={form.data.release_mode === mode} onChange={() => form.setData('release_mode', mode)} />
                    <span><strong>{t(...label)}</strong> <span className="text-slate-500">{t(...note)}</span></span>
                </label>
            ))}
            {form.data.release_mode === 'batch' && (
                <div className="flex flex-wrap items-center gap-2 text-sm">
                    <span>{t('Release when', 'ارفع لما')}</span>
                    <input className="w-20 rounded-lg border px-2 py-1" type="number" min={1} max={50} value={form.data.release_after_changes} onChange={(e) => form.setData('release_after_changes', Number(e.target.value))} aria-label={t('changes waiting', 'عدد التعديلات')} />
                    <span>{t('changes are waiting, or the customer is quiet for', 'تعديلات تتجمع، أو العميل يسكت')}</span>
                    <input className="w-24 rounded-lg border px-2 py-1" type="number" min={5} max={10080} value={form.data.release_quiet_minutes} onChange={(e) => form.setData('release_quiet_minutes', Number(e.target.value))} aria-label={t('quiet minutes', 'دقايق السكوت')} />
                    <span>{t('minutes', 'دقيقة')}</span>
                </div>
            )}
            <label className="flex gap-2 text-sm">
                <input type="checkbox" checked={form.data.deploy_enabled} onChange={(e) => form.setData('deploy_enabled', e.target.checked)} />
                <span>
                    {t('Also deploy to the server (not only the GitHub pull request)', 'ارفع على السيرفر كمان (مش بس pull request على GitHub)')}
                    <span className="block text-slate-500">{t('The cheapest AI reads the project\'s own deploy files (scripts, SSH, README), deploys, and saves the steps; later releases repeat them without AI.', 'أرخص AI بيقرا ملفات الـ deploy اللي في المشروع (السكريبتات والـ SSH والـ README)، ويرفع، ويحفظ الخطوات؛ المرات الجاية تتكرر من غير AI.')}</span>
                </span>
            </label>
        </fieldset>
    );
}
