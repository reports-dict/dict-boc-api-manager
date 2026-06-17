import { Head, Link, router } from '@inertiajs/react';
import Layout from '@/Components/Layout';
import StatusBadge from '@/Components/StatusBadge';
import { useState } from 'react';

interface Transmission {
    id: number; type: string; status: string; date_from: string; date_to: string;
    records_count: number; success_count: number; failed_count: number; duplicate_count: number;
    triggered_by: string; sent_by: string | null; created_at: string;
}
interface Paginated<T> { data: T[]; current_page: number; last_page: number; total: number; from: number | null; to: number | null; }

export default function TransmissionsIndex({
    transmissions,
    filters,
}: {
    transmissions: Paginated<Transmission>;
    filters: { type?: string; status?: string; date_from?: string; date_to?: string };
}) {
    const [f, setF] = useState(filters);

    const applyFilter = () => {
        router.get('/transmissions', f as Record<string, string>, { preserveState: true });
    };
    const reset = () => {
        setF({});
        router.get('/transmissions', {});
    };

    return (
        <Layout title="Transmissions">
            <Head title="Transmissions" />

            {/* Filters */}
            <div className="bg-white rounded border border-gray-200 shadow-sm p-3 mb-4 flex flex-wrap gap-2 items-end">
                <div>
                    <label className="block text-xs text-gray-500 mb-1">Type</label>
                    <select
                        value={f.type ?? ''}
                        onChange={(e) => setF({ ...f, type: e.target.value })}
                        className="border border-gray-300 rounded px-2 py-1 text-xs"
                    >
                        <option value="">All</option>
                        {['discharge', 'load', 'release', 'receive'].map((t) => (
                            <option key={t} value={t} className="capitalize">{t}</option>
                        ))}
                    </select>
                </div>
                <div>
                    <label className="block text-xs text-gray-500 mb-1">Status</label>
                    <select
                        value={f.status ?? ''}
                        onChange={(e) => setF({ ...f, status: e.target.value })}
                        className="border border-gray-300 rounded px-2 py-1 text-xs"
                    >
                        <option value="">All</option>
                        {['pending', 'success', 'partial', 'failed'].map((s) => (
                            <option key={s} value={s} className="capitalize">{s}</option>
                        ))}
                    </select>
                </div>
                <div>
                    <label className="block text-xs text-gray-500 mb-1">From</label>
                    <input type="date" value={f.date_from ?? ''} onChange={(e) => setF({ ...f, date_from: e.target.value })}
                        className="border border-gray-300 rounded px-2 py-1 text-xs" />
                </div>
                <div>
                    <label className="block text-xs text-gray-500 mb-1">To</label>
                    <input type="date" value={f.date_to ?? ''} onChange={(e) => setF({ ...f, date_to: e.target.value })}
                        className="border border-gray-300 rounded px-2 py-1 text-xs" />
                </div>
                <button onClick={applyFilter}
                    className="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">Filter</button>
                <button onClick={reset}
                    className="px-3 py-1 border border-gray-300 text-gray-600 text-xs rounded hover:bg-gray-50">Reset</button>
            </div>

            <div className="bg-white rounded border border-gray-200 shadow-sm">
                <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <p className="text-xs text-gray-500">{transmissions.total} total transmissions</p>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-xs">
                        <thead>
                            <tr className="bg-gray-50 border-b border-gray-100 text-left">
                                <th className="px-4 py-2 text-gray-500 font-medium">ID</th>
                                <th className="px-4 py-2 text-gray-500 font-medium">Type</th>
                                <th className="px-4 py-2 text-gray-500 font-medium">Date Range</th>
                                <th className="px-4 py-2 text-gray-500 font-medium">Status</th>
                                <th className="px-4 py-2 text-gray-500 font-medium text-right">Total</th>
                                <th className="px-4 py-2 text-gray-500 font-medium text-right">✓</th>
                                <th className="px-4 py-2 text-gray-500 font-medium text-right">⊘</th>
                                <th className="px-4 py-2 text-gray-500 font-medium text-right">⊕</th>
                                <th className="px-4 py-2 text-gray-500 font-medium">By</th>
                                <th className="px-4 py-2 text-gray-500 font-medium">Sent At</th>
                                <th className="px-4 py-2" colSpan={2}></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-50">
                            {transmissions.data.length === 0 ? (
                                <tr>
                                    <td colSpan={12} className="text-center py-10 text-gray-400">No transmissions found.</td>
                                </tr>
                            ) : transmissions.data.map((t) => (
                                <tr key={t.id} className="hover:bg-gray-50 transition-colors">
                                    <td className="px-4 py-2 text-gray-400">#{t.id}</td>
                                    <td className="px-4 py-2 font-medium capitalize text-gray-700">{t.type}</td>
                                    <td className="px-4 py-2 text-gray-500">
                                        {t.date_from === t.date_to ? t.date_from : `${t.date_from} → ${t.date_to}`}
                                    </td>
                                    <td className="px-4 py-2"><StatusBadge status={t.status} /></td>
                                    <td className="px-4 py-2 text-right text-gray-600">{t.records_count}</td>
                                    <td className="px-4 py-2 text-right text-green-600">{t.success_count}</td>
                                    <td className="px-4 py-2 text-right text-red-500">{t.failed_count}</td>
                                    <td className="px-4 py-2 text-right text-blue-500">{t.duplicate_count}</td>
                                    <td className="px-4 py-2 text-gray-500 capitalize">{t.sent_by ?? t.triggered_by}</td>
                                    <td className="px-4 py-2 text-gray-400">{t.created_at}</td>
                                    <td className="px-4 py-2">
                                        <Link href={`/transmissions/${t.id}`}
                                            className="text-blue-600 hover:underline">Details</Link>
                                    </td>
                                    <td className="px-4 py-2">
                                        <a
                                            href={`/transmissions/${t.id}/download`}
                                            download
                                            className="text-green-600 hover:underline"
                                        >
                                            Download
                                        </a>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {transmissions.last_page >= 1 && (
                    <div className="px-4 py-3 border-t border-gray-100 flex items-center justify-between gap-2">
                        {/* Record count */}
                        <p className="text-xs text-gray-400">
                            {transmissions.from != null
                                ? `Showing ${transmissions.from}–${transmissions.to} of ${transmissions.total}`
                                : 'No results'}
                        </p>

                        {/* Page buttons */}
                        {transmissions.last_page > 1 && (
                            <div className="flex items-center gap-1">
                                {/* Previous */}
                                <button
                                    onClick={() => router.get('/transmissions', { ...f, page: String(transmissions.current_page - 1) })}
                                    disabled={transmissions.current_page === 1}
                                    className="px-2 py-1 text-xs border border-gray-300 rounded text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                                >
                                    ‹ Prev
                                </button>

                                {/* Page numbers with ellipsis */}
                                {(() => {
                                    const cur  = transmissions.current_page;
                                    const last = transmissions.last_page;
                                    const pages: (number | '...')[] = [];

                                    for (let p = 1; p <= last; p++) {
                                        if (p === 1 || p === last || (p >= cur - 1 && p <= cur + 1)) {
                                            pages.push(p);
                                        } else if (pages[pages.length - 1] !== '...') {
                                            pages.push('...');
                                        }
                                    }

                                    return pages.map((p, i) =>
                                        p === '...' ? (
                                            <span key={`ellipsis-${i}`} className="px-1 text-xs text-gray-400">…</span>
                                        ) : (
                                            <button
                                                key={p}
                                                onClick={() => router.get('/transmissions', { ...f, page: String(p) })}
                                                className={`min-w-[28px] px-2 py-1 text-xs rounded ${
                                                    p === cur
                                                        ? 'bg-blue-600 text-white font-medium'
                                                        : 'border border-gray-300 text-gray-600 hover:bg-gray-50'
                                                }`}
                                            >
                                                {p}
                                            </button>
                                        )
                                    );
                                })()}

                                {/* Next */}
                                <button
                                    onClick={() => router.get('/transmissions', { ...f, page: String(transmissions.current_page + 1) })}
                                    disabled={transmissions.current_page === transmissions.last_page}
                                    className="px-2 py-1 text-xs border border-gray-300 rounded text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                                >
                                    Next ›
                                </button>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </Layout>
    );
}
