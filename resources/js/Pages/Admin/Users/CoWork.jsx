import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Users, Mail, Phone, ExternalLink, Clock, Tag, Search, ChevronRight } from 'lucide-react';
import { __ } from '@/lib/i18n';

const TAG_LIMIT = 4;

function TagList({ tags, colorClass = 'bg-green-50 text-green-700 border-green-100' }) {
    const [expanded, setExpanded] = useState(false);
    const visible = expanded ? tags : tags.slice(0, TAG_LIMIT);
    const hidden  = tags.length - TAG_LIMIT;
    return (
        <div className="flex flex-wrap gap-1.5 mt-1">
            {visible.map(t => (
                <span key={t.id}
                    className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium border ${colorClass}`}>
                    <Tag className="w-3 h-3" /> {t.tag_name ?? t.name}
                </span>
            ))}
            {!expanded && hidden > 0 && (
                <button
                    onClick={() => setExpanded(true)}
                    className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200 transition-colors">
                    +{hidden} more
                </button>
            )}
            {expanded && hidden > 0 && (
                <button
                    onClick={() => setExpanded(false)}
                    className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200 transition-colors">{__('general.show_less')}</button>
            )}
        </div>
    );
}

function LegacyCard({ worker }) {
    return (
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow p-5 flex flex-col gap-3">
            <div className="flex items-start justify-between gap-3">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-slate-700 flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
                        {worker.person_name?.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() || '??'}
                    </div>
                    <div>
                        <p className="font-semibold text-slate-900 text-sm">{worker.person_name || '—'}</p>
                        {worker.email && (
                            <p className="text-xs text-slate-500 flex items-center gap-1">
                                <Mail className="w-3 h-3" /> {worker.email}
                            </p>
                        )}
                    </div>
                </div>
                <Link
                    href={`/admin/users/legacy-coworker/${worker.id}`}
                    className="text-green-600 hover:text-green-800 transition-colors flex-shrink-0"
                    title={__('general.view_details_1')}
                >
                    <ChevronRight className="w-5 h-5" />
                </Link>
            </div>

            <div className="grid grid-cols-2 gap-2 text-xs text-slate-600">
                {worker.mobile && (
                    <span className="flex items-center gap-1">
                        <Phone className="w-3 h-3 text-slate-400" />
                        {worker.flag_path && (
                            <img src={worker.flag_path} alt="" className="w-4 h-3 object-cover rounded-sm" />
                        )}
                        {worker.mobile}
                    </span>
                )}
                {worker.whatsapp && (
                    <a
                        href={`https://wa.me/${worker.whatsapp.replace(/\D/g, '')}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-1 text-green-600 hover:underline"
                        onClick={e => e.stopPropagation()}
                    >
                        <svg className="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        {__('general.whatsapp')}</a>
                )}
                {worker.facebook && (
                    <a href={worker.facebook} target="_blank" rel="noopener noreferrer"
                        className="flex items-center gap-1 text-slate-900 hover:underline col-span-2 truncate"
                        onClick={e => e.stopPropagation()}>
                        <ExternalLink className="w-3 h-3" /> {__('general.facebook')}</a>
                )}
                {worker.linked_in && (
                    <a href={worker.linked_in} target="_blank" rel="noopener noreferrer"
                        className="flex items-center gap-1 text-slate-900 hover:underline col-span-2 truncate"
                        onClick={e => e.stopPropagation()}>
                        <ExternalLink className="w-3 h-3" /> {__('general.linkedin')}</a>
                )}
                {(worker.time_from || worker.time_to) && (
                    <span className="flex items-center gap-1 col-span-2">
                        <Clock className="w-3 h-3 text-slate-400" />
                        {worker.time_from} – {worker.time_to}
                    </span>
                )}
            </div>

            {worker.tech_tags?.length > 0 && (
                <TagList tags={worker.tech_tags} colorClass="bg-green-50 text-green-700 border-green-100" />
            )}
        </div>
    );
}

export default function CoWork({ legacyCoWorkers = [] }) {
    const [search, setSearch] = useState('');

    const filteredLegacy = legacyCoWorkers.filter(w =>
        !search ||
        w.person_name?.toLowerCase().includes(search.toLowerCase()) ||
        w.email?.toLowerCase().includes(search.toLowerCase()) ||
        w.tech_tags?.some(t => t.name?.toLowerCase().includes(search.toLowerCase()))
    );

    return (
        <AdminSidebarLayout title={__('general.co_work')} header="Private Co-Work">
            <Head title={__('general.co_work')} />

            {/* Stats row */}
            <div className="mb-6">
                <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center">
                    <span className="text-2xl font-semibold text-green-600">{legacyCoWorkers.length}</span>
                    <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.legacy_co_workers')}</span>
                </div>
            </div>

            {/* Search + list */}
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm mb-6 overflow-hidden">
                <div className="flex items-center justify-end px-4 pt-4 pb-0 gap-4 border-b border-slate-100">
                    <div className="relative mb-2">
                        <Search className="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input
                            id="cowork-search"
                            type="text"
                            placeholder="Search by name, email, tag…"
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                            className="h-9 ps-9 pe-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-transparent w-64"
                        />
                    </div>
                </div>

                <div className="p-4">
                    {filteredLegacy.length === 0 ? (
                        <div className="py-16 flex flex-col items-center text-slate-400 gap-3">
                            <Users className="w-10 h-10 opacity-30" />
                            <p className="text-sm">
                                {search ? 'No results for your search.' : 'No legacy co-workers found.'}
                            </p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            {filteredLegacy.map(w => <LegacyCard key={w.id} worker={w} />)}
                        </div>
                    )}
                </div>
            </div>
        </AdminSidebarLayout>
    );
}