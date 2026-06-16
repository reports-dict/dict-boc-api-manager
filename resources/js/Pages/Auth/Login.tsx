import { useForm, Head } from '@inertiajs/react';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        username: '',
        password: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <>
            <Head title="Sign In" />
            <div className="min-h-screen flex items-center justify-center bg-gray-900">
                <div className="w-full max-w-sm">
                    <div className="text-center mb-6">
                        <p className="text-xs font-semibold text-gray-500 uppercase tracking-widest">DICT-BOC</p>
                        <h1 className="text-2xl font-bold text-white mt-1">API Bridge</h1>
                        <p className="text-xs text-gray-500 mt-1">Port Terminal Data Transmission System</p>
                    </div>

                    <div className="bg-gray-800 rounded-lg p-6 border border-gray-700 shadow-xl">
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-medium text-gray-400 mb-1">Username</label>
                                <input
                                    type="text"
                                    value={data.username}
                                    onChange={(e) => setData('username', e.target.value)}
                                    autoFocus
                                    className="w-full bg-gray-900 border border-gray-600 text-white text-sm rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter username"
                                />
                                {errors.username && <p className="text-red-400 text-xs mt-1">{errors.username}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-400 mb-1">Password</label>
                                <input
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    className="w-full bg-gray-900 border border-gray-600 text-white text-sm rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="••••••••"
                                />
                                {errors.password && <p className="text-red-400 text-xs mt-1">{errors.password}</p>}
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium py-2 rounded transition-colors"
                            >
                                {processing ? 'Signing in…' : 'Sign In'}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
