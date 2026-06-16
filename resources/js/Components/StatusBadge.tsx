const colors: Record<string, string> = {
    success:   'bg-green-100 text-green-700',
    partial:   'bg-yellow-100 text-yellow-700',
    failed:    'bg-red-100 text-red-700',
    pending:   'bg-gray-100 text-gray-600',
    duplicate: 'bg-blue-100 text-blue-700',
    info:      'bg-sky-100 text-sky-700',
    warning:   'bg-orange-100 text-orange-700',
    error:     'bg-red-100 text-red-700',
};

export default function StatusBadge({ status }: { status: string }) {
    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize ${colors[status] ?? 'bg-gray-100 text-gray-600'}`}>
            {status}
        </span>
    );
}
