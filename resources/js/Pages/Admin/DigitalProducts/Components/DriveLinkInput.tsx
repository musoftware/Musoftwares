import { useState } from 'react';
import axios from 'axios';
import { Link2, Loader2 } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { __ } from '@/lib/i18n';

export interface DriveBook {
    token: string;
    name: string;
    size: number;
    preview_url: string;
}

interface Props {
    onReady: (book: DriveBook) => void;
}

/** A Google Drive link instead of an upload: the site brings the PDF itself and hands back a token. */
export default function DriveLinkInput({ onReady }: Props) {
    const [link, setLink] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const fetchBook = async () => {
        if (link.trim() === '' || loading) return;
        setLoading(true);
        setError('');
        try {
            const { data } = await axios.post<DriveBook>(route('admin.digitalproducts.drive.fetch'), { link: link.trim() });
            setLink('');
            onReady(data);
        } catch (failure: any) {
            setError(failure?.response?.data?.message ?? __('admin.digital_products_drive_failed'));
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="space-y-1.5">
            <p className="text-xs text-slate-500">{__('admin.digital_products_drive_hint')}</p>
            <div className="flex flex-wrap gap-2">
                <Input
                    type="url"
                    dir="ltr"
                    value={link}
                    onChange={(e) => setLink(e.target.value)}
                    onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); fetchBook(); } }}
                    placeholder="https://drive.google.com/file/d/..."
                    className="h-10 text-xs flex-1 min-w-[12rem]"
                    aria-label={__('admin.digital_products_drive_label')}
                />
                <Button type="button" variant="outline" className="h-10 text-xs" onClick={fetchBook} disabled={loading || link.trim() === ''}>
                    {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Link2 className="h-4 w-4" />}
                    <span>{loading ? __('admin.digital_products_drive_loading') : __('admin.digital_products_drive_fetch')}</span>
                </Button>
            </div>
            {error && <p className="text-xs text-red-600" role="alert">{error}</p>}
        </div>
    );
}
