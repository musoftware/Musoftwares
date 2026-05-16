import { Link } from '@inertiajs/react';

export default function Pagination({ links }: { links: any[] }) {
  if (!links || links.length <= 3) return null;

  return (
    <div className="mt-6 flex justify-center">
      <div className="flex space-x-1 flex-wrap gap-y-2">
        {links.map((link: any, key: number) => (
          <Link
            key={key}
            href={link.url || '#'}
            className={`px-4 py-2 text-sm border rounded-md ${
              link.active
                ? 'bg-indigo-600 text-white'
                : 'bg-white text-gray-700 hover:bg-gray-50'
            } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
            dangerouslySetInnerHTML={{ __html: link.label }}
            preserveScroll
          />
        ))}
      </div>
    </div>
  );
}
