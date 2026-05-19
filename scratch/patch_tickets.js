import fs from 'fs';
const file = 'resources/js/Pages/Support/Tickets/Index.jsx';
let content = fs.readFileSync(file, 'utf8');

// The original columns definition
const searchBlock = `    const columns = [
        { header: 'ID', accessor: (row) => \`#\${row.id}\` },
        { header: 'Subject', accessor: 'subject' },
        ...(isAdmin
            ? [{ header: 'Client', accessor: (row) => row.user?.name }]
            : []),
        {
            header: 'Status',
            accessor: (row) => <StatusBadge status={row.status} />,
        },
        {
            header: 'Priority',
            accessor: (row) => (
                <span
                    className={\`rounded px-2 py-1 text-xs font-medium \${
                        row.priority === 'High'
                            ? 'bg-red-100 text-red-800'
                            : row.priority === 'Medium'
                              ? 'bg-yellow-100 text-yellow-800'
                              : 'bg-green-100 text-green-800'
                    }\`}
                >
                    {row.priority}
                </span>
            ),
        },
        {
            header: 'Last Updated',
            accessor: (row) => new Date(row.updated_at).toLocaleDateString(),
        },
        {
            header: 'Actions',
            accessor: (row) => (
                <button
                    onClick={() => setSelectedTicket(row)}
                    className="text-indigo-600 hover:text-indigo-900"
                >
                    View
                </button>
            ),
        },
    ];`;

const replaceBlock = `    const columns = [
        { label: 'ID', key: 'id', render: (row) => \`#\${row.id}\` },
        { label: 'Subject', key: 'subject' },
        ...(isAdmin
            ? [{ label: 'Client', key: 'client', render: (row) => row.user?.name }]
            : []),
        {
            label: 'Status',
            key: 'status',
            render: (row) => <StatusBadge status={row.status} />,
        },
        {
            label: 'Priority',
            key: 'priority',
            render: (row) => (
                <span
                    className={\`rounded px-2 py-1 text-xs font-medium \${
                        row.priority === 'High'
                            ? 'bg-red-100 text-red-800'
                            : row.priority === 'Medium'
                              ? 'bg-yellow-100 text-yellow-800'
                              : 'bg-green-100 text-green-800'
                    }\`}
                >
                    {row.priority}
                </span>
            ),
        },
        {
            label: 'Last Updated',
            key: 'updated_at',
            render: (row) => new Date(row.updated_at).toLocaleDateString(),
        },
        {
            label: 'Actions',
            key: 'actions',
            render: (row) => (
                <button
                    onClick={() => setSelectedTicket(row)}
                    className="text-indigo-600 hover:text-indigo-900"
                >
                    View
                </button>
            ),
        },
    ];`;

content = content.replace(searchBlock, replaceBlock);
fs.writeFileSync(file, content);
