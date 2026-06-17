import { Head, useForm } from '@inertiajs/react';
import Layout from '@/Components/Layout';
import { useState } from 'react';

interface Settings {
    api_base_url: string; api_token_set: boolean;
    endpoint_discharge: string; endpoint_load: string; endpoint_release: string; endpoint_receive: string;
    auto_send_enabled: string; auto_send_time: string; auto_send_types: string[];
    email_report_enabled: string; email_report_recipients: string; email_smtp_password_set: boolean;
}

export default function SettingsIndex({ settings }: { settings: Settings }) {
    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        api_base_url: settings.api_base_url ?? '',
        api_token: '',
        endpoint_discharge: settings.endpoint_discharge ?? '',
        endpoint_load: settings.endpoint_load ?? '',
        endpoint_release: settings.endpoint_release ?? '',
        endpoint_receive: settings.endpoint_receive ?? '',
        auto_send_enabled: settings.auto_send_enabled === '1',
        auto_send_time: settings.auto_send_time ?? '00:05',
        auto_send_types: settings.auto_send_types ?? ['discharge', 'load', 'release', 'receive'],
        email_report_enabled: settings.email_report_enabled === '1',
        email_report_recipients: settings.email_report_recipients ?? '',
        email_smtp_password: '',
    });

    const [showToken, setShowToken] = useState(false);
    const [showSmtpPassword, setShowSmtpPassword] = useState(false);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/settings');
    };

    return (
        <Layout title="Settings">
            <Head title="Settings" />

            <div className="max-w-2xl">
                <form onSubmit={submit} className="space-y-4">
                    {/* API Configuration */}
                    <div className="bg-white rounded border border-gray-200 shadow-sm p-4">
                        <h2 className="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-3">API Configuration</h2>

                        <div className="space-y-3">
                            <div>
                                <label className="block text-xs font-medium text-gray-500 mb-1">API Token</label>
                                <div className="flex gap-2">
                                    <input
                                        type={showToken ? 'text' : 'password'}
                                        value={data.api_token}
                                        onChange={(e) => setData('api_token', e.target.value)}
                                        placeholder={settings.api_token_set ? '••••••••••••• (set — enter new to replace)' : 'Enter API token'}
                                        className="flex-1 border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                    <button type="button" onClick={() => setShowToken(!showToken)}
                                        className="px-3 py-1.5 border border-gray-300 text-xs rounded hover:bg-gray-50">
                                        {showToken ? 'Hide' : 'Show'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Endpoints */}
                    <div className="bg-white rounded border border-gray-200 shadow-sm p-4">
                        <h2 className="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-3">Endpoint URLs</h2>
                        <p className="text-xs text-gray-400 mb-3">
                            Leave blank to use the default Customs API URLs. During development, set these to your test receiver URLs.
                        </p>
                        <div className="space-y-3">
                            {(['discharge', 'load', 'release', 'receive'] as const).map((type) => {
                                const key = `endpoint_${type}` as keyof typeof data;
                                return (
                                    <div key={type}>
                                        <label className="block text-xs font-medium text-gray-500 mb-1 capitalize">{type}</label>
                                        <input
                                            type="url"
                                            value={data[key] as string}
                                            onChange={(e) => setData(key, e.target.value)}
                                            placeholder={`https://your-test-server/api/${type}.php`}
                                            className="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono text-xs"
                                        />
                                        {errors[key] && <p className="text-red-500 text-xs mt-1">{errors[key]}</p>}
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Scheduler */}
                    <div className="bg-white rounded border border-gray-200 shadow-sm p-4">
                        <h2 className="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-3">Auto-Send Schedule</h2>
                        <div className="flex items-center gap-4">
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.auto_send_enabled}
                                    onChange={(e) => setData('auto_send_enabled', e.target.checked)}
                                    className="accent-blue-600"
                                />
                                <span className="text-xs text-gray-700">Enable nightly auto-send (previous day's data)</span>
                            </label>
                        </div>
                        {data.auto_send_enabled && (
                            <div className="mt-3 space-y-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-500 mb-1">Send Time</label>
                                    <input
                                        type="time"
                                        value={data.auto_send_time}
                                        onChange={(e) => setData('auto_send_time', e.target.value)}
                                        className="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-500 mb-2">Data Types to Send</label>
                                    <div className="grid grid-cols-2 gap-2">
                                        {(['discharge', 'load', 'release', 'receive'] as const).map((type) => (
                                            <label key={type} className="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    checked={data.auto_send_types.includes(type)}
                                                    onChange={(e) => {
                                                        const next = e.target.checked
                                                            ? [...data.auto_send_types, type]
                                                            : data.auto_send_types.filter((t) => t !== type);
                                                        setData('auto_send_types', next);
                                                    }}
                                                    className="accent-blue-600"
                                                />
                                                <span className="text-xs text-gray-700 capitalize">{type}</span>
                                            </label>
                                        ))}
                                    </div>
                                    {data.auto_send_types.length === 0 && (
                                        <p className="text-xs text-amber-600 mt-1">At least one type must be selected.</p>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Email Reports */}
                    <div className="bg-white rounded border border-gray-200 shadow-sm p-4">
                        <h2 className="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-3">Email Reports</h2>
                        <div className="flex items-center gap-4">
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.email_report_enabled}
                                    onChange={(e) => setData('email_report_enabled', e.target.checked)}
                                    className="accent-blue-600"
                                />
                                <span className="text-xs text-gray-700">Send email report after auto-send</span>
                            </label>
                        </div>
                        {data.email_report_enabled && (
                            <div className="mt-3 space-y-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-500 mb-1">Recipients</label>
                                    <textarea
                                        value={data.email_report_recipients}
                                        onChange={(e) => setData('email_report_recipients', e.target.value)}
                                        placeholder={'Enter email addresses, one per line or comma-separated\ne.g. manager@company.com\nops@company.com'}
                                        rows={3}
                                        className="w-full border border-gray-300 rounded px-3 py-1.5 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-blue-500 resize-y"
                                    />
                                    {errors.email_report_recipients && <p className="text-red-500 text-xs mt-1">{errors.email_report_recipients}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-500 mb-1">SMTP Password</label>
                                    <div className="flex gap-2">
                                        <input
                                            type={showSmtpPassword ? 'text' : 'password'}
                                            value={data.email_smtp_password}
                                            onChange={(e) => setData('email_smtp_password', e.target.value)}
                                            placeholder={settings.email_smtp_password_set ? '••••••••••••• (set — enter new to replace)' : 'Enter SMTP password'}
                                            className="flex-1 border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        />
                                        <button type="button" onClick={() => setShowSmtpPassword(!showSmtpPassword)}
                                            className="px-3 py-1.5 border border-gray-300 text-xs rounded hover:bg-gray-50">
                                            {showSmtpPassword ? 'Hide' : 'Show'}
                                        </button>
                                    </div>
                                    <p className="text-xs text-gray-400 mt-1">
                                        SMTP: smtp.office365.com:587 · From: reports-dict@anflocor.com
                                    </p>
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="flex items-center gap-3">
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-6 py-2 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 disabled:opacity-50 transition-colors"
                        >
                            {processing ? 'Saving…' : 'Save Settings'}
                        </button>
                        {recentlySuccessful && (
                            <p className="text-green-600 text-xs">Saved!</p>
                        )}
                    </div>
                </form>
            </div>
        </Layout>
    );
}
