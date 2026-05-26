import React, { useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { PageHeader } from '@/Components/UI/PageHeader';
import { DataTable } from '@/Components/UI/DataTable';

// ─── Types ────────────────────────────────────────────────────────────────────

interface UserRow {
    id: number;
    name: string;
    email: string;
    coins_balance: number;
    avatar: string | null;
}

interface Pagination {
    data: UserRow[];
    links: any[];
    meta: any;
}

interface Props {
    users: Pagination;
    search: string;
}

// ─── Adjust Points Modal ───────────────────────────────────────────────────────

interface AdjustModalProps {
    user: UserRow;
    onClose: () => void;
}

function AdjustModal({ user, onClose }: AdjustModalProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        amount: '' as string | number,
        reason: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.points.adjust', user.id), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <div
            className="modal fade show d-block"
            style={{ backgroundColor: 'rgba(0,0,0,0.55)' }}
            onClick={(e) => e.target === e.currentTarget && onClose()}
        >
            <div className="modal-dialog modal-dialog-centered">
                <div className="modal-content rounded-0 border-0 shadow-lg">
                    <div className="modal-header border-0 bg-black text-white rounded-0">
                        <h5 className="modal-title fw-bold">
                            <i className="fas fa-coins me-2 text-warning" />
                            Adjust Points — {user.name}
                        </h5>
                        <button
                            type="button"
                            className="btn-close btn-close-white"
                            onClick={onClose}
                        />
                    </div>

                    <form onSubmit={handleSubmit}>
                        <div className="modal-body p-4">
                            {/* Current balance badge */}
                            <div className="mb-4 text-center">
                                <span className="badge bg-black fs-6 px-3 py-2 rounded-0">
                                    Current Balance:{' '}
                                    <strong className="text-warning">{user.coins_balance.toLocaleString()}</strong>{' '}
                                    pts
                                </span>
                            </div>

                            {/* Amount */}
                            <div className="mb-3">
                                <label className="form-label fw-semibold small text-uppercase tracking-wide">
                                    Amount
                                </label>
                                <input
                                    type="number"
                                    className={`form-control rounded-0 ${errors.amount ? 'is-invalid' : ''}`}
                                    placeholder="e.g. 500 to add, -200 to deduct"
                                    value={data.amount}
                                    onChange={(e) => setData('amount', e.target.value)}
                                    autoFocus
                                />
                                {errors.amount && (
                                    <div className="invalid-feedback">{errors.amount}</div>
                                )}
                                <div className="form-text">Positive = add points · Negative = deduct points</div>
                            </div>

                            {/* Reason */}
                            <div className="mb-3">
                                <label className="form-label fw-semibold small text-uppercase tracking-wide">
                                    Reason
                                </label>
                                <input
                                    type="text"
                                    className={`form-control rounded-0 ${errors.reason ? 'is-invalid' : ''}`}
                                    placeholder="e.g. Bonus for referral campaign"
                                    value={data.reason}
                                    onChange={(e) => setData('reason', e.target.value)}
                                />
                                {errors.reason && (
                                    <div className="invalid-feedback">{errors.reason}</div>
                                )}
                            </div>
                        </div>

                        <div className="modal-footer border-0 bg-light rounded-0">
                            <button
                                type="button"
                                className="btn btn-outline-secondary rounded-0"
                                onClick={onClose}
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                className="btn btn-dark rounded-0"
                                disabled={processing}
                            >
                                {processing ? (
                                    <>
                                        <span className="spinner-border spinner-border-sm me-2" />
                                        Saving…
                                    </>
                                ) : (
                                    <>
                                        <i className="fas fa-check me-2" />
                                        Apply
                                    </>
                                )}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}

// ─── History Modal ─────────────────────────────────────────────────────────────

interface HistoryEntry {
    id: number;
    action_name: string;
    coins_reward: number;
    created_at: string;
}

interface HistoryModalProps {
    user: UserRow;
    onClose: () => void;
}

function HistoryModal({ user, onClose }: HistoryModalProps) {
    const [entries, setEntries] = useState<HistoryEntry[]>([]);
    const [loading, setLoading] = useState(true);

    React.useEffect(() => {
        fetch(route('admin.points.history', user.id), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((r) => r.json())
            .then((data) => {
                setEntries(data);
                setLoading(false);
            })
            .catch(() => setLoading(false));
    }, [user.id]);

    return (
        <div
            className="modal fade show d-block"
            style={{ backgroundColor: 'rgba(0,0,0,0.55)' }}
            onClick={(e) => e.target === e.currentTarget && onClose()}
        >
            <div className="modal-dialog modal-dialog-centered modal-lg">
                <div className="modal-content rounded-0 border-0 shadow-lg">
                    <div className="modal-header border-0 bg-black text-white rounded-0">
                        <h5 className="modal-title fw-bold">
                            <i className="fas fa-history me-2 text-warning" />
                            Points History — {user.name}
                        </h5>
                        <button
                            type="button"
                            className="btn-close btn-close-white"
                            onClick={onClose}
                        />
                    </div>

                    <div className="modal-body p-0" style={{ maxHeight: '60vh', overflowY: 'auto' }}>
                        {loading ? (
                            <div className="text-center py-5">
                                <span className="spinner-border" />
                            </div>
                        ) : entries.length === 0 ? (
                            <div className="text-center py-5 text-muted">
                                <i className="fas fa-inbox fa-2x mb-3 d-block" />
                                No points history found.
                            </div>
                        ) : (
                            <table className="table table-hover mb-0 align-middle">
                                <thead className="table-dark">
                                    <tr>
                                        <th className="fw-semibold">Action / Label</th>
                                        <th className="fw-semibold text-center">Points</th>
                                        <th className="fw-semibold text-end">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {entries.map((e) => (
                                        <tr key={e.id}>
                                            <td className="small text-truncate" style={{ maxWidth: 320 }}>
                                                {e.action_name}
                                            </td>
                                            <td className="text-center">
                                                <span
                                                    className={`badge rounded-0 ${
                                                        e.coins_reward >= 0 ? 'bg-success' : 'bg-danger'
                                                    }`}
                                                >
                                                    {e.coins_reward >= 0 ? '+' : ''}
                                                    {e.coins_reward.toLocaleString()}
                                                </span>
                                            </td>
                                            <td className="text-end small text-muted">
                                                {new Date(e.created_at).toLocaleString()}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>

                    <div className="modal-footer border-0 bg-light rounded-0">
                        <button
                            type="button"
                            className="btn btn-outline-dark rounded-0"
                            onClick={onClose}
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ─── Main Page ─────────────────────────────────────────────────────────────────

export default function Index({ users, search }: Props) {
    const [adjustTarget, setAdjustTarget] = useState<UserRow | null>(null);
    const [historyTarget, setHistoryTarget] = useState<UserRow | null>(null);
    const [searchInput, setSearchInput] = useState(search ?? '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('admin.points.index'), { search: searchInput }, { preserveState: true });
    };

    const headers = [
        {
            key: 'name',
            label: 'User',
            render: (u: UserRow) => (
                <div className="d-flex align-items-center gap-2">
                    <div
                        className="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold"
                        style={{ width: 32, height: 32, fontSize: 13, flexShrink: 0 }}
                    >
                        {u.name.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <div className="fw-semibold small">{u.name}</div>
                        <div className="text-muted" style={{ fontSize: '0.72rem' }}>{u.email}</div>
                    </div>
                </div>
            ),
        },
        {
            key: 'coins_balance',
            label: 'Points Balance',
            sortable: true,
            render: (u: UserRow) => (
                <span
                    className={`badge rounded-0 fw-bold px-3 py-2 ${
                        u.coins_balance > 0
                            ? 'bg-black text-warning'
                            : u.coins_balance < 0
                            ? 'bg-danger'
                            : 'bg-secondary'
                    }`}
                    style={{ fontSize: '0.78rem', letterSpacing: 0.5 }}
                >
                    <i className="fas fa-coins me-1" />
                    {u.coins_balance.toLocaleString()} pts
                </span>
            ),
        },
    ];

    const actions = [
        {
            type: 'button',
            label: 'Adjust',
            title: 'Add / Deduct Points',
            icon: 'fas fa-plus-minus',
            variant: 'outline-dark',
            size: 'sm',
            onclick: (u: UserRow) => setAdjustTarget(u),
        },
        {
            type: 'button',
            label: 'History',
            title: 'View Points History',
            icon: 'fas fa-history',
            variant: 'outline-secondary',
            size: 'sm',
            onclick: (u: UserRow) => setHistoryTarget(u),
        },
    ];

    return (
        <AdminSidebarLayout title="Points Control" header="Points Control">
            <Head title="Points Control" />

            <div className="dashboard-container at-mobile-scroll-fix admin-table-mobile-cards">
                <PageHeader
                    title="Points Control"
                    subtitle="View and adjust user points balances across the platform."
                />

                {/* Search */}
                <form onSubmit={handleSearch} className="mt-4 mb-3 d-flex gap-2">
                    <input
                        type="text"
                        className="form-control rounded-0"
                        placeholder="Search by name or email…"
                        value={searchInput}
                        onChange={(e) => setSearchInput(e.target.value)}
                        style={{ maxWidth: 340 }}
                    />
                    <button type="submit" className="btn btn-dark rounded-0 px-4">
                        <i className="fas fa-search me-2" />
                        Search
                    </button>
                    {search && (
                        <button
                            type="button"
                            className="btn btn-outline-secondary rounded-0"
                            onClick={() => {
                                setSearchInput('');
                                router.get(route('admin.points.index'));
                            }}
                        >
                            Clear
                        </button>
                    )}
                </form>

                <div className="at-card admin-table-card">
                    <div className="card-body p-0">
                        <DataTable
                            headers={headers}
                            items={users.data}
                            actions={actions}
                            pagination={users}
                            hover={true}
                            striped={false}
                        />
                    </div>
                </div>
            </div>

            {/* Modals */}
            {adjustTarget && (
                <AdjustModal
                    user={adjustTarget}
                    onClose={() => setAdjustTarget(null)}
                />
            )}
            {historyTarget && (
                <HistoryModal
                    user={historyTarget}
                    onClose={() => setHistoryTarget(null)}
                />
            )}
        </AdminSidebarLayout>
    );
}
