import React, { useState } from 'react';
import { CheckCircle2, Clock, Circle, Send, Sparkles, Star, ShieldCheck, Check } from 'lucide-react';
import axios from 'axios';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';

export interface Milestone {
    id: number;
    title: string;
    stage: 'planning' | 'development' | 'testing' | 'delivered';
    is_completed: boolean;
    completed_at?: string;
}

export interface ProjectProgressData {
    id: number;
    name: string;
    progress_stage: 'planning' | 'development' | 'testing' | 'delivered';
    progress_percentage: number;
    is_brief_complete: boolean;
    brief_details?: string;
    is_delivered?: boolean;
    delivered_at?: string;
    status?: string;
    milestones?: Milestone[];
}

interface ProjectProgressBarProps {
    project: ProjectProgressData;
    onBriefSubmitted?: () => void;
    onProjectCompleted?: () => void;
}

const STAGES = [
    { key: 'planning', title: 'Planning', desc: 'Architecture & Specifications', num: '01' },
    { key: 'development', title: 'Engineering', desc: 'Core Implementation', num: '02' },
    { key: 'testing', title: 'Audit & QA', desc: 'Security & Verification', num: '03' },
    { key: 'delivered', title: 'Handover', desc: 'Production Deployment', num: '04' },
];

export const ProjectProgressBar: React.FC<ProjectProgressBarProps> = ({
    project,
    onBriefSubmitted,
    onProjectCompleted,
}) => {
    // Brief form state
    const [isSubmittingBrief, setIsSubmittingBrief] = useState(false);
    const [briefText, setBriefText] = useState('');
    const [showBriefForm, setShowBriefForm] = useState(false);
    const [briefSuccessMsg, setBriefSuccessMsg] = useState<string | null>(null);

    // Initial delivered detection
    const initialDelivered = Boolean(
        project.is_delivered ||
        project.progress_stage === 'delivered' ||
        (project.progress_percentage ?? 0) === 100 ||
        project.status === 'closed' ||
        Boolean(project.delivered_at)
    );

    // Delivered & Completion states
    const [isDelivered, setIsDelivered] = useState(initialDelivered);
    const [currentStage, setCurrentStage] = useState<'planning' | 'development' | 'testing' | 'delivered'>(
        initialDelivered ? 'delivered' : (project.progress_stage || 'planning')
    );
    const [percentage, setPercentage] = useState(initialDelivered ? 100 : (project.progress_percentage ?? 0));

    // Sign-off modal state
    const [showCompleteDialog, setShowCompleteDialog] = useState(false);
    const [isSubmittingComplete, setIsSubmittingComplete] = useState(false);
    const [rating, setRating] = useState(5);
    const [hoveredRating, setHoveredRating] = useState<number | null>(null);
    const [feedback, setFeedback] = useState('');
    const [completeSuccessMsg, setCompleteSuccessMsg] = useState<string | null>(null);

    const currentStageIndex = isDelivered ? 3 : STAGES.findIndex((s) => s.key === currentStage);
    const milestones = project.milestones || [];

    const handleBriefSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!briefText || briefText.length < 20) return;

        try {
            setIsSubmittingBrief(true);
            const res = await axios.post(`/api/portal/projects/${project.id}/brief`, {
                brief_details: briefText,
            });
            setBriefSuccessMsg(res.data?.message || 'Brief locked in. +100 Loyalty Points awarded.');
            setShowBriefForm(false);
            if (onBriefSubmitted) onBriefSubmitted();
        } catch (err: any) {
            alert(err.response?.data?.message || 'Failed to submit brief. Please try again.');
        } finally {
            setIsSubmittingBrief(false);
        }
    };

    const handleCompleteProject = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            setIsSubmittingComplete(true);
            const res = await axios.post(`/api/portal/projects/${project.id}/complete`, {
                rating,
                feedback,
            });

            const points = res.data?.data?.points_awarded ?? 150;
            setCompleteSuccessMsg(res.data?.message || `Project confirmed and delivered! +${points} Loyalty Points awarded.`);
            setIsDelivered(true);
            setCurrentStage('delivered');
            setPercentage(100);
            setShowCompleteDialog(false);

            if (onProjectCompleted) {
                onProjectCompleted();
            }
        } catch (err: any) {
            alert(err.response?.data?.message || 'Failed to sign off project. Please try again.');
        } finally {
            setIsSubmittingComplete(false);
        }
    };

    return (
        <section className="w-full bg-white dark:bg-[#141416]/95 border border-black/5 dark:border-white/10 rounded-2xl p-6 sm:p-7 shadow-sm dark:shadow-2xl backdrop-blur-2xl transition-colors duration-200">
            {/* Live Activity Top Header */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-black/5 dark:border-white/5">
                <div className="space-y-1.5">
                    <div className="flex items-center gap-2.5">
                        <span className="relative flex h-2 w-2">
                            {isDelivered ? (
                                <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500" />
                            ) : (
                                <>
                                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75" />
                                    <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500" />
                                </>
                            )}
                        </span>
                        <h2 className="text-xl font-semibold text-[#1d1d1f] dark:text-white tracking-tight font-sans">
                            {project.name}
                        </h2>
                        {isDelivered && (
                            <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-500/20">
                                <CheckCircle2 className="w-3 h-3" />
                                Handover Complete
                            </span>
                        )}
                    </div>
                    <p className="text-xs text-zinc-600 dark:text-zinc-400 max-w-xl font-normal leading-relaxed">
                        {isDelivered
                            ? 'All development, testing, and production handover deliverables have been verified and signed off.'
                            : 'Live sprint trajectory with continuous delivery verification. No status check-in calls required.'}
                    </p>
                </div>

                <div className="flex items-center sm:items-end gap-4 self-start sm:self-auto">
                    {!isDelivered ? (
                        <button
                            type="button"
                            onClick={() => setShowCompleteDialog(true)}
                            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-emerald-600 dark:bg-emerald-500 hover:bg-emerald-700 dark:hover:bg-emerald-400 text-white dark:text-black transition-all shadow-sm active:scale-[0.98] cursor-pointer"
                        >
                            <ShieldCheck className="w-4 h-4" />
                            <span>Confirm Completion (+150 PTS)</span>
                        </button>
                    ) : (
                        <div className="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-black/5 dark:border-white/5 text-xs text-zinc-600 dark:text-zinc-400">
                            <ShieldCheck className="w-3.5 h-3.5 text-emerald-500" />
                            <span>Verified Delivery</span>
                        </div>
                    )}

                    <div className="text-left sm:text-right border-l sm:border-l-0 pl-3 sm:pl-0 border-black/5 dark:border-white/5">
                        <div className="text-[11px] font-mono tracking-wider uppercase text-zinc-500 dark:text-zinc-400">
                            Sprint Velocity
                        </div>
                        <div className="text-3xl font-mono font-bold text-[#1d1d1f] dark:text-white tracking-tight tabular-nums mt-0.5">
                            {percentage}<span className="text-sm font-sans font-normal text-zinc-500 dark:text-zinc-400">%</span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Apple Fluid Timeline Track */}
            <div className="mt-7">
                {/* Continuous Progress Bar with Glow */}
                <div className="relative w-full h-1.5 bg-zinc-100 dark:bg-zinc-800/80 rounded-full overflow-hidden border border-black/5 dark:border-transparent">
                    <div
                        className="h-full bg-gradient-to-r from-blue-500 via-sky-400 to-emerald-400 transition-all duration-700 ease-out rounded-full shadow-[0_0_12px_rgba(56,189,248,0.4)]"
                        style={{ width: `${Math.max(percentage, isDelivered ? 100 : 5)}%` }}
                    />
                </div>

                {/* 4 Responsive Connected Stages */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-3 pt-5">
                    {STAGES.map((stg, idx) => {
                        const isDone = isDelivered || idx < currentStageIndex || (idx === currentStageIndex && percentage === 100);
                        const isCurrent = !isDelivered && idx === currentStageIndex && percentage < 100;

                        return (
                            <div
                                key={stg.key}
                                className={`group p-3.5 rounded-xl border transition-all duration-300 ${
                                    isDone
                                        ? 'border-black/5 dark:border-white/10 bg-[#f5f5f7] dark:bg-zinc-900/40 text-[#1d1d1f] dark:text-zinc-300'
                                        : isCurrent
                                        ? 'border-[#0071e3]/40 dark:border-white/20 bg-blue-50/60 dark:bg-gradient-to-b dark:from-zinc-800/60 dark:to-zinc-900/90 text-[#1d1d1f] dark:text-white shadow-md ring-1 ring-[#0071e3]/20 dark:ring-white/10'
                                        : 'border-black/5 dark:border-white/5 bg-zinc-50/70 dark:bg-zinc-950/40 text-zinc-400 dark:text-zinc-600'
                                }`}
                            >
                                <div className="flex items-center justify-between text-[11px] font-mono mb-2">
                                    <span className="text-zinc-500 dark:text-zinc-400 font-medium">{stg.num}</span>
                                    <span className="flex items-center gap-1.5">
                                        {isDone && <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500 dark:text-emerald-400" />}
                                        {isCurrent && <Clock className="w-3.5 h-3.5 text-[#0071e3] dark:text-sky-400 animate-pulse" />}
                                        {!isDone && !isCurrent && <Circle className="w-3 h-3 text-zinc-300 dark:text-zinc-700" />}
                                        <span className="text-[10px] uppercase font-mono tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {isDone ? 'Delivered' : isCurrent ? 'Active' : 'Queued'}
                                        </span>
                                    </span>
                                </div>
                                <div className="text-xs font-semibold text-[#1d1d1f] dark:text-white tracking-tight">{stg.title}</div>
                                <div className="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5 line-clamp-1">{stg.desc}</div>

                                {/* Handover Action on Stage 4 */}
                                {stg.key === 'delivered' && !isDelivered && (
                                    <button
                                        type="button"
                                        onClick={() => setShowCompleteDialog(true)}
                                        className="mt-2.5 w-full py-1 px-2 rounded-lg bg-emerald-500/10 hover:bg-emerald-600 hover:text-white text-emerald-700 dark:text-emerald-400 dark:hover:bg-emerald-500 dark:hover:text-black text-[11px] font-medium transition-colors flex items-center justify-center gap-1 cursor-pointer"
                                    >
                                        <Check className="w-3 h-3" />
                                        <span>Sign Off Handover</span>
                                    </button>
                                )}
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* Delivered Status Banner */}
            {isDelivered ? (
                <div className="mt-6 p-4 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/20 border border-emerald-200/80 dark:border-emerald-500/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="p-2 rounded-lg bg-emerald-100 dark:bg-emerald-500/10 border border-emerald-300/60 dark:border-emerald-500/30">
                            <ShieldCheck className="w-5 h-5 text-emerald-600 dark:text-emerald-300" />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-emerald-950 dark:text-white">
                                Project Finalized & Signed Off
                            </div>
                            <p className="text-[11px] text-emerald-900/80 dark:text-zinc-400 mt-0.5">
                                Client confirmation complete. Deliverables are approved for production and +150 Loyalty Points have been credited.
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        {project.delivered_at && (
                            <span className="text-[11px] font-mono text-zinc-500 dark:text-zinc-400">
                                {project.delivered_at}
                            </span>
                        )}
                        <span className="px-3 py-1 rounded-md text-xs font-mono font-semibold bg-emerald-600 text-white dark:bg-emerald-500 dark:text-black shrink-0">
                            CLOSED
                        </span>
                    </div>
                </div>
            ) : (
                /* Tactical Brief Completion Widget */
                !project.is_brief_complete && (
                    <div className="mt-6 p-4 rounded-xl bg-amber-50/80 dark:bg-gradient-to-r dark:from-amber-500/10 dark:via-zinc-900/80 dark:to-zinc-900/80 border border-amber-200/80 dark:border-amber-500/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <div className="p-2 rounded-lg bg-amber-100 dark:bg-amber-500/10 border border-amber-300/60 dark:border-amber-500/30">
                                <Sparkles className="w-4 h-4 text-amber-600 dark:text-amber-300" />
                            </div>
                            <div>
                                <div className="text-xs font-semibold text-amber-950 dark:text-white">
                                    Lock-In Project Scope & Requirements
                                </div>
                                <p className="text-[11px] text-amber-900/80 dark:text-zinc-400 mt-0.5">
                                    Define architecture parameters early to bypass manual scope discussions and earn 100 PTS.
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            onClick={() => setShowBriefForm(!showBriefForm)}
                            className="px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-[#1d1d1f] hover:bg-black text-white dark:bg-white dark:text-black dark:hover:bg-zinc-200 transition-colors shrink-0 shadow-sm cursor-pointer"
                        >
                            {showBriefForm ? 'Dismiss' : 'Lock In Brief (+100 PTS)'}
                        </button>
                    </div>
                )
            )}

            {showBriefForm && (
                <form onSubmit={handleBriefSubmit} className="mt-4 p-4 rounded-xl bg-[#fbfbfd] dark:bg-zinc-950 border border-black/10 dark:border-white/10 space-y-3 animate-in fade-in duration-200">
                    <div className="flex items-center justify-between text-xs font-mono text-zinc-500 dark:text-zinc-400">
                        <span>ENGINEERING SPECIFICATION INPUT</span>
                        <span>MIN 20 CHARACTERS</span>
                    </div>
                    <textarea
                        rows={4}
                        required
                        value={briefText}
                        onChange={(e) => setBriefText(e.target.value)}
                        placeholder="Detail functional goals, target APIs, database constraints, user roles, or benchmark milestones..."
                        className="w-full text-xs p-3.5 rounded-lg bg-white dark:bg-zinc-900/90 border border-black/15 dark:border-zinc-700 text-[#1d1d1f] dark:text-zinc-100 placeholder:text-zinc-400 dark:placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-[#0071e3]/30 dark:focus:ring-white/30 font-mono leading-relaxed"
                    />
                    <div className="flex justify-end gap-2.5 pt-1">
                        <button
                            type="button"
                            onClick={() => setShowBriefForm(false)}
                            className="px-3 py-1.5 text-xs text-zinc-600 dark:text-zinc-400 hover:text-black dark:hover:text-white cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={isSubmittingBrief || briefText.length < 20}
                            className="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold bg-emerald-600 dark:bg-emerald-500 text-white dark:text-black hover:bg-emerald-700 dark:hover:bg-emerald-400 disabled:opacity-40 transition-colors cursor-pointer"
                        >
                            <Send className="w-3.5 h-3.5" />
                            <span>{isSubmittingBrief ? 'Locking In...' : 'Confirm Scope & Claim Points'}</span>
                        </button>
                    </div>
                </form>
            )}

            {briefSuccessMsg && (
                <div className="mt-3 text-xs text-emerald-600 dark:text-emerald-400 font-mono font-medium">
                    {briefSuccessMsg}
                </div>
            )}

            {completeSuccessMsg && (
                <div className="mt-3 text-xs text-emerald-600 dark:text-emerald-400 font-mono font-medium flex items-center gap-2">
                    <CheckCircle2 className="w-4 h-4 shrink-0" />
                    <span>{completeSuccessMsg}</span>
                </div>
            )}

            {/* Milestone Checklist if available */}
            {milestones.length > 0 && (
                <div className="mt-6 pt-5 border-t border-black/5 dark:border-white/5">
                    <div className="flex items-center justify-between mb-3 text-[11px] font-mono uppercase text-zinc-500 dark:text-zinc-400">
                        <span>Phase Milestones ({isDelivered ? 'Handover' : currentStage})</span>
                        <span className="tabular-nums font-mono">
                            {isDelivered ? milestones.length : milestones.filter(m => m.is_completed).length}/{milestones.length} Completed
                        </span>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        {milestones.map((m) => (
                            <div
                                key={m.id}
                                className="flex items-center justify-between p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-950/60 border border-black/5 dark:border-white/5 text-xs"
                            >
                                <span className={(isDelivered || m.is_completed) ? 'line-through text-zinc-400 dark:text-zinc-500' : 'text-zinc-800 dark:text-zinc-200'}>
                                    {m.title}
                                </span>
                                <span className="font-mono text-[10px] text-zinc-400 dark:text-zinc-500 uppercase">
                                    {(isDelivered || m.is_completed) ? 'PASS' : 'ACTIVE'}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Confirmation Dialog for Project Sign-off */}
            <Dialog open={showCompleteDialog} onOpenChange={setShowCompleteDialog}>
                <DialogContent className="max-w-md p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-black/10 dark:border-white/10">
                    <form onSubmit={handleCompleteProject}>
                        <DialogHeader>
                            <DialogTitle className="text-lg font-semibold text-[#1d1d1f] dark:text-white flex items-center gap-2">
                                <ShieldCheck className="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                                <span>Confirm Project Handover</span>
                            </DialogTitle>
                            <DialogDescription className="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed mt-1">
                                Are you satisfied with the project deliverables? Confirming completion marks the project as delivered, verifies sprint velocity at 100%, and awards you <strong className="text-emerald-600 dark:text-emerald-400">+150 Loyalty Points</strong>.
                            </DialogDescription>
                        </DialogHeader>

                        {/* Star Rating Selection */}
                        <div className="my-5 space-y-2">
                            <label className="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                How would you rate your delivery experience?
                            </label>
                            <div className="flex items-center gap-1.5 pt-1">
                                {[1, 2, 3, 4, 5].map((starVal) => {
                                    const isFilled = (hoveredRating !== null ? hoveredRating : rating) >= starVal;
                                    return (
                                        <button
                                            type="button"
                                            key={starVal}
                                            onClick={() => setRating(starVal)}
                                            onMouseEnter={() => setHoveredRating(starVal)}
                                            onMouseLeave={() => setHoveredRating(null)}
                                            className="p-1 rounded-md text-amber-400 hover:scale-110 transition-transform cursor-pointer"
                                        >
                                            <Star
                                                className={`w-6 h-6 ${
                                                    isFilled
                                                        ? 'fill-amber-400 text-amber-400'
                                                        : 'text-zinc-300 dark:text-zinc-700'
                                                }`}
                                            />
                                        </button>
                                    );
                                })}
                                <span className="text-xs font-mono text-zinc-500 dark:text-zinc-400 ml-2">
                                    {rating}/5
                                </span>
                            </div>
                        </div>

                        {/* Optional Feedback */}
                        <div className="space-y-1.5 mb-5">
                            <label className="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                Final Handover Notes / Feedback <span className="text-zinc-400 font-normal">(Optional)</span>
                            </label>
                            <textarea
                                rows={3}
                                value={feedback}
                                onChange={(e) => setFeedback(e.target.value)}
                                placeholder="Any feedback, testimonial, or notes for our engineering team..."
                                className="w-full text-xs p-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-black/10 dark:border-white/10 text-[#1d1d1f] dark:text-zinc-100 placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                            />
                        </div>

                        <DialogFooter className="flex items-center justify-end gap-2 pt-2 border-t border-black/5 dark:border-white/5">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setShowCompleteDialog(false)}
                                disabled={isSubmittingComplete}
                                className="text-xs rounded-xl"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={isSubmittingComplete}
                                className="text-xs font-semibold bg-emerald-600 dark:bg-emerald-500 hover:bg-emerald-700 dark:hover:bg-emerald-400 text-white dark:text-black rounded-xl"
                            >
                                {isSubmittingComplete ? 'Verifying...' : 'Sign Off & Earn 150 PTS'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </section>
    );
};
