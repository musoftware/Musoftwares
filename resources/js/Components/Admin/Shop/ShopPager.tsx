import React from 'react';
import { Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { t } from '@/Components/Admin/SoftwareStore/t';

export interface Paginated<Row> {
    data: Row[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

export default function ShopPager({ page }: { page: Paginated<unknown> }) {
    if (page.last_page <= 1) return null;

    return (
        <nav className="flex flex-wrap items-center gap-3" aria-label={t('Pages', 'الصفحات')}>
            {page.prev_page_url && <Button asChild variant="outline"><Link href={page.prev_page_url}>{t('Previous', 'السابق')}</Link></Button>}
            <span className="text-sm">{t(`Page ${page.current_page} of ${page.last_page}`, `صفحة ${page.current_page} من ${page.last_page}`)}</span>
            {page.next_page_url && <Button asChild variant="outline"><Link href={page.next_page_url}>{t('Next', 'التالي')}</Link></Button>}
        </nav>
    );
}
