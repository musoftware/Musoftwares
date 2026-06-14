import React from 'react';
import { useMarketplaceMode } from './MarketplaceModeContext';
import { Tabs, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { __ } from '@/lib/i18n';

export default function MarketplaceModeToggle() {
    const { mode, setMode } = useMarketplaceMode();

    return (
        <Tabs value={mode} onValueChange={(value) => setMode(value as 'client' | 'seller')}>
            <TabsList className="grid w-full grid-cols-2 h-9 p-1">
                <TabsTrigger value="client" className="text-xs">
                    {__('general.client_mode')}
                </TabsTrigger>
                <TabsTrigger value="seller" className="text-xs">
                    {__('general.seller_mode')}
                </TabsTrigger>
            </TabsList>
        </Tabs>
    );
}
