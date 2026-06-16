import { Link, usePage, router } from '@inertiajs/react';
import { ReactNode } from 'react';

interface User { id: number; name: string; username: string; role: string; }
interface PageProps { auth: { user: User | null }; flash: { success?: string; error?: string } }

const navItems = [
    { label: 'Dashboard', href: '/', icon: '▦' },
    { label: 'Send Data', href: '/send', icon: '⬆' },
    { label: 'Transmissions', href: '/transmissions', icon: '⇄' },
    { label: 'Logs', href: '/logs', icon: '≡' },
    { label: 'Settings', href: '/settings', icon: '⚙' },
];

export default function Layout({ children, title }: { children: ReactNode; title?: string }) {
    const { auth, flash } = usePage<PageProps>().props;
    const currentPath = window.location.pathname;

    const handleLogout = () => {
        router.post('/logout');
    };

    return (
        <div className="flex h-screen bg-gray-100 text-sm text-gray-800 overflow-hidden">
            {/* Sidebar */}
            <aside className="w-52 bg-gray-900 text-gray-300 flex flex-col shrink-0">
                <div className="px-4 py-4 border-b border-gray-700">
                    <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">DICT-BOC</p>
                    <p className="text-white font-bold text-sm leading-tight mt-0.5">API Bridge</p>
                </div>

                <nav className="flex-1 py-3 px-2 space-y-0.5">
                    {navItems.map((item) => {
                        const active = currentPath === item.href ||
                            (item.href !== '/' && currentPath.startsWith(item.href));
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`flex items-center gap-2.5 px-3 py-2 rounded text-xs font-medium transition-colors ${
                                    active
                                        ? 'bg-blue-600 text-white'
                                        : 'hover:bg-gray-800 text-gray-400 hover:text-white'
                                }`}
                            >
                                <span className="text-base leading-none">{item.icon}</span>
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>

                <div className="px-3 py-3 border-t border-gray-700">
                    <p className="text-xs text-gray-400 truncate">{auth.user?.name}</p>
                    <p className="text-xs text-gray-600 truncate">{auth.user?.username}</p>
                    <button
                        onClick={handleLogout}
                        className="mt-2 w-full text-left text-xs text-red-400 hover:text-red-300 transition-colors"
                    >
                        Sign out
                    </button>
                </div>
            </aside>

            {/* Main */}
            <div className="flex-1 flex flex-col overflow-hidden">
                {/* Top bar */}
                <header className="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between shrink-0">
                    <h1 className="font-semibold text-gray-700 text-sm">{title ?? 'DICT-BOC API Bridge'}</h1>
                    <span className="text-xs text-gray-400 capitalize bg-gray-100 px-2 py-0.5 rounded">
                        {auth.user?.role}
                    </span>
                </header>

                {/* Flash messages */}
                {flash.success && (
                    <div className="mx-6 mt-3 px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded text-xs">
                        {flash.success}
                    </div>
                )}
                {flash.error && (
                    <div className="mx-6 mt-3 px-4 py-2 bg-red-50 border border-red-200 text-red-700 rounded text-xs">
                        {flash.error}
                    </div>
                )}

                {/* Content */}
                <main className="flex-1 overflow-y-auto p-6">
                    {children}
                </main>
            </div>
        </div>
    );
}
