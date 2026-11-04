import { __ } from '@/lib/i18n';
import { useState } from 'react';
import axios from 'axios';
import { CheckCircle2 } from 'lucide-react';
import { useToast } from '@/Components/ui/use-toast';

const inputClass = 'w-full px-3 py-2 bg-white rounded-xl border border-black/10 text-[#1d1d1f] focus:ring-2 focus:ring-[#0071e3] focus:outline-none';

/**
 * Name / phone / email form that saves the estimate as a guest ticket.
 * `getScope` is called on submit so the latest selection is sent.
 */
export default function EstimatorLeadForm({ toolName, getScope, title, description }) {
    const { toast } = useToast();
    const [fields, setFields] = useState({ name: '', mobile: '', email: '' });
    const [saved, setSaved] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const setField = (key) => (e) => setFields((prev) => ({ ...prev, [key]: e.target.value }));

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!fields.name.trim() || !fields.mobile.trim()) {
            toast({ title: __('frontend.estimator_incomplete_details'), description: __('frontend.estimator_name_phone_required'), variant: 'destructive' });
            return;
        }

        setSubmitting(true);
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            await axios.post(
                '/tools/lead-capture',
                { ...fields, tool_name: toolName, scope: getScope() },
                { headers: token ? { 'X-CSRF-TOKEN': token } : {} },
            );
            setSaved(true);
            toast({ title: __('frontend.estimator_request_received'), description: __('frontend.estimator_request_received_desc') });
        } catch (error) {
            console.error('Estimator lead capture failed:', error);
            toast({ title: __('frontend.estimator_submission_failed'), description: __('frontend.estimator_submission_failed_desc'), variant: 'destructive' });
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="bg-[#f5f5f7] p-6 rounded-[24px] border border-black/5 space-y-4">
            <div className="space-y-0.5">
                <h4 className="font-semibold text-xs text-[#1d1d1f]">{title || __('frontend.estimator_request_title')}</h4>
                <p className="text-[11px] text-[#1d1d1f]/60">{description || __('frontend.estimator_request_desc')}</p>
            </div>

            {saved ? (
                <div className="p-3.5 rounded-xl bg-white border border-green-500/30 text-green-700 text-xs flex items-center gap-2">
                    <CheckCircle2 className="h-4 w-4 shrink-0 text-green-600" />
                    <span>{__('frontend.estimator_request_sent')}</span>
                </div>
            ) : (
                <form onSubmit={handleSubmit} className="space-y-2.5 text-xs">
                    <input type="text" required placeholder={__('frontend.estimator_full_name')} aria-label={__('frontend.estimator_full_name')} value={fields.name} onChange={setField('name')} className={inputClass} />
                    <input type="tel" required placeholder={__('frontend.estimator_phone')} aria-label={__('frontend.estimator_phone')} value={fields.mobile} onChange={setField('mobile')} className={inputClass} />
                    <input type="email" placeholder={__('frontend.estimator_email_optional')} aria-label={__('frontend.estimator_email_optional')} value={fields.email} onChange={setField('email')} className={inputClass} />
                    <button type="submit" disabled={submitting} className="w-full py-2.5 rounded-[980px] bg-[#0071e3] hover:bg-[#0077ed] text-white font-medium text-xs transition-all cursor-pointer disabled:opacity-50 shadow-sm">
                        {submitting ? __('frontend.estimator_submitting') : __('frontend.estimator_submit')}
                    </button>
                </form>
            )}
        </div>
    );
}
