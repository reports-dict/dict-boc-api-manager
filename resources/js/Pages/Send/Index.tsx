import { Head, useForm, router } from '@inertiajs/react';
import Layout from '@/Components/Layout';
import { useState } from 'react';

const TYPES = [
    { value: 'discharge', label: 'Discharge', desc: 'Containers discharged from vessel' },
    { value: 'load', label: 'Load', desc: 'Containers loaded onto vessel' },
    { value: 'release', label: 'Release', desc: 'Containers withdrawn from terminal' },
    { value: 'receive', label: 'Receive', desc: 'Containers received at terminal' },
];

export default function SendIndex({
    defaultDateFrom,
    defaultDateTo,
}: {
    defaultDateFrom: string;
    defaultDateTo: string;
}) {
    const { data, setData, post, processing, errors } = useForm({
        types: ['discharge', 'load', 'release', 'receive'] as string[],
        date_from: defaultDateFrom,
        date_to: defaultDateTo,
        containers: '',
        send_email: false,
    });

    const [preview, setPreview] = useState<Record<string, number | { error: string }> | null>(null);
    const [previewing, setPreviewing] = useState(false);

    const toggleType = (type: string) => {
        setData('types', data.types.includes(type)
            ? data.types.filter((t) => t !== type)
            : [...data.types, type]);
        setPreview(null);
    };

    const handlePreview = async () => {
        setPreviewing(true);
        setPreview(null);
        try {
            const xsrfToken = decodeURIComponent(
                (document.cookie.match(/XSRF-TOKEN=([^;]+)/) ?? [])[1] ?? ''
            );
            const res = await fetch('/send/preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': xsrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            });
            const json = await res.json();
            setPreview(json.counts ?? null);
        } catch {
            setPreview(null);
        } finally {
            setPreviewing(false);
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/send');
    };

    return (
        <Layout title="Send Data">
            <Head title="Send Data" />

            <div className="max-w-2xl">
                <form onSubmit={submit} className="space-y-4">
                    {/* Type selection */}
                    <div className="bg-white rounded border border-gray-200 shadow-sm p-4">
                        <h2 className="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-3">Data Types</h2>
                        <div className="grid grid-cols-2 gap-2">
                            {TYPES.map((type) => {
                                const checked = data.types.includes(type.value);
                                return (
                                    <label
                                        key={type.value}
                                        className={`flex items-start gap-3 p-3 rounded border cursor-pointer transition-colors ${
                                            checked
                                                ? 'border-blue-500 bg-blue-50'
                                                : 'border-gray-200 hover:border-gray-300'
                                        }`}
                                    >
                                        <input
                                            type="checkbox"
                                            checked={checked}
                                            onChange={() => toggleType(type.value)}
                                            className="mt-0.5 accent-blue-600"
                                        />
                                        <div>
                                            <p className="text-xs font-semibold text-gray-700">{type.label}</p>
                                            <p className="text-xs text-gray-400 mt-0.5">{type.desc}</p>
                                        </div>
                                    </label>
                                );
                            })}
                        </div>
                        {errors.types && <p className="text-red-500 text-xs mt-2">{errors.types}</p>}
                    </div>

                    {/* Date range */}
                    <div className="bg-white rounded border border-gray-200 shadow-sm p-4">
                        <h2 className="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-3">Date &amp; Time Range</h2>
                        <div className="flex gap-3 items-end">
                            <div className="flex-1">
                                <label className="block text-xs text-gray-500 mb-1">From</label>
                                <input
                                    type="datetime-local"
                                    value={data.date_from}
                                    onChange={(e) => { setData('date_from', e.target.value); setPreview(null); }}
                                    className="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
                                />
                                {errors.date_from && <p className="text-red-500 text-xs mt-1">{errors.date_from}</p>}
                            </div>
                            <div className="flex-1">
                                <label className="block text-xs text-gray-500 mb-1">To</label>
                                <input
                                    type="datetime-local"
                                    value={data.date_to}
                                    onChange={(e) => { setData('date_to', e.target.value); setPreview(null); }}
                                    className="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
                                />
                                {errors.date_to && <p className="text-red-500 text-xs mt-1">{errors.date_to}</p>}
                            </div>
                        </div>
                    </div>

                    {/* Container filter */}
                    <div className="bg-white rounded border border-gray-200 shadow-sm p-4">
                        <h2 className="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-3">
                            Container Filter{' '}
                            <span className="text-gray-400 font-normal normal-case">(optional)</span>
                        </h2>
                        <textarea
                            value={data.containers}
                            onChange={(e) => { setData('containers', e.target.value); setPreview(null); }}
                            placeholder={'Enter container IDs, one per line or comma-separated\ne.g. ABCU1234567\nDEFU7654321'}
                            rows={3}
                            className="w-full border border-gray-300 rounded px-3 py-1.5 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-blue-500 resize-y"
                        />
                        <p className="text-xs text-gray-400 mt-1">
                            Leave empty to include all containers in the date range.
                        </p>
                    </div>

                    {/* Preview results */}
                    {preview && (
                        <div className="bg-white rounded border border-gray-200 shadow-sm p-4">
                            <h2 className="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Preview</h2>
                            <div className="space-y-1">
                                {Object.entries(preview).map(([type, count]) => (
                                    <div key={type} className="flex justify-between text-xs">
                                        <span className="capitalize text-gray-600">{type}</span>
                                        {typeof count === 'number'
                                            ? <span className="font-semibold text-gray-800">{count} records</span>
                                            : <span className="text-red-500">{count.error}</span>
                                        }
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Email report option */}
                    <div className="bg-white rounded border border-gray-200 shadow-sm p-4">
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={data.send_email}
                                onChange={(e) => setData('send_email', e.target.checked)}
                                className="accent-blue-600"
                            />
                            <span className="text-xs text-gray-700">Send email report after transmission</span>
                        </label>
                    </div>

                    {/* Actions */}
                    <div className="flex gap-3">
                        <button
                            type="button"
                            onClick={handlePreview}
                            disabled={previewing || data.types.length === 0}
                            className="px-4 py-2 border border-gray-300 text-gray-700 text-xs font-medium rounded hover:bg-gray-50 disabled:opacity-50 transition-colors"
                        >
                            {previewing ? 'Checking…' : 'Preview Counts'}
                        </button>
                        <button
                            type="submit"
                            disabled={processing || data.types.length === 0}
                            className="px-6 py-2 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 disabled:opacity-50 transition-colors"
                        >
                            {processing ? 'Sending…' : 'Send Data'}
                        </button>
                    </div>
                </form>
            </div>
        </Layout>
    );
}
