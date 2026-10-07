import { __ } from '@/lib/i18n';
import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Plus, BookOpen, Video, CheckCircle, ArrowRight, Play, Loader2 } from 'lucide-react';

interface Playlist {
    id: number;
    youtube_playlist_id: string;
    title: string;
    description: string;
    thumbnail: string;
    channel_title: string;
    total_videos: number;
    completed_videos: number;
    progress_percent: number;
    created_at: string;
}

interface IndexProps {
    playlists: Playlist[];
}

export default function Index({ playlists }: IndexProps) {
    const [showSyncForm, setShowSyncForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        playlist_id: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/series/sync', {
            onSuccess: () => {
                setShowSyncForm(false);
                reset();
            }
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
                    <button
                        onClick={() => setShowSyncForm(!showSyncForm)}
                        className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-red-600 to-red-500 hover:from-red-500 hover:to-red-400 text-white font-bold rounded-xl shadow-lg hover:shadow-red-500/25 transition-all duration-300 font-sans"
                    >
                        <Plus className="h-5 w-5" />
                        {__('series.index_import_playlist')}
                    </button>
                    <h2 className="font-semibold text-xl text-slate-100 leading-tight text-right font-sans">
                        {__('series.index_heading')}
                    </h2>
                </div>
            }
        >
            <Head title={__('series.index_page_title')} />

            <div className="py-12 bg-slate-950 min-h-screen text-slate-100 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto space-y-8">

                    {/* Quick Import Drawer / Form Panel */}
                    {showSyncForm && (
                        <div className="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-2xl space-y-4 text-right animate-in fade-in slide-in-from-top-4 duration-350">
                            <div className="flex items-center gap-3 border-b border-slate-800 pb-3 mb-4">
                                <Video className="h-6 w-6 text-red-500" />
                                <h3 className="text-lg font-bold text-slate-200">{__('series.index_import_title')}</h3>
                            </div>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="space-y-2">
                                    <label htmlFor="playlist_id" className="block text-sm text-slate-400">{__('series.index_playlist_label')}</label>
                                    <div className="flex flex-col md:flex-row gap-3">
                                        <input
                                            id="playlist_id"
                                            type="text"
                                            value={data.playlist_id}
                                            onChange={(e) => setData('playlist_id', e.target.value)}
                                            placeholder={__('series.index_playlist_placeholder')}
                                            className="w-full md:flex-1 bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 text-right transition-colors"
                                            required
                                        />
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="px-8 py-3 bg-gradient-to-r from-red-600 to-red-500 hover:from-red-500 hover:to-red-400 disabled:opacity-50 text-white font-bold rounded-xl shadow-lg flex items-center justify-center gap-2 transition-all duration-300 min-w-[150px]"
                                        >
                                            {processing ? (
                                                <>
                                                    <Loader2 className="h-5 w-5 animate-spin" />
                                                    {__('series.index_importing')}
                                                </>
                                            ) : (
                                                __('series.index_start_import')
                                            )}
                                        </button>
                                    </div>
                                    {errors.playlist_id && (
                                        <p className="text-sm text-red-500 mt-1">{errors.playlist_id}</p>
                                    )}
                                </div>
                            </form>
                            <p className="text-xs text-slate-500 leading-relaxed">
                                {__('series.index_import_hint')}
                            </p>
                        </div>
                    )}

                    {/* Courses Grid */}
                    {playlists.length === 0 ? (
                        <div className="text-center py-24 bg-slate-900/40 border border-slate-800 rounded-2xl">
                            <BookOpen className="h-16 w-16 mx-auto text-slate-700 mb-4 animate-pulse" />
                            <h3 className="text-xl font-bold text-slate-350">{__('series.index_empty_title')}</h3>
                            <p className="text-slate-500 mt-2 max-w-md mx-auto">
                                {__('series.index_empty_hint')}
                            </p>
                            <button
                                onClick={() => setShowSyncForm(true)}
                                className="mt-6 px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold rounded-xl border border-slate-750 transition-all duration-200"
                            >
                                {__('series.index_add_first')}
                            </button>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {playlists.map((playlist) => (
                                <div
                                    key={playlist.id}
                                    className="group bg-slate-900/50 hover:bg-slate-900/90 border border-slate-800/80 hover:border-slate-750 rounded-2xl overflow-hidden transition-all duration-300 flex flex-col justify-between shadow-lg hover:shadow-2xl hover:translate-y-[-2px]"
                                >
                                    {/* Thumbnail Banner */}
                                    <div className="relative aspect-video w-full bg-slate-950 overflow-hidden border-b border-slate-850">
                                        <img
                                            src={playlist.thumbnail}
                                            alt={playlist.title}
                                            className="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-500"
                                            loading="lazy"
                                        />
                                        <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent opacity-60"></div>
                                        
                                        <span className="absolute bottom-3 right-3 text-xs bg-slate-900/80 border border-slate-800 text-slate-300 px-2.5 py-1 rounded-lg flex items-center gap-1 font-semibold backdrop-blur-md">
                                            <Video className="h-3.5 w-3.5 text-slate-400" />
                                            {__('series.index_videos_count', { count: playlist.total_videos })}
                                        </span>
                                    </div>

                                    {/* Content Info */}
                                    <div className="p-5 flex-1 flex flex-col justify-between space-y-4">
                                        <div className="space-y-2 text-right">
                                            <span className="text-[10px] uppercase font-bold text-red-500 tracking-wider">
                                                {playlist.channel_title || __('series.index_youtube')}
                                            </span>
                                            <h3 className="text-lg font-bold text-slate-100 group-hover:text-red-400 transition-colors line-clamp-1 leading-tight">
                                                <Link href={`/series/playlist/${playlist.id}`}>
                                                    {playlist.title}
                                                </Link>
                                            </h3>
                                            <p className="text-slate-400 text-xs line-clamp-2 leading-relaxed h-8">
                                                {playlist.description || __('series.index_no_description')}
                                            </p>
                                        </div>

                                        {/* Progress Bar Area */}
                                        <div className="space-y-2 text-right pt-2 border-t border-slate-850/60">
                                            <div className="flex items-center justify-between text-xs text-slate-500">
                                                <span>{__('series.index_percent_complete', { percent: playlist.progress_percent })}</span>
                                                <span className="flex items-center gap-1">
                                                    <CheckCircle className="h-3.5 w-3.5 text-emerald-500" />
                                                    {__('series.index_lessons_progress', { done: playlist.completed_videos, total: playlist.total_videos })}
                                                </span>
                                            </div>
                                            <div className="w-full bg-slate-950 h-2 rounded-full overflow-hidden border border-slate-850">
                                                <div
                                                    className="bg-gradient-to-r from-red-600 to-red-500 h-full rounded-full transition-all duration-500"
                                                    style={{ width: `${playlist.progress_percent}%` }}
                                                ></div>
                                            </div>
                                        </div>

                                        {/* Direct Play Action */}
                                        <Link
                                            href={`/series/playlist/${playlist.id}`}
                                            className="w-full py-3 bg-slate-950 hover:bg-red-950/20 text-slate-350 hover:text-red-400 border border-slate-800 hover:border-red-900/60 font-semibold rounded-xl flex items-center justify-center gap-2 transition-all duration-300"
                                        >
                                            <Play className="h-4 w-4 fill-current shrink-0" />
                                            {__('series.index_continue')}
                                            <ArrowRight className="h-4 w-4 rtl:rotate-180 shrink-0" />
                                        </Link>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
