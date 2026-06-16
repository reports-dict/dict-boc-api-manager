import { Head, router } from '@inertiajs/react';
import Layout from '@/Components/Layout';
import StatusBadge from '@/Components/StatusBadge';
import { useState } from 'react';

interface Log { id: number; level: string; message: string; context: Record<string, unknown> | null; created_at: string; }
interface Paginated<T> { data: T[]; current_page: number; last_page: number; total: number; from: number; to: number; }

export default function LogsIndex({
    logs,
    filters,
}: {
    logs: Paginated<Log>;
    filters: { level?: string; date?: string; search?: string };
}) {
    const [f, setF] = useState(filters);
    const [expanded, setExpanded] = useState<number | null>(null);

    const apply = () => router.get('/logs', f as Record<string, string>, { preserveState: true });
    const reset = () => { setF({}); router.get('/logs', {}); };
    const goToPage = (page: number) => router.get('/logs', { ...(f as Record<string, string>), page: String(page) });

    const buildPages = (current: number, last: number): (number | '...')[] => {
        if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
        const pages: (number | '...')[] = [1];
        if (current > 3) pages.push('...');
        for (let p = Math.max(2, current - 1); p <= Math.min(last - 1, current + 1); p++) pages.push(p);
        if (current < last - 2) pages.push('...');
        pages.push(last);
        return pages;
    };

    return (
        <Layout title="Activity Logs">
            <Head title="Logs" />

            {/* Filters */}
            <div className="bg-white rounded border border-gray-200 shadow-sm p-3 mb-4 flex flex-wrap gap-2 items-end">
                <div>
                    <label className="block text-xs text-gray-500 mb-1">Level</label>
                    <select value={f.level ?? ''} onChange={(e) => setF({ ...f, level: e.target.value })}
                        className="border border-gray-300 rounded px-2 py-1 text-xs">
                        <option value="">All</option>
                        {['info', 'warning', 'error'].map((l) => (
                            <option key={l} value={l} className="capitalize">{l}</option>
                        ))}
                    </select>
                </div>
                <div>
                    <label className="block text-xs text-gray-500 mb-1">Date</label>
                    <input type="date" value={f.date ?? ''} onChange={(e) => setF({ ...f, date: e.target.value })}
                        className="border border-gray-300 rounded px-2 py-1 text-xs" />
                </div>
                <div>
                    <label className="block text-xs text-gray-500 mb-1">Search</label>
                    <input type="text" value={f.search ?? ''} onChange={(e) => setF({ ...f, search: e.target.value })}
                        placeholder="Search message…"
                        className="border border-gray-300 rounded px-2 py-1 text-xs w-48" />
                </div>
                <button onClick={apply} className="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">Filter</button>
                <button onClick={reset} className="px-3 py-1 border border-gray-300 text-gray-600 text-xs rounded hover:bg-gray-50">Reset</button>
            </div>

            <div className="bg-white rounded border border-gray-200 shadow-sm">
                <div className="px-4 py-3 border-b border-gray-100">
                    <p className="text-xs text-gray-500">
                        {logs.total === 0
                            ? 'No entries'
                            : `Showing ${logs.from}–${logs.to} of ${logs.total} entries`}
                    </p>
                </div>
                <div className="divide-y divide-gray-50">
                    {logs.data.length === 0 ? (
                        <p className="text-center py-10 text-gray-400 text-xs">No log entries found.</p>
                    ) : logs.data.map((log) => (
                        <div key={log.id}>
                            <div className="px-4 py-2.5 flex items-start gap-3 hover:bg-gray-50">
                                <span className="text-xs text-gray-400 font-mono shrink-0 w-36">{log.created_at}</span>
                                <div className="w-16 shrink-0"><StatusBadge status={log.level} /></div>
                                <p className="text-xs text-gray-700 flex-1">{log.message}</p>
                                {log.context && (
                                    <button
                                        onClick={() => setExpanded(expanded === log.id ? null : log.id)}
                                        className="text-xs text-blue-600 hover:underline shrink-0"
                                    >
                                        {expanded === log.id ? 'Hide' : 'Context'}
                                    </button>
                                )}
                            </div>
                            {expanded === log.id && log.context && (
                                <div className="px-4 py-2 bg-gray-50 border-t border-gray-100">
                                    <pre className="text-xs text-gray-600 font-mono overflow-x-auto bg-gray-100 rounded p-2">
                                        {JSON.stringify(log.context, null, 2)}
                                    </pre>
                                </div>
                            )}
                        </div>
                    ))}
                </div>

                {logs.last_page > 1 && (
                    <div className="px-4 py-3 border-t border-gray-100 flex items-center gap-1 flex-wrap">
                        <button
                            disabled={logs.current_page === 1}
                            onClick={() => goToPage(logs.current_page - 1)}
                            className="px-2 py-1 text-xs rounded border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            ← Prev
                        </button>
                        {buildPages(logs.current_page, logs.last_page).map((page, i) =>
                            page === '...'
                                ? <span key={`e${i}`} className="px-1 text-xs text-gray-400">…</span>
                                : <button
                                    key={page}
                                    onClick={() => goToPage(page)}
                                    className={`px-2 py-1 text-xs rounded ${
                                        page === logs.current_page
                                            ? 'bg-blue-600 text-white'
                                            : 'border border-gray-300 text-gray-600 hover:bg-gray-50'
                                    }`}
                                >
                                    {page}
                                </button>
                        )}
                        <button
                            disabled={logs.current_page === logs.last_page}
                            onClick={() => goToPage(logs.current_page + 1)}
                            className="px-2 py-1 text-xs rounded border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            Next →
                        </button>
                    </div>
                )}
            </div>
        </Layout>
    );
}
