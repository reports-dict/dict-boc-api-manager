import { Head, Link } from '@inertiajs/react';
import Layout from '@/Components/Layout';
import StatusBadge from '@/Components/StatusBadge';

interface TypeStat { today_count: number; last_status: string | null; last_sent: string | null; }
interface Transmission {
    id: number; type: string; status: string; date_from: string; date_to: string;
    records_count: number; success_count: number; triggered_by: string; sent_by: string | null; created_at: string;
}

const typeLabels: Record<string, string> = {
    discharge: 'Discharge', load: 'Load', release: 'Release', receive: 'Receive',
};
const typeColors: Record<string, string> = {
    discharge: 'border-l-blue-500', load: 'border-l-green-500',
    release: 'border-l-orange-500', receive: 'border-l-purple-500',
};

export default function Dashboard({
    typeStats,
    recentTransmissions,
}: {
    typeStats: Record<string, TypeStat>;
    recentTransmissions: Transmission[];
}) {
    return (
        <Layout title="Dashboard">
            <Head title="Dashboard" />

            {/* Stats cards */}
            <div className="grid grid-cols-4 gap-4 mb-6">
                {Object.entries(typeStats).map(([type, stat]) => (
                    <div key={type} className={`bg-white rounded border-l-4 ${typeColors[type]} p-4 shadow-sm`}>
                        <p className="text-xs text-gray-500 font-medium uppercase tracking-wide">{typeLabels[type]}</p>
                        <p className="text-2xl font-bold text-gray-800 mt-1">{stat.today_count}</p>
                        <p className="text-xs text-gray-400 mt-1">records sent today</p>
                        <div className="mt-2 flex items-center justify-between">
                            {stat.last_status ? <StatusBadge status={stat.last_status} /> : <span className="text-xs text-gray-400">—</span>}
                            {stat.last_sent && <span className="text-xs text-gray-400">{stat.last_sent}</span>}
                        </div>
                    </div>
                ))}
            </div>

            {/* Quick actions */}
            <div className="flex gap-2 mb-6">
                <Link
                    href="/send"
                    className="px-4 py-2 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition-colors"
                >
                    ⬆ Send Data
                </Link>
                <Link
                    href="/transmissions"
                    className="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-xs font-medium rounded hover:bg-gray-50 transition-colors"
                >
                    View All Transmissions
                </Link>
            </div>

            {/* Recent transmissions */}
            <div className="bg-white rounded border border-gray-200 shadow-sm">
                <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h2 className="text-xs font-semibold text-gray-600 uppercase tracking-wide">Recent Transmissions</h2>
                    <Link href="/transmissions" className="text-xs text-blue-600 hover:underline">View all</Link>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-xs">
                        <thead>
                            <tr className="bg-gray-50 border-b border-gray-100">
                                <th className="text-left px-4 py-2 text-gray-500 font-medium">ID</th>
                                <th className="text-left px-4 py-2 text-gray-500 font-medium">Type</th>
                                <th className="text-left px-4 py-2 text-gray-500 font-medium">Date Range</th>
                                <th className="text-left px-4 py-2 text-gray-500 font-medium">Status</th>
                                <th className="text-right px-4 py-2 text-gray-500 font-medium">Records</th>
                                <th className="text-left px-4 py-2 text-gray-500 font-medium">Triggered</th>
                                <th className="text-left px-4 py-2 text-gray-500 font-medium">Sent At</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-50">
                            {recentTransmissions.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="text-center py-8 text-gray-400">No transmissions yet.</td>
                                </tr>
                            ) : recentTransmissions.map((t) => (
                                <tr key={t.id} className="hover:bg-gray-50 transition-colors">
                                    <td className="px-4 py-2 text-gray-400">#{t.id}</td>
                                    <td className="px-4 py-2">
                                        <span className="capitalize font-medium text-gray-700">{t.type}</span>
                                    </td>
                                    <td className="px-4 py-2 text-gray-500">
                                        {t.date_from === t.date_to ? t.date_from : `${t.date_from} → ${t.date_to}`}
                                    </td>
                                    <td className="px-4 py-2"><StatusBadge status={t.status} /></td>
                                    <td className="px-4 py-2 text-right text-gray-600">{t.records_count}</td>
                                    <td className="px-4 py-2 text-gray-500 capitalize">{t.triggered_by}</td>
                                    <td className="px-4 py-2 text-gray-400">{t.created_at}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </Layout>
    );
}
