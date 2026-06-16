import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import Layout from '@/Components/Layout';
import StatusBadge from '@/Components/StatusBadge';

interface Record { id: number; container_no: string; status: string; response_message: string | null; payload: Record<string, unknown>; created_at: string; }
interface Transmission { id: number; type: string; status: string; date_from: string; date_to: string; records_count: number; success_count: number; failed_count: number; duplicate_count: number; triggered_by: string; response_summary: unknown; created_at: string; }
interface Paginated<T> { data: T[]; current_page: number; last_page: number; }

export default function TransmissionShow({
    transmission,
    records,
}: {
    transmission: Transmission;
    records: Paginated<Record>;
}) {
    const [expanded, setExpanded] = useState<number | null>(null);

    return (
        <Layout title={`Transmission #${transmission.id}`}>
            <Head title={`Transmission #${transmission.id}`} />

            <Link href="/transmissions" className="text-xs text-blue-600 hover:underline mb-4 inline-block">← Back</Link>

            {/* Header */}
            <div className="bg-white rounded border border-gray-200 shadow-sm p-4 mb-4">
                <div className="flex items-start justify-between">
                    <div>
                        <div className="flex items-center gap-3">
                            <h2 className="text-sm font-bold text-gray-800 capitalize">{transmission.type} Transmission</h2>
                            <StatusBadge status={transmission.status} />
                        </div>
                        <p className="text-xs text-gray-400 mt-1">
                            {transmission.date_from === transmission.date_to
                                ? transmission.date_from
                                : `${transmission.date_from} → ${transmission.date_to}`}
                            {' · '}{transmission.triggered_by} · {transmission.created_at}
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-4 gap-4 mt-4">
                    {[
                        { label: 'Total', value: transmission.records_count, color: 'text-gray-700' },
                        { label: 'Success', value: transmission.success_count, color: 'text-green-600' },
                        { label: 'Failed', value: transmission.failed_count, color: 'text-red-500' },
                        { label: 'Duplicate', value: transmission.duplicate_count, color: 'text-blue-500' },
                    ].map((s) => (
                        <div key={s.label} className="text-center p-3 bg-gray-50 rounded border border-gray-100">
                            <p className={`text-xl font-bold ${s.color}`}>{s.value}</p>
                            <p className="text-xs text-gray-500 mt-0.5">{s.label}</p>
                        </div>
                    ))}
                </div>
            </div>

            {/* Records */}
            <div className="bg-white rounded border border-gray-200 shadow-sm">
                <div className="px-4 py-3 border-b border-gray-100">
                    <h2 className="text-xs font-semibold text-gray-600 uppercase tracking-wide">Container Records</h2>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-xs">
                        <thead>
                            <tr className="bg-gray-50 border-b border-gray-100 text-left">
                                <th className="px-4 py-2 text-gray-500 font-medium">Container No</th>
                                <th className="px-4 py-2 text-gray-500 font-medium">Status</th>
                                <th className="px-4 py-2 text-gray-500 font-medium">Response</th>
                                <th className="px-4 py-2 text-gray-500 font-medium">Time</th>
                                <th className="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-50">
                            {records.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-4 py-8 text-center text-xs text-gray-400">
                                        No container records found for this transmission.
                                    </td>
                                </tr>
                            ) : records.data.map((r) => (
                                <React.Fragment key={r.id}>
                                    <tr className="hover:bg-gray-50">
                                        <td className="px-4 py-2 font-mono font-medium text-gray-800">{r.container_no}</td>
                                        <td className="px-4 py-2"><StatusBadge status={r.status} /></td>
                                        <td className="px-4 py-2 text-gray-500">{r.response_message ?? '—'}</td>
                                        <td className="px-4 py-2 text-gray-400">{r.created_at}</td>
                                        <td className="px-4 py-2">
                                            <button
                                                onClick={() => setExpanded(expanded === r.id ? null : r.id)}
                                                className="text-blue-600 hover:underline"
                                            >
                                                {expanded === r.id ? 'Hide' : 'Payload'}
                                            </button>
                                        </td>
                                    </tr>
                                    {expanded === r.id && (
                                        <tr>
                                            <td colSpan={5} className="px-4 py-2 bg-gray-50">
                                                <pre className="text-xs text-gray-600 overflow-x-auto bg-gray-100 rounded p-3 font-mono">
                                                    {JSON.stringify(r.payload, null, 2)}
                                                </pre>
                                            </td>
                                        </tr>
                                    )}
                                </React.Fragment>
                            ))}
                        </tbody>
                    </table>
                </div>

                {records.last_page > 1 && (
                    <div className="px-4 py-3 border-t border-gray-100 flex gap-2">
                        {Array.from({ length: records.last_page }, (_, i) => i + 1).map((page) => (
                            <button
                                key={page}
                                onClick={() => router.get(`/transmissions/${transmission.id}`, { page: String(page) })}
                                className={`px-2 py-1 text-xs rounded ${
                                    page === records.current_page
                                        ? 'bg-blue-600 text-white'
                                        : 'border border-gray-300 text-gray-600 hover:bg-gray-50'
                                }`}
                            >
                                {page}
                            </button>
                        ))}
                    </div>
                )}
            </div>
        </Layout>
    );
}
