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

/** The owner brings in an existing project (a Drive zip and its GitHub repo) for a customer to keep changing in the chat. */
export default function ImportProject() {
    const form = useForm({
        title: '', description: '', import_url: '', github_repo: '', github_token: '', locale: 'ar',
        release_mode: 'batch' as ReleaseMode, release_after_changes: 3, release_quiet_minutes: 60, deploy_enabled: true, deploy_command: '',
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
                <input className={field} placeholder={t('Google Drive link to the project .zip', 'لينك Google Drive لملف المشروع .zip')} value={form.data.import_url} onChange={(e) => form.setData('import_url', e.target.value)} dir="ltr" required />
                <p className="text-xs text-slate-500">{t('Share it as "Anyone with the link". The SSH and deploy files inside it stay on the build PC and are never shown to the customer.', 'شاركه "أي حد معاه اللينك". ملفات الـ SSH والـ deploy اللي جواه بتفضل على جهاز البناء ومبتظهرش للعميل.')}</p>
                {error('import_url')}
                <div className="grid gap-3 sm:grid-cols-2">
                    <input className={field} placeholder="https://github.com/owner/repo" value={form.data.github_repo} onChange={(e) => form.setData('github_repo', e.target.value)} dir="ltr" />
                    <input className={field} type="password" autoComplete="off" placeholder={t('GitHub token (Contents + Pull requests)', 'توكن GitHub (Contents + Pull requests)')} value={form.data.github_token} onChange={(e) => form.setData('github_token', e.target.value)} dir="ltr" />
                </div>
                {error('github_repo')}{error('github_token')}
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
    title: string; description: string; import_url: string; github_repo: string; github_token: string; locale: string;
    release_mode: ReleaseMode; release_after_changes: number; release_quiet_minutes: number; deploy_enabled: boolean; deploy_command: string;
}>>;

/** When the changes go out (GitHub pull request, then the deploy), and how the deploy runs. */
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
                <span>{t('Also deploy to the server (not only the GitHub pull request)', 'ارفع على السيرفر كمان (مش بس pull request على GitHub)')}</span>
            </label>
            {form.data.deploy_enabled && (
                <input className={field} placeholder={t('Deploy command, optional (e.g. bash deploy.sh). Empty: the AI deploys the way the project already does.', 'أمر الـ deploy، اختياري (مثلاً bash deploy.sh). فاضي: الـ AI يرفع بنفس طريقة المشروع.')} value={form.data.deploy_command} onChange={(e) => form.setData('deploy_command', e.target.value)} dir="ltr" />
            )}
        </fieldset>
    );
}
