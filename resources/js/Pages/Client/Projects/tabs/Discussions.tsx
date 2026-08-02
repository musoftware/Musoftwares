import React, { useState, useRef } from 'react';
import { formatDate } from '@/lib/utils';
import { MessageSquare, CornerDownRight, Reply, Send, Paperclip, X } from 'lucide-react';
import { EmptyState } from '@/Components/ui/EmptyState';
import { __ } from '@/lib/i18n';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import axios from 'axios';
import { toast } from 'sonner';

export interface TabDiscussion {
    id: number;
    body: string;
    author_id: number | null;
    guest_name?: string | null;
    author?: { id: number; name: string; avatar_url?: string | null } | null;
    created_at: string;
    parent_id?: number | null;
    type: string;
    commentable_id: number;
}

interface Props {
    discussions: TabDiscussion[];
    projectId?: number;
}

export function ProjectDiscussionsTab({ discussions = [], projectId }: Props) {
    const page = usePage();
    const resolvedProjectId = projectId || (page.props.project as any)?.id;
    const currentUserId = (page.props.auth as any)?.user?.id;

    const [replyingToId, setReplyingToId] = useState<number | null>(null);
    const [replyBody, setReplyBody] = useState('');
    const [messageText, setMessageText] = useState('');
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    // Organize comments into threads
    const roots = discussions.filter(item => !item.parent_id);
    const repliesMap = discussions.reduce((acc, item) => {
        if (item.parent_id) {
            if (!acc[item.parent_id]) {
                acc[item.parent_id] = [];
            }
            acc[item.parent_id].push(item);
        }
        return acc;
    }, {} as Record<number, TabDiscussion[]>);

    const handleReplySubmit = async (parentItem: TabDiscussion) => {
        if (!replyBody.trim()) return;
        setSubmitting(true);
        try {
            const response = await axios.post(route('client.projects.comments.store', { project: resolvedProjectId }), {
                type: parentItem.type || 'project',
                commentable_id: parentItem.commentable_id || resolvedProjectId,
                body: replyBody,
                parent_id: parentItem.id,
            });

            if (response.data.ok) {
                toast.success('Reply posted!');
                setReplyBody('');
                setReplyingToId(null);
                router.reload({ only: ['tabContent'] });
            }
        } catch (error) {
            toast.error('Failed to post reply.');
        } finally {
            setSubmitting(false);
        }
    };

    const handleMainSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!messageText.trim() && !selectedFile) return;
        setSubmitting(true);

        const formData = new FormData();
        formData.append('type', 'project');
        formData.append('commentable_id', String(resolvedProjectId));
        formData.append('body', messageText);
        if (selectedFile) {
            formData.append('file', selectedFile);
        }

        try {
            const response = await axios.post(route('client.projects.comments.store', { project: resolvedProjectId }), formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            if (response.data.ok) {
                setMessageText('');
                setSelectedFile(null);
                if (fileInputRef.current) fileInputRef.current.value = '';
                router.reload({ only: ['tabContent'] });
            }
        } catch (error) {
            toast.error('Failed to send message.');
        } finally {
            setSubmitting(false);
        }
    };

    const renderMarkdown = (text: string) => {
        try {
            const html = marked.parse(text);
            return { __html: DOMPurify.sanitize(html as string) };
        } catch (e) {
            return { __html: DOMPurify.sanitize(text) };
        }
    };

    const renderCommentNode = (item: TabDiscussion, isReply: boolean = false) => {
        // System message rendering
        if (item.body.startsWith('[System:')) {
            const cleanText = item.body.replace('[System:', '').replace(']', '');
            return (
                <div className="flex justify-center my-3 w-full">
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3.5 py-1 text-[11px] font-bold text-slate-500 ring-1 ring-inset ring-slate-200">
                        {cleanText}
                    </span>
                </div>
            );
        }

        const isCurrentUser = item.author_id === currentUserId;
        const authorName = item.author?.name || item.guest_name || __('general.unknown_user');
        const initials = authorName.slice(0, 1).toUpperCase();

        return (
            <div className={`flex flex-col w-full space-y-2 my-2 ${isCurrentUser ? 'items-end' : 'items-start'}`}>
                <div className={`flex gap-3 max-w-2xl rounded-2xl p-4 shadow-sm relative group ${
                    isCurrentUser 
                        ? 'bg-indigo-605 text-white rounded-tr-none' 
                        : isReply 
                            ? 'bg-slate-50 border border-slate-150 text-slate-800 rounded-tl-none ml-6' 
                            : 'bg-white border border-slate-200 text-slate-850 rounded-tl-none'
                }`} style={{ backgroundColor: isCurrentUser ? '#4f46e5' : undefined }}>
                    {!isCurrentUser && (
                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-500">
                            {initials}
                        </div>
                    )}
                    <div className="min-w-0 flex-1 space-y-1">
                        <div className="flex items-baseline justify-between gap-4">
                            <span className={`text-xs font-extrabold ${isCurrentUser ? 'text-indigo-150' : 'text-slate-900'}`}>
                                {authorName}
                            </span>
                            <span className={`text-[10px] ${isCurrentUser ? 'text-indigo-200' : 'text-slate-400'}`}>
                                {item.created_at ? formatDate(item.created_at) : ''}
                            </span>
                        </div>
                        <div 
                            className={`prose prose-slate max-w-none text-sm leading-relaxed break-words ${
                                isCurrentUser ? 'text-white prose-invert prose-p:text-white prose-a:text-indigo-200' : 'text-slate-700 prose-a:text-indigo-600 font-medium'
                            }`}
                            dangerouslySetInnerHTML={renderMarkdown(item.body)}
                        />
                        
                        {!isReply && (
                            <div className="pt-1 flex items-center gap-2">
                                <button
                                    onClick={() => {
                                        setReplyingToId(replyingToId === item.id ? null : item.id);
                                        setReplyBody('');
                                    }}
                                    className={`inline-flex items-center gap-1 text-[11px] font-bold transition-colors ${
                                        isCurrentUser ? 'text-indigo-200 hover:text-white' : 'text-indigo-600 hover:text-indigo-800'
                                    }`}
                                >
                                    <Reply className="h-3 w-3" />
                                    Reply
                                </button>
                            </div>
                        )}
                    </div>
                </div>

                {/* Reply Input Box */}
                {replyingToId === item.id && (
                    <div className="w-full max-w-xl mr-2 rounded-2xl border border-indigo-100 bg-indigo-50/40 p-4 space-y-3">
                        <Textarea
                            placeholder="Write a reply..."
                            rows={2}
                            className="text-xs rounded-xl focus:ring-indigo-500 bg-white"
                            value={replyBody}
                            onChange={(e) => setReplyBody(e.target.value)}
                        />
                        <div className="flex justify-end gap-2">
                            <Button size="sm" variant="outline" className="h-8 text-xs" onClick={() => setReplyingToId(null)}>
                                Cancel
                            </Button>
                            <Button
                                size="sm"
                                className="h-8 bg-indigo-600 hover:bg-indigo-700 text-white text-xs"
                                disabled={!replyBody.trim() || submitting}
                                onClick={() => handleReplySubmit(item)}
                            >
                                Send Reply
                            </Button>
                        </div>
                    </div>
                )}

                {/* Nested Replies */}
                {repliesMap[item.id] && repliesMap[item.id].length > 0 && (
                    <div className="w-full space-y-2 mt-1">
                        {repliesMap[item.id].map(reply => <React.Fragment key={reply.id}>{renderCommentNode(reply, true)}</React.Fragment>)}
                    </div>
                )}
            </div>
        );
    };

    return (
        <div className="flex flex-col h-[600px] border border-slate-200 rounded-2xl bg-slate-50 overflow-hidden shadow-sm">
            {/* Messages Feed */}
            <div className="flex-1 overflow-y-auto p-4 space-y-2">
                {roots.length === 0 ? (
                    <div className="h-full flex items-center justify-center">
                        <EmptyState
                            icon={MessageSquare}
                            tone="friendly"
                            title={__('general.no_discussions_yet') || 'No messages yet'}
                            description={__('general.start_the_conversation') || 'Type a message below to start the conversation.'}
                        />
                    </div>
                ) : (
                    roots.map(root => <React.Fragment key={root.id}>{renderCommentNode(root)}</React.Fragment>)
                )}
            </div>

            {/* Attachment preview if any */}
            {selectedFile && (
                <div className="px-4 py-2 border-t border-slate-200 bg-indigo-50/50 flex items-center justify-between text-xs text-indigo-800">
                    <span className="flex items-center gap-1.5 truncate font-semibold">
                        <Paperclip className="h-3.5 w-3.5" />
                        {selectedFile.name} ({Math.round(selectedFile.size / 1024)} KB)
                    </span>
                    <button onClick={() => setSelectedFile(null)} className="text-slate-400 hover:text-slate-600">
                        <X className="h-4 w-4" />
                    </button>
                </div>
            )}

            {/* Chat Input Area */}
            <form onSubmit={handleMainSubmit} className="p-4 bg-white border-t border-slate-200 flex items-end gap-3">
                <input
                    type="file"
                    ref={fileInputRef}
                    onChange={(e) => setSelectedFile(e.target.files?.[0] || null)}
                    className="hidden"
                />
                
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="h-10 w-10 text-slate-400 hover:text-indigo-600 rounded-xl shrink-0"
                    onClick={() => fileInputRef.current?.click()}
                >
                    <Paperclip className="h-5 w-5" />
                </Button>

                <div className="flex-1 min-w-0">
                    <Textarea
                        placeholder="Write a message..."
                        rows={1}
                        className="w-full text-sm rounded-xl focus:ring-indigo-500 border-slate-200 resize-none py-2.5 max-h-32"
                        value={messageText}
                        onChange={(e) => setMessageText(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter' && !e.shiftKey) {
                                e.preventDefault();
                                handleMainSubmit(e);
                            }
                        }}
                    />
                </div>

                <Button
                    type="submit"
                    size="icon"
                    disabled={(!messageText.trim() && !selectedFile) || submitting}
                    className="h-10 w-10 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shrink-0 shadow-md"
                >
                    <Send className="h-4 w-4" />
                </Button>
            </form>
        </div>
    );
}

export default ProjectDiscussionsTab;
